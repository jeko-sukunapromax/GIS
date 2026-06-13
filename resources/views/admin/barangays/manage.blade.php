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

    /* ── Spatial Analytics Panel ────────────────────────────── */
    .spatial-metrics-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 16px;
    }

    .spatial-metric-tile {
        background: rgba(15, 23, 42, 0.45);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 10px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .spatial-metric-tile:hover {
        border-color: rgba(148, 163, 184, 0.22);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    }

    .spatial-metric-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 14px;
    }

    .spatial-metric-label {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .spatial-metric-value {
        color: #f8fafc;
        font-size: 18px;
        font-weight: 800;
        font-family: 'Outfit', sans-serif;
        line-height: 1.1;
    }

    .spatial-qa-panel,
    .spatial-nearest-panel {
        padding: 16px;
        background: rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(148, 163, 184, 0.08);
        border-radius: 10px;
        margin-bottom: 12px;
    }

    .spatial-qa-bars {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .spatial-qa-row {
        display: grid;
        grid-template-columns: 82px 1fr 70px;
        align-items: center;
        gap: 10px;
    }

    .spatial-qa-label {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 600;
    }

    .spatial-qa-bar-track {
        height: 8px;
        background: rgba(15, 23, 42, 0.6);
        border-radius: 4px;
        overflow: hidden;
    }

    .spatial-qa-bar {
        height: 100%;
        border-radius: 4px;
        width: 0;
        transition: width 0.8s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .spatial-qa-val {
        color: #cbd5e1;
        font-size: 12px;
        font-weight: 700;
        text-align: right;
        font-family: 'Outfit', monospace;
    }

    .spatial-qa-verdict {
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .spatial-spinner {
        width: 32px;
        height: 32px;
        border: 3px solid rgba(167, 139, 250, 0.15);
        border-top-color: #a78bfa;
        border-radius: 50%;
        animation: spatialSpin 0.8s linear infinite;
        margin: 0 auto;
    }

    @keyframes spatialSpin {
        to { transform: rotate(360deg); }
    }

    .spatial-pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #94a3b8;
        display: inline-block;
    }

    .spatial-pulse.live {
        background: #34d399;
        box-shadow: 0 0 6px rgba(52, 211, 153, 0.5);
        animation: spatialPulseAnim 2s ease-in-out infinite;
    }

    .spatial-pulse.error {
        background: #f87171;
    }

    @keyframes spatialPulseAnim {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
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
        
        <div class="card" id="spatialAnalyticsCard">
            <div class="card-title">
                <i class="fa-solid fa-satellite" style="color: #a78bfa;"></i> PostGIS Spatial Analytics
                <span id="spatialSyncIndicator" style="margin-left: auto; display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 500; color: #94a3b8; text-transform: none; letter-spacing: 0;">
                    <span class="spatial-pulse"></span> Loading…
                </span>
            </div>

            <!-- Loading state -->
            <div id="spatialLoading" style="text-align: center; padding: 32px 16px;">
                <div class="spatial-spinner"></div>
                <div style="color: #94a3b8; font-size: 13px; margin-top: 14px;">Querying PostGIS geometry engine…</div>
            </div>

            <!-- Error state -->
            <div id="spatialError" style="display: none; padding: 18px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.18); border-radius: 8px;">
                <div style="display: flex; align-items: flex-start; gap: 10px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: #f87171; margin-top: 2px;"></i>
                    <div>
                        <div style="color: #f87171; font-weight: 700; font-size: 13px; margin-bottom: 4px;">Spatial Analysis Unavailable</div>
                        <div id="spatialErrorMsg" style="color: #94a3b8; font-size: 12px; line-height: 1.5;"></div>
                    </div>
                </div>
            </div>

            <!-- Data state -->
            <div id="spatialData" style="display: none;">
                <!-- Metrics Grid -->
                <div class="spatial-metrics-grid">
                    <div class="spatial-metric-tile">
                        <div class="spatial-metric-icon" style="background: rgba(56, 189, 248, 0.12); color: #38bdf8;">
                            <i class="fa-solid fa-vector-square"></i>
                        </div>
                        <div class="spatial-metric-label">PostGIS Area</div>
                        <div class="spatial-metric-value" id="spatialArea">—</div>
                    </div>
                    <div class="spatial-metric-tile">
                        <div class="spatial-metric-icon" style="background: rgba(167, 139, 250, 0.12); color: #a78bfa;">
                            <i class="fa-solid fa-ruler-combined"></i>
                        </div>
                        <div class="spatial-metric-label">Perimeter</div>
                        <div class="spatial-metric-value" id="spatialPerimeter">—</div>
                    </div>
                    <div class="spatial-metric-tile">
                        <div class="spatial-metric-icon" style="background: rgba(52, 211, 153, 0.12); color: #34d399;">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="spatial-metric-label">Features Inside</div>
                        <div class="spatial-metric-value" id="spatialFeatures">—</div>
                    </div>
                    <div class="spatial-metric-tile">
                        <div class="spatial-metric-icon" style="background: rgba(251, 191, 36, 0.12); color: #fbbf24;">
                            <i class="fa-solid fa-road"></i>
                        </div>
                        <div class="spatial-metric-label">Road Network</div>
                        <div class="spatial-metric-value" id="spatialRoads">—</div>
                    </div>
                </div>

                <!-- Area QA Comparison -->
                <div class="spatial-qa-panel" id="spatialQaPanel" style="display: none;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <i class="fa-solid fa-scale-balanced" style="color: #a78bfa; font-size: 13px;"></i>
                        <span style="color: #f8fafc; font-weight: 700; font-size: 13px;">Area Quality Assurance</span>
                    </div>
                    <div class="spatial-qa-bars">
                        <div class="spatial-qa-row">
                            <span class="spatial-qa-label">Stored Area</span>
                            <div class="spatial-qa-bar-track">
                                <div class="spatial-qa-bar" id="qaBarStored" style="background: #38bdf8;"></div>
                            </div>
                            <span class="spatial-qa-val" id="qaValStored">—</span>
                        </div>
                        <div class="spatial-qa-row">
                            <span class="spatial-qa-label">PostGIS Area</span>
                            <div class="spatial-qa-bar-track">
                                <div class="spatial-qa-bar" id="qaBarComputed" style="background: #a78bfa;"></div>
                            </div>
                            <span class="spatial-qa-val" id="qaValComputed">—</span>
                        </div>
                    </div>
                    <div id="qaVerdict" class="spatial-qa-verdict"></div>
                </div>

                <!-- Nearest Facility -->
                <div class="spatial-nearest-panel" id="spatialNearestPanel" style="display: none;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <i class="fa-solid fa-compass" style="color: #34d399; font-size: 13px;"></i>
                        <span style="color: #f8fafc; font-weight: 700; font-size: 13px;">Nearest Facility</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px; background: rgba(15,23,42,0.45); border-radius: 8px; border: 1px solid rgba(148,163,184,0.08);">
                        <div>
                            <div style="color: #f8fafc; font-weight: 700; font-size: 14px;" id="nearestName">—</div>
                            <div style="color: #94a3b8; font-size: 11px; margin-top: 3px;" id="nearestType">—</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: #34d399; font-weight: 800; font-size: 16px;" id="nearestDistance">—</div>
                            <div style="color: #64748b; font-size: 10px;">from centroid</div>
                        </div>
                    </div>
                </div>

                <!-- Sync Footer -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(148,163,184,0.1);">
                    <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 11px;">
                        <i class="fa-solid fa-database"></i>
                        <span>Synced: <span id="spatialSyncedAt" style="color: #94a3b8;">—</span></span>
                    </div>
                    <a href="{{ route('admin.features.index', ['barangay_id' => $barangay->id]) }}" class="btn btn-secondary" style="padding: 7px 14px; font-size: 12px;">
                        <i class="fa-solid fa-list"></i> View Features
                    </a>
                </div>
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
            
            <div class="form-group">
                <label>District</label>
                <select name="district">
                    <option value="">Select District</option>
                    @for($i = 1; $i <= 9; $i++)
                        <option value="District {{ $i }}" {{ $barangay->district == "District $i" ? 'selected' : '' }}>District {{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Barangay Chairman</label>
                    <input type="text" name="barangay_chairman" value="{{ $barangay->barangay_chairman }}" placeholder="e.g. Mr. John Doe">
                </div>
                <div class="form-group">
                    <label>SK Chairman</label>
                    <input type="text" name="sk_chairman" value="{{ $barangay->sk_chairman }}" placeholder="e.g. Jane Smith">
                </div>
            </div>

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
    // Dynamic land use calculation
    document.addEventListener('DOMContentLoaded', function() {
        const totalInput = document.querySelector('input[name="total_area"]');
        const agriInput = document.querySelector('input[name="agri_area"]');
        const resInput = document.querySelector('input[name="residential_area"]');
        const commInput = document.querySelector('input[name="commercial_area"]');
        const unidentifiedInput = document.querySelector('input[name="unidentified_area"]');

        if (totalInput && unidentifiedInput) {
            function recalculateUnidentified() {
                const total = parseFloat(totalInput.value) || 0;
                const agri = parseFloat(agriInput.value) || 0;
                const res = parseFloat(resInput.value) || 0;
                const comm = parseFloat(commInput.value) || 0;
                
                const unidentified = total - (agri + res + comm);
                unidentifiedInput.value = unidentified >= 0 ? parseFloat(unidentified.toFixed(5)) : 0;
            }

            [totalInput, agriInput, resInput, commInput].forEach(input => {
                if (input) {
                    input.addEventListener('input', recalculateUnidentified);
                }
            });
        }
    });

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
            } else {
                alert('Error toggling visibility');
                checkbox.checked = !checkbox.checked;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error toggling visibility');
            checkbox.checked = !checkbox.checked;
        });
    }

    // ── Spatial Analytics ─────────────────────────────────────────
    (function loadSpatialAnalytics() {
        const url = @json(route('admin.barangays.spatial-analysis', $barangay));

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                document.getElementById('spatialLoading').style.display = 'none';

                if (data.status !== 'ready') {
                    showSpatialError(data.message || 'PostGIS spatial analysis is unavailable.');
                    return;
                }

                renderSpatialData(data);
            })
            .catch(err => {
                document.getElementById('spatialLoading').style.display = 'none';
                showSpatialError('Could not connect to the spatial analysis endpoint: ' + err.message);
            });
    })();

    function showSpatialError(message) {
        document.getElementById('spatialError').style.display = 'block';
        document.getElementById('spatialErrorMsg').textContent = message;

        const indicator = document.getElementById('spatialSyncIndicator');
        indicator.querySelector('.spatial-pulse').classList.add('error');
        indicator.querySelector('.spatial-pulse').classList.remove('live');
        indicator.lastChild.textContent = ' Unavailable';
    }

    function renderSpatialData(data) {
        document.getElementById('spatialData').style.display = 'block';

        // Update indicator
        const indicator = document.getElementById('spatialSyncIndicator');
        indicator.querySelector('.spatial-pulse').classList.add('live');
        indicator.lastChild.textContent = ' Live';

        // Format helpers
        const fmtHa = (v) => v != null ? parseFloat(v).toLocaleString('en-US', { maximumFractionDigits: 2 }) + ' ha' : 'N/A';
        const fmtM = (v) => {
            if (v == null) return 'N/A';
            const m = parseFloat(v);
            return m >= 1000
                ? (m / 1000).toLocaleString('en-US', { maximumFractionDigits: 2 }) + ' km'
                : m.toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' m';
        };

        // Metrics
        document.getElementById('spatialArea').textContent = fmtHa(data.computed_area_hectares);
        document.getElementById('spatialPerimeter').textContent = fmtM(data.perimeter_meters);
        document.getElementById('spatialFeatures').textContent = data.contained_features != null ? data.contained_features : '0';
        document.getElementById('spatialRoads').textContent = fmtM(data.road_length_meters);

        // Synced at
        if (data.synced_at) {
            const d = new Date(data.synced_at);
            document.getElementById('spatialSyncedAt').textContent = d.toLocaleDateString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });
        }

        // Area QA
        const stored = data.stored_area_hectares;
        const computed = data.computed_area_hectares;

        if (stored != null && computed != null) {
            const qaPanel = document.getElementById('spatialQaPanel');
            qaPanel.style.display = 'block';

            document.getElementById('qaValStored').textContent = fmtHa(stored);
            document.getElementById('qaValComputed').textContent = fmtHa(computed);

            const maxVal = Math.max(stored, computed, 1);
            requestAnimationFrame(() => {
                document.getElementById('qaBarStored').style.width = Math.round((stored / maxVal) * 100) + '%';
                document.getElementById('qaBarComputed').style.width = Math.round((computed / maxVal) * 100) + '%';
            });

            const diff = data.area_difference_hectares ?? Math.abs(stored - computed);
            const pct = stored > 0 ? ((diff / stored) * 100).toFixed(2) : 0;
            const verdict = document.getElementById('qaVerdict');

            if (diff < 0.5) {
                verdict.style.background = 'rgba(52, 211, 153, 0.08)';
                verdict.style.border = '1px solid rgba(52, 211, 153, 0.18)';
                verdict.style.color = '#34d399';
                verdict.innerHTML = '<i class="fa-solid fa-circle-check"></i> Excellent match — difference is only ' + diff.toFixed(2) + ' ha (' + pct + '%)';
            } else if (diff < 5) {
                verdict.style.background = 'rgba(251, 191, 36, 0.08)';
                verdict.style.border = '1px solid rgba(251, 191, 36, 0.18)';
                verdict.style.color = '#fbbf24';
                verdict.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Minor discrepancy — ' + diff.toFixed(2) + ' ha difference (' + pct + '%)';
            } else {
                verdict.style.background = 'rgba(239, 68, 68, 0.08)';
                verdict.style.border = '1px solid rgba(239, 68, 68, 0.18)';
                verdict.style.color = '#f87171';
                verdict.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Significant discrepancy — ' + diff.toFixed(2) + ' ha difference (' + pct + '%). Consider updating the stored area.';
            }
        }

        // Nearest Facility
        if (data.nearest_feature) {
            const panel = document.getElementById('spatialNearestPanel');
            panel.style.display = 'block';

            document.getElementById('nearestName').textContent = data.nearest_feature.name;
            document.getElementById('nearestType').textContent = (data.nearest_feature.feature_type || '').replace(/_/g, ' ');
            document.getElementById('nearestDistance').textContent = fmtM(data.nearest_feature.distance_meters);
        }
    }
</script>
@endsection
