@extends('layouts.admin')

@section('content')
<style>
    .manage-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        background: rgba(30, 41, 59, 0.45);
        border: 1px solid rgba(148, 163, 184, 0.12);
        padding: 24px;
        border-radius: 12px;
    }
    
    .manage-title {
        font-family: 'Outfit', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #f8fafc;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .manage-subtitle {
        color: #94a3b8;
        font-size: 14px;
    }

    .visibility-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(15, 23, 42, 0.6);
        padding: 12px 20px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.12);
    }
    
    /* Switch UI */
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s; border-radius: 24px; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #38bdf8; }
    input:checked + .slider:before { transform: translateX(20px); }

    .grid-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .card {
        background: rgba(30, 41, 59, 0.45);
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 12px;
        padding: 24px;
    }
    
    .card-title {
        font-family: 'Outfit', sans-serif;
        font-size: 18px;
        font-weight: 600;
        color: #f8fafc;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-group { margin-bottom: 16px; }
    
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #cbd5e1;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    input, select, textarea {
        width: 100%;
        padding: 10px 14px;
        background: rgba(15, 23, 42, 0.4);
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 8px;
        font-size: 14px;
        color: #f8fafc;
        outline: none;
        transition: all 0.2s ease;
    }
    
    input:focus, select:focus, textarea:focus {
        border-color: #38bdf8;
        background: rgba(15, 23, 42, 0.6);
        box-shadow: 0 0 12px rgba(56, 189, 248, 0.15);
    }
    
    .file-upload-box {
        border: 2px dashed rgba(56, 189, 248, 0.3);
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        background: rgba(56, 189, 248, 0.05);
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 16px;
    }
    
    .file-upload-box:hover {
        background: rgba(56, 189, 248, 0.1);
        border-color: #38bdf8;
    }
    
    .file-upload-box i {
        font-size: 32px;
        color: #38bdf8;
        margin-bottom: 12px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .btn-primary { background: #38bdf8; color: #090d16; }
    .btn-primary:hover { background: #0ea5e9; transform: translateY(-1px); box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); }
    
    .btn-secondary { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.1); }
    .btn-secondary:hover { background: rgba(255,255,255,0.15); }

    .alert { padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; }
    .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #f87171; }
    
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(56, 189, 248, 0.1);
        color: #38bdf8;
        border: 1px solid rgba(56, 189, 248, 0.2);
    }
</style>

<div class="manage-header">
    <div>
        <div class="manage-title">
            <i class="fa-solid fa-layer-group" style="color: #38bdf8;"></i> 
            Manage Barangay: {{ $barangay->name }}
        </div>
        <div class="manage-subtitle">Update boundaries, edit attributes, and control map visibility</div>
    </div>
    
    <div class="visibility-toggle">
        <div>
            <div style="font-weight: 600; color: white; font-size: 14px;">Public Map Visibility</div>
            <div style="font-size: 12px; color: #94a3b8;" id="visibility-status-text">
                {{ $barangay->is_visible ? 'Visible to public' : 'Hidden from public' }}
            </div>
        </div>
        <label class="switch">
            <input type="checkbox" id="visibilityToggle" {{ $barangay->is_visible ? 'checked' : '' }} onchange="toggleVisibility()">
            <span class="slider"></span>
        </label>
    </div>
</div>

<div class="grid-container">
    <!-- Left Column: Boundary Management -->
    <div>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-title">
                <i class="fa-solid fa-map-location-dot"></i> Boundary Information
            </div>
            
            <div style="margin-bottom: 20px; font-size: 13px; color: #94a3b8; line-height: 1.5;">
                This barangay's boundary can be drawn manually or updated via official Shapefile/GeoJSON data in the Upload center.
            </div>
            
            <div style="margin-bottom: 20px; padding: 16px; background: rgba(15,23,42,0.4); border: 1px solid rgba(148,163,184,0.1); border-radius: 8px;">
                @if($barangay->boundary_source)
                    <div style="color: #cbd5e1; font-size: 12px; margin-bottom: 4px;">Current Boundary Source:</div>
                    <div style="color: #38bdf8; font-weight: 600; font-family: monospace;">{{ $barangay->boundary_source }}</div>
                    <div style="color: #64748b; font-size: 11px; margin-top: 4px;">Updated: {{ $barangay->boundary_updated_at ? $barangay->boundary_updated_at->format('M d, Y H:i A') : 'N/A' }}</div>
                @else
                    <div style="color: #cbd5e1; font-size: 13px; margin-bottom: 4px;">
                        <i class="fa-solid fa-draw-polygon" style="color: #38bdf8;"></i> Digitized Boundary
                    </div>
                    <div style="color: #64748b; font-size: 12px;">No official shapefile imported yet.</div>
                @endif
            </div>

            <a href="{{ route('admin.uploads.index') }}" class="btn btn-secondary" style="width: 100%;">
                <i class="fa-solid fa-arrow-up-from-bracket"></i> Go to Upload Data
            </a>

            @if($barangay->boundary)
                <a href="{{ route('admin.barangays.boundary.download', $barangay) }}" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                    <i class="fa-solid fa-download"></i> Download Current GeoJSON
                </a>
            @endif
        </div>

        <div class="card" style="margin-bottom: 24px;">
            <div class="card-title">
                <i class="fa-solid fa-clock-rotate-left"></i> Boundary Versions
            </div>

            @forelse($boundaryVersions as $version)
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px; background: rgba(15,23,42,0.35); border: 1px solid rgba(148,163,184,0.12); border-radius: 8px; margin-bottom: 10px;">
                    <div>
                        <div style="color: #f8fafc; font-size: 13px; font-weight: 700;">Version #{{ $version->id }}</div>
                        <div style="color: #94a3b8; font-size: 11px; margin-top: 3px;">{{ $version->label ?? 'Boundary snapshot' }}</div>
                        <div style="color: #64748b; font-size: 11px; margin-top: 3px;">
                            {{ $version->created_at?->format('M d, Y h:i A') }}
                            @if($version->created_by)
                                · {{ $version->created_by }}
                            @endif
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
                        <a href="{{ route('admin.barangays.boundary-versions.download', [$barangay, $version]) }}" class="btn btn-secondary" style="padding: 7px 10px; font-size: 12px;">
                            <i class="fa-solid fa-download"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.barangays.boundary-versions.restore', [$barangay, $version]) }}" onsubmit="return confirm('Restore this boundary version? Current boundary will be saved as a new version first.')">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="padding: 7px 10px; font-size: 12px;">
                                <i class="fa-solid fa-rotate-left"></i> Restore
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.barangays.boundary-versions.destroy', [$barangay, $version]) }}" onsubmit="return confirm('Delete this saved boundary version?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding: 7px 10px; font-size: 12px;">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="color: #94a3b8; font-size: 13px; padding: 14px; background: rgba(15,23,42,0.35); border-radius: 8px;">
                    No saved boundary versions yet. A version is saved automatically before a boundary is replaced.
                </div>
            @endforelse
        </div>
        
        <div class="card">
            <div class="card-title">
                <i class="fa-solid fa-chart-pie"></i> Map Features Overview
            </div>
            <div style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">
                Summary of GIS features plotted within this barangay's jurisdiction.
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; padding: 10px; background: rgba(15,23,42,0.4); border-radius: 8px;">
                    <span>Total Features</span>
                    <span class="status-badge">{{ $barangay->features()->count() }} items</span>
                </div>
                
                <a href="{{ route('admin.features.index', ['barangay_id' => $barangay->id]) }}" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                    <i class="fa-solid fa-list"></i> View All Features
                </a>
            </div>
        </div>
    </div>

    <!-- Right Column: Attribute Editing -->
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-pen-to-square"></i> Edit Attributes
        </div>
        
        <form action="{{ route('admin.barangays.update-attributes', $barangay) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Population</label>
                    <input type="text" name="population" value="{{ $barangay->population }}">
                </div>
                <div class="form-group">
                    <label>Hazard Level</label>
                    <input type="text" name="hazard_level" value="{{ $barangay->hazard_level }}">
                </div>
            </div>
            
            <div class="form-group">
                <label>Primary Land Use</label>
                <input type="text" name="land_use" value="{{ $barangay->land_use }}">
            </div>
            
            <div style="margin: 24px 0; border-top: 1px dashed rgba(148,163,184,0.2);"></div>
            
            <div class="form-group">
                <label>Total Area (Hectares)</label>
                <input type="number" step="0.00001" name="total_area" value="{{ $barangay->total_area }}">
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Agricultural Area</label>
                    <input type="number" step="0.00001" name="agri_area" value="{{ $barangay->agri_area }}">
                </div>
                <div class="form-group">
                    <label>Residential Area</label>
                    <input type="number" step="0.00001" name="residential_area" value="{{ $barangay->residential_area }}">
                </div>
                <div class="form-group">
                    <label>Commercial Area</label>
                    <input type="number" step="0.00001" name="commercial_area" value="{{ $barangay->commercial_area }}">
                </div>
                <div class="form-group">
                    <label>Unidentified Area</label>
                    <input type="number" step="0.00001" name="unidentified_area" value="{{ $barangay->unidentified_area }}">
                </div>
            </div>
            
            <div style="margin: 24px 0; border-top: 1px dashed rgba(148,163,184,0.2);"></div>
            
            <div class="form-group">
                <label>Description / Notes</label>
                <textarea name="description" rows="4">{{ $barangay->description }}</textarea>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fa-solid fa-save"></i> Save Attributes
                </button>
                <a href="{{ route('admin.barangays.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle visibility via AJAX
    function toggleVisibility() {
        const checkbox = document.getElementById('visibilityToggle');
        const statusText = document.getElementById('visibility-status-text');
        
        fetch('{{ route('admin.barangays.toggle-visibility', $barangay) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusText.textContent = data.is_visible ? 'Visible to public' : 'Hidden from public';
                // Show a quick toast or let the UI speak for itself
            } else {
                alert('Error toggling visibility');
                checkbox.checked = !checkbox.checked; // Revert
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error toggling visibility');
            checkbox.checked = !checkbox.checked; // Revert
        });
    }
</script>
@endsection
