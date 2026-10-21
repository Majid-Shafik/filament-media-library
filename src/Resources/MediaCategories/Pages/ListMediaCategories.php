<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Pages;

use MajidShafik\FilamentMediaLibrary\Exceptions\CyclicCategoryException;
use MajidShafik\FilamentMediaLibrary\Resources\MediaCategories\MediaCategoryResource;
use MajidShafik\FilamentMediaLibrary\Models\MediaCategory;
use MajidShafik\FilamentMediaLibrary\Services\MediaCategoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ListMediaCategories extends ListRecords
{
    protected static string $resource = MediaCategoryResource::class;

    protected string $view = 'filament.pages.media-categories-tree';

    public string $viewMode = 'tree'; // 'tree' or 'table'
    public string $search = '';
    public array $expanded = [];

    public function mount(): void
    {
        parent::mount();

        if (Schema::hasTable('media_categories')) {
            $this->expanded = MediaCategory::whereNull('parent_id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
    }

    public function toggleExpand(int $categoryId): void
    {
        if (in_array($categoryId, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$categoryId]));
        } else {
            $this->expanded[] = $categoryId;
        }
    }

    public function expandAll(): void
    {
        if (Schema::hasTable('media_categories')) {
            $this->expanded = MediaCategory::pluck('id')->map(fn ($id) => (int) $id)->all();
        }
    }

    public function collapseAll(): void
    {
        $this->expanded = [];
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function getStats(): array
    {
        if (!Schema::hasTable('media_categories')) {
            return ['root' => 0, 'sub' => 0, 'total' => 0, 'media' => 0];
        }

        $root = MediaCategory::whereNull('parent_id')->count();
        $sub = MediaCategory::whereNotNull('parent_id')->count();
        $total = $root + $sub;
        $media = Schema::hasTable('media') ? \MajidShafik\FilamentMediaLibrary\Models\Media::count() : 0;

        return [
            'root'  => $root,
            'sub'   => $sub,
            'total' => $total,
            'media' => $media,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRootCategory')
                ->label('إضافة تصنيف رئيسي')
                ->icon('heroicon-o-folder-plus')
                ->color('primary')
                ->visible(fn () => Auth::user()?->can('create', MediaCategory::class) ?? false)
                ->modalHeading('إنشاء تصنيف رئيسي جديد')
                ->modalSubmitActionLabel('حفظ التصنيف')
                ->form([
                    TextInput::make('name')
                        ->label('اسم التصنيف')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: صور المشاريع، فعاليات...'),

                    TextInput::make('slug')
                        ->label('الاسم اللطيف (Slug)')
                        ->maxLength(255)
                        ->helperText('يُترك فارغاً للتوليد التلقائي.'),
                ])
                ->action(function (array $data) {
                    if (empty($data['slug'])) {
                        $data['slug'] = Str::slug($data['name']);
                    }

                    $cat = MediaCategory::create([
                        'name'      => $data['name'],
                        'slug'      => $data['slug'],
                        'parent_id' => null,
                    ]);

                    $this->expanded[] = (int) $cat->id;

                    Notification::make()
                        ->title('تم إنشاء التصنيف الرئيسي بنجاح')
                        ->success()
                        ->send();
                }),

            Action::make('createSubcategory')
                ->label('إضافة فرع')
                ->visible(fn () => Auth::user()?->can('create', MediaCategory::class) ?? false)
                ->modalHeading('إضافة تصنيف فرعي')
                ->modalSubmitActionLabel('حفظ التصنيف الفرعي')
                ->fillForm(fn (array $arguments) => [
                    'parent_id' => $arguments['parent_id'] ?? null,
                ])
                ->form([
                    Select::make('parent_id')
                        ->label('التصنيف الأب')
                        ->options(fn () => MediaCategory::pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    TextInput::make('name')
                        ->label('اسم التصنيف الفرعي')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('اسم الفرع...'),

                    TextInput::make('slug')
                        ->label('الاسم اللطيف (Slug)')
                        ->maxLength(255)
                        ->helperText('يُترك فارغاً للتوليد التلقائي.'),
                ])
                ->action(function (array $data) {
                    if (empty($data['slug'])) {
                        $data['slug'] = Str::slug($data['name']);
                    }

                    $cat = MediaCategory::create([
                        'name'      => $data['name'],
                        'slug'      => $data['slug'],
                        'parent_id' => $data['parent_id'],
                    ]);

                    if (!in_array((int) $data['parent_id'], $this->expanded, true)) {
                        $this->expanded[] = (int) $data['parent_id'];
                    }

                    Notification::make()
                        ->title('تم إنشاء التصنيف الفرعي بنجاح')
                        ->success()
                        ->send();
                }),

            Action::make('editCategoryAction')
                ->label('تعديل التصنيف')
                ->visible(fn () => Auth::user()?->can('update', MediaCategory::class) ?? false)
                ->modalHeading('تعديل بيانات التصنيف')
                ->modalSubmitActionLabel('حفظ التعديلات')
                ->fillForm(function (array $arguments) {
                    $cat = MediaCategory::findOrFail($arguments['id']);
                    return [
                        'id'        => $cat->id,
                        'name'      => $cat->name,
                        'slug'      => $cat->slug,
                        'parent_id' => $cat->parent_id,
                    ];
                })
                ->form([
                    TextInput::make('name')
                        ->label('اسم التصنيف')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('slug')
                        ->label('الاسم اللطيف (Slug)')
                        ->maxLength(255),

                    Select::make('parent_id')
                        ->label('التصنيف الأب')
                        ->options(function ($get) {
                            $catId = $get('id');
                            $query = MediaCategory::query();
                            if ($catId) {
                                $cat = MediaCategory::find($catId);
                                if ($cat) {
                                    $excluded = array_merge([$cat->id], $cat->getAllDescendantIds()->all());
                                    $query->whereNotIn('id', $excluded);
                                }
                            }
                            return $query->pluck('name', 'id');
                        })
                        ->searchable()
                        ->nullable()
                        ->placeholder('بدون تصنيف أب (تصنيف رئيسي)'),
                ])
                ->action(function (array $data, array $arguments, Action $action) {
                    $cat = MediaCategory::findOrFail($arguments['id']);
                    $newParentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

                    if (empty($data['slug'])) {
                        $data['slug'] = Str::slug($data['name']);
                    }

                    $cat->name = $data['name'];
                    $cat->slug = $data['slug'];
                    $cat->save();

                    if ($newParentId !== $cat->parent_id) {
                        try {
                            app(MediaCategoryService::class)->assignParent($cat, $newParentId);
                        } catch (CyclicCategoryException $e) {
                            Notification::make()
                                ->title('فشل نقل التصنيف')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    }

                    Notification::make()
                        ->title('تم تحديث التصنيف بنجاح')
                        ->success()
                        ->send();
                }),

            Action::make('deleteCategoryAction')
                ->label('حذف')
                ->visible(fn () => Auth::user()?->can('delete', MediaCategory::class) ?? false)
                ->requiresConfirmation()
                ->color('danger')
                ->modalHeading('تأكيد حذف التصنيف')
                ->modalDescription('سيتم التأكد من خلو التصنيف من أي فروع أو وسائط مرتبطة وفق القواعد الهندسية قبل الحذف.')
                ->action(function (array $arguments, Action $action) {
                    try {
                        $cat = MediaCategory::findOrFail($arguments['id']);
                        app(MediaCategoryService::class)->deleteCategory($cat);
                        Notification::make()
                            ->title('تم حذف التصنيف بنجاح')
                            ->success()
                            ->send();
                    } catch (CyclicCategoryException $e) {
                        Notification::make()
                            ->title('تعذر حذف التصنيف')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        $action->halt();
                    }
                }),
        ];
    }

    /**
     * Get computed tree structure for rendering.
     */
    public function getTreeNodes(): array
    {
        if (!Schema::hasTable('media_categories')) {
            return [];
        }

        $allCategories = MediaCategory::with(['children', 'media'])->get();
        $mediaTableExists = Schema::hasTable('media');

        $byId = [];
        foreach ($allCategories as $cat) {
            $directMediaCount = $mediaTableExists ? $cat->media->count() : 0;
            $byId[$cat->id] = [
                'id'                 => $cat->id,
                'name'               => $cat->name,
                'slug'               => $cat->slug,
                'parent_id'          => $cat->parent_id,
                'direct_media_count' => $directMediaCount,
                'total_media_count'  => $directMediaCount,
                'children_ids'       => [],
                'level'              => 0,
                'breadcrumb'         => $cat->name,
            ];
        }

        foreach ($byId as $id => &$node) {
            if ($node['parent_id'] && isset($byId[$node['parent_id']])) {
                $byId[$node['parent_id']]['children_ids'][] = $id;
            }
        }
        unset($node);

        foreach ($byId as $id => &$node) {
            $catModel = $allCategories->firstWhere('id', $id);
            if ($catModel) {
                $descendantIds = $catModel->getAllDescendantIds();
                $extraCount = 0;
                foreach ($descendantIds as $did) {
                    if (isset($byId[$did])) {
                        $extraCount += $byId[$did]['direct_media_count'];
                    }
                }
                $node['total_media_count'] = $node['direct_media_count'] + $extraCount;

                $crumbs = [$node['name']];
                $curr = $node['parent_id'];
                while ($curr && isset($byId[$curr])) {
                    array_unshift($crumbs, $byId[$curr]['name']);
                    $curr = $byId[$curr]['parent_id'];
                }
                $node['breadcrumb'] = implode(' ← ', $crumbs);
            }
        }
        unset($node);

        $search = trim($this->search);
        $matchingIds = [];
        if ($search !== '') {
            foreach ($byId as $id => $node) {
                if (str_contains(mb_strtolower($node['name']), mb_strtolower($search)) || str_contains(mb_strtolower($node['slug']), mb_strtolower($search))) {
                    $matchingIds[] = $id;
                    $curr = $node['parent_id'];
                    while ($curr && isset($byId[$curr])) {
                        if (!in_array($curr, $this->expanded, true)) {
                            $this->expanded[] = $curr;
                        }
                        $matchingIds[] = $curr;
                        $curr = $byId[$curr]['parent_id'];
                    }
                }
            }
        }

        $flatTree = [];
        $walk = function (int $id, int $level) use (&$walk, &$flatTree, &$byId, $search, &$matchingIds) {
            if (!isset($byId[$id])) {
                return;
            }

            if ($search !== '' && !in_array($id, $matchingIds, true)) {
                return;
            }

            $node = $byId[$id];
            $node['level'] = $level;
            $node['has_children'] = !empty($node['children_ids']);
            $node['is_expanded'] = in_array($id, $this->expanded, true);

            $flatTree[] = $node;

            if ($node['has_children'] && $node['is_expanded']) {
                foreach ($node['children_ids'] as $childId) {
                    $walk($childId, $level + 1);
                }
            }
        };

        foreach ($byId as $id => $node) {
            if ($node['parent_id'] === null) {
                $walk($id, 0);
            }
        }

        return $flatTree;
    }
}
