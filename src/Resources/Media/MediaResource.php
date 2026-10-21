<?php

namespace MajidShafik\FilamentMediaLibrary\Resources;

use MajidShafik\FilamentMediaLibrary\Resources\Media\Pages;
use MajidShafik\FilamentMediaLibrary\Resources\Media\Schemas\MediaForm;
use MajidShafik\FilamentMediaLibrary\Resources\Media\Schemas\MediaInfolist;
use MajidShafik\FilamentMediaLibrary\Resources\Media\Tables\MediaTable;
use MajidShafik\FilamentMediaLibrary\Models\Media;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'مكتبة الوسائط';

    protected static ?string $pluralLabel = 'مكتبة الوسائط';

    protected static ?string $modelLabel = 'وسيط';

    protected static UnitEnum|string|null $navigationGroup = 'مكتبة الوسائط';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public static function form(Schema $schema): Schema
    {
        return MediaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MediaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'view'   => Pages\ViewMedia::route('/{record}'),
            'edit'   => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}
