<?php

namespace MajidShafik\FilamentMediaLibrary\Rules;

use MajidShafik\FilamentMediaLibrary\Exceptions\MediaSecurityException;
use MajidShafik\FilamentMediaLibrary\Services\MediaSecurityValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ValidImageFileRule implements ValidationRule
{
    protected MediaSecurityValidator $validator;

    public function __construct(?MediaSecurityValidator $validator = null)
    {
        $this->validator = $validator ?? new MediaSecurityValidator();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        $files = is_array($value) ? $value : [$value];

        foreach ($files as $file) {
            $cleanPath = null;
            try {
                if ($file instanceof UploadedFile) {
                    $res = $this->validator->validateAndSanitize($file);
                    $cleanPath = $res['clean_path'] ?? null;
                } elseif (is_string($file)) {
                    $resolved = null;
                    if (file_exists($file)) {
                        $resolved = $file;
                    } else {
                        foreach (['public', 'local'] as $diskName) {
                            if (Storage::disk($diskName)->exists($file)) {
                                $resolved = Storage::disk($diskName)->path($file);
                                break;
                            }
                        }
                    }

                    if ($resolved && file_exists($resolved)) {
                        $res = $this->validator->validateAndSanitize($resolved);
                        $cleanPath = $res['clean_path'] ?? null;
                    }
                }
            } catch (MediaSecurityException $e) {
                $fail($e->getMessage());
                return;
            } catch (\Throwable $e) {
                $fail('تعذر التحقق من أمان الصورة المرفوعة.');
                return;
            } finally {
                if ($cleanPath && file_exists($cleanPath)) {
                    @unlink($cleanPath);
                }
            }
        }
    }
}
