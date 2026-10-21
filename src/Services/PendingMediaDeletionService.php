<?php

namespace MajidShafik\FilamentMediaLibrary\Services;

use App\Models\Content;
use MajidShafik\FilamentMediaLibrary\Models\PendingMediaDeletion;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PendingMediaDeletionService
{
    /**
     * Timeout after which a processing task is considered stale (10 minutes).
     */
    protected int $staleTimeoutMinutes = 10;

    /**
     * Maximum retry attempts before giving up.
     */
    protected int $maxAttempts = 5;

    public function __construct(int $staleTimeoutMinutes = 10, int $maxAttempts = 5)
    {
        $this->staleTimeoutMinutes = $staleTimeoutMinutes;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Claim a batch of deletion tasks atomically.
     *
     * @param int $batchSize
     * @return array{tasks: \Illuminate\Support\Collection, worker_token: string}
     */
    public function claimBatch(int $batchSize = 20): array
    {
        $workerToken = (string) Str::uuid();
        $staleThreshold = now()->subMinutes($this->staleTimeoutMinutes);

        $tasks = DB::transaction(function () use ($batchSize, $workerToken, $staleThreshold) {
            $isMysql = DB::connection()->getDriverName() === 'mysql';

            $query = PendingMediaDeletion::query()
                ->where(function ($q) use ($staleThreshold) {
                    $q->where('status', 'pending')
                      ->orWhere(function ($stale) use ($staleThreshold) {
                          $stale->where('status', 'processing')
                                ->where('locked_at', '<=', $staleThreshold);
                      });
                })
                ->where('attempts', '<', $this->maxAttempts)
                ->orderBy('id', 'asc')
                ->limit($batchSize);

            if ($isMysql) {
                $query->lock('for update skip locked');
            } else {
                $query->lockForUpdate();
            }

            $records = $query->get();
            $ids = $records->pluck('id')->all();

            if (!empty($ids)) {
                PendingMediaDeletion::whereIn('id', $ids)->update([
                    'status'      => 'processing',
                    'claim_token' => $workerToken,
                    'attempts'    => DB::raw('attempts + 1'),
                    'locked_at'   => now(),
                ]);
            }

            return $records;
        });

        return [
            'tasks'        => $tasks,
            'worker_token' => $workerToken,
        ];
    }

    /**
     * Process a single claimed task safely.
     *
     * @param PendingMediaDeletion $task
     * @param string $workerToken
     * @return bool
     */
    public function processTask(PendingMediaDeletion $task, string $workerToken): bool
    {
        // 1. Double check fencing token before doing any physical file manipulation
        $currentTask = PendingMediaDeletion::find($task->id);
        if (!$currentTask || $currentTask->claim_token !== $workerToken) {
            Log::warning("Zombie worker aborted: Task #{$task->id} claim token expired or stolen by another worker.");
            return false;
        }

        $disk = $task->disk ?: 'public';
        $filePath = trim($task->file_path);

        // Path sanity check: prevent directory traversal or deletion of root/broad folders
        if (empty($filePath) || str_contains($filePath, '..') || $filePath === 'uploads' || $filePath === 'uploads/' || $filePath === '/') {
            $this->markAsFailed(
                $task->id,
                $workerToken,
                'مسار الملف غير آمن أو مشبوه؛ تم إيقاف الحذف التلقائي للمراجعة الإدارية.'
            );
            return false;
        }

        // Strict Namespace Restriction: Only files residing in uploads/media_library/ (with unique UUIDs) are auto-deleted.
        // Legacy or unverified paths cannot guarantee non-reusability, so auto-deletion is halted for manual review.
        if (!str_starts_with($filePath, 'uploads/media_library/')) {
            $this->markAsFailed(
                $task->id,
                $workerToken,
                'المسار لا يتبع مكتبة الوسائط المشفرة (uploads/media_library/)؛ تم إيقاف الحذف التلقائي منعاً لمسح ملفات معاد استخدام مسارها.'
            );
            return false;
        }

        // 2. Comprehensive Reference Guard (Fail-safe)
        try {
            if ($this->isFileReferenced($disk, $filePath)) {
                $this->markAsFailed(
                    $task->id,
                    $workerToken,
                    'تم إلغاء الحذف الفيزيائي: الملف ما زال مستخدماً أو مرجعاً في محتوى أو بلوك أو قسم.'
                );
                return false;
            }
        } catch (\Throwable $e) {
            $this->markAsFailed(
                $task->id,
                $workerToken,
                'فشل فحص مراجع الملف في قاعدة البيانات: ' . $e->getMessage()
            );
            return false;
        }

        // 3. Physical Deletion from Storage
        try {
            $storage = Storage::disk($disk);
            $fileExists = $storage->exists($filePath);

            if ($fileExists) {
                // Re-verify fencing token immediately before physical file deletion
                $stillValid = PendingMediaDeletion::where('id', $task->id)
                    ->where('claim_token', $workerToken)
                    ->where('status', 'processing')
                    ->exists();

                if (!$stillValid) {
                    Log::warning("Zombie worker aborted immediately before storage deletion: Task #{$task->id} claim expired.");
                    return false;
                }

                $deleted = $storage->delete($filePath);
                if (!$deleted && $storage->exists($filePath)) {
                    throw new \RuntimeException('فشل استدعاء Storage::delete للمسار: ' . $filePath);
                }
            }

            // 4. Update status with Fencing Token verification
            $affected = PendingMediaDeletion::where('id', $task->id)
                ->where('claim_token', $workerToken)
                ->update([
                    'status'       => 'completed',
                    'processed_at' => now(),
                    'claim_token'  => null,
                    'last_error'   => null,
                ]);

            if ($affected === 0) {
                Log::warning("Zombie worker detected post-deletion: Task #{$task->id} claim token expired while deleting file.");
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->handleDeletionFailure($task->id, $workerToken, $e->getMessage());
            return false;
        }
    }

    /**
     * Process all pending tasks in batches.
     *
     * @param int $batchSize
     * @return array{processed: int, completed: int, failed: int}
     */
    public function processAllPending(int $batchSize = 20): array
    {
        $stats = ['processed' => 0, 'completed' => 0, 'failed' => 0];

        $claim = $this->claimBatch($batchSize);
        $tasks = $claim['tasks'];
        $workerToken = $claim['worker_token'];

        foreach ($tasks as $task) {
            $stats['processed']++;
            $success = $this->processTask($task, $workerToken);
            if ($success) {
                $stats['completed']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Comprehensive reference check across models and tables.
     */
    public function isFileReferenced(string $disk, string $filePath): bool
    {
        // 1. Check contents.featured_image
        if (Schema::hasTable('contents') && Schema::hasColumn('contents', 'featured_image')) {
            $contentFeatured = DB::table('contents')
                ->where('featured_image', $filePath)
                ->exists();
            if ($contentFeatured) {
                return true;
            }
        }

        // 2. Check contents.content_blocks
        if (Schema::hasTable('contents') && Schema::hasColumn('contents', 'content_blocks')) {
            $contentBlocks = DB::table('contents')
                ->where('content_blocks', 'LIKE', '%' . $filePath . '%')
                ->exists();
            if ($contentBlocks) {
                return true;
            }
        }

        // 3. Check sections.content_blocks
        if (Schema::hasTable('sections') && Schema::hasColumn('sections', 'content_blocks')) {
            $sectionBlocks = DB::table('sections')
                ->where('content_blocks', 'LIKE', '%' . $filePath . '%')
                ->exists();
            if ($sectionBlocks) {
                return true;
            }
        }

        // 4. Check media table if present
        if (Schema::hasTable('media')) {
            $mediaExists = DB::table('media')
                ->where('disk', $disk)
                ->where('file_path', $filePath)
                ->exists();
            if ($mediaExists) {
                return true;
            }
        }

        // 5. Check media_usages table if present
        if (Schema::hasTable('media_usages') && Schema::hasTable('media')) {
            $usageExists = DB::table('media_usages')
                ->join('media', 'media_usages.media_id', '=', 'media.id')
                ->where('media.disk', $disk)
                ->where('media.file_path', $filePath)
                ->exists();
            if ($usageExists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark a task as failed permanently.
     */
    protected function markAsFailed(int $taskId, string $workerToken, string $error): void
    {
        PendingMediaDeletion::where('id', $taskId)
            ->where('claim_token', $workerToken)
            ->update([
                'status'      => 'failed',
                'last_error'  => $error,
                'claim_token' => null,
            ]);
    }

    /**
     * Handle physical deletion failure with retry backoff.
     */
    protected function handleDeletionFailure(int $taskId, string $workerToken, string $error): void
    {
        $task = PendingMediaDeletion::find($taskId);
        if (!$task || $task->claim_token !== $workerToken) {
            return;
        }

        $newStatus = ($task->attempts >= $this->maxAttempts) ? 'failed' : 'pending';

        PendingMediaDeletion::where('id', $taskId)
            ->where('claim_token', $workerToken)
            ->update([
                'status'      => $newStatus,
                'last_error'  => $error,
                'claim_token' => null,
                'locked_at'   => null,
            ]);
    }
}
