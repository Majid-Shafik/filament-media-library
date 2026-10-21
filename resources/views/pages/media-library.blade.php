<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Top Filter & Control Toolbar --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                {{-- Search Box --}}
                <div class="relative w-full sm:w-64">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="بحث بالاسم أو العنوان..." 
                        class="w-full pl-3 pr-9 py-2 text-xs bg-gray-50 dark:bg-gray-800/60 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-gray-100 placeholder-gray-400"
                    />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-4 h-4" />
                    </div>
                    @if(!empty($search))
                        <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-gray-400 hover:text-gray-600">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-3.5 h-3.5" />
                        </button>
                    @endif
                </div>

                {{-- Category Filter --}}
                @php
                    $categories = \Illuminate\Support\Facades\Schema::hasTable('media_categories') ? \App\Models\MediaCategory::all() : collect();
                @endphp
                <select 
                    wire:model.live="selectedCategoryId" 
                    class="text-xs py-2 pr-7 pl-3 bg-gray-50 dark:bg-gray-800/60 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 focus:ring-primary-500"
                >
                    <option value="">كافة التصنيفات</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                {{-- MIME Type Filter --}}
                <select 
                    wire:model.live="selectedMime" 
                    class="text-xs py-2 pr-7 pl-3 bg-gray-50 dark:bg-gray-800/60 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 focus:ring-primary-500"
                >
                    <option value="">كافة الأنواع</option>
                    <option value="image/jpeg">JPEG / JPG</option>
                    <option value="image/png">PNG</option>
                    <option value="image/webp">WebP</option>
                </select>

                {{-- Usage Filter --}}
                <select 
                    wire:model.live="selectedUsage" 
                    class="text-xs py-2 pr-7 pl-3 bg-gray-50 dark:bg-gray-800/60 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 focus:ring-primary-500"
                >
                    <option value="">كافة الحالات</option>
                    <option value="used">مستخدم في محتوى</option>
                    <option value="unused">غير مستخدم (آمن للحذف)</option>
                </select>

                {{-- Sort By --}}
                <select 
                    wire:model.live="sortBy" 
                    class="text-xs py-2 pr-7 pl-3 bg-gray-50 dark:bg-gray-800/60 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 focus:ring-primary-500"
                >
                    <option value="latest">الأحدث رفعاً</option>
                    <option value="oldest">الأقدم رفعاً</option>
                    <option value="name_asc">الاسم (أ - ي)</option>
                    <option value="name_desc">الاسم (ي - أ)</option>
                    <option value="size_desc">الأكبر حجماً</option>
                    <option value="size_asc">الأصغر حجماً</option>
                </select>

                {{-- Reset Filters --}}
                @if(!empty($search) || $selectedCategoryId || $selectedMime || $selectedUsage || $sortBy !== 'latest')
                    <button 
                        type="button" 
                        wire:click="resetFilters" 
                        class="text-xs text-rose-600 hover:text-rose-700 font-medium px-2 py-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/20 transition"
                    >
                        إعادة ضبط الفلاتر
                    </button>
                @endif
            </div>

            {{-- Right View Switcher & Upload Button --}}
            <div class="flex items-center gap-2 self-end lg:self-auto">
                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 bg-gray-50 dark:bg-gray-800/80">
                    <button 
                        type="button" 
                        wire:click="setViewMode('grid')" 
                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-md transition {{ $viewMode === 'grid' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}"
                        title="عرض شبكة بطاقات"
                    >
                        <x-filament::icon icon="heroicon-o-squares-2x2" class="w-4 h-4" />
                        شبكة
                    </button>
                    <button 
                        type="button" 
                        wire:click="setViewMode('table')" 
                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-md transition {{ $viewMode === 'table' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}"
                        title="عرض جدول تقليدي"
                    >
                        <x-filament::icon icon="heroicon-o-table-cells" class="w-4 h-4" />
                        جدول
                    </button>
                </div>

                <x-filament::button 
                    size="sm" 
                    icon="heroicon-o-arrow-up-tray" 
                    wire:click="mountAction('uploadMediaModal')"
                >
                    رفع وسيط جديد
                </x-filament::button>
            </div>
        </div>

        {{-- Bulk Selection Bar --}}
        @if(!empty($selectedIds))
            <div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800/60 rounded-xl text-xs">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-check-circle" class="w-4 h-4 text-primary-600" />
                    <span class="font-medium text-primary-900 dark:text-primary-200">
                        تم تحديد {{ count($selectedIds) }} وسائط
                    </span>
                    <button 
                        type="button" 
                        wire:click="deselectAll" 
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 underline mr-2"
                    >
                        إلغاء التحديد
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::button 
                        size="xs" 
                        color="danger" 
                        icon="heroicon-o-trash" 
                        wire:click="mountAction('bulkDeleteAction')"
                    >
                        حذف المحدد آمنياً
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- Main Content: Table or Interactive Grid --}}
        @if($viewMode === 'table')
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                {{ $this->table }}
            </div>
        @else
            @php
                $mediaItems = $this->gridMedia;
                $activePreview = $this->getActivePreviewData();
            @endphp

            <div class="grid grid-cols-12 gap-4 items-start">
                {{-- Grid Cards Column --}}
                <div class="{{ $activePreview ? 'col-span-12 lg:col-span-8 xl:col-span-9' : 'col-span-12' }} space-y-4">
                    @if($mediaItems->isEmpty())
                        <div class="p-12 text-center bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-50 dark:bg-primary-950/40 text-primary-500 mb-4">
                                <x-filament::icon icon="heroicon-o-photo" class="w-8 h-8" />
                            </div>
                            @if(!empty($search) || $selectedCategoryId || $selectedMime || $selectedUsage)
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">لا توجد وسائط مطابقة للبحث والفلاتر</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">حاول تعديل شروط البحث أو اختيار تصنيف آخر.</p>
                                <div class="mt-4">
                                    <x-filament::button size="sm" color="gray" wire:click="resetFilters">
                                        مسح الفلاتر
                                    </x-filament::button>
                                </div>
                            @else
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">مكتبة الوسائط فارغة حتى الآن</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">ابدأ برفع أول صورة إلى المكتبة المركزية لتتمكن من استخدامها في صفحات ومحتويات الموقع.</p>
                                <div class="mt-4">
                                    <x-filament::button size="sm" icon="heroicon-o-arrow-up-tray" wire:click="mountAction('uploadMediaModal')">
                                        رفع أول وسيط
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- Cards Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 {{ $activePreview ? 'xl:grid-cols-4' : 'lg:grid-cols-5 xl:grid-cols-6' }} gap-3">
                            @foreach($mediaItems as $item)
                                @php
                                    $isSelected = in_array($item->id, $selectedIds, true);
                                    $isActive = $activePreviewId === $item->id;
                                    $hasUsages = $item->usages_count > 0;
                                @endphp
                                <div 
                                    wire:key="media-card-{{ $item->id }}" 
                                    wire:click="openPreview({{ $item->id }})" 
                                    class="group relative bg-white dark:bg-gray-900 rounded-xl border {{ $isActive ? 'ring-2 ring-primary-500 border-primary-500' : ($isSelected ? 'ring-2 ring-primary-400 border-primary-300' : 'border-gray-200 dark:border-gray-800 hover:border-primary-400 dark:hover:border-primary-600') }} overflow-hidden shadow-sm hover:shadow transition duration-150 cursor-pointer flex flex-col"
                                >
                                    {{-- Image Thumbnail Container --}}
                                    <div class="relative w-full aspect-square bg-gray-100 dark:bg-gray-800/80 overflow-hidden flex items-center justify-center">
                                        <img 
                                            src="{{ $item->url }}" 
                                            alt="{{ $item->alt_text ?? $item->title }}" 
                                            loading="lazy" 
                                            class="w-full h-full object-cover transition duration-200 group-hover:scale-105"
                                        />

                                        {{-- Selection Checkbox (Top-Right in RTL) --}}
                                        <div class="absolute top-2 right-2 z-10" wire:click.stop="toggleSelect({{ $item->id }})">
                                            <input 
                                                type="checkbox" 
                                                {{ $isSelected ? 'checked' : '' }} 
                                                class="w-4 h-4 rounded text-primary-600 border-gray-300 bg-white/90 dark:bg-gray-900/90 shadow focus:ring-primary-500 cursor-pointer"
                                            />
                                        </div>

                                        {{-- Usages Status Badge (Top-Left in RTL) --}}
                                        <div class="absolute top-2 left-2 z-10 pointer-events-none">
                                            @if($hasUsages)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-600/90 text-white shadow backdrop-blur-sm" title="مستخدم في محتويات نشطة">
                                                    مستخدم ({{ $item->usages_count }})
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-700/80 text-gray-200 shadow backdrop-blur-sm" title="غير مستخدم (آمن للحذف)">
                                                    حر
                                                </span>
                                            @endif
                                        </div>

                                        {{-- MIME Type Label --}}
                                        <div class="absolute bottom-2 left-2 z-10 pointer-events-none">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-mono uppercase bg-black/60 text-white backdrop-blur-sm">
                                                {{ str_replace('image/', '', $item->mime_type) }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Card Caption --}}
                                    <div class="p-2.5 flex-1 flex flex-col justify-between text-right">
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate" title="{{ $item->title ?: $item->file_name }}">
                                                {{ $item->title ?: $item->file_name }}
                                            </h4>
                                            @if($item->category)
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                                    {{ $item->category->name }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between text-[10px] text-gray-400 mt-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                                            <span>{{ number_format($item->file_size / 1024, 0) }} KB</span>
                                            <span>{{ $item->width && $item->height ? "{$item->width}×{$item->height}" : '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Pagination Links --}}
                        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
                            {{ $mediaItems->links() }}
                        </div>
                    @endif
                </div>

                {{-- Side Details Pane / Slide-over --}}
                @if($activePreview)
                    <div class="col-span-12 lg:col-span-4 xl:col-span-3 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4 sticky top-4 space-y-4">
                        {{-- Pane Header --}}
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-o-information-circle" class="w-4 h-4 text-primary-500" />
                                تفاصيل ومراجع الوسيط
                            </h3>
                            <button 
                                type="button" 
                                wire:click="closePreview" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg"
                                title="إغلاق اللوحة"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                            </button>
                        </div>

                        {{-- Big Preview Image --}}
                        <div class="rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center max-h-56">
                            <a href="{{ $activePreview['url'] }}" target="_blank" class="block w-full h-full text-center">
                                <img 
                                    src="{{ $activePreview['url'] }}" 
                                    alt="{{ $activePreview['alt_text'] ?? $activePreview['title'] }}" 
                                    class="max-h-56 mx-auto object-contain"
                                />
                            </a>
                        </div>

                        {{-- Metadata Section --}}
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">العنوان:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[180px]">{{ $activePreview['title'] }}</span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">اسم الملف:</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300 truncate max-w-[180px]">{{ $activePreview['file_name'] }}</span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">التصنيف:</span>
                                <span class="font-medium text-primary-600 dark:text-primary-400">{{ $activePreview['category_name'] }}</span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">النوع والحجم:</span>
                                <span class="text-gray-700 dark:text-gray-300 font-mono">{{ $activePreview['mime_type'] }} ({{ $activePreview['size_kb'] }} KB)</span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">الأبعاد:</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $activePreview['width'] && $activePreview['height'] ? "{$activePreview['width']} × {$activePreview['height']} px" : '-' }}</span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                                <span class="text-gray-500 dark:text-gray-400">تاريخ الرفع:</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $activePreview['created_at'] }}</span>
                            </div>

                            @if(!empty($activePreview['alt_text']))
                                <div class="py-1 border-b border-gray-100 dark:border-gray-800">
                                    <span class="text-gray-500 dark:text-gray-400 block mb-0.5">النص البديل:</span>
                                    <span class="text-gray-800 dark:text-gray-200">{{ $activePreview['alt_text'] }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Active Usages Section (حصر الاستخدامات) --}}
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-1">
                                    <x-filament::icon icon="heroicon-m-link" class="w-3.5 h-3.5 text-gray-500" />
                                    المراجع والاستخدامات ({{ $activePreview['usages_count'] }})
                                </h4>
                            </div>

                            @if($activePreview['is_in_use'])
                                <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-[11px] text-amber-800 dark:text-amber-300 space-y-1">
                                    <p class="font-semibold flex items-center gap-1">
                                        <x-filament::icon icon="heroicon-m-shield-exclamation" class="w-4 h-4 text-amber-600" />
                                        الوسيط محمي من الحذف
                                    </p>
                                    <p class="text-[10px] leading-relaxed">
                                        مستخدم حالياً في محتويات أو صفحات منشورة. يجب فك ارتباطه أولاً لتتمكن من حذفه.
                                    </p>
                                </div>

                                <div class="max-h-36 overflow-y-auto space-y-1 divide-y divide-gray-100 dark:divide-gray-800 border border-gray-200 dark:border-gray-800 rounded-lg p-1">
                                    @foreach($activePreview['usages_list'] as $usage)
                                        <div class="p-1.5 text-[11px] flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded">
                                            <div class="flex items-center gap-1.5 truncate">
                                                <span class="text-primary-600 font-semibold">[{{ $usage['type'] }} #{{ $usage['id'] }}]</span>
                                                <span class="text-gray-800 dark:text-gray-200 truncate">{{ $usage['title'] }}</span>
                                            </div>
                                            <span class="text-[10px] text-gray-400 font-mono">{{ $usage['field_name'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-[11px] text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                    <x-filament::icon icon="heroicon-m-check-badge" class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>الوسيط غير مستخدم في أي محتوى، ويمكن حذفه بأمان.</span>
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2">
                            <a 
                                href="{{ \App\Filament\Resources\Media\MediaResource::getUrl('edit', ['record' => $activePreview['id']]) }}" 
                                class="flex-1 inline-flex justify-center items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <x-filament::icon icon="heroicon-o-pencil" class="w-3.5 h-3.5" />
                                تعديل البيانات
                            </a>

                            @if(!$activePreview['is_in_use'])
                                <button 
                                    type="button" 
                                    wire:click="deleteSingleMedia({{ $activePreview['id'] }})" 
                                    wire:confirm="هل أنت متأكد من رغبتك في حذف هذا الوسيط وجدولة إزالة الملف نهائياً؟" 
                                    class="inline-flex justify-center items-center gap-1 px-3 py-1.5 text-xs font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 rounded-lg transition"
                                >
                                    <x-filament::icon icon="heroicon-o-trash" class="w-3.5 h-3.5" />
                                    حذف آمن
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
