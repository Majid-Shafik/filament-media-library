<?php

namespace MajidShafik\FilamentMediaLibrary\Resources\Media\Schemas;

use MajidShafik\FilamentMediaLibrary\Models\Media;
use MajidShafik\FilamentMediaLibrary\Rules\ValidImageFileRule;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Group::make()
                    ->columnSpan(['lg' => 8])
                    ->schema([
                        Section::make('ملف الوسيط والمعاينة')
                            ->schema([
                                FileUpload::make('attachment')
                                    ->label('اختر ملف الصورة أو اسحبه هنا')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(10240)
                                    ->required()
                                    ->rules([new ValidImageFileRule()])
                                    ->helperText('الملفات المدعومة: JPEG, PNG, WebP بحد أقصى 10 ميغابايت. تخضع جميع الصور للتحقق الأمني وإعادة الترميز عبر GD.')
                                    ->visibleOn('create'),

                                Toggle::make('force_duplicate')
                                    ->label('السماح برفع نسخة مكررة إذا تم اكتشاف تطابق')
                                    ->helperText('الوضع الافتراضي يمنع تكرار الصور المتطابقة للحفاظ على مساحة التخزين.')
                                    ->default(false)
                                    ->visibleOn('create'),

                                Placeholder::make('current_preview')
                                    ->label('معاينة الوسيط الحالية')
                                    ->content(fn (?Media $record) => $record ? new HtmlString('<div class="flex items-center justify-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"><img src="' . e($record->url) . '" alt="' . e($record->alt_text ?? $record->title) . '" class="max-h-80 max-w-full rounded shadow object-contain" /></div>') : null)
                                    ->visibleOn(['edit', 'view']),
                            ]),

                        Section::make('البيانات الأساسية')
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان الوسيط')
                                    ->maxLength(255)
                                    ->placeholder('عنوان يصف الصورة...')
                                    ->helperText('في حال تركه فارغاً عند الرفع، سيتم اعتماد اسم الملف تلقائياً.'),

                                TextInput::make('alt_text')
                                    ->label('النص البديل (Alt Text)')
                                    ->maxLength(255)
                                    ->placeholder('وصف محتوى الصورة لمحركات البحث وقارئات الشاشة...')
                                    ->helperText('مهم جداً لتحسين محركات البحث وإمكانية الوصول.'),

                                Textarea::make('caption')
                                    ->label('الوصف التوضيحي (Caption)')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->placeholder('شرح أو تفاصيل إضافية حول الصورة...'),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(['lg' => 4])
                    ->schema([
                        Section::make('التصنيف والتنظيم')
                            ->schema([
                                Select::make('category_id')
                                    ->label('تصنيف الوسائط')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->placeholder('بدون تصنيف'),
                            ]),

                        Section::make('البيانات التقنية للملف')
                            ->visibleOn(['edit', 'view'])
                            ->schema([
                                Placeholder::make('file_name')
                                    ->label('اسم الملف')
                                    ->content(fn (?Media $record) => $record?->file_name),

                                Placeholder::make('mime_type')
                                    ->label('نوع الملف (MIME)')
                                    ->content(fn (?Media $record) => $record?->mime_type),

                                Placeholder::make('file_size_formatted')
                                    ->label('حجم الملف')
                                    ->content(fn (?Media $record) => $record?->file_size ? number_format($record->file_size / 1024, 1) . ' KB' : '-'),

                                Placeholder::make('dimensions')
                                    ->label('الأبعاد بالبكسل')
                                    ->content(fn (?Media $record) => $record?->width && $record?->height ? "{$record->width} × {$record->height} px" : '-'),

                                Placeholder::make('sha256_hash')
                                    ->label('بصمة الملف (SHA-256)')
                                    ->content(fn (?Media $record) => $record?->sha256_hash ?: 'غير مفهرسة'),

                                Placeholder::make('file_path')
                                    ->label('المسار النسبي للتخزين')
                                    ->content(fn (?Media $record) => $record?->file_path),

                                Placeholder::make('created_at_formatted')
                                    ->label('تاريخ الرفع')
                                    ->content(fn (?Media $record) => $record?->created_at?->format('Y-m-d H:i')),
                            ]),
                    ]),
            ]);
    }
}
