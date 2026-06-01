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
    
    .preview-file-block {
        padding: 24px;
        border: 1px solid rgba(148, 163, 184, 0.1) !important;
        border-radius: 14px;
        background: rgba(15, 23, 42, 0.45) !important;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }
</style>

<div class="page-header">
    <div class="page-title">Upload Data</div>
    <div class="page-subtitle">Upload GeoJSON files or zipped shapefiles for barangay boundary mapping</div>
</div>

<form action="{{ route('admin.uploads.preview') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
    @csrf
    
    <label class="upload-zone" for="upload_file" id="dropZone">
        <div id="uploadPlaceholder" class="upload-placeholder">
            <div class="upload-icon-container">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
            </div>
            <div class="upload-zone-title">Drag and drop your file(s) here</div>
            <div class="upload-zone-desc">Supports: .geojson, .json, or .zip containing .shp and .dbf files. Multi-select enabled, max 50MB per file.</div>
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
    <input type="file" id="upload_file" name="upload_files[]" style="display: none;" accept=".geojson,.json,.zip" multiple>
</form>

@if(session('upload_preview'))
    @php($preview = session('upload_preview'))
    <div class="card preview-card-wrapper">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 18px; flex-wrap: wrap;">
            <div>
                <div class="card-title" style="margin-bottom: 6px;">Upload Preview</div>
                <div style="font-size: 13px; color: #94a3b8;">Review detected boundaries before saving them to the database.</div>
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

                <form action="{{ route('admin.uploads.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                    <button type="submit" class="btn-confirm-save">
                        <i class="fa-solid fa-circle-check"></i> Confirm & Save
                    </button>
                </form>
            </div>
        </div>

        @foreach($preview['files'] as $file)
            <div class="preview-file-block">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
                    <div>
                        <div style="color: #f8fafc; font-weight: 700; font-size: 16px; display: flex; align-items: center;">
                            <i class="fa-solid fa-file-shield" style="color: #38bdf8; font-size: 18px; margin-right: 10px;"></i>
                            {{ $file['file_name'] }}
                        </div>
                        <div style="color: #94a3b8; font-size: 12px; margin-top: 4px; margin-left: 28px;">{{ $file['file_type'] }} · {{ $file['file_size'] }}</div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
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

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>BARANGAY</th>
                                <th>RESULT</th>
                                <th>AREA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(array_slice($file['items'], 0, 12) as $item)
                                <tr>
                                    <td style="font-weight: 600; color: #f8fafc; display: flex; align-items: center;">
                                        <i class="fa-solid fa-map-pin" style="color: #64748b; font-size: 11px; margin-right: 8px;"></i>
                                        {{ $item['display_name'] }}
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $item['action'] === 'Create' ? 'status-create' : ($item['action'] === 'Update' ? 'status-update' : 'status-skipped') }}" style="padding: 5px 10px; border-radius: 20px; font-size: 10.5px;">
                                            @if($item['action'] === 'Create')
                                                <i class="fa-solid fa-circle-plus" style="font-size: 9px; margin-right: 4px;"></i> New barangay
                                            @elseif($item['action'] === 'Update')
                                                <i class="fa-solid fa-circle-check" style="font-size: 9px; margin-right: 4px;"></i> Update existing
                                            @else
                                                <i class="fa-solid fa-circle-minus" style="font-size: 9px; margin-right: 4px;"></i> Skipped
                                            @endif
                                        </span>
                                    </td>
                                    <td style="color: #94a3b8; font-family: monospace; font-weight: 600;">{{ $item['area'] ? number_format($item['area'], 2).' ha' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #64748b; padding: 22px;">
                                        No polygon boundaries were detected in this file.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(count($file['items']) > 12)
                    <div style="margin-top: 12px; color: #94a3b8; font-size: 12px; margin-left: 4px;">Showing first 12 of {{ count($file['items']) }} detected boundaries.</div>
                @endif
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
