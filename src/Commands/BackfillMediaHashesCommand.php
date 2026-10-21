<?php

namespace MajidShafik\FilamentMediaLibrary\Commands;

use MajidShafik\FilamentMediaLibrary\Models\Media;
use MajidShafik\FilamentMediaLibrary\Services\MediaLifecycleService;
use Illuminate\Console\Command;

class BackfillMediaHashesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:backfill-hashes 
                            {--chunk=100 : عدد السجلات المعالجة في كل دفعة}
                            {--all : الاستمرار في المعالجة حتى انتهاء كافة السجلات غير المفهرسة}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'حساب بصمات SHA-256 للصور القديمة غير المفهرسة في مكتبة الوسائط بطريقة آمنة وتدريجية';

    /**
     * Execute the console command.
     */
    public function handle(MediaLifecycleService $service): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $processAll = (bool) $this->option('all');

        $totalRemaining = Media::whereNull('sha256_hash')->count();

        if ($totalRemaining === 0) {
            $this->info('جميع سجلات الوسائط مفهرسة ببصمة SHA-256 بالفعل. لا توجد سجلات قديمة بحاجة للمعالجة.');
            return self::SUCCESS;
        }

        $this->info("عدد السجلات غير المفهرسة: {$totalRemaining}");

        $totalUpdated = 0;
        $totalFailed = 0;

        do {
            $records = Media::whereNull('sha256_hash')
                ->limit($chunkSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            $bar = $this->output->createProgressBar($records->count());
            $bar->start();

            foreach ($records as $media) {
                try {
                    $hash = $media->ensureHashCalculated();
                    if ($hash !== null) {
                        $totalUpdated++;
                    } else {
                        $totalFailed++;
                    }
                } catch (\Throwable $e) {
                    $totalFailed++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            if (!$processAll) {
                break;
            }
        } while (Media::whereNull('sha256_hash')->exists());

        $this->newLine();
        $this->info("تمت المعالجة بنجاح. السجلات المحدثة بالبصمة: {$totalUpdated} | السجلات التي تعذر حساب بصمتها (ملفات مفقودة أو غير قابلة للقراءة): {$totalFailed}");

        return self::SUCCESS;
    }
}
