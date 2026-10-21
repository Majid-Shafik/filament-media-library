<?php

namespace MajidShafik\FilamentMediaLibrary\Services;

use MajidShafik\FilamentMediaLibrary\Exceptions\MediaDuplicateDetectedException;
use MajidShafik\FilamentMediaLibrary\Exceptions\MediaReferenceConflictException;
use MajidShafik\FilamentMediaLibrary\Exceptions\MediaSecurityException;
use App\Models\Content;
use MajidShafik\FilamentMediaLibrary\Models\Media;
use MajidShafik\FilamentMediaLibrary\Models\MediaUsage;
use MajidShafik\FilamentMediaLibrary\Models\PendingMediaDeletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLifecycleService
{
    protected MediaSecurityValidator $validator;
    protected PendingMediaDeletionService $outboxService;

    public function __construct(
        ?MediaSecurityValidator $validator = null,
        ?PendingMediaDeletionService $outboxService = null
    ) {
        $this->validator = $validator ?? new MediaSecurityValidator();
        $this->outboxService = $outboxService ?? new PendingMediaDeletionService();
    }

    /**
     * Upload and sanitize a new media file into the centralized library.
     *
     * @param UploadedFile $file
     * @param array $attributes
     * @param bool $force If true, bypasses duplicate check and uploads anyway
     * @return Media
     * @throws MediaSecurityException
     * @throws MediaDuplicateDetectedException
     */
    public function uploadMedia(UploadedFile $file, array $attributes = [], bool $force = false): Media
    {
        // 1. Security scan, dimension check, and GD re-encoding
        $sanitized = $this->validator->validateAndSanitize($file);
        $cleanPath = $sanitized['clean_path'];

        // 2. Compute cryptographic SHA-256 fingerprint from sanitized bytes
        $sha256Hash = $this->calculateFileHash($cleanPath);

        // 3. Resolve user for permission check
        $userId = $attributes['created_by'] ?? Auth::id();
        $user = $userId ? User::find($userId) : Auth::user();

        // 4. Concurrency lock to prevent simultaneous duplicate uploads
        $lock = null;
        try {
            $lock = Cache::lock("media_upload_hash_{$sha256Hash}", 10);
            $lock->block(5);
        } catch (\Throwable $e) {
            $lock = null;
        }

        try {
            // 5. Duplicate Detection (unless explicitly forced)
            if (!$force) {
                $duplicate = $this->findDuplicateByHash($sha256Hash, $user, $sanitized['file_size']);
                if ($duplicate) {
                    throw new MediaDuplicateDetectedException($duplicate, $sha256Hash);
                }
            }

            $disk = $attributes['disk'] ?? 'public';
            $year = date('Y');
            $month = date('m');
            $uuid = (string) Str::uuid();
            $microtime = (int) (microtime(true) * 10000);
            $ext = $sanitized['extension'];

            $storagePath = "uploads/media_library/{$year}/{$month}/{$uuid}_{$microtime}.{$ext}";

            // 6. Stream sanitized file to permanent storage
            $stream = fopen($cleanPath, 'rb');
            if (!$stream) {
                throw new MediaSecurityException('تعذر قراءة ملف الصورة المعالج لحفظه في التخزين.');
            }

            try {
                $saved = Storage::disk($disk)->put($storagePath, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (!$saved) {
                    throw new MediaSecurityException('فشل حفظ ملف الصورة في قرص التخزين.');
                }

                // 7. Create Media database record including sha256_hash
                return Media::create([
                    'category_id' => $attributes['category_id'] ?? null,
                    'title'       => $attributes['title'] ?? pathinfo($sanitized['original_name'], PATHINFO_FILENAME),
                    'alt_text'    => $attributes['alt_text'] ?? null,
                    'caption'     => $attributes['caption'] ?? null,
                    'disk'        => $disk,
                    'file_path'   => $storagePath,
                    'file_name'   => $sanitized['original_name'],
                    'mime_type'   => $sanitized['mime_type'],
                    'file_size'   => $sanitized['file_size'],
                    'sha256_hash' => $sha256Hash,
                    'width'       => $sanitized['width'],
                    'height'      => $sanitized['height'],
                    'created_by'  => $userId,
                ]);
            } catch (\Throwable $e) {
                // If DB insert or anything fails after writing to storage, clean up storage file
                Storage::disk($disk)->delete($storagePath);
                throw $e;
            }
        } finally {
            $lock?->release();
            if (file_exists($cleanPath)) {
                @unlink($cleanPath);
            }
        }
    }

    /**
     * Compute SHA-256 hash of a local file.
     *
     * @param string $filePath
     * @return string
     * @throws MediaSecurityException
     */
    public function calculateFileHash(string $filePath): string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new MediaSecurityException('تعذر قراءة الملف لحساب بصمة SHA-256.');
        }

        $hash = hash_file('sha256', $filePath);
        if ($hash === false) {
            throw new MediaSecurityException('فشل حساب بصمة SHA-256 للملف.');
        }

        return strtolower($hash);
    }

    /**
     * Find an existing media item matching the given SHA-256 hash.
     * Respects user view permissions to avoid disclosing unauthorized media.
     *
     * @param string $hash
     * @param User|null $user
     * @param int|null $fileSize
     * @return Media|null
     */
    public function findDuplicateByHash(string $hash, ?User $user = null, ?int $fileSize = null): ?Media
    {
        $hash = strtolower(trim($hash));
        if (empty($hash)) {
            return null;
        }

        // 1. Search existing hashed media
        $media = Media::where('sha256_hash', $hash)
            ->latest('id')
            ->first();

        // 2. If not found and fileSize provided, check unhashed legacy media with matching size
        if (!$media && $fileSize !== null && $fileSize > 0) {
            $candidates = Media::whereNull('sha256_hash')
                ->where('file_size', $fileSize)
                ->get();

            foreach ($candidates as $candidate) {
                $candidateHash = $candidate->ensureHashCalculated();
                if ($candidateHash && hash_equals($hash, $candidateHash)) {
                    $media = $candidate;
                    break;
                }
            }
        }

        if (!$media) {
            return null;
        }

        // 3. Permission check: do not disclose if user cannot view
        if ($user !== null) {
            $canView = false;
            try {
                if ($media->created_by && (int) $user->id === (int) $media->created_by) {
                    $canView = true;
                } elseif (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                    $canView = true;
                } elseif ($user->can('view', $media)) {
                    $canView = true;
                }
            } catch (\Throwable $e) {
                $canView = $media->created_by && ((int) $user->id === (int) $media->created_by);
            }

            if (!$canView) {
                return null;
            }
        }

        return $media;
    }

    /**
     * Check if an uploaded file matches an existing media record.
     *
     * @param UploadedFile $file
     * @param User|null $user
     * @return Media|null
     * @throws MediaSecurityException
     */
    public function findDuplicate(UploadedFile $file, ?User $user = null): ?Media
    {
        $sanitized = $this->validator->validateAndSanitize($file);
        $cleanPath = $sanitized['clean_path'];

        try {
            $hash = $this->calculateFileHash($cleanPath);
            return $this->findDuplicateByHash($hash, $user, $sanitized['file_size']);
        } finally {
            if (file_exists($cleanPath)) {
                @unlink($cleanPath);
            }
        }
    }

    /**
     * Backfill missing SHA-256 hashes for legacy media records in chunks.
     *
     * @param int $limit
     * @return int Number of updated records
     */
    public function backfillLegacyHashes(int $limit = 100): int
    {
        $records = Media::whereNull('sha256_hash')
            ->limit($limit)
            ->get();

        $updated = 0;
        foreach ($records as $media) {
            if ($media->ensureHashCalculated() !== null) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Attach a media item to a content record with strict pessimistic locking.
     *
     * @param Content $content
     * @param int $mediaId
     * @param string $fieldName
     * @param string $usagePath
     * @return void
     * @throws MediaReferenceConflictException
     */
    public function attachToContent(
        Content $content,
        int $mediaId,
        string $fieldName = 'featured_image',
        string $usagePath = 'featured_image'
    ): void {
        DB::transaction(function () use ($content, $mediaId, $fieldName, $usagePath) {
            // 1. Lock Content row first
            $lockedContent = Content::where('id', $content->id)->lockForUpdate()->firstOrFail();

            // 2. Lock Media row second (deterministic lock ordering)
            $lockedMedia = Media::where('id', $mediaId)->lockForUpdate()->firstOrFail();

            if ($lockedMedia->trashed()) {
                throw new MediaReferenceConflictException('لا يمكن ربط وسيط موجود في سلة المهملات.');
            }

            // 3. Upsert media usage slot
            MediaUsage::updateOrCreate(
                [
                    'usable_type' => Content::class,
                    'usable_id'   => $lockedContent->id,
                    'field_name'  => $fieldName,
                    'usage_path'  => $usagePath,
                ],
                [
                    'media_id'    => $lockedMedia->id,
                ]
            );

            // 4. Synchronize content record fields
            if ($fieldName === 'featured_image') {
                $lockedContent->featured_image_media_id = $lockedMedia->id;
                $lockedContent->featured_image = $lockedMedia->file_path;
                $lockedContent->saveQuietly();
            }
        });
    }

    /**
     * Detach a media item from a content record.
     */
    public function detachFromContent(
        Content $content,
        string $fieldName = 'featured_image',
        string $usagePath = 'featured_image'
    ): void {
        DB::transaction(function () use ($content, $fieldName, $usagePath) {
            $lockedContent = Content::where('id', $content->id)->lockForUpdate()->firstOrFail();

            MediaUsage::where('usable_type', Content::class)
                ->where('usable_id', $lockedContent->id)
                ->where('field_name', $fieldName)
                ->where('usage_path', $usagePath)
                ->delete();

            if ($fieldName === 'featured_image') {
                $lockedContent->featured_image_media_id = null;
                $lockedContent->featured_image = null;
                $lockedContent->saveQuietly();
            }
        });
    }

    /**
     * Polymorphically attach a media item to any Eloquent model.
     *
     * @param Model $record
     * @param int $mediaId
     * @param string $fieldName
     * @param string $usagePath
     * @return void
     * @throws MediaReferenceConflictException
     */
    public function attachToRecord(
        Model $record,
        int $mediaId,
        string $fieldName = 'featured_image',
        string $usagePath = 'featured_image'
    ): void {
        DB::transaction(function () use ($record, $mediaId, $fieldName, $usagePath) {
            $lockedRecord = $record->newQuery()->where($record->getKeyName(), $record->getKey())->lockForUpdate()->firstOrFail();
            $lockedMedia = Media::where('id', $mediaId)->lockForUpdate()->firstOrFail();

            if ($lockedMedia->trashed()) {
                throw new MediaReferenceConflictException('لا يمكن ربط وسيط موجود في سلة المهملات.');
            }

            MediaUsage::updateOrCreate(
                [
                    'usable_type' => $lockedRecord->getMorphClass(),
                    'usable_id'   => $lockedRecord->getKey(),
                    'field_name'  => $fieldName,
                    'usage_path'  => $usagePath,
                ],
                [
                    'media_id'    => $lockedMedia->id,
                ]
            );

            $idCol = "{$fieldName}_media_id";
            if (Schema::hasColumn($lockedRecord->getTable(), $idCol)) {
                $lockedRecord->{$idCol} = $lockedMedia->id;
            }
            if (Schema::hasColumn($lockedRecord->getTable(), $fieldName)) {
                $lockedRecord->{$fieldName} = $lockedMedia->file_path;
            }
            $lockedRecord->saveQuietly();
        });
    }

    /**
     * Polymorphically detach a media item from any Eloquent model.
     *
     * @param Model $record
     * @param string $fieldName
     * @param string $usagePath
     * @return void
     */
    public function detachFromRecord(
        Model $record,
        string $fieldName = 'featured_image',
        string $usagePath = 'featured_image'
    ): void {
        DB::transaction(function () use ($record, $fieldName, $usagePath) {
            $lockedRecord = $record->newQuery()->where($record->getKeyName(), $record->getKey())->lockForUpdate()->firstOrFail();

            MediaUsage::where('usable_type', $lockedRecord->getMorphClass())
                ->where('usable_id', $lockedRecord->getKey())
                ->where('field_name', $fieldName)
                ->where('usage_path', $usagePath)
                ->delete();

            $idCol = "{$fieldName}_media_id";
            if (Schema::hasColumn($lockedRecord->getTable(), $idCol)) {
                $lockedRecord->{$idCol} = null;
            }
            if (Schema::hasColumn($lockedRecord->getTable(), $fieldName)) {
                $lockedRecord->{$fieldName} = null;
            }
            $lockedRecord->saveQuietly();
        });
    }

    /**
     * Synchronize media usages for block images stored inside content_blocks.
     *
     * @param Model $record
     * @return void
     */
    public function syncBlockMediaUsages(Model $record): void
    {
        if (!Schema::hasTable('media_usages') || !Schema::hasTable('media')) {
            return;
        }

        $blocks = $record->content_blocks;
        if (is_string($blocks)) {
            $blocks = json_decode($blocks, true);
        }

        $activeSlots = [];

        if (is_array($blocks)) {
            foreach ($blocks as $bIndex => $block) {
                if (!isset($block['data']) || !is_array($block['data'])) {
                    continue;
                }

                $data = $block['data'];

                // 1. Direct block image field
                if (!empty($data['image'])) {
                    $mediaId = !empty($data['image_media_id']) ? (int) $data['image_media_id'] : null;
                    if (!$mediaId && is_string($data['image'])) {
                        $mediaId = Media::where('file_path', $data['image'])->value('id');
                    }

                    if ($mediaId) {
                        $activeSlots["block_{$bIndex}_image"] = $mediaId;
                    }
                }

                // 2. Repeater image fields (slides, categories, gallery_items, items, cards, stats)
                foreach (['slides', 'categories', 'gallery_items', 'items', 'cards', 'stats'] as $repeaterName) {
                    if (!empty($data[$repeaterName]) && is_array($data[$repeaterName])) {
                        foreach ($data[$repeaterName] as $rIndex => $item) {
                            if (!is_array($item)) {
                                continue;
                            }

                            if (!empty($item['image'])) {
                                $mediaId = !empty($item['image_media_id']) ? (int) $item['image_media_id'] : null;
                                if (!$mediaId && is_string($item['image'])) {
                                    $mediaId = Media::where('file_path', $item['image'])->value('id');
                                }

                                if ($mediaId) {
                                    $activeSlots["block_{$bIndex}_{$repeaterName}_{$rIndex}_image"] = $mediaId;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Upsert active slots
        foreach ($activeSlots as $slotPath => $mediaId) {
            // Verify media exists and is not trashed
            if (!Media::where('id', $mediaId)->exists()) {
                continue;
            }

            MediaUsage::updateOrCreate(
                [
                    'usable_type' => $record->getMorphClass(),
                    'usable_id'   => $record->getKey(),
                    'field_name'  => 'content_blocks',
                    'usage_path'  => $slotPath,
                ],
                [
                    'media_id'    => $mediaId,
                ]
            );
        }

        // Clean up stale slots for this record's content_blocks
        $query = MediaUsage::where('usable_type', $record->getMorphClass())
            ->where('usable_id', $record->getKey())
            ->where('field_name', 'content_blocks');

        if (!empty($activeSlots)) {
            $query->whereNotIn('usage_path', array_keys($activeSlots));
        }

        $query->delete();
    }

    /**
     * Force delete a media record and queue its file for physical deletion via Outbox.
     *
     * @param int $mediaId
     * @return void
     * @throws MediaReferenceConflictException
     */
    public function forceDeleteMedia(int $mediaId): void
    {
        $taskData = DB::transaction(function () use ($mediaId) {
            $media = Media::withTrashed()->where('id', $mediaId)->lockForUpdate()->firstOrFail();

            // Verify no active usages exist
            $hasUsages = MediaUsage::where('media_id', $media->id)->exists();
            if ($hasUsages) {
                throw new MediaReferenceConflictException('فشل الحذف النهائي: الوسيط مرتبط حالياً بمحتوى منشور أو مؤرشف.');
            }

            // Verify no content points to this media id
            $hasContentRef = DB::table('contents')
                ->where('featured_image_media_id', $media->id)
                ->exists();
            if ($hasContentRef) {
                throw new MediaReferenceConflictException('فشل الحذف النهائي: الوسيط مستخدم كصورة بارزة لمحتوى.');
            }

            // Record into outbox
            $outboxTask = PendingMediaDeletion::create([
                'media_id'   => $media->id,
                'disk'       => $media->disk ?: 'public',
                'file_path'  => $media->file_path,
                'status'     => 'pending',
                'attempts'   => 0,
            ]);

            $disk = $media->disk ?: 'public';
            $filePath = $media->file_path;

            // Physical force delete from DB
            $media->forceDelete();

            return [
                'task_id'   => $outboxTask->id,
                'disk'      => $disk,
                'file_path' => $filePath,
            ];
        });

        // After commit, attempt immediate physical deletion outside transaction
        DB::afterCommit(function () use ($taskData) {
            try {
                $storage = Storage::disk($taskData['disk']);
                if ($storage->exists($taskData['file_path'])) {
                    $storage->delete($taskData['file_path']);
                }

                PendingMediaDeletion::where('id', $taskData['task_id'])
                    ->where('status', 'pending')
                    ->update([
                        'status'       => 'completed',
                        'processed_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                // If immediate deletion fails or times out, the outbox worker will retry it safely
                PendingMediaDeletion::where('id', $taskData['task_id'])
                    ->where('status', 'pending')
                    ->update([
                        'last_error' => 'فشلت محاولة afterCommit الفورية: ' . $e->getMessage(),
                    ]);
            }
        });
    }
}
