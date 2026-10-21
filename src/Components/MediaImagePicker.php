<?php

namespace MajidShafik\FilamentMediaLibrary\Components;

use MajidShafik\FilamentMediaLibrary\Exceptions\MediaDuplicateDetectedException;
use MajidShafik\FilamentMediaLibrary\Models\Media;
use MajidShafik\FilamentMediaLibrary\Models\MediaCategory;
use App\Models\Setting;
use MajidShafik\FilamentMediaLibrary\Rules\ValidImageFileRule;
use MajidShafik\FilamentMediaLibrary\Services\MediaLifecycleService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MediaImagePicker
{
    /**
     * Check if the authenticated user has permission to upload directly to the central Media Library.
     * Controlled via General Settings -> Attachment Settings:
     * 1. Toggle: allow_users_upload_to_media_library
     * 2. Roles: roles_allowed_to_upload_media
     */
    public static function canUploadToLibrary(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Super Admin & Admin always have authority
        try {
            if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
                return true;
            }
        } catch (\Throwable $e) {
            // Safety in test environments
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        $setting = Setting::first();
        $allowed = $setting?->options['allow_users_upload_to_media_library'] ?? true;
        if (!$allowed) {
            return false;
        }

        $allowedRoles = $setting?->options['roles_allowed_to_upload_media'] ?? [];
        if (!empty($allowedRoles)) {
            try {
                return $user->hasAnyRole($allowedRoles);
            } catch (\Throwable $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get maximum image size in MB from General Settings.
     */
    public static function getMaxImageSizeMB(): int
    {
        $setting = Setting::first();
        return (int) ($setting?->options['max_image_size'] ?? 5);
    }

    /**
     * Get number of configured rows from General Settings.
     * Default: 2 rows.
     */
    public static function getPickerRows(): int
    {
        $setting = Setting::first();
        $rows = (int) ($setting?->options['media_picker_rows'] ?? 2);
        return $rows < 1 ? 2 : $rows;
    }

    /**
     * Get number of images per page in the media picker modal based on General Settings.
     * Default: 2 rows * 5 images per row = 10 images.
     */
    public static function getPickerPerPage(): int
    {
        return static::getPickerRows() * 5;
    }

    /**
     * Resolve uploaded file instance from temporary attachment path.
     */
    public static function resolveUploadedFile(?string $attachment): ?UploadedFile
    {
        if (!$attachment) {
            return null;
        }

        $resolvedPath = null;
        foreach (['public', 'local'] as $diskName) {
            if (Storage::disk($diskName)->exists($attachment)) {
                $resolvedPath = Storage::disk($diskName)->path($attachment);
                break;
            }
        }

        if (!$resolvedPath && file_exists($attachment)) {
            $resolvedPath = $attachment;
        }

        if (!$resolvedPath || !file_exists($resolvedPath) || !is_readable($resolvedPath)) {
            return null;
        }

        return new UploadedFile(
            $resolvedPath,
            basename($attachment),
            @mime_content_type($resolvedPath) ?: 'image/jpeg',
            null,
            true
        );
    }

    /**
     * Get duplicate media item if attachment matches an existing record.
     */
    public static function getDuplicateForAttachment(?string $attachment): ?Media
    {
        $file = static::resolveUploadedFile($attachment);
        if (!$file) {
            return null;
        }

        try {
            return app(MediaLifecycleService::class)->findDuplicate($file, Auth::user());
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Build the reusable Media Image Picker component.
     *
     * @param string $imagePathField Name of the image path column (e.g. 'featured_image')
     * @param string|null $mediaIdField Name of the media foreign key column (e.g. 'featured_image_media_id')
     * @param string $imagePathField Name of the image path column (e.g. 'featured_image' or 'image')
     * @param string|null $mediaIdField Name of the media foreign key column (e.g. 'featured_image_media_id' or 'image_media_id')
     * @param string $label Display label
     * @param string|null $directory Custom upload directory for direct uploads
     * @param bool $required Whether the image is mandatory
     * @param array $acceptedFileTypes Allowed mime types
     * @param string|null $helperText Optional helper text to display with the picker
     * @return Group
     */
    public static function make(
        string $imagePathField = 'featured_image',
        ?string $mediaIdField = 'featured_image_media_id',
        string $label = 'الصورة البارزة',
        ?string $directory = null,
        bool $required = false,
        array $acceptedFileTypes = ['image/jpeg', 'image/png', 'image/webp'],
        ?string $helperText = null
    ): Group {
        $components = [];

        // 1. Hidden inputs to store state
        $imageField = Hidden::make($imagePathField)
            ->live()
            ->validationAttribute($label);

        if ($required) {
            $imageField->required();
        }

        $components[] = $imageField;

        if ($mediaIdField) {
            $components[] = Hidden::make($mediaIdField)->live();
        }

        // 2. Visual preview card
        $previewCard = Placeholder::make("{$imagePathField}_preview_card")
            ->label($label)
            ->columnSpanFull()
            ->content(function (Get $get) use ($imagePathField, $mediaIdField) {
                $path = $get($imagePathField);
                $mediaId = $mediaIdField ? $get($mediaIdField) : null;

                return view('filament.components.media-image-picker-preview', [
                    'path'    => $path,
                    'mediaId' => $mediaId,
                ]);
            });

        if ($helperText) {
            $previewCard->helperText($helperText);
        }

        $components[] = $previewCard;

        // Compute allowed types for central library
        $centralAcceptedTypes = array_values(array_intersect($acceptedFileTypes, ['image/jpeg', 'image/png', 'image/webp']));
        if (empty($centralAcceptedTypes)) {
            $centralAcceptedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        }

        // 3. Action buttons (Upload to Library, Direct Upload, Choose from Library, Remove)
        $components[] = Actions::make([
            // Action 1: Upload new image through centralized MediaLifecycleService into Media Library
            Action::make("upload_{$imagePathField}")
                ->label('رفع صورة للمكتبة')
                ->icon(Heroicon::OutlinedCloudArrowUp)
                ->color('primary')
                ->button()
                ->visible(fn () => static::canUploadToLibrary())
                ->modalHeading('رفع صورة جديدة إلى مكتبة الوسائط المركزية')
                ->modalWidth(Width::Large)
                ->modalSubmitActionLabel('بدء الرفع والتعيين')
                ->form([
                    FileUpload::make('attachment')
                        ->label('ملف الصورة')
                        ->image()
                        ->acceptedFileTypes($centralAcceptedTypes)
                        ->maxSize(fn () => static::getMaxImageSizeMB() * 1024)
                        ->required()
                        ->live()
                        ->rules([new ValidImageFileRule()])
                        ->helperText(function () use ($centralAcceptedTypes) {
                            $typesStr = implode(', ', array_map(fn($t) => strtoupper(str_replace('image/', '', $t)), $centralAcceptedTypes));
                            return "الأنواع المدعومة: {$typesStr} (بحد أقصى " . static::getMaxImageSizeMB() . ' MB حسب إعدادات الموقع). يتم فحص الملف أمنياً وإعادة ترميزه عبر GD وحفظه مركزياً.';
                        }),

                    ViewField::make('duplicate_notice')
                        ->view('filament.components.media-duplicate-alert')
                        ->visible(fn (Get $get) => !empty(static::getDuplicateForAttachment($get('attachment')))),

                    Radio::make('duplicate_handling')
                        ->label('إجراء التعامل مع الصورة المكررة')
                        ->visible(fn (Get $get) => !empty(static::getDuplicateForAttachment($get('attachment'))))
                        ->options([
                            'use_existing' => 'استخدام الصورة الموجودة في المكتبة (موصى به - ربط الصورة الحالية دون تكرار)',
                            'force_upload' => 'متابعة الرفع كنسخة مستقلة جديدة في المكتبة',
                        ])
                        ->default('use_existing'),

                    Select::make('category_id')
                        ->label('المجلد / التصنيف')
                        ->options(fn () => Schema::hasTable('media_categories') ? MediaCategory::pluck('name', 'id') : [])
                        ->searchable()
                        ->nullable()
                        ->placeholder('المكتبة الرئيسية (بدون تصنيف)'),

                    TextInput::make('title')
                        ->label('عنوان الصورة (اختياري)')
                        ->maxLength(255)
                        ->placeholder('يُترك فارغاً لاعتماد اسم الملف الأصلي'),

                    TextInput::make('alt_text')
                        ->label('النص البديل (Alt Text)')
                        ->maxLength(255)
                        ->placeholder('اختياري - يفيد في محركات البحث والوصولية'),

                    Textarea::make('caption')
                        ->label('الوصف التوضيحي (Caption)')
                        ->rows(2)
                        ->placeholder('اختياري - وصف توضيحي للصورة'),
                ])
                ->action(function (array $data, Set $set) use ($imagePathField, $mediaIdField) {
                    $attachment = $data['attachment'] ?? null;
                    if (!$attachment) {
                        Notification::make()->title('لم يتم رفع أي ملف')->danger()->send();
                        return;
                    }

                    $uploadedFile = static::resolveUploadedFile($attachment);
                    if (!$uploadedFile) {
                        Notification::make()->title('تعذر العثور على الملف المرفوع مؤقتاً')->danger()->send();
                        return;
                    }

                    $lifecycleService = app(MediaLifecycleService::class);
                    $duplicate = static::getDuplicateForAttachment($attachment);
                    $duplicateHandling = $data['duplicate_handling'] ?? 'use_existing';

                    // 1. If duplicate detected and user chose to use existing:
                    if ($duplicate && $duplicateHandling === 'use_existing') {
                        $set($imagePathField, $duplicate->file_path);
                        if ($mediaIdField) {
                            $set($mediaIdField, $duplicate->id);
                        }

                        // Clean temp file
                        foreach (['public', 'local'] as $diskName) {
                            if (Storage::disk($diskName)->exists($attachment)) {
                                Storage::disk($diskName)->delete($attachment);
                            }
                        }

                        Notification::make()
                            ->title('تم استخدام الصورة الموجودة بنجاح')
                            ->body("تم ربط الصورة '{$duplicate->title}' (#{$duplicate->id}) بالمحتوى دون إنشاء نسخة مكررة.")
                            ->success()
                            ->send();

                        return;
                    }

                    // 2. Upload (forced if user selected force_upload, otherwise normal)
                    $force = ($duplicate && $duplicateHandling === 'force_upload');

                    try {
                        $media = $lifecycleService->uploadMedia($uploadedFile, [
                            'category_id' => $data['category_id'] ?? null,
                            'title'       => !empty($data['title']) ? $data['title'] : pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
                            'alt_text'    => $data['alt_text'] ?? null,
                            'caption'     => $data['caption'] ?? null,
                            'created_by'  => Auth::id(),
                        ], force: $force);

                        $set($imagePathField, $media->file_path);
                        if ($mediaIdField) {
                            $set($mediaIdField, $media->id);
                        }

                        // Clean temp file
                        foreach (['public', 'local'] as $diskName) {
                            if (Storage::disk($diskName)->exists($attachment)) {
                                Storage::disk($diskName)->delete($attachment);
                            }
                        }

                        Notification::make()
                            ->title($force ? 'تم رفع نسخة مستقلة جديدة بنجاح' : 'تم رفع الصورة بنجاح وتعيينها للمحتوى')
                            ->success()
                            ->send();
                    } catch (MediaDuplicateDetectedException $e) {
                        $dup = $e->getDuplicateMedia();
                        Notification::make()
                            ->title('تم اكتشاف صورة مكررة')
                            ->body("الصورة المرفوعة مطابقة تماماً لـ '{$dup->title}' (#{$dup->id}) في المكتبة.")
                            ->warning()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('فشل رفع الصورة: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Action 2: Traditional / Direct Upload (Upload direct to content folder without registering in media library)
            Action::make("upload_direct_{$imagePathField}")
                ->label('رفع صورة مباشر (تقليدي)')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color(fn () => static::canUploadToLibrary() ? 'gray' : 'primary')
                ->button()
                ->modalHeading('رفع صورة مباشرة للمحتوى (بالشكل التقليدي)')
                ->modalWidth(Width::Large)
                ->modalSubmitActionLabel('حفظ وتعيين الصورة')
                ->form([
                    FileUpload::make('direct_image')
                        ->label('ملف الصورة')
                        ->image()
                        ->disk('public')
                        ->directory(fn ($record) => $directory ?: ($record ? "uploads/contents/{$record->id}" : "uploads/contents/temp"))
                        ->acceptedFileTypes($acceptedFileTypes)
                        ->maxSize(fn () => static::getMaxImageSizeMB() * 1024)
                        ->helperText(fn () => 'يتم حفظ الصورة مباشرة في مجلد المحتوى بالشكل التقليدي دون تسجيلها في مكتبة الوسائط العامة (بحد أقصى ' . static::getMaxImageSizeMB() . ' MB حسب إعدادات الموقع).')
                        ->required(),
                ])
                ->action(function (array $data, Set $set) use ($imagePathField, $mediaIdField) {
                    $path = $data['direct_image'] ?? null;
                    if (!$path) {
                        Notification::make()->title('لم يتم رفع أي ملف')->danger()->send();
                        return;
                    }

                    $set($imagePathField, $path);
                    if ($mediaIdField) {
                        $set($mediaIdField, null);
                    }

                    Notification::make()
                        ->title('تم رفع الصورة وحفظها بنجاح (رفع مباشر)')
                        ->success()
                        ->send();
                }),

            // Action 3: Choose existing image from central media library (Visual Grid Picker)
            Action::make("choose_{$imagePathField}")
                ->label('اختيار من مكتبة الوسائط')
                ->icon(Heroicon::OutlinedPhoto)
                ->color('gray')
                ->button()
                ->modalHeading('اختيار صورة من مكتبة الوسائط المركزية')
                ->modalWidth(Width::FiveExtraLarge)
                ->modalSubmitActionLabel('اعتماد الصورة المحددة')
                ->form([
                    Grid::make(2)->schema([
                        Select::make('filter_category_id')
                            ->label('تصفية حسب التصنيف')
                            ->options(fn () => Schema::hasTable('media_categories') ? MediaCategory::pluck('name', 'id') : [])
                            ->searchable()
                            ->nullable()
                            ->placeholder('جميع التصنيفات')
                            ->live(),

                        TextInput::make('filter_search')
                            ->label('البحث بالاسم أو العنوان أو النص البديل (Alt)')
                            ->placeholder('اكتب للبحث في اسم الملف أو العنوان أو Alt Text...')
                            ->live(debounce: 300),
                    ]),

                    ViewField::make('selected_media_id')
                        ->label('اختر الصورة من المعرض أدناه')
                        ->view('filament.components.media-grid-picker')
                        ->required()
                        ->columnSpanFull()
                        ->viewData(function (Get $get) {
                            if (!Schema::hasTable('media')) {
                                return [
                                    'mediaItems' => collect(),
                                    'perPage'    => static::getPickerPerPage(),
                                ];
                            }

                            $query = Media::query()->with('category')->withCount('usages')->latest('id');

                            if ($catId = $get('filter_category_id')) {
                                $query->where('category_id', $catId);
                            }

                            if ($search = $get('filter_search')) {
                                $s = '%' . trim($search) . '%';
                                $query->where(function ($q) use ($s) {
                                    $q->where('title', 'like', $s)
                                      ->orWhere('file_name', 'like', $s)
                                      ->orWhere('alt_text', 'like', $s)
                                      ->orWhere('caption', 'like', $s);
                                });
                            }

                            return [
                                'mediaItems'     => $query->limit(200)->get(),
                                'configuredRows' => static::getPickerRows(),
                                'perPage'        => static::getPickerPerPage(),
                            ];
                        }),
                ])
                ->action(function (array $data, Set $set) use ($imagePathField, $mediaIdField) {
                    $mediaId = (int) ($data['selected_media_id'] ?? 0);
                    $media = Media::find($mediaId);

                    if (!$media) {
                        Notification::make()->title('لم يتم العثور على الصورة المحددة')->danger()->send();
                        return;
                    }

                    $set($imagePathField, $media->file_path);
                    if ($mediaIdField) {
                        $set($mediaIdField, $media->id);
                    }

                    Notification::make()
                        ->title('تم اختيار الصورة من المكتبة بنجاح')
                        ->success()
                        ->send();
                }),

            // Action 4: Remove image association safely without deleting original media
            Action::make("remove_{$imagePathField}")
                ->label('إزالة الصورة')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->button()
                ->visible(fn (Get $get) => filled($get($imagePathField)) || ($mediaIdField && filled($get($mediaIdField))))
                ->requiresConfirmation()
                ->modalHeading('تأكيد إزالة ارتباط الصورة')
                ->modalDescription('سيتم فك ارتباط الصورة عن هذا السجل فقط. لن يتم حذف ملف الصورة الأصلي من مكتبة الوسائط.')
                ->modalSubmitActionLabel('نعم، أزل الارتباط')
                ->action(function (Set $set) use ($imagePathField, $mediaIdField) {
                    $set($imagePathField, null);
                    if ($mediaIdField) {
                        $set($mediaIdField, null);
                    }

                    Notification::make()
                        ->title('تمت إزالة ارتباط الصورة بنجاح')
                        ->info()
                        ->send();
                }),
        ])
        ->alignment(Alignment::Start)
        ->columnSpanFull();

        return Group::make($components)->columnSpanFull();
    }
}