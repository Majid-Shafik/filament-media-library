<x-filament-panels::page>
    {{-- Scoped CSS for authentic Filament Media Library Pro V4 design --}}
    <style>
        .fml-root {
            --fml-primary: #6366f1;
            --fml-primary-hover: #4f46e5;
            --fml-primary-light: #eef2ff;
            --fml-border: #e2e8f0;
            --fml-bg-card: #ffffff;
            --fml-text-main: #0f172a;
            --fml-text-muted: #64748b;
            font-family: inherit;
        }
        .dark .fml-root {
            --fml-border: #334155;
            --fml-bg-card: #1e293b;
            --fml-text-main: #f8fafc;
            --fml-text-muted: #94a3b8;
            --fml-primary-light: rgba(99, 102, 241, 0.15);
        }

        /* Top Action Bar / Breadcrumb Bar */
        .fml-folder-bar {
            background: var(--fml-bg-card);
            border: 1px solid var(--fml-border);
            border-radius: 14px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            margin-bottom: 14px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .fml-breadcrumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--fml-text-main);
            overflow-x: auto;
        }
        .fml-crumb-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--fml-text-muted);
            text-decoration: none;
            cursor: pointer;
            transition: color 0.15s ease;
            white-space: nowrap;
        }
        .fml-crumb-item:hover {
            color: var(--fml-primary);
        }
        .fml-crumb-item.active {
            color: var(--fml-text-main);
            font-weight: 700;
        }

        /* Toolbar: Search, Filter, Sort */
        .fml-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .fml-search-box {
            position: relative;
            flex: 1;
            min-width: 240px;
        }
        .fml-search-input {
            width: 100%;
            background: var(--fml-bg-card);
            border: 1px solid var(--fml-border);
            border-radius: 12px;
            padding: 9px 38px 9px 14px;
            font-size: 13px;
            color: var(--fml-text-main);
            outline: none;
            transition: all 0.2s ease;
        }
        .fml-search-input:focus {
            border-color: var(--fml-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .fml-search-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 12px;
            color: var(--fml-text-muted);
            pointer-events: none;
        }
        .fml-btn-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: var(--fml-bg-card);
            border: 1px solid var(--fml-border);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: var(--fml-text-main);
            cursor: pointer;
            transition: all 0.15s ease;
            outline: none;
        }
        .fml-btn-pill:hover {
            border-color: var(--fml-primary);
            color: var(--fml-primary);
        }
        .fml-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--fml-primary);
            color: #ffffff !important;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.25);
            transition: all 0.15s ease;
        }
        .fml-btn-primary:hover {
            background: var(--fml-primary-hover);
            transform: translateY(-1px);
        }

        /* Results Counter Header */
        .fml-results-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--fml-text-muted);
            padding: 4px 6px 12px 6px;
            border-bottom: 1px solid var(--fml-border);
            margin-bottom: 16px;
        }
        .fml-select-all-btn {
            background: none;
            border: none;
            color: var(--fml-primary);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-transform: none;
            letter-spacing: normal;
        }
        .fml-select-all-btn:hover {
            text-decoration: underline;
        }

        /* Two-Column Explorer Layout */
        .fml-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            align-items: start;
        }
        @media (min-width: 1024px) {
            .fml-layout.has-inspector {
                grid-template-columns: 1fr 360px;
            }
        }

        /* Cards Grid */
        .fml-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
        }
        @media (min-width: 640px) {
            .fml-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 16px;
            }
        }

        /* Folder Card */
        .fml-folder-card {
            background: var(--fml-bg-card);
            border: 1px solid var(--fml-border);
            border-radius: 14px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 140px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .fml-folder-card:hover {
            border-color: var(--fml-primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08);
            transform: translateY(-2px);
        }
        .fml-folder-icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: var(--fml-primary-light);
            color: var(--fml-primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .fml-folder-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--fml-text-main);
            margin-top: 10px;
            word-break: break-word;
            line-height: 1.3;
        }
        .fml-folder-meta {
            font-size: 11px;
            color: var(--fml-text-muted);
            margin-top: 4px;
        }

        /* Media Thumbnail Card */
        .fml-media-card {
            position: relative;
            background: var(--fml-bg-card);
            border: 2px solid var(--fml-border);
            border-radius: 14px;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .fml-media-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .fml-media-card.is-active {
            border-color: var(--fml-primary) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25), 0 8px 20px rgba(99, 102, 241, 0.15);
        }
        .fml-media-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            background: #090d16;
            transition: transform 0.3s ease;
        }
        .fml-media-card:hover .fml-media-img {
            transform: scale(1.03);
        }
        .fml-media-overlay {
            position: absolute;
            inset: auto 0 0 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.5) 60%, transparent 100%);
            padding: 24px 10px 8px 10px;
            color: #ffffff;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .fml-media-title {
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 1px 2px rgba(0,0,0,0.6);
        }
        .fml-media-sub {
            font-size: 10px;
            color: #cbd5e1;
            margin-top: 2px;
        }

        /* Checkbox in card corner */
        .fml-card-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
            width: 18px;
            height: 18px;
            border-radius: 5px;
            background: rgba(255,255,255,0.85);
            border: 1px solid #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .fml-card-checkbox.checked {
            background: var(--fml-primary);
            border-color: var(--fml-primary);
            color: #ffffff;
        }

        /* Heart Favorite Button on Card */
        .fml-card-fav {
            width: 20px;
            height: 20px;
            min-width: 20px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(239, 68, 68, 0.35);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 0;
            outline: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .fml-card-fav:not(.is-fav) {
            opacity: 0;
            transform: scale(0.85);
            pointer-events: none;
        }
        .fml-media-card:hover .fml-card-fav:not(.is-fav) {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }
        .fml-card-fav:not(.is-fav):hover {
            background: #ffffff;
            border-color: #ef4444;
            transform: scale(1.15);
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.25);
        }
        .fml-card-fav.is-fav {
            opacity: 1 !important;
            background: #ffffff !important;
            border-color: #fca5a5 !important;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35) !important;
            transform: scale(1) !important;
            pointer-events: auto !important;
        }
        .fml-card-fav.is-fav:hover {
            background: #fef2f2 !important;
            transform: scale(1.18) !important;
        }

        /* Inspector Side Panel */
        .fml-inspector {
            background: var(--fml-bg-card);
            border: 1px solid var(--fml-border);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            position: sticky;
            top: 20px;
        }
        .fml-inspector-preview-box {
            background: #090d16;
            border: 1px solid #1e293b;
            border-radius: 12px;
            height: 240px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 14px;
            position: relative;
        }
        .fml-inspector-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .fml-inspector-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--fml-text-main);
            word-break: break-word;
            margin-bottom: 2px;
        }
        .fml-inspector-subtitle {
            font-size: 12px;
            color: var(--fml-text-muted);
            margin-bottom: 16px;
        }

        /* Accordion section */
        .fml-accordion {
            border-top: 1px solid var(--fml-border);
            padding-top: 12px;
            margin-top: 12px;
        }
        .fml-accordion-header {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--fml-text-muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .fml-info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            padding: 5px 0;
            border-bottom: 1px dashed rgba(148, 163, 184, 0.2);
        }
        .fml-info-label {
            color: var(--fml-text-muted);
        }
        .fml-info-value {
            color: var(--fml-text-main);
            font-weight: 600;
            text-align: left;
            direction: ltr;
        }
        .fml-info-value-rtl {
            color: var(--fml-text-main);
            font-weight: 600;
            text-align: right;
            direction: rtl;
        }

        /* Badge Pills */
        .fml-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .fml-badge-success {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .fml-badge-warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        /* Action Buttons Row */
        .fml-action-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 16px;
        }
        .fml-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            background: var(--fml-bg-card);
            border: 1px solid var(--fml-border);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--fml-text-main);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fml-btn-secondary:hover {
            border-color: var(--fml-primary);
            color: var(--fml-primary);
        }
        .fml-btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #e11d48;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fml-btn-danger:hover {
            background: #ffe4e6;
            border-color: #fda4af;
        }
    </style>

    <div class="fml-root" dir="rtl">
        {{-- 1. Folder Breadcrumb Bar --}}
        <div class="fml-folder-bar">
            <div class="fml-breadcrumbs">
                @php
                    $breadcrumbs = $this->getBreadcrumbsList();
                @endphp
                @foreach($breadcrumbs as $index => $crumb)
                    @if($index > 0)
                        <span style="color: var(--fml-text-muted); font-size: 12px;">/</span>
                    @endif
                    <div 
                        class="fml-crumb-item {{ $crumb['id'] === $currentFolderId ? 'active' : '' }}"
                        wire:click="openFolder({{ $crumb['id'] ? $crumb['id'] : 'null' }})"
                    >
                        @if($index === 0)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        @endif
                        <span>{{ $crumb['name'] }}</span>
                    </div>
                @endforeach
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                @if(auth()->user()?->can('create', \App\Models\Media::class))
                    <button 
                        type="button" 
                        class="fml-btn-primary" 
                        wire:click="mountAction('uploadAction')"
                    >
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span>رفع وسائط</span>
                    </button>
                @endif
                @if(auth()->user()?->can('create', \App\Models\MediaCategory::class))
                    <button 
                        type="button" 
                        class="fml-btn-pill" 
                        wire:click="mountAction('createFolderAction')"
                    >
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        </svg>
                        <span>إنشاء مجلد</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Search & Filter Toolbar --}}
        <div class="fml-toolbar">
            {{-- Search Box --}}
            <div class="fml-search-box">
                <input 
                    type="text" 
                    class="fml-search-input" 
                    placeholder="بحث في الملفات أو المجلدات بالاسم..." 
                    wire:model.live.debounce.300ms="search"
                />
                <div class="fml-search-icon">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            {{-- Filter Selector --}}
            <select 
                class="fml-btn-pill" 
                wire:model.live="filterMime"
                style="appearance: auto; padding-left: 24px;"
            >
                <option value="">كافة الأنواع (MIME)</option>
                <option value="image/jpeg">JPEG / JPG</option>
                <option value="image/png">PNG</option>
                <option value="image/webp">WebP الحديث</option>
            </select>

            {{-- Usage Filter --}}
            <select 
                class="fml-btn-pill" 
                wire:model.live="filterUsage"
                style="appearance: auto; padding-left: 24px;"
            >
                <option value="">كافة حالات الاستخدام</option>
                <option value="used">مستخدم في صفحات الموقع</option>
                <option value="unused">غير مستخدم (آمن للحذف)</option>
            </select>

            {{-- Favorites Filter Toggle Button --}}
            <button 
                type="button" 
                class="fml-btn-pill {{ $onlyFavorites ? 'active' : '' }}" 
                wire:click="$toggle('onlyFavorites')"
                style="{{ $onlyFavorites ? 'background: #fef2f2; border-color: #f87171; color: #dc2626; font-weight: 700;' : '' }}"
                title="تصفية وعرض صوري المفضلة فقط"
            >
                <svg style="width: 14px; height: 14px; {{ $onlyFavorites ? 'color: #ef4444; fill: #ef4444;' : '' }}" fill="{{ $onlyFavorites ? '#ef4444' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <span>صوري المفضلة</span>
            </button>

            {{-- Sort Selector --}}
            <select 
                class="fml-btn-pill" 
                wire:model.live="sortBy"
                style="appearance: auto; padding-left: 24px;"
            >
                <option value="latest">الأحدث رفعاً</option>
                <option value="oldest">الأقدم رفعاً</option>
                <option value="name_asc">الاسم (أ - ي)</option>
                <option value="name_desc">الاسم (ي - أ)</option>
                <option value="size_desc">الأكبر حجماً</option>
                <option value="size_asc">الأصغر حجماً</option>
            </select>

            @if(!empty($search) || $filterMime || $filterUsage || $onlyFavorites || $sortBy !== 'latest')
                <button 
                    type="button" 
                    class="fml-btn-pill" 
                    wire:click="resetFilters" 
                    style="color: #e11d48; border-color: #fecdd3;"
                >
                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>إلغاء التصفية</span>
                </button>
            @endif
        </div>

        {{-- 3. Results Counter Bar --}}
        @php
            $folders = $this->getFolders();
            $mediaItems = $this->getMediaItems();
            $totalCount = count($folders) + count($mediaItems);
            $selectedCount = count($selectedIds);
            $activeData = $this->getActiveMediaData();
            $userFavIds = $this->getUserFavoriteIds();
        @endphp

        <div class="fml-results-bar">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span>النتائج · {{ $totalCount }}</span>
                @if($selectedCount > 0)
                    <span style="color: var(--fml-primary);">({{ $selectedCount }} محدد)</span>
                    @can('deleteAny', App\Models\Media::class)
                        <button 
                            type="button" 
                            class="fml-select-all-btn" 
                            style="color: #e11d48; font-weight: 700; margin-right: 8px;"
                            wire:click="mountAction('bulkDeleteAction')"
                        >
                            حذف المحدد آمنياً
                        </button>
                    @endcan
                @endif
            </div>

            <div>
                @if($selectedCount === count($mediaItems) && count($mediaItems) > 0)
                    <button type="button" class="fml-select-all-btn" wire:click="deselectAll">
                        إلغاء تحديد الكل
                    </button>
                @else
                    <button type="button" class="fml-select-all-btn" wire:click="selectAll">
                        تحديد الكل ({{ count($mediaItems) }})
                    </button>
                @endif
            </div>
        </div>

        {{-- 4. Main Two-Column Explorer Layout --}}
        <div class="fml-layout {{ $activeData ? 'has-inspector' : '' }}">
            {{-- Left: Grid (Folders + Media) --}}
            <div>
                @if($totalCount === 0)
                    <div style="text-align: center; padding: 60px 20px; background: var(--fml-bg-card); border: 1px dashed var(--fml-border); border-radius: 16px;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--fml-primary-light); color: var(--fml-primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                            <svg style="width: 28px; height: 28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 15px; font-weight: 700; color: var(--fml-text-main); margin-bottom: 4px;">المجلد فارغ حالياً</h4>
                        <p style="font-size: 13px; color: var(--fml-text-muted); margin-bottom: 16px;">لا توجد أي وسائط أو مجلدات فرعية مطابقة في هذا المسار.</p>
                        @if(auth()->user()?->can('create', \App\Models\Media::class))
                            <button type="button" class="fml-btn-primary" wire:click="mountAction('uploadAction')">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <span>رفع وسيط جديد هنا</span>
                            </button>
                        @endif
                    </div>
                @else
                    <div class="fml-grid">
                        {{-- A. Folder Cards --}}
                        @foreach($folders as $folder)
                            <div 
                                class="fml-folder-card"
                                wire:click="openFolder({{ $folder->id }})"
                                title="انقر لفتح مجلد {{ $folder->name }}"
                            >
                                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                    <div class="fml-folder-icon-wrapper">
                                        <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                    </div>
                                </div>

                                <div>
                                    <div class="fml-folder-name">{{ $folder->name }}</div>
                                    <div class="fml-folder-meta">
                                        {{ $folder->media_count }} ملفات
                                        @if($folder->children_count > 0)
                                            · {{ $folder->children_count }} مجلدات
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- B. Media Cards --}}
                        @foreach($mediaItems as $item)
                            @php
                                $isChecked = in_array($item->id, $selectedIds, true);
                                $isActive = ($activeMediaId === $item->id);
                                $isFav = in_array($item->id, $userFavIds, true);
                            @endphp
                            <div 
                                class="fml-media-card {{ $isActive ? 'is-active' : '' }}"
                                wire:click="selectMedia({{ $item->id }})"
                                x-data="{ isFav: {{ $isFav ? 'true' : 'false' }} }"
                            >
                                {{-- Top Left Actions (Checkbox + Heart Favorite Button) --}}
                                <div style="position: absolute; top: 10px; left: 10px; z-index: 10; display: flex; align-items: center; gap: 6px;">
                                    {{-- Selection Checkbox --}}
                                    <div 
                                        class="fml-card-checkbox {{ $isChecked ? 'checked' : '' }}"
                                        wire:click.stop="toggleCheck({{ $item->id }})"
                                        title="تحديد العنصر"
                                        style="position: static;"
                                    >
                                        @if($isChecked)
                                            <svg style="width: 12px; height: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @endif
                                    </div>

                                    {{-- Favorite Heart Button --}}
                                    <button 
                                        type="button"
                                        class="fml-card-fav"
                                        :class="isFav ? 'is-fav' : ''"
                                        @click.stop="isFav = !isFav; $wire.toggleFavorite({{ $item->id }})"
                                        :title="isFav ? 'إزالة من صوري المفضلة' : 'إضافة إلى صوري المفضلة'"
                                    >
                                        <template x-if="isFav">
                                            <!-- Solid Red Heart -->
                                            <svg style="width: 13px; height: 13px; display: block;" viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="1.5">
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                            </svg>
                                        </template>
                                        <template x-if="!isFav">
                                            <!-- Hollow Red Heart -->
                                            <svg style="width: 13px; height: 13px; display: block;" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                            </svg>
                                        </template>
                                    </button>
                                </div>

                                {{-- Image preview --}}
                                <img 
                                    src="{{ $item->url }}" 
                                    alt="{{ $item->alt_text ?? $item->title }}" 
                                    loading="lazy" 
                                    class="fml-media-img"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2364748b\'%3E%3Cpath d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/%3E%3C/svg%3E';"
                                />

                                {{-- Gradient Title Overlay --}}
                                <div class="fml-media-overlay">
                                    <div class="fml-media-title">{{ $item->title ?: $item->file_name }}</div>
                                    <div class="fml-media-sub">
                                        {{ strtoupper(pathinfo($item->file_name, PATHINFO_EXTENSION)) }} · {{ number_format($item->file_size / 1024, 0) }} KB
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Right: Inspector Sidebar --}}
            @if($activeData)
                <div class="fml-inspector">
                    {{-- Close Button --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--fml-text-main);">معاينة وتفاصيل الملف</span>
                        <button 
                            type="button" 
                            wire:click="closeInspector" 
                            style="background: none; border: none; cursor: pointer; color: var(--fml-text-muted);"
                            title="إغلاق اللوحة"
                        >
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Large Preview Box --}}
                    <div class="fml-inspector-preview-box">
                        <img 
                            src="{{ $activeData['url'] }}" 
                            alt="{{ $activeData['title'] }}" 
                            class="fml-inspector-img"
                        />
                        <a 
                            href="{{ $activeData['url'] }}" 
                            target="_blank" 
                            style="position: absolute; bottom: 8px; left: 8px; background: rgba(15,23,42,0.7); color: #fff; padding: 4px 8px; border-radius: 6px; font-size: 11px; text-decoration: none;"
                            title="فتح بحجمه الكامل"
                        >
                            فتح الرابط الكامل ↗
                        </a>
                    </div>

                    {{-- Header Meta --}}
                    <div class="fml-inspector-title">{{ $activeData['title'] ?: $activeData['file_name'] }}</div>
                    <div class="fml-inspector-subtitle">
                        {{ strtoupper(pathinfo($activeData['file_name'], PATHINFO_EXTENSION)) }} — {{ $activeData['size_kb'] }} KB
                        @if($activeData['width'] && $activeData['height'])
                            · {{ $activeData['width'] }} × {{ $activeData['height'] }} px
                        @endif
                    </div>

                    {{-- INFORMATION Accordion --}}
                    <div class="fml-accordion">
                        <div class="fml-accordion-header">
                            <span>معلومات الملف (Information)</span>
                        </div>
                        <div class="fml-info-row">
                            <span class="fml-info-label">اسم الملف</span>
                            <span class="fml-info-value" title="{{ $activeData['file_name'] }}">
                                {{ \Illuminate\Support\Str::limit($activeData['file_name'], 22) }}
                            </span>
                        </div>
                        <div class="fml-info-row">
                            <span class="fml-info-label">المجلد الحالي</span>
                            <span class="fml-info-value-rtl">{{ $activeData['category_name'] }}</span>
                        </div>
                        <div class="fml-info-row">
                            <span class="fml-info-label">تم الرفع بواسطة</span>
                            <span class="fml-info-value-rtl">{{ $activeData['created_by'] }}</span>
                        </div>
                        <div class="fml-info-row">
                            <span class="fml-info-label">تاريخ الرفع</span>
                            <span class="fml-info-value">{{ $activeData['created_at'] }}</span>
                        </div>
                        <div class="fml-info-row">
                            <span class="fml-info-label">نوع الوسيط (MIME)</span>
                            <span class="fml-info-value">{{ $activeData['mime_type'] }}</span>
                        </div>
                    </div>

                    {{-- USAGES Accordion --}}
                    <div class="fml-accordion">
                        <div class="fml-accordion-header">
                            <span>الاستخدامات في الموقع (Usages)</span>
                            @if($activeData['is_in_use'])
                                <span class="fml-badge fml-badge-warning">مستخدم ({{ $activeData['usages_count'] }})</span>
                            @else
                                <span class="fml-badge fml-badge-success">آمن للحذف</span>
                            @endif
                        </div>

                        @if($activeData['is_in_use'])
                            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; font-size: 11px; color: #92400e;">
                                هذا الوسيط مرتبط بمحتويات منشورة في الموقع، لذلك يتم منع حذفه بالخطأ لحماية الموقع من الروابط المعطلة.
                            </div>
                            <div style="max-height: 120px; overflow-y: auto;">
                                @foreach($activeData['usages_list'] as $usage)
                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; padding: 4px 0; border-bottom: 1px solid var(--fml-border);">
                                        <span style="font-weight: 600; color: var(--fml-text-main);">{{ $usage['title'] }}</span>
                                        <span style="color: var(--fml-text-muted);">{{ $usage['type'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 8px 10px; font-size: 11px; color: #065f46;">
                                لا يوجد أي محتوى أو صفحة مرتبطة بهذه الصورة حالياً. يمكن حذفها بأمان تام.
                            </div>
                        @endif
                    </div>

                    {{-- Quick Action Buttons --}}
                    @php
                        $canUpdateMedia = auth()->user()?->can('update', App\Models\Media::class) ?? false;
                        $canDeleteMedia = auth()->user()?->can('delete', App\Models\Media::class) ?? false;
                    @endphp
                    <div class="fml-action-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 16px;">
                        <button 
                            type="button" 
                            class="fml-btn-secondary" 
                            style="{{ !$canUpdateMedia ? 'grid-column: span 2;' : '' }}"
                            x-data 
                            @click="navigator.clipboard.writeText('{{ $activeData['url'] }}'); $tooltip('تم نسخ الرابط إلى الحافظة');"
                        >
                            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                            <span>نسخ الرابط</span>
                        </button>

                        @if($canUpdateMedia)
                            <button 
                                type="button" 
                                class="fml-btn-secondary" 
                                wire:click="mountAction('editMediaAction', { mediaId: {{ $activeData['id'] }} })"
                            >
                                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>تعديل</span>
                            </button>
                        @endif

                        @if($canDeleteMedia)
                            @if($activeData['is_in_use'])
                                <button 
                                    type="button" 
                                    class="fml-btn-secondary" 
                                    style="grid-column: span 2; opacity: 0.6; cursor: not-allowed;" 
                                    title="لا يمكن حذف صورة مستخدمة في الموقع"
                                    disabled
                                >
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span>محمي من الحذف</span>
                                </button>
                            @else
                                <button 
                                    type="button" 
                                    class="fml-btn-danger" 
                                    style="grid-column: span 2;"
                                    wire:click="deleteMedia({{ $activeData['id'] }})"
                                    wire:confirm="هل أنت متأكد من رغبتك في حذف هذا الوسيط نهائياً؟"
                                >
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>حذف آمن</span>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
