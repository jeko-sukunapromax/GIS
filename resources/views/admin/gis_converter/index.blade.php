@extends('layouts.admin')

@section('content')
<style>
    .converter-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 24px;
    }

    .converter-subtitle {
        color: var(--text-muted);
        font-size: 14px;
        margin-top: 6px;
    }

    .converter-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 20px;
        align-items: start;
    }

    .converter-drop {
        border: 1px dashed rgba(56, 189, 248, 0.45);
        background: rgba(15, 23, 42, 0.42);
        border-radius: 8px;
        padding: 24px;
        display: grid;
        gap: 14px;
    }

    .converter-file-input {
        width: 100%;
        padding: 14px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.58);
        color: var(--text-heading);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .summary-tile {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 8px;
        padding: 13px 14px;
        background: rgba(15, 23, 42, 0.4);
    }

    .summary-label {
        color: var(--text-muted);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .summary-value {
        color: var(--text-heading);
        font-size: 17px;
        font-weight: 800;
        margin-top: 5px;
    }

    .chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .data-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid rgba(56, 189, 248, 0.22);
        background: rgba(56, 189, 248, 0.08);
        color: #bae6fd;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .converter-section-title {
        color: var(--text-heading);
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.9px;
        margin-bottom: 12px;
    }

    .action-panel {
        display: grid;
        gap: 12px;
    }

    .action-card {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 8px;
        padding: 16px;
        background: rgba(15, 23, 42, 0.42);
    }

    .action-title {
        color: var(--text-heading);
        font-weight: 800;
        margin-bottom: 12px;
    }

    .mini-note {
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.45;
        margin-top: 8px;
    }

    .sample-props {
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.5;
    }

    @media (max-width: 1100px) {
        .converter-grid,
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="converter-header">
    <div>
        <h1 class="page-title" style="margin-bottom: 0;">GIS Converter</h1>
        <p class="converter-subtitle">Normalize KML, Shapefile ZIP, or GeoJSON into a downloadable GeoJSON FeatureCollection.</p>
    </div>
    <a href="{{ route('admin.uploads.index') }}" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload Data
    </a>
</div>

<div class="converter-grid">
    <div style="display: grid; gap: 20px;">
        <div class="card">
            <h3 style="margin-bottom: 18px;">Convert File</h3>
            <form action="{{ route('admin.gis-converter.preview') }}" method="POST" enctype="multipart/form-data" class="converter-drop">
                @csrf
                <div>
                    <label class="form-label" for="gis_file">GIS File</label>
                    <input class="converter-file-input" type="file" id="gis_file" name="gis_file" accept=".geojson,.json,.kml,.zip" required>
                    <div class="mini-note">Accepted: GeoJSON, KML, or zipped Shapefile with WGS84 longitude/latitude coordinates.</div>
                </div>
                <button type="submit" class="btn btn-primary" style="justify-content: center;">
                    <i class="fa-solid fa-code-compare"></i> Convert & Inspect
                </button>
            </form>
        </div>

        @if(session('gis_conversion_preview'))
            @php($preview = session('gis_conversion_preview'))
            @php($inspection = $preview['inspection'] ?? [])
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap; margin-bottom:18px;">
                    <div>
                        <h3 style="margin-bottom: 6px;">Converted GeoJSON Preview</h3>
                        <div style="color: var(--text-muted); font-size: 13px;">{{ $preview['source_name'] }}</div>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a href="{{ route('admin.gis-converter.download', ['preview_token' => $preview['token']]) }}" class="btn btn-secondary">
                            <i class="fa-solid fa-file-arrow-down"></i> Download GeoJSON
                        </a>
                        <form action="{{ route('admin.gis-converter.cancel') }}" method="POST" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                        </form>
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-tile">
                        <div class="summary-label">Source</div>
                        <div class="summary-value">{{ $preview['source_format'] }}</div>
                    </div>
                    <div class="summary-tile">
                        <div class="summary-label">Output</div>
                        <div class="summary-value">GeoJSON</div>
                    </div>
                    <div class="summary-tile">
                        <div class="summary-label">Features</div>
                        <div class="summary-value">{{ number_format($preview['feature_count']) }}</div>
                    </div>
                    <div class="summary-tile">
                        <div class="summary-label">Attributes</div>
                        <div class="summary-value">{{ count($inspection['property_keys'] ?? []) }}</div>
                    </div>
                </div>

                <div style="display:grid; gap:20px;">
                    <div>
                        <div class="converter-section-title">Geometry Types</div>
                        <div class="chip-row">
                            @foreach(($inspection['geometry_counts'] ?? []) as $type => $count)
                                <span class="data-chip"><i class="fa-solid fa-draw-polygon"></i> {{ $type }} · {{ $count }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="converter-section-title">Detected Attributes</div>
                        <div class="chip-row">
                            @forelse(array_slice($inspection['property_keys'] ?? [], 0, 18) as $key)
                                <span class="data-chip">{{ $key }}</span>
                            @empty
                                <span style="color: var(--text-muted); font-size: 13px;">No attributes found.</span>
                            @endforelse
                            @if(count($inspection['property_keys'] ?? []) > 18)
                                <span class="data-chip">+{{ count($inspection['property_keys']) - 18 }} more</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="converter-section-title">Sample Records</div>
                        <div class="table-responsive" style="max-height: 520px; overflow: auto; border: 1px solid rgba(148, 163, 184, 0.08); border-radius: 10px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="position: sticky; top: 0; z-index: 1; background: #111827;">Name</th>
                                        <th style="position: sticky; top: 0; z-index: 1; background: #111827;">Geometry</th>
                                        <th style="position: sticky; top: 0; z-index: 1; background: #111827;">Attributes</th>
                                        <th style="position: sticky; top: 0; z-index: 1; background: #111827;">Sample Properties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($inspection['sample_rows'] ?? []) as $row)
                                        <tr>
                                            <td style="font-weight: 700; color: var(--text-heading);">{{ $row['name'] }}</td>
                                            <td>{{ $row['geometry_type'] }}</td>
                                            <td>{{ $row['property_count'] }}</td>
                                            <td class="sample-props">
                                                @foreach($row['properties'] as $key => $value)
                                                    <div><strong style="color:#cbd5e1;">{{ $key }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="color: var(--text-muted); font-size: 12px; margin-top: 10px;">
                            Showing all {{ count($inspection['sample_rows'] ?? []) }} record(s).
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-bottom: 18px;">Import Actions</h3>

        @if(session('gis_conversion_preview'))
            @php($preview = session('gis_conversion_preview'))
            <div class="action-panel">
                <div class="action-card">
                    <div class="action-title">Import as Boundaries</div>
                    <form action="{{ route('admin.gis-converter.import') }}" method="POST" style="display:grid; gap:12px;">
                        @csrf
                        <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                        <input type="hidden" name="import_mode" value="boundaries">
                        <button type="submit" class="btn btn-primary" style="justify-content:center;">
                            <i class="fa-solid fa-border-top-left"></i> Save Boundaries
                        </button>
                    </form>
                    <div class="mini-note">Uses Polygon or MultiPolygon features. Names are matched to barangay records when possible.</div>
                </div>

                <div class="action-card">
                    <div class="action-title">Import as Map Features</div>
                    <form action="{{ route('admin.gis-converter.import') }}" method="POST" style="display:grid; gap:12px;">
                        @csrf
                        <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                        <input type="hidden" name="import_mode" value="features">

                        <div>
                            <label class="form-label" for="feature_barangay_id">Default Barangay</label>
                            <select class="form-control" id="feature_barangay_id" name="feature_barangay_id">
                                <option value="">Use file property</option>
                                @foreach($barangays as $barangay)
                                    <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="feature_type">Default Layer Type</label>
                            <select class="form-control" id="feature_type" name="feature_type">
                                <option value="">Auto-detect from properties</option>
                                @foreach($layerTypes->groupBy('category') as $category => $types)
                                    <optgroup label="{{ ucwords(str_replace('_', ' ', $category)) }}">
                                        @foreach($types as $type)
                                            <option value="{{ $type->code }}">{{ $type->name }} · {{ ucfirst($type->geom_type) }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="justify-content:center;">
                            <i class="fa-solid fa-location-dot"></i> Save Map Features
                        </button>
                    </form>
                    <div class="mini-note">Uses Point, LineString, or Polygon features. Properties become feature metadata.</div>
                </div>
            </div>
        @else
            <div style="color: var(--text-muted); font-size: 13px; line-height: 1.6;">
                Convert a GIS file to enable download and import actions.
            </div>
        @endif
    </div>
</div>
@endsection
