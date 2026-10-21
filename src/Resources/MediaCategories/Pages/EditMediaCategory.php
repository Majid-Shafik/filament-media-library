<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Pages;

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
