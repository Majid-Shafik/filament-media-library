<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Media\Schemas;

use MajidShafik\FilamentMediaLibrary\Models\Media;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Group::make()
                    ->columnSpan(['lg' => 8])
                    ->schema([
                        Section::make('معاينة الوسيط')
                            ->schema([
                                ImageEntry::make('file_path')
                                    ->label('')
                                    ->disk('public')
                                    ->height(380)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('حصر الاستخدامات والمراجع في النظام')
                            ->description('يوضح هذا القسم السجلات والصفحات التي ترتبط بهذا الملف، لمنع الحذف العرضي أو كسر الروابط.')
                            ->schema([
                                TextEntry::make('usages_count')
                                    ->label('إجمالي عدد الاستخدامات')
                                    ->counts('usages')
                                    ->badge()
                                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                                RepeatableEntry::make('usages')
                                    ->label('السجلات المرتبطة')
                                    ->schema([
                                        TextEntry::make('usable_type')
                                            ->label('نوع السجل')
                                            ->formatStateUsing(fn ($state) => class_basename($state)),

                                        TextEntry::make('usable_id')
                                            ->label('رقم المعرف (#ID)'),

                                        TextEntry::make('field_name')
                                            ->label('الحقل المرتبط')
                                            ->badge(),

                                        TextEntry::make('created_at')
                                            ->label('تاريخ الربط')
                                            ->dateTime(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull()
                                    ->placeholder('لا توجد استخدامات مسجلة لهذا الوسيط حالياً. يمكنك حذفه بأمان عند الحاجة.'),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(['lg' => 4])
                    ->schema([
                        Section::make('البيانات الأساسية والتصنيف')
                            ->schema([
                                TextEntry::make('title')
                                    ->label('العنوان'),

                                TextEntry::make('category.name')
                                    ->label('التصنيف')
                                    ->badge()
                                    ->placeholder('بدون تصنيف'),

                                TextEntry::make('alt_text')
                                    ->label('النص البديل')
                                    ->placeholder('غير محدد'),

                                TextEntry::make('caption')
                                    ->label('الوصف التوضيحي')
                                    ->placeholder('غير محدد'),
                            ]),

                        Section::make('البيانات التقنية للملف')
                            ->schema([
                                TextEntry::make('file_name')
                                    ->label('اسم الملف الأصلي'),

                                TextEntry::make('mime_type')
                                    ->label('نوع الملف (MIME)')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('file_size')
                                    ->label('حجم الملف')
                                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 1) . ' KB'),

                                TextEntry::make('dimensions')
                                    ->label('الأبعاد بالبكسل')
                                    ->state(fn (Media $record) => $record->width && $record->height ? "{$record->width} × {$record->height} px" : '-'),

                                TextEntry::make('file_path')
                                    ->label('مسار التخزين')
                                    ->copyable(),

                                TextEntry::make('disk')
                                    ->label('قرص التخزين')
                                    ->badge(),

                                TextEntry::make('createdBy.name')
                                    ->label('رُفع بواسطة')
                                    ->placeholder('غير محدد'),

                                TextEntry::make('created_at')
                                    ->label('تاريخ الرفع')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
