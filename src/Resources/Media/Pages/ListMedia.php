<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Media\Pages;

use MajidShafik\FilamentMediaLibrary\Exceptions\MediaDuplicateDetectedException;
use MajidShafik\FilamentMediaLibrary\Exceptions\MediaSecurityException;
use MajidShafik\FilamentMediaLibrary\Resources\Media\MediaResource;
use App\Models\Content;
use MajidShafik\FilamentMediaLibrary\Models\Media;
use MajidShafik\FilamentMediaLibrary\Models\MediaCategory;
use MajidShafik\FilamentMediaLibrary\Rules\ValidImageFileRule;
use MajidShafik\FilamentMediaLibrary\Services\MediaLifecycleService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected string $view = 'filament.pages.media-library-pro';

    public ?int $currentFolderId = null;
    public string $search = '';
    public ?string $filterMime = null;
    public ?string $filterUsage = null; // null, 'used', 'unused'
    public bool $onlyFavorites = false;
    public string $sortBy = 'latest'; // 'latest', 'oldest', 'name_asc', 'name_desc', 'size_desc', 'size_asc'
    public array $selectedIds = [];
    public ?int $activeMediaId = null;
    public int $perPage = 24;

    protected $queryString = [
        'currentFolderId' => ['except' => null, 'as' => 'folder'],
        'search'          => ['except' => ''],
        'filterMime'      => ['except' => null, 'as' => 'mime'],
        'filterUsage'     => ['except' => null, 'as' => 'usage'],
        'onlyFavorites'   => ['except' => false, 'as' => 'favorites'],
        'sortBy'          => ['except' => 'latest', 'as' => 'sort'],
    ];

    public function mount(): void
    {
        parent::mount();

        if (request()->has('folder')) {
            $this->currentFolderId = (int) request()->get('folder');
        } elseif (request()->has('category')) {
            $this->currentFolderId = (int) request()->get('category');
        }
    }

    public function openFolder(?int $folderId): void
    {
        $this->currentFolderId = $folderId;
        $this->search = '';
        $this->selectedIds = [];
    }

    public function selectMedia(int $mediaId): void
    {
        $this->activeMediaId = ($this->activeMediaId === $mediaId) ? null : $mediaId;
    }

    public function openPreview(int $mediaId): void
    {
        $this->activeMediaId = $mediaId;
    }

    public function getActivePreviewData(): ?array
    {
        return $this->getActiveMediaData();
    }

    public function closeInspector(): void
    {
        $this->activeMediaId = null;
    }

    public function toggleCheck(int $mediaId): void
    {
        if (in_array($mediaId, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$mediaId]));
        } else {
            $this->selectedIds[] = $mediaId;
        }
    }

    public function selectAll(): void
    {
        $currentMediaIds = $this->getMediaItemsQuery()->pluck('id')->all();
        $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $currentMediaIds)));
    }

    public function deselectAll(): void
    {
        $this->selectedIds = [];
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterMime = null;
        $this->filterUsage = null;
        $this->onlyFavorites = false;
        $this->sortBy = 'latest';
    }

    public function toggleFavorite(int $mediaId): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $user->favoriteMedia()->toggle($mediaId);
    }

    public function getUserFavoriteIds(): array
    {
        $user = Auth::user();
        if (!$user || !Schema::hasTable('media_favorites')) {
            return [];
        }

        return $user->favoriteMedia()->pluck('media.id')->all();
    }

    public function getBreadcrumbsList(): array
    {
        $crumbs = [
            ['id' => null, 'name' => 'مكتبة الوسائط']
        ];

        if (!$this->currentFolderId || !Schema::hasTable('media_categories')) {
            return $crumbs;
        }

        $folder = MediaCategory::find($this->currentFolderId);
        if (!$folder) {
            return $crumbs;
        }

        $lineage = [$folder];
        $curr = $folder->parent_id;
        while ($curr) {
            $parent = MediaCategory::find($curr);
            if (!$parent) break;
            array_unshift($lineage, $parent);
            $curr = $parent->parent_id;
        }

        foreach ($lineage as $item) {
            $crumbs[] = ['id' => $item->id, 'name' => $item->name];
        }

        return $crumbs;
    }

    public function getFolders()
    {
        if (!Schema::hasTable('media_categories')) {
            return collect();
        }

        $query = MediaCategory::query()->withCount('media', 'children');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        } else {
            if ($this->currentFolderId) {
                $query->where('parent_id', $this->currentFolderId);
            } else {
                $query->whereNull('parent_id');
            }
        }

        return $query->orderBy('name', 'asc')->get();
    }

    public function getMediaItemsQuery(): Builder
    {
        if (!Schema::hasTable('media')) {
            return Media::query()->whereRaw('1 = 0');
        }

        $query = Media::query()->with(['category'])->withCount('usages');

        if ($this->onlyFavorites) {
            $userId = Auth::id();
            $query->whereHas('favoritedByUsers', fn($q) => $q->where('users.id', $userId));
        }

        if (!empty($this->search)) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', $s)
                  ->orWhere('file_name', 'like', $s)
                  ->orWhere('alt_text', 'like', $s);
            });
        } else {
            if ($this->currentFolderId) {
                $query->where('category_id', $this->currentFolderId);
            }
        }

        if ($this->filterMime) {
            $query->where('mime_type', $this->filterMime);
        }

        if ($this->filterUsage === 'used') {
            $query->where(function ($q) {
                $q->whereHas('usages')->orWhereIn('id', function ($sub) {
                    $sub->select('featured_image_media_id')->from('contents')->whereNotNull('featured_image_media_id');
                });
            });
        } elseif ($this->filterUsage === 'unused') {
            $query->whereDoesntHave('usages')->whereNotIn('id', function ($sub) {
                $sub->select('featured_image_media_id')->from('contents')->whereNotNull('featured_image_media_id');
            });
        }

        return match ($this->sortBy) {
            'oldest'    => $query->orderBy('id', 'asc'),
            'name_asc'  => $query->orderBy('title', 'asc'),
            'name_desc' => $query->orderBy('title', 'desc'),
            'size_desc' => $query->orderBy('file_size', 'desc'),
            'size_asc'  => $query->orderBy('file_size', 'asc'),
            default     => $query->orderBy('id', 'desc'),
        };
    }

    public function getMediaItems()
    {
        return $this->getMediaItemsQuery()->limit(100)->get();
    }

    public function getActiveMediaData(): ?array
    {
        if (!$this->activeMediaId || !Schema::hasTable('media')) {
            return null;
        }

        $media = Media::with(['category', 'usages.usable', 'createdBy'])->find($this->activeMediaId);
        if (!$media) {
            return null;
        }

        $contentRefs = DB::table('contents')
            ->where('featured_image_media_id', $media->id)
            ->select('id', 'title_ar', 'title_en', 'slug_ar', 'status')
            ->get();

        $usagesList = [];
        foreach ($media->usages as $usage) {
            $usagesList[] = [
                'type'       => class_basename($usage->usable_type),
                'id'         => $usage->usable_id,
                'title'      => $usage->usable?->title_ar ?? $usage->usable?->title ?? "#{$usage->usable_id}",
                'field_name' => $usage->field_name,
                'date'       => $usage->created_at?->format('Y-m-d H:i'),
            ];
        }

        foreach ($contentRefs as $c) {
            $alreadyIncluded = false;
            foreach ($usagesList as $u) {
                if ($u['type'] === 'Content' && $u['id'] == $c->id) {
                    $alreadyIncluded = true;
                    break;
                }
            }
            if (!$alreadyIncluded) {
                $usagesList[] = [
                    'type'       => 'Content',
                    'id'         => $c->id,
                    'title'      => $c->title_ar ?: ($c->title_en ?: "#{$c->id}"),
                    'field_name' => 'featured_image',
                    'date'       => '-',
                ];
            }
        }

        return [
            'id'            => $media->id,
            'title'         => $media->title,
            'alt_text'      => $media->alt_text,
            'caption'       => $media->caption,
            'file_name'     => $media->file_name,
            'file_path'     => $media->file_path,
            'url'           => $media->url,
            'mime_type'     => $media->mime_type,
            'file_size'     => $media->file_size,
            'size_kb'       => number_format($media->file_size / 1024, 1),
            'width'         => $media->width,
            'height'        => $media->height,
            'category_name' => $media->category?->name ?? 'مكتبة الوسائط العامة',
            'created_by'    => $media->createdBy?->name ?? 'غير محدد',
            'created_at'    => $media->created_at?->format('Y-m-d H:i'),
            'usages_count'  => count($usagesList),
            'usages_list'   => $usagesList,
            'is_in_use'     => count($usagesList) > 0,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadAction')
                ->label('رفع وسائط')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => Auth::user()?->can('create', Media::class) ?? false)
                ->authorize(fn () => Auth::user()?->can('create', Media::class) ?? false)
                ->modalHeading('رفع وسائط إلى ' . ($this->currentFolderId && ($f = MediaCategory::find($this->currentFolderId)) ? $f->name : 'المكتبة الرئيسية'))
                ->modalSubmitActionLabel('بدء الرفع والمعالجة')
                ->form([
                    FileUpload::make('attachments')
                        ->label('ملفات الصور (رفع فردي أو جماعي)')
                        ->multiple()
                        ->reorderable(false)
                        ->panelLayout('grid')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(10240)
                        ->required()
                        ->rules([new ValidImageFileRule()])
                        ->helperText('الأنواع المدعومة: JPG, PNG, WebP (بحد أقصى 10MB لكل صورة). يمكنك تحديد أو سحب عدة صور معاً دفعة واحدة.'),

                    Select::make('category_id')
                        ->label('المجلد / التصنيف')
                        ->default(fn () => $this->currentFolderId)
                        ->options(fn () => Schema::hasTable('media_categories') ? MediaCategory::pluck('name', 'id') : [])
                        ->searchable()
                        ->nullable()
                        ->placeholder('المجلد الرئيسي (بدون تصنيف)'),

                    TextInput::make('title')
                        ->label('عنوان الوسيط (اختياري)')
                        ->maxLength(255)
                        ->placeholder('يُترك فارغاً لاعتماد اسم كل ملف تلقائياً'),

                    TextInput::make('alt_text')
                        ->label('النص البديل (Alt Text)')
                        ->maxLength(255)
                        ->placeholder('اختياري لجميع الصور المرفوعة'),

                    Textarea::make('caption')
                        ->label('الوصف التوضيحي')
                        ->rows(2)
                        ->placeholder('اختياري لجميع الصور المرفوعة'),

                    Toggle::make('force_duplicate')
                        ->label('السماح برفع نسخ مكررة إذا تم اكتشاف تطابق')
                        ->helperText('الوضع الافتراضي يتخطى رفع الصور المتطابقة لحفظ مساحة التخزين.')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    if (!Auth::user()?->can('create', Media::class)) {
                        Notification::make()->title('غير مصرح لك برفع وسائط')->danger()->send();
                        return;
                    }
                    $raw = $data['attachments'] ?? $data['attachment'] ?? [];
                    $attachments = is_array($raw) ? array_values(array_filter($raw)) : array_filter([$raw]);

                    if (empty($attachments)) {
                        Notification::make()->title('لم يتم اختيار أي ملف للرفع')->danger()->send();
                        return;
                    }

                    $lifecycleService = app(MediaLifecycleService::class);
                    $successCount = 0;
                    $failedItems = [];
                    $duplicateItems = [];
                    $lastMedia = null;
                    $total = count($attachments);
                    $force = !empty($data['force_duplicate']);

                    foreach ($attachments as $index => $attachment) {
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

                        if (!$resolvedPath || !file_exists($resolvedPath)) {
                            $failedItems[] = "تعذر العثور على الملف المرفوع مؤقتاً (#" . ($index + 1) . ")";
                            continue;
                        }

                        $originalName = basename($attachment);
                        $mimeType = @mime_content_type($resolvedPath) ?: 'image/jpeg';
                        $uploadedFile = new UploadedFile($resolvedPath, $originalName, $mimeType, null, true);

                        $itemTitle = !empty($data['title'])
                            ? ($total > 1 ? "{$data['title']} (" . ($index + 1) . ")" : $data['title'])
                            : pathinfo($originalName, PATHINFO_FILENAME);

                        try {
                            $media = $lifecycleService->uploadMedia($uploadedFile, [
                                'category_id' => $data['category_id'] ?? $this->currentFolderId,
                                'title'       => $itemTitle,
                                'alt_text'    => $data['alt_text'] ?? null,
                                'caption'     => $data['caption'] ?? null,
                                'created_by'  => Auth::id(),
                            ], force: $force);

                            $successCount++;
                            $lastMedia = $media;

                            foreach (['public', 'local'] as $diskName) {
                                if (Storage::disk($diskName)->exists($attachment)) {
                                    Storage::disk($diskName)->delete($attachment);
                                }
                            }
                        } catch (MediaDuplicateDetectedException $e) {
                            $dup = $e->getDuplicateMedia();
                            $duplicateItems[] = "{$originalName}: مطابق للصورة '{$dup->title}' (#{$dup->id})";

                            foreach (['public', 'local'] as $diskName) {
                                if (Storage::disk($diskName)->exists($attachment)) {
                                    Storage::disk($diskName)->delete($attachment);
                                }
                            }
                        } catch (MediaSecurityException $e) {
                            $failedItems[] = "{$originalName}: " . $e->getMessage();
                        } catch (\Throwable $e) {
                            $failedItems[] = "{$originalName}: " . $e->getMessage();
                        }
                    }

                    if ($lastMedia) {
                        $this->activeMediaId = $lastMedia->id;
                    }

                    if (!empty($duplicateItems)) {
                        Notification::make()
                            ->title("تم اكتشاف " . count($duplicateItems) . " صور مكررة في المكتبة وتخطيها")
                            ->body(implode("\n", array_slice($duplicateItems, 0, 5)))
                            ->warning()
                            ->send();
                    }

                    if ($successCount > 0 && empty($failedItems)) {
                        Notification::make()
                            ->title($successCount > 1 ? "تم رفع ومعالجة {$successCount} وسائط بنجاح" : "تم رفع ومعالجة الوسيط بنجاح")
                            ->success()
                            ->send();
                    } elseif ($successCount > 0 && !empty($failedItems)) {
                        Notification::make()
                            ->title("تم رفع {$successCount} وسائط بنجاح، مع تعذر " . count($failedItems) . " ملفات")
                            ->body(implode("\n", array_slice($failedItems, 0, 5)))
                            ->warning()
                            ->send();
                    } elseif ($successCount === 0 && !empty($failedItems)) {
                        Notification::make()
                            ->title('فشل رفع الملفات')
                            ->body(implode("\n", array_slice($failedItems, 0, 5)))
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('createFolderAction')
                ->label('إنشاء مجلد')
                ->icon('heroicon-o-folder-plus')
                ->color('gray')
                ->visible(fn () => Auth::user()?->can('create', MediaCategory::class) ?? false)
                ->authorize(fn () => Auth::user()?->can('create', MediaCategory::class) ?? false)
                ->modalHeading('إنشاء مجلد تصنيف جديد')
                ->modalSubmitActionLabel('حفظ المجلد')
                ->form([
                    TextInput::make('name')
                        ->label('اسم المجلد / التصنيف')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: صور المشاريع، فعاليات 2026...'),

                    TextInput::make('slug')
                        ->label('الاسم اللطيف (Slug)')
                        ->maxLength(255)
                        ->helperText('يُترك فارغاً للتوليد التلقائي.'),
                ])
                ->action(function (array $data) {
                    if (!Auth::user()?->can('create', MediaCategory::class)) {
                        Notification::make()->title('غير مصرح لك بإنشاء مجلدات')->danger()->send();
                        return;
                    }

                    if (empty($data['slug'])) {
                        $data['slug'] = Str::slug($data['name']);
                    }

                    $folder = MediaCategory::create([
                        'name'      => $data['name'],
                        'slug'      => $data['slug'],
                        'parent_id' => $this->currentFolderId,
                    ]);

                    Notification::make()
                        ->title("تم إنشاء المجلد '{$folder->name}' بنجاح")
                        ->success()
                        ->send();
                }),

            Action::make('bulkDeleteAction')
                ->label('حذف المحدد')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('تأكيد حذف الوسائط المحددة')
                ->modalDescription('سيتم فحص الاستخدامات الفعلية في صفحات ومحتويات النظام، ولن يتم حذف أي وسيط مرتبط بمحتوى منشور.')
                ->visible(fn () => (Auth::user()?->can('deleteAny', Media::class) ?? false) && !empty($this->selectedIds))
                ->authorize(fn () => Auth::user()?->can('deleteAny', Media::class) ?? false)
                ->action(function () {
                    if (!Auth::user()?->can('deleteAny', Media::class)) {
                        Notification::make()->title('غير مصرح لك بحذف الوسائط')->danger()->send();
                        return;
                    }

                    if (empty($this->selectedIds)) return;

                    $deleted = 0;
                    $skipped = 0;
                    $service = app(MediaLifecycleService::class);
                    $mediaItems = Media::whereIn('id', $this->selectedIds)->get();

                    foreach ($mediaItems as $media) {
                        $usagesCount = $media->usages()->count();
                        $contentCount = DB::table('contents')->where('featured_image_media_id', $media->id)->count();

                        if ($usagesCount > 0 || $contentCount > 0) {
                            $skipped++;
                            continue;
                        }

                        try {
                            $service->forceDeleteMedia($media->id);
                            $deleted++;
                        } catch (\Throwable $e) {
                            $skipped++;
                        }
                    }

                    $this->selectedIds = [];
                    if ($this->activeMediaId && !Media::where('id', $this->activeMediaId)->exists()) {
                        $this->activeMediaId = null;
                    }

                    if ($skipped > 0) {
                        Notification::make()
                            ->title("تم حذف {$deleted} وسائط بنجاح، وتخطي {$skipped} وسائط لوجود استخدامات نشطة.")
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title("تم حذف {$deleted} وسائط بنجاح.")
                            ->success()
                            ->send();
                    }
                }),

            Action::make('editMediaAction')
                ->label('تعديل البيانات')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn () => Auth::user()?->can('update', Media::class) ?? false)
                ->authorize(fn () => Auth::user()?->can('update', Media::class) ?? false)
                ->modalHeading('تعديل بيانات الوسيط')
                ->modalSubmitActionLabel('حفظ التعديلات')
                ->fillForm(function (array $arguments) {
                    $mediaId = $arguments['mediaId'] ?? $this->activeMediaId ?? null;
                    if (!$mediaId) {
                        return [];
                    }

                    $media = Media::find($mediaId);
                    if (!$media) {
                        return [];
                    }

                    return [
                        'title'       => $media->title,
                        'alt_text'    => $media->alt_text,
                        'caption'     => $media->caption,
                        'category_id' => $media->category_id,
                    ];
                })
                ->form([
                    TextInput::make('title')
                        ->label('عنوان الوسيط')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('alt_text')
                        ->label('النص البديل (Alt Text)')
                        ->maxLength(255),

                    Select::make('category_id')
                        ->label('المجلد / التصنيف')
                        ->options(fn () => Schema::hasTable('media_categories') ? MediaCategory::pluck('name', 'id') : [])
                        ->searchable()
                        ->nullable()
                        ->placeholder('المكتبة الرئيسية (بدون تصنيف)'),

                    Textarea::make('caption')
                        ->label('الوصف التوضيحي')
                        ->rows(2),
                ])
                ->action(function (array $data, array $arguments) {
                    $mediaId = $arguments['mediaId'] ?? $this->activeMediaId ?? null;
                    if (!$mediaId) {
                        Notification::make()->title('لم يتم العثور على الوسيط')->danger()->send();
                        return;
                    }

                    $media = Media::find($mediaId);
                    if (!$media) {
                        Notification::make()->title('الوسيط غير موجود')->danger()->send();
                        return;
                    }

                    $media->update([
                        'title'       => $data['title'],
                        'alt_text'    => $data['alt_text'] ?? null,
                        'caption'     => $data['caption'] ?? null,
                        'category_id' => $data['category_id'] ?? null,
                    ]);

                    Notification::make()
                        ->title('تم تحديث بيانات الوسيط بنجاح')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function deleteMedia(int $mediaId): void
    {
        $user = Auth::user();
        if (!$user || !$user->can('delete', Media::class)) {
            Notification::make()->title('غير مصرح لك بحذف الوسائط')->danger()->send();
            return;
        }

        $media = Media::find($mediaId);
        if (!$media) return;

        $usagesCount = $media->usages()->count();
        $contentCount = DB::table('contents')->where('featured_image_media_id', $media->id)->count();

        if ($usagesCount > 0 || $contentCount > 0) {
            Notification::make()
                ->title('لا يمكن حذف الوسيط')
                ->body('الوسيط مرتبط حالياً بـ ' . ($usagesCount + $contentCount) . ' محتوى. يجب فك ارتباطه أولاً.')
                ->danger()
                ->send();
            return;
        }

        try {
            app(MediaLifecycleService::class)->forceDeleteMedia($media->id);

            if ($this->activeMediaId === $mediaId) {
                $this->activeMediaId = null;
            }

            $this->selectedIds = array_values(array_diff($this->selectedIds, [$mediaId]));

            Notification::make()
                ->title('تم حذف الوسيط وجدولة إزالة الملف بنجاح')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('تعذر حذف الوسيط')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
