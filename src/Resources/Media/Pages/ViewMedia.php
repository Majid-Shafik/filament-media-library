<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Pages;

use MajidShafik\FilamentMediaLibrary\Resources\Media\MediaResource;
use MajidShafik\FilamentMediaLibrary\Models\Media;
use MajidShafik\FilamentMediaLibrary\Services\MediaLifecycleService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewMedia extends ViewRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('حذف وسيط نهائياً')
                ->modalDescription('سيتم التحقق من عدم وجود أي صفحات أو محتويات تستخدم هذا الوسيط قبل بدء الحذف.')
                ->before(function (Actions\DeleteAction $action, Media $record) {
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
        ];
    }
}
