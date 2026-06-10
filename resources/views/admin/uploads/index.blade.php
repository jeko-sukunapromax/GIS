@extends('layouts.admin')

@section('content')
<style>
    .page-header {
        margin-bottom: 24px;
    }
    
    .page-title {
        font-family: 'Outfit', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #f8fafc;
        margin-bottom: 4px;
    }
    
    .page-subtitle {
        color: #94a3b8;
        font-size: 14px;
    }

    .upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        text-align: center;
    }

    /* Premium Widescreen Upload Zone Redesign */
    .upload-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        border: 2px dashed rgba(56, 189, 248, 0.35);
        border-radius: 16px;
        padding: 48px 32px;
        text-align: center;
        background: linear-gradient(180deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.4) 100%);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    
    .upload-zone:hover {
        background: linear-gradient(180deg, rgba(56, 189, 248, 0.05) 0%, rgba(56, 189, 248, 0.02) 100%);
        border-color: rgba(56, 189, 248, 0.7);
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.06);
    }

    .upload-icon-container {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        background: rgba(56, 189, 248, 0.08);
        border: 1px solid rgba(56, 189, 248, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .upload-zone:hover .upload-icon-container {
        transform: translateY(-4px);
        background: rgba(56, 189, 248, 0.12);
        border-color: rgba(56, 189, 248, 0.45);
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
    }
    
    .upload-icon-container i {
        font-size: 26px;
        color: #38bdf8;
        margin-bottom: 0 !important;
    }
    
    .upload-zone-title {
        font-size: 16px;
        font-weight: 700;
        color: #f8fafc;
        margin-bottom: 8px;
        letter-spacing: 0.02em;
    }
    
    .upload-zone-desc {
        font-size: 13px;
        color: #64748b;
        max-width: 600px;
        line-height: 1.5;
    }

    /* Selected file card styling */
    .selected-file-card {
        display: flex;
        align-items: center;
        gap: 16px;
        background: rgba(15, 23, 42, 0.65);
        border: 1px solid rgba(56, 189, 248, 0.22);
        border-radius: 14px;
        padding: 16px 20px;
        text-align: left;
        width: 100%;
        max-width: 480px;
        margin: 0 auto 28px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 0 15px rgba(56, 189, 248, 0.03);
        transition: all 0.25s ease;
    }

    .file-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: rgba(56, 189, 248, 0.1);
        border: 1px solid rgba(56, 189, 248, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #38bdf8;
        font-size: 22px;
        flex-shrink: 0;
    }

    .file-details {
        flex-grow: 1;
        min-width: 0;
    }

    .file-name {
        font-size: 15px;
        font-weight: 700;
        color: #f8fafc;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .file-status {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #94a3b8;
        margin-top: 4px;
    }

    .pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 1.6s infinite;
        display: inline-block;
    }

    @keyframes pulse-green {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }

    /* Buttons row design */
    .upload-actions-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-preview-upload {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #38bdf8;
        color: #0f172a;
        font-weight: 700;
        font-size: 14px;
        padding: 10px 24px;
        border-radius: 10px;
        border: 1px solid #7dd3fc;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.25);
    }

    .btn-preview-upload:hover {
        background: #0ea5e9;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(56, 189, 248, 0.4);
    }

    .btn-cancel-upload {
        display: inline-flex;
        align-items: center;
        background: rgba(148, 163, 184, 0.08);
        border: 1px solid rgba(148, 163, 184, 0.2);
        color: #94a3b8;
        font-weight: 600;
        font-size: 13px;
        padding: 10px 20px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .btn-cancel-upload:hover {
        background: rgba(148, 163, 184, 0.15);
        color: #cbd5e1;
        border-color: rgba(148, 163, 184, 0.3);
    }

    .card {
        background: rgba(30, 41, 59, 0.45);
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 12px;
        padding: 24px;
    }
    
    .card-title {
        font-family: 'Outfit', sans-serif;
        font-size: 16px;
        font-weight: 700;
        color: #f8fafc;
        margin-bottom: 20px;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-processed { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .status-failed { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .status-create { background: rgba(56, 189, 248, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); }
    .status-update { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-skipped { background: rgba(148, 163, 184, 0.12); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.24); }

    /* Premium Upload Preview Redesigns */
    .preview-card-wrapper {
        margin-bottom: 28px;
        border: 1px solid rgba(56, 189, 248, 0.25) !important;
        background: linear-gradient(180deg, rgba(14, 165, 233, 0.08) 0%, rgba(9, 13, 22, 0.5) 100%) !important;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 12px 40px rgba(14, 165, 233, 0.05);
    }
    
    .btn-confirm-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 14px;
        padding: 10px 24px;
        border-radius: 10px;
        border: 1px solid #34d399;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
    }
    
    .btn-confirm-save:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }

    .btn-cancel-preview {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(15, 23, 42, 0.72);
        color: #cbd5e1;
        font-weight: 700;
        font-size: 14px;
        padding: 10px 18px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .btn-cancel-preview:hover {
        background: rgba(239, 68, 68, 0.12);
        color: #fecaca;
        border-color: rgba(248, 113, 113, 0.36);
        transform: translateY(-1px);
    }

    .btn-download-converted {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(56, 189, 248, 0.1);
        color: #7dd3fc;
        font-weight: 700;
        font-size: 12px;
        padding: 8px 12px;
        border-radius: 9px;
        border: 1px solid rgba(56, 189, 248, 0.28);
        text-decoration: none;
        transition: all 0.2s ease-in-out;
    }

    .btn-download-converted:hover {
        background: rgba(56, 189, 248, 0.18);
        color: #e0f2fe;
        transform: translateY(-1px);
    }
    
    .preview-file-block {
        padding: 24px;
        border: 1px solid rgba(148, 163, 184, 0.1) !important;
        border-radius: 14px;
        background: rgba(15, 23, 42, 0.45) !important;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }
    .upload-settings {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }
    .upload-settings .form-field {
        display: grid;
        gap: 7px;
    }
    .upload-settings label {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .upload-settings select {
        width: 100%;
        min-height: 42px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.72);
        color: #f8fafc;
        padding: 0 12px;
        font-weight: 700;
    }
    .feature-import-options {
        display: contents;
    }
    .upload-settings-note {
        grid-column: 1 / -1;
        color: #94a3b8;
        font-size: 12px;
        line-height: 1.5;
        padding: 10px 12px;
        border: 1px solid rgba(56, 189, 248, 0.16);
        border-radius: 10px;
        background: rgba(56, 189, 248, 0.06);
    }
    .preview-filter-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .preview-filter-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 34px;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.58);
        color: #94a3b8;
        font-size: 12px;
        font-weight: 800;
        padding: 0 13px;
        cursor: pointer;
        transition: all 0.18s ease;
    }
    .preview-filter-btn:hover,
    .preview-filter-btn.is-active {
        border-color: rgba(56, 189, 248, 0.48);
        background: rgba(56, 189, 248, 0.12);
        color: #e0f2fe;
    }
    .preview-search {
        margin-left: auto;
        min-width: min(320px, 100%);
        min-height: 36px;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.64);
        color: #f8fafc;
        padding: 0 14px;
        font-size: 13px;
        outline: none;
    }
    .preview-search:focus {
        border-color: rgba(56, 189, 248, 0.65);
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.09);
    }
    .preview-table-scroll {
        max-height: 560px;
        overflow: auto;
        border: 1px solid rgba(148, 163, 184, 0.08);
        border-radius: 12px;
    }
    .preview-table-scroll table {
        margin-bottom: 0;
    }
    .preview-table-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #111827;
    }
    .preview-count-note {
        margin-top: 12px;
        color: #94a3b8;
        font-size: 12px;
        margin-left: 4px;
    }
    .preview-row[hidden] {
        display: none;
    }
    .review-action-cell {
        min-width: 240px;
        color: #94a3b8;
        font-size: 12px;
    }
    .reconcile-select {
        width: 100%;
        min-height: 34px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.72);
        color: #f8fafc;
        padding: 0 10px;
        font-size: 12px;
        font-weight: 700;
        outline: none;
    }
    .reconcile-select + .reconcile-select {
        margin-top: 7px;
    }
    .reconcile-note {
        margin-top: 6px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.35;
    }
    .reconcile-note strong {
        color: #bae6fd;
    }
