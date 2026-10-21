<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Tables;

use MajidShafik\FilamentMediaLibrary\Exceptions\CyclicCategoryException;
use MajidShafik\FilamentMediaLibrary\Models\MediaCategory;
use MajidShafik\FilamentMediaLibrary\Services\MediaCategoryService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MediaCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم التصنيف')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('الاسم اللطيف (Slug)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parent.name')
                    ->label('التصنيف الأب')
                    ->placeholder('تصنيف رئيسي')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('children_count')
                    ->label('الفروع المباشرة')
                    ->counts('children')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('media_count')
                    ->label('الوسائط المباشرة')
                    ->counts('media')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('التصنيف الأب')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('root_only')
                    ->label('التصنيفات الرئيسية فقط')
                    ->query(fn ($query) => $query->whereNull('parent_id')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_media')
                        ->label('استعراض الوسائط')
                        ->icon('heroicon-o-photo')
                        ->color('success')
                        ->url(fn (MediaCategory $record) => \MajidShafik\FilamentMediaLibrary\Resources\Media\MediaResource::getUrl('index', ['category' => $record->id])),

                    EditAction::make(),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('حذف تصنيف الوسائط')
                        ->modalDescription('سيتم التحقق من عدم وجود تصنيفات فرعية أو وسائط مرتبطة قبل إتمام الحذف.')
                        ->action(function (MediaCategory $record, DeleteAction $action) {
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
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Standard bulk actions
                ]),
            ]);
    }
}
