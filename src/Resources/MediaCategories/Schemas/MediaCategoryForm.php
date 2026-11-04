<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\MediaCategories\Schemas;

use MajidShafik\FilamentMediaLibrary\Models\MediaCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Group::make()
                    ->columnSpanFull()
                    ->schema([
                        Section::make('بيانات التصنيف')
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم التصنيف')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, callable $set, callable $get) {
                                        if (empty($get('slug')) && !empty($state)) {
                                            $set('slug', \Illuminate\Support\Str::slug($state));
                                        }
                                    })
                                    ->placeholder('أدخل اسم التصنيف...'),

                                TextInput::make('slug')
                                    ->label('الاسم اللطيف (Slug)')
                                    ->maxLength(255)
                                    ->unique(MediaCategory::class, 'slug', ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'الاسم اللطيف (Slug) مستخدم بالفعل، يرجى اختيار اسم أو رابط آخر.',
                                    ])
                                    ->helperText('يُترك فارغاً للتوليد التلقائي من الاسم.'),

                                Select::make('parent_id')
                                    ->label('التصنيف الأب')
                                    ->options(function (?MediaCategory $record) {
                                        $query = MediaCategory::query();
                                        if ($record && $record->exists) {
                                            $excluded = array_merge([$record->id], $record->getAllDescendantIds()->all());
                                            $query->whereNotIn('id', $excluded);
                                        }
                                        return $query->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->placeholder('بدون تصنيف أب (تصنيف رئيسي)'),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
}
