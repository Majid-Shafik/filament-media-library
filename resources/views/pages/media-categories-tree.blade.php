<x-filament-panels::page>
    {{-- Scoped CSS for authentic Filament Category Tree design --}}
    <style>
        .fct-root {
            --fct-primary: #6366f1;
            --fct-primary-hover: #4f46e5;
            --fct-primary-light: #eef2ff;
            --fct-border: #e2e8f0;
            --fct-bg-card: #ffffff;
            --fct-text-main: #0f172a;
            --fct-text-muted: #64748b;
            --fct-bg-hover: #f8fafc;
            --fct-bg-root: #fbfcfe;
            font-family: inherit;
        }
        .dark .fct-root {
            --fct-primary-light: rgba(99, 102, 241, 0.15);
            --fct-border: #334155;
            --fct-bg-card: #1e293b;
            --fct-text-main: #f8fafc;
            --fct-text-muted: #94a3b8;
            --fct-bg-hover: #1e293b80;
            --fct-bg-root: #0f172a40;
        }

        /* Top Header & Stats Container */
        .fct-header-card {
            background: var(--fct-bg-card);
            border: 1px solid var(--fct-border);
            border-radius: 16px;
            padding: 18px 22px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .fct-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }
        .fct-title-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .fct-title-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--fct-primary-light);
            color: var(--fct-primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .fct-title-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--fct-text-main);
            line-height: 1.2;
        }
        .fct-subtitle-text {
            font-size: 13px;
            color: var(--fct-text-muted);
            margin-top: 3px;
        }

        /* Stats Cards Row */
        .fct-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid var(--fct-border);
        }
        .fct-stat-box {
            background: var(--fct-bg-root);
            border: 1px solid var(--fct-border);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
        }
        .fct-stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--fct-text-muted);
        }
        .fct-stat-num {
            font-size: 20px;
            font-weight: 800;
            color: var(--fct-text-main);
            margin-top: 2px;
        }

        /* Toolbar */
        .fct-toolbar {
            background: var(--fct-bg-card);
            border: 1px solid var(--fct-border);
            border-radius: 14px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .fct-search-box {
            position: relative;
            flex: 1;
            min-width: 240px;
            max-width: 440px;
        }
        .fct-search-input {
            width: 100%;
            background: var(--fct-bg-card);
            border: 1px solid var(--fct-border);
            border-radius: 10px;
            padding: 8px 36px 8px 12px;
            font-size: 13px;
            color: var(--fct-text-main);
            outline: none;
            transition: all 0.2s ease;
        }
        .fct-search-input:focus {
            border-color: var(--fct-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .fct-search-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 12px;
            color: var(--fct-text-muted);
            pointer-events: none;
        }

        /* Buttons */
        .fct-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            background: var(--fct-primary);
            color: #ffffff !important;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.25);
            transition: all 0.15s ease;
        }
        .fct-btn-primary:hover {
            background: var(--fct-primary-hover);
            transform: translateY(-1px);
        }
        .fct-btn-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--fct-bg-card);
            border: 1px solid var(--fct-border);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--fct-text-main);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fct-btn-pill:hover {
            border-color: var(--fct-primary);
            color: var(--fct-primary);
        }

        /* View Mode Switcher */
        .fct-switch-group {
            display: inline-flex;
            padding: 2px;
            border-radius: 9px;
            background: var(--fct-bg-root);
            border: 1px solid var(--fct-border);
        }
        .fct-switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            background: none;
            color: var(--fct-text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fct-switch-btn.active {
            background: var(--fct-bg-card);
            color: var(--fct-primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        /* Tree Card Container */
        .fct-tree-card {
            background: var(--fct-bg-card);
            border: 1px solid var(--fct-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .fct-tree-header {
            display: grid;
            grid-template-columns: 1fr 220px 170px;
            padding: 12px 20px;
            background: rgba(248, 250, 252, 0.85);
            border-bottom: 1px solid var(--fct-border);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--fct-text-muted);
        }
        .dark .fct-tree-header {
            background: rgba(15, 23, 42, 0.45);
        }
        @media (max-width: 768px) {
            .fct-tree-header {
                grid-template-columns: 1fr 120px;
            }
            .fct-tree-header .hide-mobile {
                display: none;
            }
        }

        /* Node Row */
        .fct-node-row {
            display: grid;
            grid-template-columns: 1fr 220px 170px;
            padding: 11px 20px;
            align-items: center;
            border-bottom: 1px solid var(--fct-border);
            transition: background 0.15s ease;
            position: relative;
        }
        .fct-node-row:last-child {
            border-bottom: none;
        }
        .fct-node-row:hover {
            background: var(--fct-bg-hover);
        }
        .fct-node-row.is-root {
            background: var(--fct-bg-root);
            border-right: 4px solid var(--fct-primary);
        }
        @media (max-width: 768px) {
            .fct-node-row {
                grid-template-columns: 1fr 120px;
            }
            .fct-node-row .hide-mobile {
                display: none;
            }
        }

        /* Node Left Section: Chevron + Folder + Name */
        .fct-node-left {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .fct-chevron-btn {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: none;
            background: none;
            color: var(--fct-text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fct-chevron-btn:hover {
            background: rgba(148, 163, 184, 0.15);
            color: var(--fct-primary);
        }
        .fct-folder-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .fct-node-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--fct-text-main);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .fct-node-slug {
            font-size: 11px;
            color: var(--fct-text-muted);
            font-family: monospace;
            background: rgba(148, 163, 184, 0.12);
            padding: 1px 6px;
            border-radius: 5px;
            flex-shrink: 0;
        }
        .fct-node-breadcrumb {
            font-size: 11px;
            color: var(--fct-text-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-top: 2px;
        }

        /* Badges */
        .fct-badge-count {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .fct-badge-direct {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .dark .fct-badge-direct {
            background: rgba(5, 150, 105, 0.15);
            color: #34d399;
            border-color: rgba(5, 150, 105, 0.3);
        }
        .fct-badge-total {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .dark .fct-badge-total {
            background: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
            border-color: rgba(37, 99, 235, 0.3);
        }

        /* Actions Bar */
        .fct-actions-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }
        .fct-action-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid var(--fct-border);
            background: var(--fct-bg-card);
            color: var(--fct-text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .fct-action-btn:hover {
            border-color: var(--fct-primary);
            color: var(--fct-primary);
            background: var(--fct-primary-light);
        }
        .fct-action-btn.delete:hover {
            border-color: #fda4af;
            color: #e11d48;
            background: #fff1f2;
        }
    </style>

    <div class="fct-root" dir="rtl">
        {{-- A. Top Header & Stats Card --}}
        @php
            $stats = $this->getStats();
        @endphp
        <div class="fct-header-card">
            <div class="fct-header-top">
                <div class="fct-title-area">
                    <div class="fct-title-icon">
                        <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="fct-title-text">تصنيفات الوسائط</div>
                        <div class="fct-subtitle-text">تنظيم وهيكلة مكتبة وسائط الموقع في شجرة تصنيفات هرمية مرنة ومتعددة المستويات.</div>
                    </div>
                </div>

                @can('create', App\Models\MediaCategory::class)
                    <div>
                        <button 
                            type="button" 
                            class="fct-btn-primary" 
                            wire:click="mountAction('createRootCategory')"
                        >
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>تصنيف رئيسي جديد</span>
                        </button>
                    </div>
                @endcan
            </div>

            {{-- Stats Badges --}}
            <div class="fct-stats-grid">
                <div class="fct-stat-box">
                    <span class="fct-stat-label">إجمالي التصنيفات</span>
                    <span class="fct-stat-num">{{ $stats['total'] }}</span>
                </div>
                <div class="fct-stat-box">
                    <span class="fct-stat-label">تصنيفات رئيسية</span>
                    <span class="fct-stat-num">{{ $stats['root'] }}</span>
                </div>
                <div class="fct-stat-box">
                    <span class="fct-stat-label">تصنيفات فرعية</span>
                    <span class="fct-stat-num">{{ $stats['sub'] }}</span>
                </div>
                <div class="fct-stat-box">
                    <span class="fct-stat-label">إجمالي الوسائط</span>
                    <span class="fct-stat-num" style="color: var(--fct-primary);">{{ $stats['media'] }}</span>
                </div>
            </div>
        </div>

        {{-- B. Controls Toolbar --}}
        <div class="fct-toolbar">
            {{-- Search Box --}}
            <div class="fct-search-box">
                <input 
                    type="text" 
                    class="fct-search-input" 
                    placeholder="بحث في التصنيفات بالاسم أو الـ Slug..." 
                    wire:model.live.debounce.300ms="search"
                />
                <div class="fct-search-icon">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                @if(!empty($search))
                    <button 
                        type="button" 
                        wire:click="$set('search', '')" 
                        style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--fct-text-muted);"
                    >
                        ✕
                    </button>
                @endif
            </div>

            {{-- Expand / Collapse & View Mode --}}
            <div style="display: flex; align-items: center; gap: 8px;">
                @if($viewMode === 'tree')
                    <button type="button" class="fct-btn-pill" wire:click="expandAll" title="توسيع كافة الفروع">
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/>
                        </svg>
                        <span>توسيع الكل</span>
                    </button>
                    <button type="button" class="fct-btn-pill" wire:click="collapseAll" title="طي كافة الفروع">
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v4m0 0H5m4 0L4 2m11 1v4m0 0h4m-4 0l5-5M9 21v-4m0 0H5m4 0l-5 5m11-5v4m0-4h4m-4 0l5 5"/>
                        </svg>
                        <span>طي الكل</span>
                    </button>
                @endif

                <div class="fct-switch-group">
                    <button 
                        type="button" 
                        class="fct-switch-btn {{ $viewMode === 'tree' ? 'active' : '' }}" 
                        wire:click="setViewMode('tree')"
                    >
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                        <span>عرض شجري</span>
                    </button>
                    <button 
                        type="button" 
                        class="fct-switch-btn {{ $viewMode === 'table' ? 'active' : '' }}" 
                        wire:click="setViewMode('table')"
                    >
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span>عرض جدول</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- C. Tree Nodes or Table View --}}
        @if($viewMode === 'table')
            <div class="fct-tree-card">
                {{ $this->table }}
            </div>
        @else
            @php
                $treeNodes = $this->getTreeNodes();
            @endphp

            @if(empty($treeNodes))
                <div class="fct-tree-card" style="padding: 60px 20px; text-align: center;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--fct-primary-light); color: var(--fct-primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                        <svg style="width: 30px; height: 30px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </div>
                    @if(!empty($search))
                        <h4 style="font-size: 16px; font-weight: 700; color: var(--fct-text-main); margin-bottom: 4px;">لم يتم العثور على نتائج</h4>
                        <p style="font-size: 13px; color: var(--fct-text-muted); margin-bottom: 16px;">لا يوجد أي تصنيف يتطابق مع "{{ $search }}".</p>
                        <button type="button" class="fct-btn-pill" wire:click="$set('search', '')">إلغاء البحث</button>
                    @else
                        <h4 style="font-size: 16px; font-weight: 700; color: var(--fct-text-main); margin-bottom: 4px;">لا توجد تصنيفات وسائط حالياً</h4>
                        <p style="font-size: 13px; color: var(--fct-text-muted); margin-bottom: 16px;">ابدأ بتنظيم مكتبة الوسائط بإنشاء أول تصنيف رئيسي مثل: صور المشاريع، الفعاليات، أو التقارير السنوية.</p>
                        @can('create', App\Models\MediaCategory::class)
                            <button type="button" class="fct-btn-primary" wire:click="mountAction('createRootCategory')">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>إنشاء أول تصنيف رئيسي</span>
                            </button>
                        @endcan
                    @endif
                </div>
            @else
                <div class="fct-tree-card">
                    {{-- Tree Header --}}
                    <div class="fct-tree-header">
                        <div>اسم التصنيف والهيكل الهرمي</div>
                        <div class="hide-mobile" style="text-align: center;">الوسائط التابعة</div>
                        <div style="text-align: left;">إجراءات سريعة</div>
                    </div>

                    {{-- Tree Rows --}}
                    <div>
                        @foreach($treeNodes as $node)
                            <div 
                                wire:key="category-node-{{ $node['id'] }}" 
                                class="fct-node-row {{ $node['level'] === 0 ? 'is-root' : '' }}"
                            >
                                {{-- 1. Name & Hierarchy Indentation --}}
                                <div class="fct-node-left" style="padding-right: {{ $node['level'] * 26 }}px;">
                                    {{-- Expand / Collapse Button --}}
                                    @if($node['has_children'])
                                        <button 
                                            type="button" 
                                            class="fct-chevron-btn" 
                                            wire:click="toggleExpand({{ $node['id'] }})"
                                            title="{{ $node['is_expanded'] ? 'طي الفروع' : 'توسيع الفروع' }}"
                                        >
                                            @if($node['is_expanded'])
                                                <svg style="width: 16px; height: 16px; color: var(--fct-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @else
                                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            @endif
                                        </button>
                                    @else
                                        <div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #cbd5e1;"></span>
                                        </div>
                                    @endif

                                    {{-- Folder Icon --}}
                                    <div class="fct-folder-icon">
                                        @if($node['level'] === 0)
                                            <svg style="width: 22px; height: 22px; color: #f59e0b;" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                            </svg>
                                        @else
                                            <svg style="width: 20px; height: 20px; color: #6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                            </svg>
                                        @endif
                                    </div>

                                    {{-- Name & Breadcrumb Lineage --}}
                                    <div style="min-width: 0;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="fct-node-name">{{ $node['name'] }}</span>
                                            @if(!empty($node['slug']))
                                                <span class="fct-node-slug">{{ $node['slug'] }}</span>
                                            @endif
                                        </div>
                                        @if($node['level'] > 0)
                                            <div class="fct-node-breadcrumb" title="{{ $node['breadcrumb'] }}">
                                                {{ $node['breadcrumb'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- 2. Media Counts --}}
                                <div class="hide-mobile" style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <span class="fct-badge-count fct-badge-direct" title="عدد الوسائط داخل هذا التصنيف مباشرة">
                                        {{ $node['direct_media_count'] }} مباشر
                                    </span>
                                    @if($node['has_children'] && $node['total_media_count'] > $node['direct_media_count'])
                                        <span class="fct-badge-count fct-badge-total" title="إجمالي الوسائط شاملة كافة الفروع المنحدرة">
                                            {{ $node['total_media_count'] }} كلي
                                        </span>
                                    @endif
                                </div>

                                {{-- 3. Row Actions --}}
                                <div class="fct-actions-bar">
                                    {{-- Add Subcategory Action --}}
                                    @can('create', App\Models\MediaCategory::class)
                                        <button 
                                            type="button" 
                                            class="fct-action-btn" 
                                            wire:click="mountAction('createSubcategory', { parent_id: {{ $node['id'] }} })"
                                            title="إضافة تصنيف فرعي جديد تحت {{ $node['name'] }}"
                                        >
                                            <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                    @endcan

                                    {{-- View Media Link --}}
                                    <a 
                                        href="{{ \App\Filament\Resources\Media\MediaResource::getUrl('index', ['folder' => $node['id']]) }}" 
                                        class="fct-action-btn" 
                                        title="استعراض وسائط هذا المجلد"
                                    >
                                        <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </a>

                                    {{-- Edit Category Action --}}
                                    @can('update', App\Models\MediaCategory::class)
                                        <button 
                                            type="button" 
                                            class="fct-action-btn" 
                                            wire:click="mountAction('editCategoryAction', { id: {{ $node['id'] }} })"
                                            title="تعديل بيانات التصنيف"
                                        >
                                            <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    @endcan

                                    {{-- Delete Category Action --}}
                                    @can('delete', App\Models\MediaCategory::class)
                                        <button 
                                            type="button" 
                                            class="fct-action-btn delete" 
                                            wire:click="mountAction('deleteCategoryAction', { id: {{ $node['id'] }} })"
                                            title="حذف التصنيف"
                                        >
                                            <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Filament Action Modals Rendering Container --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
