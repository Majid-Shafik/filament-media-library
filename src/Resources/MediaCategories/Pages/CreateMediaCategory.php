<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Pages;

use MajidShafik\FilamentMediaLibrary\Exceptions\CyclicCategoryException;
use MajidShafik\FilamentMediaLibrary\Resources\MediaCategories\MediaCategoryResource;
use MajidShafik\FilamentMediaLibrary\Services\MediaCategoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateMediaCategory extends CreateRecord
{
    protected static string $resource = MediaCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        unset($data['parent_id']);

        $category = static::getModel()::create($data);

        if ($parentId !== null) {
            try {
                app(MediaCategoryService::class)->assignParent($category, $parentId);
            } catch (CyclicCategoryException $e) {
                $category->delete();
                throw ValidationException::withMessages([
                    'parent_id' => $e->getMessage(),
                ]);
            }
        }

        return $category;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