</style>

<div class="page-header">
    <div class="page-title">Upload Data</div>
    <div class="page-subtitle">Upload GeoJSON, KML, or zipped shapefiles and preview them as converted GeoJSON before saving.</div>
</div>

<form action="{{ route('admin.uploads.preview') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
    @csrf

    <div class="upload-settings">
        <div class="form-field">
            <label for="import_mode">Import Mode</label>
            <select id="import_mode" name="import_mode">
                <option value="boundaries" @selected(old('import_mode', 'boundaries') === 'boundaries')>Barangay Boundaries</option>
                <option value="features" @selected(old('import_mode') === 'features')>Map Features</option>
            </select>
        </div>

        <div class="feature-import-options">
            <div class="form-field">
                <label for="feature_barangay_id">Default Barangay</label>
                <select id="feature_barangay_id" name="feature_barangay_id">
                    <option value="">Use file property</option>
                    @foreach($barangays as $barangay)
                        <option value="{{ $barangay->id }}" @selected((string) old('feature_barangay_id') === (string) $barangay->id)>{{ $barangay->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="feature_type">Default Layer Type</label>
                <select id="feature_type" name="feature_type">
                    <option value="">Auto-detect from file properties</option>
                    @foreach($layerTypes->groupBy('category') as $category => $types)
                        <optgroup label="{{ ucwords(str_replace('_', ' ', $category)) }}">
                            @foreach($types as $type)
                                <option value="{{ $type->code }}" data-geom-type="{{ $type->geom_type }}" @selected(old('feature_type') === $type->code)>
                                    {{ $type->name }} · {{ ucfirst($type->geom_type) }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="upload-settings-note" id="uploadModeNote">
            Boundary mode imports Polygon/MultiPolygon GeoJSON, KML, or zipped shapefiles into barangay boundary records.
        </div>
    </div>
    
    <label class="upload-zone" for="upload_file" id="dropZone">
        <div id="uploadPlaceholder" class="upload-placeholder">
            <div class="upload-icon-container">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
            </div>
            <div class="upload-zone-title">Drag and drop your file(s) here</div>
            <div class="upload-zone-desc" id="uploadZoneDesc">Supports: .geojson, .json, .kml, or .zip containing shapefile parts. Multi-select enabled, max 50MB per file.</div>
        </div>
        
        <div id="fileInfo" style="display: none; width: 100%; text-align: center;">
            <div id="fileNameDisplay"></div>
            
            <div class="upload-actions-container">
                <button type="submit" class="btn-preview-upload" onclick="event.stopPropagation()">
                    <i class="fa-solid fa-eye"></i> Preview Upload
                </button>
                <button type="button" class="btn-cancel-upload" onclick="event.preventDefault(); event.stopPropagation(); document.getElementById('upload_file').value = ''; document.getElementById('upload_file').dispatchEvent(new Event('change'));">
                    Cancel / Select Different
                </button>
            </div>
        </div>
    </label>
    <input type="file" id="upload_file" name="upload_files[]" style="display: none;" accept=".geojson,.json,.kml,.zip" multiple>
</form>

@if(session('upload_preview'))
    @php($preview = session('upload_preview'))
    <div class="card preview-card-wrapper">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 18px; flex-wrap: wrap;">
            <div>
                <div class="card-title" style="margin-bottom: 6px;">Upload Preview</div>
                <div style="font-size: 13px; color: #94a3b8;">Review detected {{ ($preview['files'][0]['mode'] ?? 'boundaries') === 'features' ? 'map features' : 'boundaries' }} before saving them to the database.</div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <form action="{{ route('admin.uploads.cancel-preview') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                    <button type="submit" class="btn-cancel-preview">
                        <i class="fa-solid fa-xmark"></i> Cancel Preview
                    </button>
                </form>

                <form action="{{ route('admin.uploads.store') }}" method="POST" id="uploadPreviewSaveForm">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                    <button type="submit" class="btn-confirm-save">
                        <i class="fa-solid fa-circle-check"></i> Confirm & Save
                    </button>
                </form>
            </div>
        </div>

        @foreach($preview['files'] as $file)
            @php($fileIndex = $loop->index)
            @php($previewTableId = 'upload-preview-'.$loop->index)
            @php($previewModeLabel = ($file['mode'] ?? 'boundaries') === 'features' ? 'map features' : 'boundaries')
            <div class="preview-file-block">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
                    <div>
                        <div style="color: #f8fafc; font-weight: 700; font-size: 16px; display: flex; align-items: center;">
                            <i class="fa-solid fa-file-shield" style="color: #38bdf8; font-size: 18px; margin-right: 10px;"></i>
                            {{ $file['file_name'] }}
                        </div>
                        <div style="color: #94a3b8; font-size: 12px; margin-top: 4px; margin-left: 28px;">
                            {{ $file['file_type'] }} · {{ $file['file_size'] }}
                            @if(!empty($file['source_format']))
                                · Source: {{ $file['source_format'] }}
                            @endif
                            @if(!empty($file['feature_count']))
                                · {{ $file['feature_count'] }} converted feature(s)
                            @endif
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @if(!empty($file['converted_download_url']))
                            <a href="{{ $file['converted_download_url'] }}" class="btn-download-converted">
                                <i class="fa-solid fa-file-arrow-down"></i> Converted GeoJSON
                            </a>
                        @endif
                        <span class="status-badge status-update" style="padding: 6px 12px; border-radius: 20px;">
                            <i class="fa-solid fa-pen" style="font-size: 9px; margin-right: 4px;"></i> Existing: {{ $file['matched'] }}
                        </span>
                        <span class="status-badge status-create" style="padding: 6px 12px; border-radius: 20px;">
                            <i class="fa-solid fa-plus" style="font-size: 9px; margin-right: 4px;"></i> New: {{ $file['created'] }}
                        </span>
                        @if($file['skipped'] > 0)
                            <span class="status-badge status-skipped" style="padding: 6px 12px; border-radius: 20px;">
                                <i class="fa-solid fa-ban" style="font-size: 9px; margin-right: 4px;"></i> Skipped: {{ $file['skipped'] }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="preview-filter-bar" data-preview-controls="{{ $previewTableId }}" data-preview-label="{{ $previewModeLabel }}">
                    <button type="button" class="preview-filter-btn is-active" data-preview-filter="all">
                        <i class="fa-solid fa-list"></i> All: {{ count($file['items']) }}
                    </button>
                    <button type="button" class="preview-filter-btn" data-preview-filter="Update">
                        <i class="fa-solid fa-circle-check"></i> Existing: {{ $file['matched'] }}
                    </button>
                    <button type="button" class="preview-filter-btn" data-preview-filter="Create">
                        <i class="fa-solid fa-circle-plus"></i> New: {{ $file['created'] }}
                    </button>
                    @if($file['skipped'] > 0)
                        <button type="button" class="preview-filter-btn" data-preview-filter="Skipped">
                            <i class="fa-solid fa-circle-minus"></i> Skipped: {{ $file['skipped'] }}
                        </button>
                    @endif
                    <input type="search" class="preview-search" data-preview-search placeholder="Search {{ $previewModeLabel }}...">
                </div>

                <div class="table-responsive preview-table-scroll">
                    <table>
                        <thead>
                            @if(($file['mode'] ?? 'boundaries') === 'features')
                                <tr>
                                    <th>FEATURE</th>
                                    <th>BARANGAY / LAYER</th>
                                    <th>RESULT</th>
                                    <th>GEOMETRY</th>
                                </tr>
                            @else
                                <tr>
                                    <th>BARANGAY</th>
                                    <th>RESULT</th>
                                    <th>AREA</th>
                                    <th>REVIEW ACTION</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody id="{{ $previewTableId }}" data-preview-total="{{ count($file['items']) }}">
                            @forelse($file['items'] as $item)
                                @php($itemIndex = $loop->index)
                                <tr class="preview-row" data-preview-action="{{ $item['action'] }}" data-preview-search="{{ strtolower($item['display_name'].' '.($item['barangay_name'] ?? '').' '.($item['feature_type_name'] ?? '').' '.($item['geometry_type'] ?? '').' '.($item['reason'] ?? '')) }}">
                                    <td style="font-weight: 600; color: #f8fafc; display: flex; align-items: center;">
                                        <i class="fa-solid fa-map-pin" style="color: #64748b; font-size: 11px; margin-right: 8px;"></i>
                                        {{ $item['display_name'] }}
                                    </td>
                                    @if(($file['mode'] ?? 'boundaries') === 'features')
                                        <td style="color: #94a3b8; font-size: 12px; line-height: 1.45;">
                                            <div style="color: #f8fafc; font-weight: 700;">{{ $item['barangay_name'] ?? 'No barangay' }}</div>
                                            <div>{{ $item['feature_type_name'] ?? 'No layer type' }}</div>
                                        </td>
                                    @endif
                                    <td>
                                        <span class="status-badge {{ $item['action'] === 'Create' ? 'status-create' : ($item['action'] === 'Update' ? 'status-update' : 'status-skipped') }}" style="padding: 5px 10px; border-radius: 20px; font-size: 10.5px;">
                                            @if($item['action'] === 'Create')
                                                <i class="fa-solid fa-circle-plus" style="font-size: 9px; margin-right: 4px;"></i> {{ ($file['mode'] ?? 'boundaries') === 'features' ? 'New feature' : 'New barangay' }}
                                            @elseif($item['action'] === 'Update')
                                                <i class="fa-solid fa-circle-check" style="font-size: 9px; margin-right: 4px;"></i> Update existing
                                            @else
                                                <i class="fa-solid fa-circle-minus" style="font-size: 9px; margin-right: 4px;"></i> Skipped
                                            @endif
                                        </span>
                                        @if(!empty($item['reason']))
                                            <div style="margin-top: 6px; color: #fca5a5; font-size: 11px; line-height: 1.35;">
                                                {{ $item['reason'] }}
                                            </div>
                                        @endif
                                    </td>
                                    @if(($file['mode'] ?? 'boundaries') === 'features')
                                        <td style="color: #94a3b8; font-family: monospace; font-weight: 600;">
                                            {{ $item['geometry_type'] ?? '—' }}
                                            @if(($item['metadata_count'] ?? 0) > 0)
                                                <div style="font-family: Inter, sans-serif; font-size: 11px; color: #64748b; margin-top: 4px;">{{ $item['metadata_count'] }} metadata field(s)</div>
                                            @endif
                                        </td>
                                    @else
                                        <td style="color: #94a3b8; font-family: monospace; font-weight: 600;">{{ $item['area'] ? number_format($item['area'], 2).' ha' : '—' }}</td>
                                        <td class="review-action-cell">
                                            @if(!empty($item['is_municipal_boundary']))
                                                <span class="status-badge status-update">Municipal boundary</span>
                                                <div class="reconcile-note">Saved to the Bayambang municipal boundary record.</div>
                                            @elseif($item['action'] === 'Update')
                                                <select class="reconcile-select" name="boundary_decisions[{{ $fileIndex }}][{{ $itemIndex }}][action]" form="uploadPreviewSaveForm">
                                                    <option value="default">Update matched barangay</option>
                                                    <option value="skip">Skip this boundary</option>
                                                </select>
                                                <div class="reconcile-note">Matched to existing barangay ID #{{ $item['barangay_id'] ?? '—' }}.</div>
                                            @elseif($item['action'] === 'Create')
                                                <select class="reconcile-select" name="boundary_decisions[{{ $fileIndex }}][{{ $itemIndex }}][action]" form="uploadPreviewSaveForm">
                                                    @if(!empty($item['suggested_barangay_id']))
                                                        <option value="match" selected>Use suggested match</option>
                                                    @else
                                                        <option value="skip" selected>Skip unmatched</option>
                                                        <option value="match">Match existing barangay</option>
                                                    @endif
                                                    <option value="create">Create new barangay</option>
                                                    @if(!empty($item['suggested_barangay_id']))
                                                        <option value="skip">Skip unmatched</option>
                                                    @endif
                                                </select>
                                                <select class="reconcile-select" name="boundary_decisions[{{ $fileIndex }}][{{ $itemIndex }}][barangay_id]" form="uploadPreviewSaveForm">
                                                    <option value="">Choose existing barangay...</option>
                                                    @foreach($barangays as $barangay)
                                                        <option value="{{ $barangay->id }}" @selected(($item['suggested_barangay_id'] ?? null) === $barangay->id)>{{ $barangay->name }}</option>
                                                    @endforeach
                                                </select>
                                                @if(!empty($item['suggested_barangay_name']))
                                                    <div class="reconcile-note">Suggested match: <strong>{{ $item['suggested_barangay_name'] }}</strong>.</div>
                                                @else
                                                    <div class="reconcile-note">No safe match found. This row will be skipped unless you choose create or match.</div>
                                                @endif
                                            @else
                                                <span class="status-badge status-skipped">Skipped</span>
                                                <input type="hidden" name="boundary_decisions[{{ $fileIndex }}][{{ $itemIndex }}][action]" value="skip" form="uploadPreviewSaveForm">
                                                <div class="reconcile-note">{{ $item['reason'] ?? 'This row is not importable.' }}</div>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($file['mode'] ?? 'boundaries') === 'features' ? 4 : 4 }}" style="text-align: center; color: #64748b; padding: 22px;">
                                        No importable records were detected in this file.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="preview-count-note" data-preview-count-note="{{ $previewTableId }}">
                    Showing all {{ count($file['items']) }} detected {{ $previewModeLabel }}. Use search or filters before confirming save.
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="card">
    <div class="card-title">Recent Uploads</div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>FILE</th>
                    <th>TYPE</th>
                    <th>SIZE</th>
                    <th>UPLOADER</th>
                    <th>DATE</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($uploads as $upload)
                    <tr>
                        <td style="font-weight: 500; color: #f8fafc;">{{ $upload->file_name }}</td>
                        <td style="color: #94a3b8;">{{ $upload->file_type }}</td>
                        <td style="color: #94a3b8;">{{ $upload->file_size }}</td>
                        <td style="color: #94a3b8;">{{ $upload->uploaded_by }}</td>
                        <td style="color: #94a3b8;">{{ $upload->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if($upload->status === 'Processed')
                                <span class="status-badge status-processed">Processed</span>
                            @elseif($upload->status === 'Pending')
                                <span class="status-badge status-pending">Pending</span>
                            @else
                                <span class="status-badge status-failed">Failed</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('admin.uploads.destroy', $upload) }}" method="POST" style="display: inline;" onsubmit="showConfirmModal(event, this)">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">
                            No uploaded data yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Custom Premium Glassmorphic Modal -->
<div id="confirmModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s ease-in-out;">
    <div style="background: #1e293b; border: 1px solid rgba(239, 68, 68, 0.3); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 0 40px rgba(239, 68, 68, 0.08); border-radius: 16px; width: 420px; padding: 28px; text-align: center; transform: scale(0.9); transition: transform 0.2s ease-in-out;">
        <div style="background: rgba(239, 68, 68, 0.1); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 1px solid rgba(239, 68, 68, 0.2);">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 28px; color: #ef4444;"></i>
        </div>
        <h3 style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #f8fafc; margin-bottom: 8px;">Confirm Deletion</h3>
        <p style="font-size: 14px; color: #94a3b8; line-height: 1.5; margin-bottom: 24px; padding: 0 10px;">Are you sure you want to delete this record from the upload history? This action cannot be undone.</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" id="cancelModalBtn" style="padding: 10px 20px; background: rgba(148, 163, 184, 0.1); border: 1px solid rgba(148, 163, 184, 0.2); color: #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">Cancel</button>
            <button type="button" id="confirmModalBtn" style="padding: 10px 20px; background: #ef4444; border: 1px solid #dc2626; color: #ffffff; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);">Yes, Delete</button>
        </div>
    </div>
</div>

<style>
    #cancelModalBtn:hover {
        background: rgba(148, 163, 184, 0.2) !important;
        color: #f8fafc !important;
    }
    #confirmModalBtn:hover {
        background: #dc2626 !important;
        box-shadow: 0 6px 8px -1px rgba(239, 68, 68, 0.5) !important;
    }
