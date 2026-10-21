<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\MediaCategories;

use MajidShafik\FilamentMediaLibrary\Resources\MediaCategories\Pages;
use MajidShafik\FilamentMediaLibrary\Resources\MediaCategories\Schemas\MediaCategoryForm;
use MajidShafik\FilamentMediaLibrary\Resources\MediaCategories\Tables\MediaCategoriesTable;
use MajidShafik\FilamentMediaLibrary\Models\MediaCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MediaCategoryResource extends Resource
{
    protected static ?string $model = MediaCategory::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'تصنيفات الوسائط';

    protected static ?string $pluralLabel = 'تصنيفات الوسائط';

    protected static ?string $modelLabel = 'تصنيف وسائط';

    protected static UnitEnum|string|null $navigationGroup = 'مكتبة الوسائط';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    public static function form(Schema $schema): Schema
    {
        return MediaCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMediaCategories::route('/'),
            'create' => Pages\CreateMediaCategory::route('/create'),
            'edit'   => Pages\EditMediaCategory::route('/{record}/edit'),
        ];
    }
}
