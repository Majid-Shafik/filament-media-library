<?php

namespace MajidShafik\FilamentMediaLibrary\Services;

use MajidShafik\FilamentMediaLibrary\Exceptions\MediaSecurityException;
use App\Models\Setting;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class MediaSecurityValidator
{
    /**
     * Allowed extensions and their strictly mapped MIME types.
     */
    protected const ALLOWED_MIME_MAP = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    /**
     * Magic byte signatures for supported image types.
     */
    protected const MAGIC_BYTES = [
        'jpeg' => ["\xFF\xD8\xFF"],
        'png'  => ["\x89PNG\r\n\x1a\n"],
        'webp' => ["RIFF"], // RIFF....WEBP
    ];

    /**
     * Absolute ceiling for estimated raw memory consumption (64 MB).
     */
    protected const MAX_RAW_MEMORY_BYTES = 67108864; // 64 * 1024 * 1024

    /**
     * Validate and sanitize an uploaded image file.
     *
     * @param UploadedFile|string $file
     * @param string|null $suggestedExtension
     * @return array
     * @throws MediaSecurityException
     */
    public function validateAndSanitize($file, ?string $suggestedExtension = null): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($filePath);
        $clientExtension = $file instanceof UploadedFile
            ? strtolower($file->getClientOriginalExtension())
            : strtolower($suggestedExtension ?? pathinfo($filePath, PATHINFO_EXTENSION));

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new MediaSecurityException('الملف المرفوع غير موجود أو لا يمكن قراءته.');
        }

        // 1. Verify Extension Whitelist
        if (!array_key_exists($clientExtension, self::ALLOWED_MIME_MAP)) {
            throw new MediaSecurityException(
                "امتداد الملف '.{$clientExtension}' غير مسموح به. الامتدادات المدعومة فقط: " . implode(', ', array_keys(self::ALLOWED_MIME_MAP))
            );
        }

        // 2. Server-side MIME Sniffing via Fileinfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($filePath);

        $expectedMime = self::ALLOWED_MIME_MAP[$clientExtension];
        if ($detectedMime !== $expectedMime) {
            throw new MediaSecurityException(
                "نوع الملف الحقيقي ({$detectedMime}) لا يتطابق مع امتداده المزعوم ({$clientExtension})."
            );
        }

        // 3. Magic Bytes Verification
        $this->verifyMagicBytes($filePath, $detectedMime);

        // 4. Content Scanning for Embedded Scripts (PHP/HTML/JS)
        $this->scanForEmbeddedExecutablePatterns($filePath);

        // 5. File Size Verification against Administrative Settings
        $fileSize = filesize($filePath);
        $maxSizeBytes = $this->getMaxFileSizeBytes();
        if ($fileSize <= 0 || $fileSize > $maxSizeBytes) {
            $maxMb = round($maxSizeBytes / (1024 * 1024), 2);
            throw new MediaSecurityException(
                "حجم الملف يتجاوز الحد الأقصى المسموح به ({$maxMb} ميغابايت)."
            );
        }

        // 6. Dimensions and Image Integrity Check via getimagesize
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            throw new MediaSecurityException('الملف تالف أو لا يمثل صورة صالحة.');
        }

        [$width, $height] = $imageInfo;
        $maxWidth = $this->getMaxImageWidth();
        $maxHeight = $this->getMaxImageHeight();

        if ($width > $maxWidth || $height > $maxHeight) {
            throw new MediaSecurityException(
                "أبعاد الصورة ({$width}x{$height}) تتجاوز الأبعاد القصوى المسموح بها ({$maxWidth}x{$maxHeight} بكسل)."
            );
        }

        // 7. Pre-decoding Raw Memory Estimation (Decompression Bomb Protection)
        $this->verifyMemorySafety($width, $height);

        // 8. Re-encoding and Sanitization via GD (Metadata Stripping & Structure Normalization)
        $cleanPath = $this->reencodeImage($filePath, $detectedMime, $clientExtension);

        $cleanSize = filesize($cleanPath);
        $cleanImageInfo = @getimagesize($cleanPath);
        $finalWidth = $cleanImageInfo ? $cleanImageInfo[0] : $width;
        $finalHeight = $cleanImageInfo ? $cleanImageInfo[1] : $height;

        return [
            'original_name' => $originalName,
            'clean_path'    => $cleanPath,
            'extension'     => $clientExtension === 'jpeg' ? 'jpg' : $clientExtension,
            'mime_type'     => $detectedMime,
            'file_size'     => $cleanSize,
            'width'         => $finalWidth,
            'height'        => $finalHeight,
        ];
    }

    /**
     * Verify initial byte signatures.
     */
    protected function verifyMagicBytes(string $filePath, string $mime): void
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new MediaSecurityException('تعذر فحص التوقيع الرقمي للملف.');
        }

        $header = fread($handle, 16);
        fclose($handle);

        if (strlen($header) < 4) {
            throw new MediaSecurityException('الملف مبتور أو فارغ.');
        }

        if ($mime === 'image/jpeg') {
            if (!str_starts_with($header, "\xFF\xD8\xFF")) {
                throw new MediaSecurityException('التوقيع الرقمي لملف JPEG غير صالح.');
            }
        } elseif ($mime === 'image/png') {
            if (!str_starts_with($header, "\x89PNG\r\n\x1a\n")) {
                throw new MediaSecurityException('التوقيع الرقمي لملف PNG غير صالح.');
            }
        } elseif ($mime === 'image/webp') {
            if (!str_starts_with($header, 'RIFF') || substr($header, 8, 4) !== 'WEBP') {
                throw new MediaSecurityException('التوقيع الرقمي لملف WebP غير صالح.');
            }
        }
    }

    /**
     * Scan for embedded script patterns.
     */
    protected function scanForEmbeddedExecutablePatterns(string $filePath): void
    {
        $content = file_get_contents($filePath, false, null, 0, 4096);
        if ($content === false) {
            return;
        }

        $patterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<script/i',
            '/<\s*html/i',
            '/<\s*body/i',
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new MediaSecurityException('تم اكتشاف كود تنفيذي أو نصوص برمجية مشبوهة داخل الملف.');
            }
        }
    }

    /**
     * Verify that raw uncompressed image memory won't exhaust PHP memory limit.
     */
    protected function verifyMemorySafety(int $width, int $height): void
    {
        // 4 bytes per pixel (RGBA) * 1.5 GD structure overhead
        $estimatedBytes = $width * $height * 4 * 1.5;

        if ($estimatedBytes > self::MAX_RAW_MEMORY_BYTES) {
            throw new MediaSecurityException(
                'أبعاد الصورة تتطلب ذاكرة معالجة تفوق الحد الآمن للخادم (خطر قنبلة فك ضغط).'
            );
        }

        $memoryLimit = $this->getMemoryLimitBytes();
        if ($memoryLimit > 0) {
            $currentUsage = memory_get_usage(true);
            if (($currentUsage + $estimatedBytes) > ($memoryLimit * 0.85)) {
                throw new MediaSecurityException(
                    'سعة الذاكرة الحالية للخادم غير كافية لمعالجة الصورة بأمان.'
                );
            }
        }
    }

    /**
     * Re-encode the image using PHP GD to strip metadata and eliminate polyglots.
     */
    protected function reencodeImage(string $sourcePath, string $mime, string $ext): string
    {
        if (!extension_loaded('gd')) {
            throw new MediaSecurityException('ملحق PHP GD غير مفعّل على الخادم.');
        }

        $gdInfo = gd_info();

        $tempCleanFile = tempnam(sys_get_temp_dir(), 'sfd_clean_');
        if ($tempCleanFile === false) {
            throw new MediaSecurityException('تعذر إنشاء مسار مؤقت لمعالجة الصورة.');
        }

        $resource = null;

        try {
            switch ($mime) {
                case 'image/jpeg':
                    if (empty($gdInfo['JPEG Support'])) {
                        throw new MediaSecurityException('دعم JPEG غير مفعّل في ملحق GD.');
                    }
                    $resource = @imagecreatefromjpeg($sourcePath);
                    if (!$resource) {
                        throw new MediaSecurityException('فشل فك ترميز صورة JPEG؛ الملف تالف أو غير متوافق.');
                    }
                    $resource = $this->autoRotateJpeg($resource, $sourcePath);
                    $saved = @imagejpeg($resource, $tempCleanFile, 88);
                    break;

                case 'image/png':
                    if (empty($gdInfo['PNG Support'])) {
                        throw new MediaSecurityException('دعم PNG غير مفعّل في ملحق GD.');
                    }
                    $resource = @imagecreatefrompng($sourcePath);
                    if (!$resource) {
                        throw new MediaSecurityException('فشل فك ترميز صورة PNG؛ الملف تالف أو غير متوافق.');
                    }
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                    $saved = @imagepng($resource, $tempCleanFile, 8);
                    break;

                case 'image/webp':
                    if (empty($gdInfo['WebP Support'])) {
                        throw new MediaSecurityException('دعم WebP غير مفعّل في ملحق GD.');
                    }
                    $resource = @imagecreatefromwebp($sourcePath);
                    if (!$resource) {
                        throw new MediaSecurityException('فشل فك ترميز صورة WebP؛ الملف تالف أو غير متوافق.');
                    }
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                    $saved = @imagewebp($resource, $tempCleanFile, 88);
                    break;

                default:
                    throw new MediaSecurityException("نوع الصورة ({$mime}) غير مدعوم للمعالجة.");
            }

            if (!$saved || filesize($tempCleanFile) === 0) {
                throw new MediaSecurityException('فشلت عملية إعادة ترميز الصورة وتأمينها.');
            }

            return $tempCleanFile;
        } catch (\Throwable $e) {
            if (file_exists($tempCleanFile)) {
                @unlink($tempCleanFile);
            }
            if ($e instanceof MediaSecurityException) {
                throw $e;
            }
            throw new MediaSecurityException('حدث خطأ أثناء معالجة الصورة: ' . $e->getMessage(), 0, $e);
        } finally {
            if ($resource && (is_resource($resource) || $resource instanceof \GdImage)) {
                imagedestroy($resource);
            }
        }
    }

    /**
     * Auto-rotate JPEG according to EXIF orientation.
     *
     * @param resource|\GdImage $resource
     * @param string $path
     * @return resource|\GdImage
     */
    protected function autoRotateJpeg($resource, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $resource;
        }

        $exif = @exif_read_data($path);
        if (empty($exif['Orientation'])) {
            return $resource;
        }

        $rotated = null;
        switch ((int) $exif['Orientation']) {
            case 3:
                $rotated = imagerotate($resource, 180, 0);
                break;
            case 6:
                $rotated = imagerotate($resource, -90, 0);
                break;
            case 8:
                $rotated = imagerotate($resource, 90, 0);
                break;
        }

        if ($rotated && (is_resource($rotated) || $rotated instanceof \GdImage)) {
            imagedestroy($resource);
            return $rotated;
        }

        return $resource;
    }

    /**
     * Get maximum allowed file size in bytes from Setting or default 5 MB.
     */
    protected function getMaxFileSizeBytes(): int
    {
        $settings = Cache::remember('global_settings', 600, function () {
            try {
                return Setting::first()?->options;
            } catch (\Throwable) {
                return null;
            }
        });

        $sizeMb = (int) ($settings['max_image_size'] ?? 5);
        if ($sizeMb <= 0 || $sizeMb > 50) {
            $sizeMb = 5;
        }

        return $sizeMb * 1024 * 1024;
    }

    /**
     * Get maximum image width from Setting or default 3840 px.
     */
    protected function getMaxImageWidth(): int
    {
        $settings = Cache::remember('global_settings', 600, function () {
            try {
                return Setting::first()?->options;
            } catch (\Throwable) {
                return null;
            }
        });

        return (int) ($settings['max_image_width'] ?? 3840);
    }

    /**
     * Get maximum image height from Setting or default 2160 px.
     */
    protected function getMaxImageHeight(): int
    {
        $settings = Cache::remember('global_settings', 600, function () {
            try {
                return Setting::first()?->options;
            } catch (\Throwable) {
                return null;
            }
        });

        return (int) ($settings['max_image_height'] ?? 2160);
    }

    /**
     * Parse PHP memory limit to bytes.
     */
    protected function getMemoryLimitBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') {
            return -1;
        }

        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $val = (int) $memoryLimit;

        switch ($last) {
            case 'g':
                $val *= 1024;
                // fall through
            case 'm':
                $val *= 1024;
                // fall through
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