</style>

<script>
    const fileInput = document.getElementById('upload_file');
    const dropZone = document.getElementById('dropZone');
    const fileInfo = document.getElementById('fileInfo');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const importMode = document.getElementById('import_mode');
    const featureOptions = document.querySelectorAll('.feature-import-options .form-field');
    const uploadModeNote = document.getElementById('uploadModeNote');
    const uploadZoneDesc = document.getElementById('uploadZoneDesc');

    function syncImportModeUi() {
        const isFeatureMode = importMode.value === 'features';

        featureOptions.forEach(option => {
            option.style.display = isFeatureMode ? 'grid' : 'none';
        });

        uploadModeNote.textContent = isFeatureMode
            ? 'Map Feature mode converts GeoJSON, KML, or zipped shapefiles into GeoJSON features, then saves Point, LineString, or Polygon records into the selected layer.'
            : 'Boundary mode converts Polygon/MultiPolygon GeoJSON, KML, or zipped shapefiles into barangay boundary records.';
        uploadZoneDesc.textContent = isFeatureMode
            ? 'Supports: .geojson, .json, .kml, or .zip shapefiles with Point, LineString, or Polygon geometry. Converted GeoJSON is available during preview.'
            : 'Supports: .geojson, .json, .kml, or .zip containing shapefile parts. Multi-select enabled, max 50MB per file.';
    }

    importMode.addEventListener('change', syncImportModeUi);
    syncImportModeUi();

    document.querySelectorAll('[data-preview-controls]').forEach(controlBar => {
        const tableId = controlBar.dataset.previewControls;
        const tableBody = document.getElementById(tableId);
        const label = controlBar.dataset.previewLabel || 'records';
        const note = document.querySelector(`[data-preview-count-note="${tableId}"]`);
        const searchInput = controlBar.querySelector('[data-preview-search]');
        const buttons = controlBar.querySelectorAll('[data-preview-filter]');

        if (!tableBody) {
            return;
        }

        const applyPreviewFilters = () => {
            const activeFilter = controlBar.querySelector('.preview-filter-btn.is-active')?.dataset.previewFilter || 'all';
            const searchTerm = (searchInput?.value || '').trim().toLowerCase();
            const rows = tableBody.querySelectorAll('.preview-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const actionMatches = activeFilter === 'all' || row.dataset.previewAction === activeFilter;
                const searchMatches = !searchTerm || (row.dataset.previewSearch || '').includes(searchTerm);
                const shouldShow = actionMatches && searchMatches;

                row.hidden = !shouldShow;
                if (shouldShow) {
                    visibleCount++;
                }
            });

            if (note) {
                note.textContent = `Showing ${visibleCount} of ${rows.length} detected ${label}.`;
            }
        };

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                buttons.forEach(item => item.classList.remove('is-active'));
                button.classList.add('is-active');
                applyPreviewFilters();
            });
        });

        searchInput?.addEventListener('input', applyPreviewFilters);
        applyPreviewFilters();
    });

      fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            uploadPlaceholder.style.display = 'none';
            if (this.files.length === 1) {
                const sizeKB = (this.files[0].name.toLowerCase().endsWith('.zip')) ? 'Zipped Archive' : `${(this.files[0].size / 1024).toFixed(1)} KB`;
                const isZip = this.files[0].name.toLowerCase().endsWith('.zip');
                fileNameDisplay.innerHTML = `
                    <div class="selected-file-card">
                        <div class="file-icon-box">
                            <i class="fa-solid ${isZip ? 'fa-file-zipper' : 'fa-file-code'}"></i>
                        </div>
                        <div class="file-details">
                            <div class="file-name" title="${escapeHtml(this.files[0].name)}">${escapeHtml(this.files[0].name)}</div>
                            <div class="file-status">
                                <span class="pulse-dot"></span>
                                <span>Ready to process · ${sizeKB}</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                let fileListHtml = `
                    <div class="selected-file-card" style="flex-direction: column; align-items: stretch; max-width: 520px; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(148, 163, 184, 0.12); padding-bottom: 10px;">
                            <div class="file-icon-box" style="width: 40px; height: 40px; font-size: 18px; background: rgba(56, 189, 248, 0.08);">
                                <i class="fa-solid fa-files"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #f8fafc; font-size: 14px;">${this.files.length} Files Selected</div>
                                <div class="file-status" style="margin-top: 2px;">
                                    <span class="pulse-dot"></span>
                                    <span>Ready to process</span>
                                </div>
                            </div>
                        </div>
                        <div style="max-height: 140px; overflow-y: auto; padding-right: 4px;">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                `;
                for (let i = 0; i < this.files.length; i++) {
                    const sizeKB = (this.files[i].size / 1024).toFixed(1);
                    const isZip = this.files[i].name.toLowerCase().endsWith('.zip');
                    fileListHtml += `
                        <li style="margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; color: #cbd5e1; font-size: 12.5px; padding: 6px 8px; background: rgba(15, 23, 42, 0.3); border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.02);">
                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 12px; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid ${isZip ? 'fa-file-zipper' : 'fa-file'}" style="color: #38bdf8; font-size: 11px;"></i>
                                ${escapeHtml(this.files[i].name)}
                            </span>
                            <span style="color: #64748b; font-family: monospace; font-size: 10.5px; flex-shrink: 0;">${sizeKB} KB</span>
                        </li>
                    `;
                }
                fileListHtml += `
                            </ul>
                        </div>
                    </div>
                `;
                fileNameDisplay.innerHTML = fileListHtml;
            }
            fileInfo.style.display = 'block';
            
            // Highlight the drop zone
            dropZone.style.borderColor = '#38bdf8';
            dropZone.style.background = 'linear-gradient(180deg, rgba(56, 189, 248, 0.06) 0%, rgba(56, 189, 248, 0.02) 100%)';
            dropZone.style.boxShadow = '0 0 25px rgba(56, 189, 248, 0.08)';
        } else {
            uploadPlaceholder.style.display = 'block';
            fileInfo.style.display = 'none';
            dropZone.style.borderColor = 'rgba(56, 189, 248, 0.35)';
            dropZone.style.background = 'linear-gradient(180deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.4) 100%)';
            dropZone.style.boxShadow = 'none';
        }
    });

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;

        return div.innerHTML;
    }

	    // --- Custom Confirm Modal Logic ---
    let formToSubmit = null;

    function showConfirmModal(event, form) {
        event.preventDefault();
        formToSubmit = form;
        
        const modal = document.getElementById('confirmModal');
        const content = modal.querySelector('div');
        
        modal.style.display = 'flex';
        // Force a reflow
        modal.offsetHeight;
        
        modal.style.opacity = '1';
        content.style.transform = 'scale(1)';
    }

    document.getElementById('cancelModalBtn').addEventListener('click', closeConfirmModal);
    document.getElementById('confirmModalBtn').addEventListener('click', function() {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });

    // Close when clicking outside content
    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeConfirmModal();
        }
    });

    function closeConfirmModal() {
        const modal = document.getElementById('confirmModal');
        const content = modal.querySelector('div');
        
        modal.style.opacity = '0';
        content.style.transform = 'scale(0.9)';
        
        setTimeout(() => {
            modal.style.display = 'none';
            formToSubmit = null;
        }, 200);
    }
</script>
@endsection
