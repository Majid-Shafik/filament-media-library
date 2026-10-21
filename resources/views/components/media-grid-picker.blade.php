@php
    $statePath = $getStatePath();
    $currentState = $getState();
    $totalCount = count($mediaItems ?? []);
    $defaultRows = $configuredRows ?? 2;
    $itemIdsList = $mediaItems ? $mediaItems->pluck('id')->values()->all() : [];
@endphp

<div 
    x-data="{ 
        selectedId: @entangle($statePath),
        currentPage: 1,
        configuredRows: {{ $defaultRows }},
        gridCols: parseInt(localStorage.getItem('sfd_media_picker_cols') || '5'),
        itemIds: {{ json_encode($itemIdsList) }},
        favoriteIds: {{ json_encode(array_values($userFavoriteIds ?? [])) }},
        onlyFavsLocal: false,
        csrfToken: '{{ csrf_token() }}',

        get activeItemIds() {
            if (!this.onlyFavsLocal) {
                return this.itemIds;
            }
            return this.itemIds.filter(id => this.isFavorite(id));
        },
        get visibleCount() {
            return this.activeItemIds.length;
        },
        get totalPages() {
            return Math.ceil(this.visibleCount / this.perPage) || 1;
        },
        get pageItemIds() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.activeItemIds.slice(start, start + this.perPage);
        },
        get perPage() {
            return this.configuredRows * this.gridCols;
        },
        setCols(cols) {
            this.gridCols = cols;
            localStorage.setItem('sfd_media_picker_cols', cols);
            this.currentPage = 1;
        },
        isFavorite(id) {
            return this.favoriteIds.includes(id);
        },
        toggleLocalFavs() {
            this.onlyFavsLocal = !this.onlyFavsLocal;
            this.currentPage = 1;
        },
        isVisible(id) {
            return this.pageItemIds.includes(id);
        },
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
                const container = this.$refs.gridScrollContainer;
                if (container) {
                    container.scrollTop = 0;
                }
            }
        },
        nextPage() {
            this.goToPage(this.currentPage + 1);
        },
        prevPage() {
            this.goToPage(this.currentPage - 1);
        },
        selectMedia(id) {
            this.selectedId = id;
            $wire.set('{{ $statePath }}', id);
        },
        async toggleFavorite(id, event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const idx = this.favoriteIds.indexOf(id);
            const willBeFav = idx === -1;
            if (willBeFav) {
                this.favoriteIds.push(id);
            } else {
                this.favoriteIds.splice(idx, 1);
            }

            try {
                const url = '{{ url('/admin/media') }}/' + id + '/toggle-favorite';
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) {
                    // Revert on failure
                    if (willBeFav) {
                        this.favoriteIds = this.favoriteIds.filter(x => x !== id);
                    } else {
                        this.favoriteIds.push(id);
                    }
                }
            } catch (e) {
                console.error('Error toggling favorite:', e);
            }
        }
    }"
    x-init="if (![2, 3, 4, 5].includes(gridCols)) { gridCols = 5; }"
    x-effect="if (currentPage > totalPages) { currentPage = 1; }"
    style="width: 100%; direction: rtl;"
