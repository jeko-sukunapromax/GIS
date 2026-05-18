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

    .upload-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        border: 2px dashed rgba(56, 189, 248, 0.4);
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        background: rgba(30, 41, 59, 0.45);
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 24px;
    }
    
    .upload-zone:hover {
        background: rgba(56, 189, 248, 0.05);
        border-color: #38bdf8;
    }
    
    .upload-zone i {
        font-size: 32px;
        color: #38bdf8;
        margin-bottom: 16px;
    }
    
    .upload-zone-title {
        font-size: 16px;
        font-weight: 600;
        color: #f8fafc;
        margin-bottom: 8px;
    }
    
    .upload-zone-desc {
        font-size: 13px;
        color: #64748b;
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
</style>

<div class="page-header">
    <div class="page-title">Upload Data</div>
    <div class="page-subtitle">Upload shapefiles, GeoJSON, or CSV files</div>
</div>

<form action="{{ route('admin.uploads.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
    @csrf
    
    <label class="upload-zone" for="upload_file" id="dropZone">
        <div id="uploadPlaceholder">
            <i class="fa-solid fa-arrow-up-from-bracket"></i>
            <div class="upload-zone-title">Drag and drop your file(s) here</div>
            <div class="upload-zone-desc">Supports: .shp, .geojson, .json, .csv, .kml, .gpx, or .zip — Multi-select enabled (Max 50MB per file)</div>
        </div>
        
        <div id="fileInfo" style="display: none; width: 100%; max-width: 500px; margin-left: auto; margin-right: auto; text-align: center;">
            <div style="font-size: 40px; color: #38bdf8; margin-bottom: 16px;">
                <i class="fa-solid fa-file-circle-check"></i>
            </div>
            <div id="fileNameDisplay" style="margin-bottom: 24px;"></div>
            
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 15px; font-weight: 600;" onclick="event.stopPropagation()">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Process & Auto-Map Boundaries
            </button>
            <div style="margin-top: 12px; font-size: 12px; color: #94a3b8; text-decoration: underline; cursor: pointer;" onclick="event.preventDefault(); document.getElementById('upload_file').value = ''; document.getElementById('upload_file').dispatchEvent(new Event('change'));">
                Cancel / Select Different Files
            </div>
        </div>
    </label>
    <input type="file" id="upload_file" name="upload_files[]" style="display: none;" accept=".shp,.geojson,.json,.csv,.kml,.gpx,.zip" multiple>
</form>

<div class="card">
    <div class="card-title">Recent Uploads</div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>FILE</th>
                    <th>TYPE</th>
                    <th>SIZE</th>
                    <th>UPLOADED BY</th>
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
                fileNameDisplay.innerHTML = `<div style="font-size: 18px; color: #f8fafc; font-weight: 600;">${this.files[0].name}</div><div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">Ready to process</div>`;
            } else {
                let fileListHtml = `<div style="font-size: 16px; color: #f8fafc; font-weight: 600; margin-bottom: 12px;">${this.files.length} Files Selected</div>`;
                fileListHtml += `<div style="max-height: 120px; overflow-y: auto; text-align: left; padding: 12px; background: rgba(15, 23, 42, 0.4); border-radius: 8px; border: 1px solid rgba(148, 163, 184, 0.1);"><ul style="list-style: none; padding: 0; margin: 0;">`;
                for (let i = 0; i < this.files.length; i++) {
                    const sizeKB = (this.files[i].size / 1024).toFixed(1);
                    fileListHtml += `<li style="margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; color: #cbd5e1; font-size: 13px;">
                        <span><i class="fa-solid fa-file" style="color: #38bdf8; font-size: 11px; margin-right: 8px;"></i> ${this.files[i].name}</span>
                        <span style="color: #64748b; font-family: monospace; font-size: 11px;">${sizeKB} KB</span>
                    </li>`;
                }
                fileListHtml += `</ul></div>`;
                fileNameDisplay.innerHTML = fileListHtml;
            }
            fileInfo.style.display = 'block';
            
            // Highlight the drop zone
            dropZone.style.borderColor = '#38bdf8';
            dropZone.style.background = 'rgba(56, 189, 248, 0.08)';
        } else {
            uploadPlaceholder.style.display = 'block';
            fileInfo.style.display = 'none';
            dropZone.style.borderColor = 'rgba(56, 189, 248, 0.4)';
            dropZone.style.background = 'rgba(30, 41, 59, 0.45)';
        }
    });

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
