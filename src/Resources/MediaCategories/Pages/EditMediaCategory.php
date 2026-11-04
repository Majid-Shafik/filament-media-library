<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\MediaCategories\Pages;

use MajidShafik\FilamentMediaLibrary\Exceptions\CyclicCategoryException;
use MajidShafik\FilamentMediaLibrary\Resources\MediaCategories\MediaCategoryResource;
use MajidShafik\FilamentMediaLibrary\Services\MediaCategoryService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditMediaCategory extends EditRecord
{
    protected static string $resource = MediaCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function ($record, Actions\DeleteAction $action) {
                    try {
                        app(MediaCategoryService::class)->deleteCategory($record);
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (empty($data['slug'])) {
            $data['slug'] = 'cat-' . Str::random(6);
        }

        if (static::getModel()::where('slug', $data['slug'])->where('id', '!=', $record->id)->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'هذا الاسم اللطيف (Slug) مستخدم بالفعل، يرجى اختيار اسم أو رابط آخر.',
                'name' => 'يوجد تصنيف بهذا الاسم أو الاسم اللطيف مسبقاً، يرجى تغيير الاسم.',
            ]);
        }

        $newParentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        unset($data['parent_id']);

        $record->update($data);

        if ($newParentId !== $record->parent_id) {
            try {
                app(MediaCategoryService::class)->assignParent($record, $newParentId);
            } catch (CyclicCategoryException $e) {
                throw ValidationException::withMessages([
                    'parent_id' => $e->getMessage(),
                ]);
            }
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