>
    <style>
        /* Base Grid with Dynamic Column Distribution */
        .sfd-media-picker-grid {
            display: grid;
            column-gap: 16px;
            row-gap: 20px;
            padding: 6px 2px;
            box-sizing: border-box;
            transition: all 0.25s ease;
        }

        /* Mobile / Small Screens responsiveness */
        @media (max-width: 768px) {
            .sfd-media-picker-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                column-gap: 12px !important;
                row-gap: 16px !important;
            }
        }
        @media (max-width: 520px) {
            .sfd-media-picker-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                column-gap: 10px !important;
                row-gap: 14px !important;
            }
        }

        /* Card transition and subtle elevation */
        .sfd-media-card {
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            position: relative;
            text-align: right;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
            animation: sfdMediaFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .sfd-media-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -2px rgba(0,0,0,0.12);
        }

        @keyframes sfdMediaFadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Hover overlay elements: hidden by default, fade in smoothly on mouse enter */
        .sfd-card-hover-overlay {
            opacity: 0;
            transform: translateY(-4px);
            transition: opacity 0.22s ease, transform 0.22s ease;
            pointer-events: none;
        }
        .sfd-media-card:hover .sfd-card-hover-overlay {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* Segmented Density / Columns Switcher Button */
        .sfd-density-btn {
            height: 28px;
            padding: 0 8px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            cursor: pointer;
            transition: all 0.18s ease;
        }
        .sfd-density-btn:hover:not(.active) {
            background: rgba(0,0,0,0.04);
            color: #0f172a;
        }
        .sfd-density-btn.active {
            background: #ffffff;
            color: #059669;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Pagination buttons */
        .sfd-page-btn {
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .sfd-page-btn:hover:not(:disabled) {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .sfd-page-btn.active {
            background: #10b981;
            border-color: #10b981;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35);
        }
        .sfd-page-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        /* Action Buttons Row on Top-Left of Card */
        .sfd-card-actions-row {
            position: absolute;
            top: 6px;
            left: 6px;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Heart Favorite Button */
        .sfd-fav-btn {
            height: 25px;
            width: 25px;
            min-width: 25px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 0;
            outline: none;
        }
        .sfd-fav-btn:not(.is-fav) {
            background: rgba(15, 23, 42, 0.85);
            color: #ffffff;
        }
        .sfd-fav-btn:not(.is-fav):hover {
            background: rgba(15, 23, 42, 0.98);
            color: #f87171;
            transform: scale(1.12);
            border-color: rgba(248, 113, 113, 0.5);
        }
        .sfd-fav-btn.is-fav {
            background: #ffffff !important;
            color: #ef4444 !important;
            border-color: #fecaca !important;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35) !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
            pointer-events: auto !important;
        }
        .sfd-fav-btn.is-fav:hover {
            background: #fef2f2 !important;
            transform: scale(1.14) !important;
            box-shadow: 0 3px 8px rgba(239, 68, 68, 0.45) !important;
        }

        /* Quick Favorites Filter Button in Toolbar */
        .sfd-fav-filter-btn {
            height: 28px;
            padding: 0 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.18s ease;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
        }
        .sfd-fav-filter-btn:hover {
            background: #f8fafc;
            color: #ef4444;
            border-color: #fca5a5;
        }
        .sfd-fav-filter-btn.active {
            background: #fef2f2;
            border-color: #f87171;
            color: #dc2626;
            box-shadow: 0 1px 3px rgba(239, 68, 68, 0.2);
        }
    </style>

    <!-- Top Status Bar & View Density Switcher -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; font-size: 12px; color: #64748b; padding: 0 4px; flex-wrap: wrap; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span>
                <span x-text="onlyFavsLocal ? 'إجمالي الصور المفضلة: ' : 'إجمالي الصور المتاحة: '"></span>
                <strong style="color: #0f172a; font-weight: 700;" x-text="visibleCount"></strong>
            </span>

            <!-- Quick Favorites Filter Button -->
            <button 
                type="button" 
                @click="toggleLocalFavs()"
                :class="onlyFavsLocal ? 'active' : ''"
                class="sfd-fav-filter-btn"
                :title="onlyFavsLocal ? 'إلغاء تصفية المفضلة وعرض كافة الصور' : 'عرض صوري المفضلة فقط'"
            >
                <svg width="13" height="13" viewBox="0 0 24 24" :fill="onlyFavsLocal ? '#ef4444' : 'none'" :stroke="onlyFavsLocal ? '#ef4444' : 'currentColor'" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <span>صوري المفضلة</span>
                <span 
                    style="font-size: 10px; padding: 1px 6px; border-radius: 9999px; background: rgba(239, 68, 68, 0.12); color: #dc2626; font-weight: 700;" 
                    x-text="favoriteIds.length"
                ></span>
            </button>

            <!-- Column Density Switcher (5, 4, 3, 2 columns) -->
            <div 
                style="display: inline-flex; align-items: center; background: #f1f5f9; padding: 2px; border-radius: 8px; border: 1px solid #e2e8f0; gap: 2px;"
                title="تغيير كثافة العرض وعدد الصور في الصف"
            >
                <!-- 5 Columns -->
                <button 
                    type="button"
                    @click="setCols(5)"
                    :class="gridCols === 5 ? 'active' : ''"
                    class="sfd-density-btn"
                    title="5 صور في الصف (كثافة عالية / حجم مصغر)"
                >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="1" y="4" width="3" height="16" rx="1"/>
                        <rect x="5.5" y="4" width="3" height="16" rx="1"/>
                        <rect x="10" y="4" width="3" height="16" rx="1"/>
                        <rect x="14.5" y="4" width="3" height="16" rx="1"/>
                        <rect x="19" y="4" width="3" height="16" rx="1"/>
                    </svg>
                    <span>5</span>
                </button>

                <!-- 4 Columns -->
                <button 
                    type="button"
                    @click="setCols(4)"
                    :class="gridCols === 4 ? 'active' : ''"
                    class="sfd-density-btn"
                    title="4 صور في الصف"
                >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="1.5" y="4" width="4" height="16" rx="1"/>
                        <rect x="7" y="4" width="4" height="16" rx="1"/>
                        <rect x="12.5" y="4" width="4" height="16" rx="1"/>
                        <rect x="18" y="4" width="4" height="16" rx="1"/>
                    </svg>
                    <span>4</span>
                </button>

                <!-- 3 Columns -->
                <button 
                    type="button"
                    @click="setCols(3)"
                    :class="gridCols === 3 ? 'active' : ''"
                    class="sfd-density-btn"
                    title="3 صور في الصف (حجم متوسط)"
                >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="2" y="4" width="5.5" height="16" rx="1"/>
                        <rect x="9.25" y="4" width="5.5" height="16" rx="1"/>
                        <rect x="16.5" y="4" width="5.5" height="16" rx="1"/>
                    </svg>
                    <span>3</span>
                </button>

                <!-- 2 Columns -->
                <button 
                    type="button"
                    @click="setCols(2)"
                    :class="gridCols === 2 ? 'active' : ''"
                    class="sfd-density-btn"
                    title="صورتان في الصف (حجم كبير وتفاصيل واضحة)"
                >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="2" y="4" width="9" height="16" rx="1.5"/>
                        <rect x="13" y="4" width="9" height="16" rx="1.5"/>
                    </svg>
                    <span>2</span>
                </button>
            </div>

            <!-- Page items count tag -->
            <template x-if="totalPages > 1">
                <span style="font-size: 11px; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 6px;">
                    (<span x-text="perPage"></span> صور / صفحة)
                </span>
            </template>
        </div>

        <!-- Selection Confirmation Status -->
        <div x-show="selectedId" x-cloak style="color: #059669; font-weight: 600; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" style="width: 14px; height: 14px; display: inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <span>تم تحديد صورة (اضغط "اعتماد الصورة المحددة" للتأكيد)</span>
        </div>
    </div>

    @if(empty($mediaItems) || $totalCount === 0)
        <div style="padding: 44px 16px; border: 2px dashed #e2e8f0; border-radius: 12px; text-align: center; background: #f8fafc;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; color: #94a3b8;">
                <svg width="22" height="22" style="width: 22px; height: 22px; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <p style="margin: 0 0 4px; font-weight: 600; font-size: 13px; color: #334155;">لم يتم العثور على أي صورة تطابق البحث</p>
            <p style="margin: 0; font-size: 11px; color: #94a3b8;">جرّب تعديل التصنيف أو كتابة كلمات بحث أخرى (بالاسم أو العنوان أو النص البديل Alt).</p>
        </div>
    @else
        <!-- Grid Scroll Container with Dynamic Column Count -->
        <div 
            x-ref="gridScrollContainer"
            style="max-height: 500px; overflow-y: auto; padding: 4px; box-sizing: border-box;"
        >
            <div 
                class="sfd-media-picker-grid"
                :style="'grid-template-columns: repeat(' + gridCols + ', minmax(0, 1fr));'"
            >
                <!-- Empty Favorites Notice -->
                <template x-if="onlyFavsLocal && visibleCount === 0">
                    <div style="grid-column: 1 / -1; padding: 36px 16px; border: 2px dashed #fca5a5; border-radius: 12px; text-align: center; background: #fff5f5;">
                        <div style="color: #ef4444; font-size: 26px; margin-bottom: 6px;">❤️</div>
                        <p style="margin: 0 0 4px; font-weight: 700; font-size: 13px; color: #991b1b;">لا توجد صور في مفضلتك مطابقة للبحث الحالي</p>
                        <p style="margin: 0; font-size: 11px; color: #b91c1c;">انقر على أيقونة القلب في أي صورة لإضافتها إلى صوري المفضلة والوصول إليها بسرعة في أي وقت.</p>
                    </div>
                </template>

                @foreach($mediaItems as $item)
                    @php
                        $url = $item->url ?: \Illuminate\Support\Facades\Storage::disk($item->disk ?? 'public')->url($item->file_path);
                        $dim = ($item->width && $item->height) ? "{$item->width}×{$item->height}" : '';
                        $size = $item->file_size ? number_format($item->file_size / 1024, 0) . ' KB' : '';
                        $usagesCount = $item->usages_count ?? 0;
                    @endphp

                    <div 
                        x-show="isVisible({{ $item->id }})"
                        x-cloak
                        x-transition:enter="transition ease-out duration-250"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        @click="selectMedia({{ $item->id }})"
                        :style="selectedId == {{ $item->id }} 
                            ? 'border: 2px solid #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25); background: #f0fdf4;' 
                            : 'border: 1px solid #e2e8f0; background: #ffffff;'"
                        class="sfd-media-card"
                    >
                        <!-- Hidden Radio Input -->
                        <input 
                            type="radio" 
                            name="{{ $statePath }}" 
                            value="{{ $item->id }}" 
                            x-model="selectedId" 
                            style="position: absolute; opacity: 0; pointer-events: none;"
                        />

                        <!-- Thumbnail Container (Height dynamically scales based on column density) -->
                        <div 
                            :style="gridCols === 2 ? 'height: 230px;' : (gridCols === 3 ? 'height: 185px;' : (gridCols === 4 ? 'height: 155px;' : 'height: 130px;'))"
                            style="position: relative; width: 100%; overflow: hidden; background: #f1f5f9; display: flex; align-items: center; justify-content: center; transition: height 0.22s ease;"
                        >
                            <img 
                                src="{{ $url }}" 
                                alt="{{ $item->alt_text ?: $item->title }}" 
                                :style="gridCols === 2 ? 'height: 230px;' : (gridCols === 3 ? 'height: 185px;' : (gridCols === 4 ? 'height: 155px;' : 'height: 130px;'))"
                                style="width: 100%; object-fit: cover; display: block; transition: height 0.22s ease;"
                                onerror="this.onerror=null; this.src='/assets/images/default-thumbnail.jpg';"
                                loading="lazy"
                            />

                            <!-- Action Buttons Row on Top-Left of Card: Preview + Heart Favorite -->
                            <div class="sfd-card-actions-row">
                                <!-- Open in New Tab Button (Hidden by default, fades in on hover) -->
                                <a 
                                    href="{{ $url }}" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    @click.stop
                                    title="معاينة الصورة بالحجم الحقيقي في تبويب جديد دون إغلاق المودل"
                                    class="sfd-card-hover-overlay"
                                    style="background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px); color: #ffffff; border-radius: 6px; padding: 4px 7px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.15);"
                                >
                                    <svg width="12" height="12" style="width: 12px; height: 12px; display: inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                    <span>معاينة</span>
                                </a>

                                <!-- Favorite Heart Button (Hollow when false, solid red when true) -->
                                <button 
                                    type="button" 
                                    @click.stop="toggleFavorite({{ $item->id }}, $event)"
                                    class="sfd-fav-btn"
                                    :class="isFavorite({{ $item->id }}) ? 'is-fav' : 'sfd-card-hover-overlay'"
                                    :title="isFavorite({{ $item->id }}) ? 'إزالة من صوري المفضلة' : 'إضافة إلى صوري المفضلة'"
                                >
                                    <template x-if="isFavorite({{ $item->id }})">
                                        <!-- Solid Red Heart -->
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="!isFavorite({{ $item->id }})">
                                        <!-- Hollow Heart -->
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    </template>
                                </button>
                            </div>

                            <!-- Usage Status Badge (Top-Right, Hidden by default, fades in on hover) -->
                            <div class="sfd-card-hover-overlay" style="position: absolute; top: 6px; right: 6px; z-index: 15;">
                                @if($usagesCount > 0)
                                    <span style="display: inline-flex; align-items: center; padding: 2px 6px; border-radius: 5px; font-size: 10px; font-weight: 600; background: rgba(16, 185, 129, 0.95); color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.2);" title="مستخدم في محتويات الموقع">
                                        مستخدم ({{ $usagesCount }})
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; padding: 2px 6px; border-radius: 5px; font-size: 10px; font-weight: 600; background: rgba(71, 85, 105, 0.85); color: #f8fafc; box-shadow: 0 1px 3px rgba(0,0,0,0.2);" title="غير مستخدم">
                                        حر
                                    </span>
                                @endif
                            </div>

                            <!-- Selected Checkmark Circle (Always visible when selected) -->
                            <div 
                                x-show="selectedId == {{ $item->id }}" 
                                x-cloak
                                style="position: absolute; bottom: 6px; right: 6px; z-index: 15; background: #10b981; color: #ffffff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"
                            >
                                <svg width="13" height="13" style="width: 13px; height: 13px; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                        </div>

                        <!-- Caption & Metadata Area -->
                        <div style="padding: 8px 10px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; box-sizing: border-box;">
                            <div>
                                <!-- File Name / Title -->
                                <div 
                                    style="font-size: 12px; font-weight: 700; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 2px;"
                                    title="{{ $item->title ?: $item->file_name }}"
                                >
                                    {{ $item->title ?: $item->file_name }}
                                </div>

                                <!-- Alt Text -->
                                <div 
                                    style="font-size: 11px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 4px;"
                                    title="النص البديل (Alt): {{ $item->alt_text ?: '—' }}"
                                >
                                    <span style="font-weight: 600; color: #94a3b8;">Alt:</span> 
                                    {{ $item->alt_text ?: '—' }}
                                </div>
                            </div>

                            <!-- Footer Info: Category & Size/Dimensions -->
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 10px; color: #94a3b8; padding-top: 4px; border-top: 1px solid #f1f5f9; margin-top: 4px;">
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 80px;" title="{{ $item->category?->name ?? 'المكتبة العامة' }}">
                                    {{ $item->category?->name ?? 'عام' }}
                                </span>
                                <span style="white-space: nowrap; font-family: monospace;">{{ $dim ?: $size }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Pagination Bar (Appears when items exceed configured rows * cols) -->
        <template x-if="totalPages > 1">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding: 10px 4px 0; border-top: 1px solid #f1f5f9; flex-wrap: wrap; gap: 8px;">
                <div style="font-size: 12px; color: #64748b;">
                    صفحة <strong style="color: #0f172a;" x-text="currentPage"></strong> من <strong style="color: #0f172a;" x-text="totalPages"></strong>
                    <span style="color: #94a3b8; margin-right: 4px;">(إجمالي <span x-text="visibleCount"></span> صورة)</span>
                </div>

                <div style="display: flex; align-items: center; gap: 5px;">
                    <!-- Previous Button -->
                    <button 
                        type="button"
                        @click="prevPage()"
                        :disabled="currentPage === 1"
                        class="sfd-page-btn"
                        title="الصفحة السابقة"
                        style="padding: 0 10px;"
                    >
                        السابق
                    </button>

                    <!-- Page Number Buttons (1, 2, 3...) -->
                    <template x-for="p in totalPages" :key="p">
                        <button 
                            type="button"
                            @click="goToPage(p)"
                            class="sfd-page-btn"
                            :class="{ 'active': currentPage === p }"
                            x-text="p"
                            x-show="totalPages <= 8 || p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1"
                        ></button>
                    </template>

                    <!-- Next Button -->
                    <button 
                        type="button"
                        @click="nextPage()"
                        :disabled="currentPage === totalPages"
                        class="sfd-page-btn"
                        title="الصفحة التالية"
                        style="padding: 0 10px;"
                    >
                        التالي
                    </button>
                </div>
            </div>
        </template>
    @endif
</div>