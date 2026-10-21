@php
    $resolvedMedia = null;
    if (!empty($mediaId)) {
        $resolvedMedia = \App\Models\Media::with('category')->find($mediaId);
    }
    if (!$resolvedMedia && !empty($path)) {
        $resolvedMedia = \App\Models\Media::with('category')->where('file_path', $path)->first();
    }

    $imageUrl = null;
    if ($resolvedMedia) {
        $imageUrl = $resolvedMedia->url ?: \Illuminate\Support\Facades\Storage::disk($resolvedMedia->disk ?? 'public')->url($resolvedMedia->file_path);
    } elseif (!empty($path)) {
        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            $imageUrl = $path;
        } else {
            $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        }
    }
@endphp

<style>
    .sfd-image-preview-wrapper {
        width: 100%;
        direction: rtl;
        text-align: right;
        box-sizing: border-box;
        font-family: inherit;
    }

    /* Selected State Card */
    .sfd-preview-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 14px 18px;
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05);
        transition: all 0.2s ease-in-out;
        box-sizing: border-box;
        width: 100%;
    }
    .sfd-preview-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.08);
    }
    .dark .sfd-preview-card {
        background: #111827;
        border-color: #374151;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
    }
    .dark .sfd-preview-card:hover {
        border-color: #4b5563;
        box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.5);
    }

    .sfd-preview-main {
        display: flex;
        align-items: center;
        gap: 16px;
        flex: 1;
        min-width: 0;
    }

    /* Thumbnail Frame */
    .sfd-preview-thumb-wrap {
        width: 130px;
        height: 92px;
        min-width: 130px;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
        border: 1px solid #e2e8f0;
        background-color: #f1f5f9;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }
    .dark .sfd-preview-thumb-wrap {
        border-color: #374151;
        background-color: #1f2937;
    }
    .sfd-preview-thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.25s ease;
    }
    .sfd-preview-thumb-wrap:hover .sfd-preview-thumb-img {
        transform: scale(1.06);
    }

    /* Thumbnail Hover Overlay */
    .sfd-thumb-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
        text-decoration: none;
    }
    .sfd-preview-thumb-wrap:hover .sfd-thumb-overlay {
        opacity: 1;
    }
    .sfd-thumb-overlay-badge {
        background: rgba(255, 255, 255, 0.95);
        color: #0f172a;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.25);
    }

    /* Info Column */
    .sfd-preview-info {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 0;
    }

    /* Badges Row */
    .sfd-badges-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .sfd-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2.5px 10px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 600;
        line-height: 1.4;
    }
    .sfd-badge-success {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .dark .sfd-badge-success {
        background: rgba(6, 78, 59, 0.4);
        color: #6ee7b7;
        border-color: #047857;
    }
    .sfd-badge-category {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-weight: 500;
    }
    .dark .sfd-badge-category {
        background: rgba(30, 58, 138, 0.4);
        color: #93c5fd;
        border-color: #1d4ed8;
    }
    .sfd-badge-warning {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .dark .sfd-badge-warning {
        background: rgba(120, 53, 15, 0.4);
        color: #fde68a;
        border-color: #92400e;
    }

    /* Title */
    .sfd-preview-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.35;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .dark .sfd-preview-title {
        color: #f8fafc;
    }

    /* Chips Row */
    .sfd-chips-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .sfd-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 2.5px 8px;
        font-size: 11px;
        color: #64748b;
    }
    .dark .sfd-chip {
        background: #1f2937;
        border-color: #374151;
        color: #9ca3af;
    }
    .sfd-chip strong {
        font-weight: 600;
        color: #0f172a;
    }
    .dark .sfd-chip strong {
        color: #f1f5f9;
    }

    /* Action Preview Button */
    .sfd-preview-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .sfd-preview-action-btn:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    .dark .sfd-preview-action-btn {
        background: #1f2937;
        border-color: #4b5563;
        color: #e5e7eb;
    }
    .dark .sfd-preview-action-btn:hover {
        background: #374151;
        border-color: #6b7280;
        color: #ffffff;
    }

    /* Empty State Card */
    .sfd-empty-preview {
        padding: 24px 16px;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        text-align: center;
        background-color: rgba(248, 250, 252, 0.7);
        transition: all 0.2s ease;
    }
    .dark .sfd-empty-preview {
        border-color: #374151;
        background-color: rgba(17, 24, 39, 0.4);
    }
    .sfd-empty-icon-box {
        width: 48px;
        height: 48px;
        min-width: 48px;
        min-height: 48px;
        border-radius: 50%;
        background-color: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        color: #94a3b8;
    }
    .dark .sfd-empty-icon-box {
        background-color: #1f2937;
        color: #64748b;
    }

    @media (max-width: 640px) {
        .sfd-preview-card {
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
        }
        .sfd-preview-main {
            flex-direction: column;
            align-items: stretch;
        }
        .sfd-preview-thumb-wrap {
            width: 100%;
            height: 150px;
        }
        .sfd-preview-action-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="sfd-image-preview-wrapper">
    @if(!empty($imageUrl))
        <div class="sfd-preview-card">
            <div class="sfd-preview-main">
                <!-- Thumbnail -->
                <div class="sfd-preview-thumb-wrap">
                    <img 
                        src="{{ $imageUrl }}" 
                        alt="{{ $resolvedMedia?->alt_text ?: ($resolvedMedia?->title ?: 'معاينة الصورة') }}" 
                        class="sfd-preview-thumb-img"
                        onerror="this.onerror=null; this.src='{{ asset('assets/images/default-thumbnail.jpg') }}';"
                    />
                    <a href="{{ $imageUrl }}" target="_blank" class="sfd-thumb-overlay" title="استعراض بالحجم الكامل في نافذة جديدة">
                        <span class="sfd-thumb-overlay-badge">
                            <svg width="13" height="13" style="display:inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            معاينة
                        </span>
                    </a>
                </div>

                <!-- Info -->
                <div class="sfd-preview-info">
                    <!-- Badges -->
                    <div class="sfd-badges-row">
                        @if($resolvedMedia)
                            <span class="sfd-badge-pill sfd-badge-success">
                                <svg width="13" height="13" style="display: inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                مرتبطة بمكتبة الوسائط (#{{ $resolvedMedia->id }})
                            </span>
                            @if($resolvedMedia->category)
                                <span class="sfd-badge-pill sfd-badge-category">
                                    <svg width="13" height="13" style="display: inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                    </svg>
                                    {{ $resolvedMedia->category->name }}
                                </span>
                            @endif
                        @else
                            <span class="sfd-badge-pill sfd-badge-warning">
                                <svg width="13" height="13" style="display: inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                                مسار صورة مباشر (محلي)
                            </span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h4 class="sfd-preview-title" title="{{ $resolvedMedia?->title ?: ($resolvedMedia?->file_name ?: basename($path)) }}">
                        {{ $resolvedMedia?->title ?: ($resolvedMedia?->file_name ?: basename($path)) }}
                    </h4>

                    <!-- Technical Specs Chips -->
                    <div class="sfd-chips-row">
                        @if($resolvedMedia)
                            @if($resolvedMedia->width && $resolvedMedia->height)
                                <span class="sfd-chip">
                                    <svg width="12" height="12" style="display:inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                                    </svg>
                                    الأبعاد: <strong style="direction: ltr; display: inline-block;">{{ $resolvedMedia->width }} × {{ $resolvedMedia->height }}</strong> بكسل
                                </span>
                            @endif
                            @if($resolvedMedia->file_size)
                                <span class="sfd-chip">
                                    <svg width="12" height="12" style="display:inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-16.5-.52c-1.01.143-2.01.317-3 .52m0 0A10.5 10.5 0 0 0 3 10.5c0 3.328 1.54 6.296 3.935 8.243m14.13-13.223A10.5 10.5 0 0 1 21 10.5c0 3.328-1.54 6.296-3.935 8.243" />
                                    </svg>
                                    الحجم: <strong>{{ number_format($resolvedMedia->file_size / 1024, 1) }}</strong> KB
                                </span>
                            @endif
                            @if($resolvedMedia->mime_type)
                                <span class="sfd-chip">
                                    <svg width="12" height="12" style="display:inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.386a9.012 9.012 0 0 0 3.632-3.632c.486-.827.313-1.908-.386-2.607L9.82 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                    </svg>
                                    النوع: <strong style="text-transform: uppercase;">{{ strtoupper(str_replace('image/', '', $resolvedMedia->mime_type)) }}</strong>
                                </span>
                            @endif
                        @else
                            <span class="sfd-chip" title="{{ $path }}">
                                <svg width="12" height="12" style="display:inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                                المسار: <code style="font-family: monospace; font-size: 10.5px; direction: ltr; display: inline-block;">{{ basename($path) }}</code>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Preview Action Link -->
            <a href="{{ $imageUrl }}" target="_blank" class="sfd-preview-action-btn" title="فتح الصورة بالحجم الكامل في نافذة جديدة">
                <svg width="14" height="14" style="display:inline-block;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                معاينة بالحجم الكامل
            </a>
        </div>
    @else
        <!-- Empty State (Kept as loved by the user with rich scoped styling) -->
        <div class="sfd-empty-preview">
            <div class="sfd-empty-icon-box">
                <svg width="24" height="24" style="width: 24px; height: 24px; max-width: 24px; max-height: 24px; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
            <p style="margin: 0 0 4px; font-weight: 600; font-size: 0.875rem; color: #334155;" class="dark:text-gray-200">لم يتم تحديد صورة بعد</p>
            <p style="margin: 0; font-size: 0.75rem; color: #64748b;" class="dark:text-gray-400">يمكنك رفع صورة جديدة مع فحصها آلياً، أو اختيار صورة من مكتبة الوسائط المركزية أدناه.</p>
        </div>
    @endif
</div>
