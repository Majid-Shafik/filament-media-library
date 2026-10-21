@php
    $attachment = $get('attachment');
    $duplicate = \App\Filament\Components\MediaImagePicker::getDuplicateForAttachment($attachment);
@endphp

@if($duplicate)
    <div class="p-4 rounded-xl border border-amber-300 dark:border-amber-600/50 bg-amber-50/70 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 shadow-sm transition-all duration-200">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-amber-200/60 dark:bg-amber-800/40 text-amber-700 dark:text-amber-300 shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-bold text-amber-900 dark:text-amber-100 mb-1">
                    تنبيه: تم العثور على صورة مطابقة تماماً في مكتبة الوسائط
                </h4>
                <p class="text-xs text-amber-800/90 dark:text-amber-300/90 mb-3">
                    الملف الذي اخترته يطابق بالبايتات بصمة (SHA-256) صورة موجودة مسبقاً. يُفضل استخدام الصورة الحالية لتفادي تكرار التخزين.
                </p>

                <!-- Duplicate preview card -->
                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-white/80 dark:bg-gray-900/80 border border-amber-200 dark:border-amber-700/50">
                    <div class="w-16 h-16 rounded-md overflow-hidden bg-gray-100 dark:bg-gray-800 shrink-0 border border-gray-200 dark:border-gray-700">
                        <img src="{{ $duplicate->url }}" alt="{{ $duplicate->alt_text ?: $duplicate->title }}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0 text-xs space-y-1">
                        <div class="font-bold text-gray-900 dark:text-gray-100 truncate">
                            {{ $duplicate->title }}
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span>📁 {{ $duplicate->category?->name ?? 'المكتبة الرئيسية' }}</span>
                            <span>📐 {{ $duplicate->width }} × {{ $duplicate->height }} px</span>
                            <span>⚖️ {{ number_format($duplicate->file_size / 1024, 1) }} KB</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                مستخدم ({{ $duplicate->usages()->count() }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
