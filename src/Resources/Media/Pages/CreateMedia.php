<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Media\Pages;

use MajidShafik\FilamentMediaLibrary\Exceptions\MediaDuplicateDetectedException;
use MajidShafik\FilamentMediaLibrary\Exceptions\MediaSecurityException;
use MajidShafik\FilamentMediaLibrary\Resources\Media\MediaResource;
use MajidShafik\FilamentMediaLibrary\Services\MediaLifecycleService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $attachment = $data['attachment'] ?? null;

        if (!$attachment) {
            throw ValidationException::withMessages([
                'attachment' => 'ملف الصورة مطلوب للرفع.',
            ]);
        }

        $resolvedPath = null;
        $possibleDisks = ['public', 'local'];

        foreach ($possibleDisks as $diskName) {
            if (Storage::disk($diskName)->exists($attachment)) {
                $resolvedPath = Storage::disk($diskName)->path($attachment);
                break;
            }
        }

        if (!$resolvedPath && file_exists($attachment)) {
            $resolvedPath = $attachment;
        }

        if (!$resolvedPath || !file_exists($resolvedPath)) {
            throw ValidationException::withMessages([
                'attachment' => 'تعذر العثور على الملف المرفوع مؤقتاً.',
            ]);
        }

        $originalName = basename($attachment);
        $mimeType = @mime_content_type($resolvedPath) ?: 'image/jpeg';

        $uploadedFile = new UploadedFile(
            $resolvedPath,
            $originalName,
            $mimeType,
            null,
            true
        );

        $force = !empty($data['force_duplicate']);

        try {
            $lifecycleService = app(MediaLifecycleService::class);
            $media = $lifecycleService->uploadMedia($uploadedFile, [
                'category_id' => $data['category_id'] ?? null,
                'title'       => !empty($data['title']) ? $data['title'] : pathinfo($originalName, PATHINFO_FILENAME),
                'alt_text'    => $data['alt_text'] ?? null,
                'caption'     => $data['caption'] ?? null,
                'created_by'  => Auth::id(),
            ], force: $force);
        } catch (MediaDuplicateDetectedException $e) {
            $dup = $e->getDuplicateMedia();
            throw ValidationException::withMessages([
                'attachment' => "تم العثور على صورة مطابقة تماماً في مكتبة الوسائط: '{$dup->title}' (#{$dup->id}). يمكنك تفعيل خيار 'السماح برفع نسخة مكررة' إذا أردت رفعها كنسخة مستقلة.",
            ]);
        } catch (MediaSecurityException $e) {
            throw ValidationException::withMessages([
                'attachment' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'attachment' => 'فشلت معالجة ملف الصورة: ' . $e->getMessage(),
            ]);
        }

        foreach ($possibleDisks as $diskName) {
            if (Storage::disk($diskName)->exists($attachment)) {
                Storage::disk($diskName)->delete($attachment);
            }
        }

        return $media;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
