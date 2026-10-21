<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Tables;

use MajidShafik\FilamentMediaLibrary\Models\Media;
use MajidShafik\FilamentMediaLibrary\Services\MediaLifecycleService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('file_path')
                    ->label('المعاينة')
                    ->disk('public')
                    ->square()
                    ->size(60),

                TextColumn::make('title')
                    ->label('عنوان الوسيط')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('file_name')
                    ->label('اسم الملف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->badge()
                    ->placeholder('بدون تصنيف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mime_type')
                    ->label('النوع')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('file_size')
                    ->label('الحجم')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 1) . ' KB')
                    ->sortable(),

                TextColumn::make('dimensions')
                    ->label('الأبعاد')
                    ->state(fn (Media $record) => $record->width && $record->height ? "{$record->width} × {$record->height}" : '-'),

                TextColumn::make('usages_count')
                    ->label('الاستخدامات')
                    ->counts('usages')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الرفع')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('mime_type')
                    ->label('نوع الملف')
                    ->options([
                        'image/jpeg' => 'JPEG / JPG',
                        'image/png'  => 'PNG',
                        'image/webp' => 'WebP',
                    ]),

                Filter::make('unused')
                    ->label('غير مستخدم (آمن للحذف)')
                    ->query(function (Builder $query) {
                        return $query->whereDoesntHave('usages')
                            ->whereNotIn('id', function ($q) {
                                $q->select('featured_image_media_id')
                                    ->from('contents')
                                    ->whereNotNull('featured_image_media_id');
                            });
                    }),

                Filter::make('used')
                    ->label('مستخدم في محتوى')
                    ->query(function (Builder $query) {
                        return $query->where(function ($q) {
                            $q->whereHas('usages')
                                ->orWhereIn('id', function ($sub) {
                                    $sub->select('featured_image_media_id')
                                        ->from('contents')
                                        ->whereNotNull('featured_image_media_id');
                                });
                        });
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('حذف وسيط نهائياً')
                        ->modalDescription('سيتم التحقق من عدم وجود أي صفحات أو محتويات تستخدم هذا الوسيط قبل بدء الحذف.')
                        ->before(function (DeleteAction $action, Media $record) {
                            $usagesCount = $record->usages()->count();
                            $contentCount = DB::table('contents')->where('featured_image_media_id', $record->id)->count();

                            if ($usagesCount > 0 || $contentCount > 0) {
                                Notification::make()
                                    ->title('لا يمكن حذف الوسيط')
                                    ->body('هذا الوسيط مرتبط حالياً بـ ' . ($usagesCount + $contentCount) . ' محتوى. يجب فك ارتباطه أولاً قبل الحذف.')
                                    ->danger()
                                    ->send();
                                $action->halt();
                            }
                        })
                        ->action(function (Media $record) {
                            app(MediaLifecycleService::class)->forceDeleteMedia($record->id);
                            Notification::make()
                                ->title('تم حذف الوسيط وجدولة إزالة الملف بنجاح')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $deleted = 0;
                            $skipped = 0;
                            $service = app(MediaLifecycleService::class);

                            foreach ($records as $record) {
                                $usagesCount = $record->usages()->count();
                                $contentCount = DB::table('contents')->where('featured_image_media_id', $record->id)->count();

                                if ($usagesCount > 0 || $contentCount > 0) {
                                    $skipped++;
                                    continue;
                                }

                                try {
                                    $service->forceDeleteMedia($record->id);
                                    $deleted++;
                                } catch (\Throwable $e) {
                                    $skipped++;
                                }
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
                ]),
            ]);
    }
}
