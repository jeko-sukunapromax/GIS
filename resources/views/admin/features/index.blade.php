<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Editor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-base: #090d16;
            --bg-panel: rgba(15, 23, 42, 0.85);
            --bg-card: rgba(30, 41, 59, 0.45);
            --border-color: rgba(148, 163, 184, 0.12);
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --text-heading: #f8fafc;
            --accent-blue: #38bdf8;
            --accent-blue-hover: #0ea5e9;
            --accent-blue-glow: rgba(56, 189, 248, 0.25);
            --glass-blur: blur(16px);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { 
            background-color: var(--bg-base); 
            color: var(--text-main); 
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .header {
            height: 64px;
            background: var(--bg-panel);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 24px;
            backdrop-filter: var(--glass-blur);
            z-index: 1000;
        }
        
        .header h1 { 
            font-family: 'Outfit', sans-serif;
            font-size: 18px; 
            font-weight: 700; 
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header h1 i {
            color: var(--accent-blue);
            text-shadow: 0 0 10px var(--accent-blue-glow);
        }
        
        .container { display: flex; height: calc(100vh - 64px); }
        
        .form-panel {
            width: 400px;
            background: var(--bg-panel);
            padding: 20px;
            overflow-y: auto;
            border-right: 1px solid var(--border-color);
            backdrop-filter: var(--glass-blur);
            z-index: 10;
        }
        
        .map-panel { flex: 1; position: relative; }
        #map { height: 100%; width: 100%; background: #090d16; }
        .measure-widget {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: var(--glass-blur);
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.28);
        }
        .measure-widget.is-active {
            border-color: rgba(56, 189, 248, 0.45);
            background: rgba(15, 23, 42, 0.96);
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.14), 0 12px 30px rgba(0, 0, 0, 0.34);
        }
        .measure-action {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: rgba(30, 41, 59, 0.7);
            color: var(--text-main);
            min-height: 34px;
            padding: 0 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        .measure-action:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }
        .measure-action:hover,
        .measure-action.active {
            border-color: rgba(56, 189, 248, 0.45);
            background: rgba(56, 189, 248, 0.16);
            color: var(--accent-blue);
        }
        .measure-action.icon-only {
            width: 34px;
            padding: 0;
        }
        .measure-readout {
            min-width: 118px;
            color: var(--text-heading);
            font-size: 12px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .measure-hint {
            color: var(--text-muted);
            font-size: 10px;
            line-height: 1.2;
            white-space: nowrap;
        }
        .measure-point-label {
            border: 1px solid rgba(56, 189, 248, 0.5);
            background: rgba(15, 23, 42, 0.92);
            color: #e0f2fe;
            border-radius: 999px;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.24);
        }
        #map.measure-mode-active {
            cursor: crosshair;
        }
        
        /* Floating Basemap Switcher Widget */
        .basemap-widget {
            position: absolute;
            bottom: 24px;
            left: 24px;
            z-index: 700;
            display: flex;
            gap: 8px;
            padding: 6px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: var(--glass-blur);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .basemap-option {
            position: relative;
            width: 72px;
            height: 52px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            cursor: pointer;
            overflow: hidden;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            padding-bottom: 4px;
        }
        .basemap-option:hover {
            border-color: rgba(56, 189, 248, 0.4);
            transform: translateY(-2px);
        }
        .basemap-option.active {
            border-color: var(--accent-blue);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.3);
        }
        .basemap-option span {
            font-size: 9px;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.8);
            z-index: 2;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .basemap-option::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.1) 80%);
            z-index: 1;
        }
        .basemap-option.dark-style {
            background: linear-gradient(135deg, #090d16 0%, #1e293b 100%);
        }
        .basemap-option.satellite-style {
            background: linear-gradient(135deg, #134e4a 0%, #065f46 100%);
        }
        .basemap-option.roadmap-style {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .basemap-option.terrain-style {
            background: linear-gradient(135deg, #78350f 0%, #b45309 100%);
        }
        
        .form-group { margin-bottom: 16px; }
        
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text-main);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px 14px;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            color: var(--text-heading);
            outline: none;
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--accent-blue);
            background: rgba(15, 23, 42, 0.6);
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.15);
        }
        
        select option {
            background-color: #0f172a;
            color: var(--text-heading);
        }
        
        textarea { resize: vertical; min-height: 60px; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 5px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: var(--accent-blue);
            color: #090d16;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
        }
        
        .btn-primary:hover { 
            background: var(--accent-blue-hover); 
            transform: translateY(-1px);
            box-shadow: 0 0 20px var(--accent-blue);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--border-color);
            color: var(--text-main);
            text-decoration: none;
        }
        
        .btn-secondary:hover { 
            background: rgba(255, 255, 255, 0.1); 
            color: white;
        }
        
        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 4px 8px;
            font-size: 11px;
            width: auto;
            margin: 0;
            border-radius: 6px;
        }
        
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.35);
            color: white;
        }

        .btn-mini {
            width: auto;
            margin: 0;
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 6px;
        }
        
        h3 {
            color: var(--text-heading);
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 15px;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 5px;
        }

        .alert-success { 
            background: rgba(16, 185, 129, 0.15); 
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0; 
            padding: 10px 14px; 
            border-radius: 8px; 
            margin-bottom: 16px; 
            font-weight: 500; 
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .badge-point { background: rgba(56, 189, 248, 0.12); color: var(--accent-blue); border: 1px solid rgba(56, 189, 248, 0.2); }
        .badge-line { background: rgba(168, 85, 247, 0.12); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.2); }
        .badge-poly { background: rgba(234, 179, 8, 0.12); color: #fef08a; border: 1px solid rgba(234, 179, 8, 0.2); }

        .feature-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 23, 42, 0.25);
            border: 1px solid var(--border-color);
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .feature-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .feature-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-heading);
        }

        .feature-sub {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .metadata-section {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
        }

        .section-desc {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .draw-instruction-banner,
        .geometry-summary {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.34);
            padding: 12px;
            margin-bottom: 14px;
        }
        .draw-instruction-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--accent-blue);
            background: rgba(56, 189, 248, 0.08);
            border-color: rgba(56, 189, 248, 0.24);
            font-size: 12px;
            line-height: 1.45;
        }
        .draw-instruction-banner i {
            margin-top: 2px;
        }
        .geometry-summary {
            display: none;
        }
        .geometry-summary.active {
            display: block;
        }
        .geometry-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
        .geometry-summary-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-heading);
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .geometry-summary-value {
            color: var(--accent-blue);
            font-size: 13px;
            font-weight: 800;
            line-height: 1.45;
        }
        .geometry-summary-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 12px;
        }
        .geometry-raw {
            display: none;
            width: 100%;
            min-height: 92px;
            margin-top: 10px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
        }
        .geometry-raw.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fa-solid fa-layer-group"></i> Interactive Map Editor</h1>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="{{ route('admin.layer-types.index') }}" class="btn btn-secondary" style="width: auto; margin: 0; padding: 8px 16px; background: rgba(56, 189, 248, 0.12); color: var(--accent-blue); border: 1px solid rgba(56, 189, 248, 0.25);">
                <i class="fa-solid fa-gear"></i> Layer Settings
            </a>
            <a href="{{ route('admin.barangays.index') }}" class="btn btn-secondary" style="width: auto; margin: 0; padding: 8px 16px;">
                <i class="fa-solid fa-arrow-left"></i> Barangays List
            </a>
            <a href="/" class="btn btn-secondary" target="_blank" style="width: auto; margin: 0; padding: 8px 16px;">
                <i class="fa-solid fa-eye"></i> View Live Map
            </a>
        </div>
    </div>
    
    <div class="container">
        <div class="form-panel">
            <!-- BARANGAY SELECTOR -->
            <div class="form-group">
                <label for="barangay-select">Active Barangay</label>
                <select id="barangay-select" onchange="switchBarangay(this.value)">
                    @foreach($barangays as $brgy)
                        <option value="{{ $brgy->id }}" {{ $selectedBarangay && $selectedBarangay->id == $brgy->id ? 'selected' : '' }}>
                            {{ $brgy->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- ADD NEW FEATURE FORM -->
            <form action="{{ route('admin.features.store') }}" method="POST" id="featureForm">
                @csrf
                <input type="hidden" name="barangay_id" value="{{ $selectedBarangay ? $selectedBarangay->id : '' }}">

                <h3>1. Add Map Asset</h3>
                
                <div class="form-group">
                    <label for="name">Asset Name *</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Tococ East Health Center" required>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="layer_type">Category</label>
                        <select id="layer_type" name="layer_type" onchange="updateFeatureTypes(this.value)">
                            @foreach($layerTypes->groupBy('category') as $category => $items)
                                <option value="{{ $category }}">{{ ucwords(str_replace('_', ' ', $category)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="feature_type">Feature Type</label>
                        <select id="feature_type" name="feature_type" onchange="handleFeatureTypeChange(this.value)">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </div>
                </div>
                
                <!-- Premium Add Layer Modal Trigger Button -->
                <div style="display: flex; justify-content: flex-end; margin-top: -8px; margin-bottom: 12px;">
                    <button type="button" onclick="openLayerModal()" style="background: none; border: none; font-size: 11px; color: var(--accent-blue); cursor: pointer; display: flex; align-items: center; gap: 4px; font-weight: 600; opacity: 0.8; transition: opacity 0.2s; padding: 0; outline: none;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                        <i class="fa-solid fa-circle-plus" style="font-size: 12px;"></i> Add Layer Type
                    </button>
                </div>



                <!-- Capture Geometry fields (Hidden behind the scenes, filled by map interaction) -->
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                
                <!-- JSON String container for road/zone boundaries -->
                <input type="hidden" id="coordinates" name="coordinates">

                <div id="draw-instruction-banner" class="draw-instruction-banner">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Select a feature type, then draw its location on the map.</span>
                </div>

                <div id="geometry-summary" class="geometry-summary">
                    <div class="geometry-summary-header">
                        <div class="geometry-summary-title">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Geometry Captured</span>
                        </div>
                        <span class="badge" id="geometry-type-badge">Ready</span>
                    </div>
                    <div class="geometry-summary-value" id="geometry-summary-value">No geometry captured yet.</div>
                    <textarea id="geometry-raw" class="geometry-raw" readonly></textarea>
                    <div class="geometry-summary-actions">
                        <button type="button" class="btn btn-secondary btn-mini" onclick="redrawGeometry()" title="Remove current shape and draw again">
                            <i class="fa-solid fa-rotate-left"></i> Redraw
                        </button>
                        <button type="button" class="btn btn-secondary btn-mini" onclick="toggleRawGeometry()" title="Show captured coordinates">
                            <i class="fa-solid fa-code"></i> Coordinates
                        </button>
                        <button type="button" class="btn btn-danger btn-mini" onclick="clearCapturedGeometry()" title="Clear captured geometry">
                            <i class="fa-solid fa-xmark"></i> Clear
                        </button>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="status">Publishing Status</label>
                        <select id="status" name="status">
                            <option value="active">Active</option>
                            <option value="draft">Draft</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Public Map</label>
                        <label style="display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0; font-size:13px; color:var(--text-main); padding:10px 0;">
                            <input type="checkbox" name="is_public" value="1" checked style="width:auto;">
                            Visible to public
                        </label>
                    </div>
                </div>

                <!-- DYNAMIC METADATA FORM -->
                <h3>3. Asset Metadata Details</h3>
                <div id="metadata-fields" class="metadata-section">
                    <!-- Populated dynamically via JS based on selected feature type -->
                </div>

                <button type="submit" class="btn btn-primary" id="save-btn" style="margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-plus"></i> Save Asset to Map
                </button>
            </form>

            <!-- ACTIVE FEATURES LIST -->
            <h3>Existing Assets ({{ $features->count() }})</h3>
            <div style="max-height: 280px; overflow-y: auto;">
                @forelse($features as $feat)
                    <div class="feature-item">
                        <div class="feature-info">
                            <div class="feature-name">{{ $feat->name }}</div>
                            <div class="feature-sub">
                                @php
                                    $matchedType = $layerTypes->firstWhere('code', $feat->feature_type);
                                    $color = $matchedType ? $matchedType->color : '#38bdf8';
                                    $icon = $matchedType ? $matchedType->icon : 'fa-solid fa-location-dot';
                                    $typeName = $matchedType ? $matchedType->name : ucwords(str_replace('_', ' ', $feat->feature_type));
                                @endphp
                                <span class="badge" style="background: {{ $color }}15; color: {{ $color }}; border: 1px solid {{ $color }}35;">
                                    <i class="{{ $icon }}"></i> {{ $typeName }}
                                </span>
                                <span class="badge" style="background: {{ $feat->is_public ? 'rgba(16,185,129,0.12)' : 'rgba(148,163,184,0.12)' }}; color: {{ $feat->is_public ? '#86efac' : '#cbd5e1' }}; border: 1px solid {{ $feat->is_public ? 'rgba(16,185,129,0.25)' : 'rgba(148,163,184,0.2)' }};">
                                    {{ $feat->is_public ? 'Public' : 'Hidden' }}
                                </span>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid rgba(255,255,255,0.08);">
                                    {{ ucfirst($feat->status ?? 'active') }}
                                </span>
                            </div>
                        </div>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <button type="button" class="btn btn-secondary btn-mini" onclick='openFeatureEditModal(@json($feat))'>
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <form action="{{ route('admin.features.toggle-public', $feat) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary btn-mini" title="{{ $feat->is_public ? 'Hide from public' : 'Publish to public' }}">
                                    <i class="fa-solid {{ $feat->is_public ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.features.destroy', $feat) }}" method="POST" onsubmit="return confirm('Delete this asset from map?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px;">
                        No assets found in this Barangay yet. Draw on the map to add some!
                    </div>
                @endforelse
            </div>
        </div>
        
        <div class="map-panel">
            <div id="map"></div>
            <div class="measure-widget" aria-label="Measurement tools">
                <button type="button" id="measure-toggle" class="measure-action" onclick="toggleMeasureMode()" title="Measure distance">
                    <i class="fa-solid fa-ruler"></i>
                    <span>Measure</span>
                </button>
                <div>
                    <div class="measure-readout" id="measure-distance">0 m</div>
                    <div class="measure-hint" id="measure-hint">Click ruler, then click points</div>
                </div>
                <button type="button" id="measure-undo" class="measure-action icon-only" onclick="undoMeasurePoint()" title="Undo last point" disabled>
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
                <button type="button" class="measure-action icon-only" onclick="clearMeasure()" title="Clear measurement">
                    <i class="fa-solid fa-eraser"></i>
                </button>
            </div>
            
            <!-- Basemap Selector Widget -->
            <div class="basemap-widget" aria-label="Basemap selector">
                <div class="basemap-option dark-style active" onclick="changeBasemap('dark', this)" title="Dark Theme">
                    <i class="fa-solid fa-moon" style="font-size: 14px; color: #bae6fd; margin-bottom: 2px; z-index: 2; text-shadow: 0 0 8px rgba(56,189,248,0.4);"></i>
                    <span>Dark</span>
                </div>
                <div class="basemap-option satellite-style" onclick="changeBasemap('satellite', this)" title="Satellite View">
                    <i class="fa-solid fa-earth-asia" style="font-size: 14px; color: #a7f3d0; margin-bottom: 2px; z-index: 2; text-shadow: 0 0 8px rgba(16,185,129,0.4);"></i>
                    <span>Satellite</span>
                </div>
                <div class="basemap-option roadmap-style" onclick="changeBasemap('roadmap', this)" title="Street View">
                    <i class="fa-solid fa-map" style="font-size: 14px; color: #fed7aa; margin-bottom: 2px; z-index: 2; text-shadow: 0 0 8px rgba(245,158,11,0.4);"></i>
                    <span>Roadmap</span>
                </div>
                <div class="basemap-option terrain-style" onclick="changeBasemap('terrain', this)" title="Terrain Map">
                    <i class="fa-solid fa-mountain" style="font-size: 14px; color: #ddd6fe; margin-bottom: 2px; z-index: 2; text-shadow: 0 0 8px rgba(139,92,246,0.4);"></i>
                    <span>Terrain</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        @if(session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 2800,
                timerProgressBar: true,
                background: '#0f172a',
                color: '#f8fafc'
            });
        @endif

        @if(session('error'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: @json(session('error')),
                showConfirmButton: false,
                timer: 3600,
                timerProgressBar: true,
                background: '#0f172a',
                color: '#f8fafc'
            });
        @endif

        const activeBarangay = @json($selectedBarangay);
        const activeFeatures = @json($features);
        const dbLayerTypes = @json($layerTypes);
        
        // Initial setup for Map centering
        const centerLat = activeBarangay && activeBarangay.latitude ? activeBarangay.latitude : 15.8287;
        const centerLng = activeBarangay && activeBarangay.longitude ? activeBarangay.longitude : 120.4173;
        
        const map = L.map('map', { maxZoom: 20 }).setView([centerLat, centerLng], 14);

        // Premium basemaps dictionary matching dashboard style
        const basemaps = {
            dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap &copy; CARTO',
                maxZoom: 20,
                maxNativeZoom: 20
            }),
            roadmap: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 20,
                maxNativeZoom: 19
            }),
            satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                maxZoom: 20,
                maxNativeZoom: 18
            }),
            terrain: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                attribution: 'Map data: &copy; OpenStreetMap contributors, SRTM | Map style: &copy; OpenTopoMap (CC-BY-SA)',
                maxZoom: 20,
                maxNativeZoom: 17
            })
        };

        let currentBasemap = 'dark';
        basemaps[currentBasemap].addTo(map);

        function changeBasemap(type, element) {
            if (!basemaps[type]) return;

            map.removeLayer(basemaps[currentBasemap]);
            basemaps[type].addTo(map);
            currentBasemap = type;

            document.querySelectorAll('.basemap-option').forEach(opt => opt.classList.remove('active'));
            if (element) {
                element.classList.add('active');
            } else {
                const activeOpt = document.querySelector(`.basemap-option[onclick*="${type}"]`);
                if (activeOpt) activeOpt.classList.add('active');
            }
        }

        const drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        const measureItems = L.layerGroup().addTo(map);
        let measureMode = false;
        let measurePoints = [];
        let measurePointMarkers = [];
        let measureLine = null;
        let measurePreviewLine = null;

        // RENDER BARANGAY BOUNDARY IF EXISTS
        let mapBounds = [];

        if (activeBarangay && activeBarangay.boundary && activeBarangay.boundary.length > 0) {
            const boundaryPoly = L.polygon(activeBarangay.boundary, {
                color: 'rgba(56, 189, 248, 0.4)',
                fillColor: 'rgba(56, 189, 248, 0.1)',
                fillOpacity: 0.05,
                weight: 2,
                dashArray: '5, 5'
            }).addTo(map);
            
            // Collect boundary coords
            activeBarangay.boundary.forEach(coord => mapBounds.push(coord));

            // Put a Label
            boundaryPoly.bindTooltip(activeBarangay.name + " Boundary", {
                permanent: false,
                direction: "center"
            });
        }

        // RENDER EXISTING MAP FEATURES
        activeFeatures.forEach(feat => {
            if (feat.latitude && feat.longitude) {
                // Determine icon and color dynamically based on database layer types
                const config = dbLayerTypes.find(t => t.code === feat.feature_type) || {};
                const color = config.color || '#38bdf8';
                const iconClass = config.icon || 'fa-solid fa-location-dot';
                
                const customIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color: ${color}; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.5);"><i class="${iconClass}" style="font-size: 11px;"></i></div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });
                
                L.marker([feat.latitude, feat.longitude], { icon: customIcon })
                    .addTo(map)
                    .bindPopup(`<strong>${feat.name}</strong><br><span style="font-size: 11px; color:#94a3b8;">${config.name || feat.feature_type}</span>`);
                
                // Collect marker coords
                mapBounds.push([parseFloat(feat.latitude), parseFloat(feat.longitude)]);
            } else if (feat.coordinates && feat.coordinates.length > 0) {
                const config = dbLayerTypes.find(t => t.code === feat.feature_type) || {};
                const color = config.color || '#8b5cf6';
                
                const coords = typeof feat.coordinates === 'string' ? JSON.parse(feat.coordinates) : feat.coordinates;
                // Collect coordinates
                coords.forEach(coord => mapBounds.push(coord));

                if (feat.feature_type === 'road_network') {
                    L.polyline(coords, {
                        color: color,
                        weight: 4,
                        opacity: 0.8
                    }).addTo(map).bindPopup(`<strong>${feat.name}</strong><br><span style="font-size: 11px; color:#94a3b8;">${config.name || 'Road Network'}</span><br><span style="font-size: 11px; color:#38bdf8;">Length: ${formatDistance(totalDistance(coords.map(coord => L.latLng(coord[0], coord[1]))))}</span>`);
                } else if (feat.feature_type === 'population_density') {
                    L.polygon(coords, {
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.15,
                        weight: 2,
                        dashArray: '3, 3'
                    }).addTo(map).bindPopup(`<strong>${feat.name}</strong><br><span style="font-size: 11px; color:#94a3b8;">${config.name || 'Density Zone'}</span>`);
                } else {
                    // Fallback for custom vector lines/polygons
                    const geomType = config.geom_type || 'polyline';
                    if (geomType === 'polyline') {
                        L.polyline(coords, { color: color, weight: 4, opacity: 0.8 })
                            .addTo(map).bindPopup(`<strong>${feat.name}</strong><br><span style="font-size: 11px; color:#94a3b8;">${config.name}</span><br><span style="font-size: 11px; color:#38bdf8;">Length: ${formatDistance(totalDistance(coords.map(coord => L.latLng(coord[0], coord[1]))))}</span>`);
                    } else {
                        L.polygon(coords, { color: color, fillColor: color, fillOpacity: 0.15, weight: 2 })
                            .addTo(map).bindPopup(`<strong>${feat.name}</strong><br><span style="font-size: 11px; color:#94a3b8;">${config.name}</span>`);
                    }
                }
            }
        });

        // AUTO-CENTER & ZOOM SO EVERY SINGLE RENDERED ASSET IS PERFECTLY VISIBLE
        if (mapBounds.length > 0) {
            map.fitBounds(mapBounds, { padding: [50, 50] });
        }

        // LEAFLET DRAW CONTROLS
        const drawControl = new L.Control.Draw({
            draw: {
                marker: true,
                polyline: {
                    shapeOptions: {
                        color: '#c084fc',
                        weight: 4
                    }
                },
                polygon: {
                    allowIntersection: false,
                    showArea: true,
                    shapeOptions: {
                        color: '#fbbf24',
                        fillColor: '#fbbf24',
                        fillOpacity: 0.15,
                        weight: 2
                    }
                },
                rectangle: false,
                circle: false,
                circlemarker: false
            },
            edit: false
        });
        map.addControl(drawControl);

        let activeDrawLayer = null;

        function formatDistance(meters) {
            if (!Number.isFinite(meters) || meters <= 0) return '0 m';

            if (meters >= 1000) {
                return `${(meters / 1000).toLocaleString('en-US', {
                    maximumFractionDigits: 2
                })} km`;
            }

            return `${meters.toLocaleString('en-US', {
                maximumFractionDigits: 1
            })} m`;
        }

        function totalDistance(points) {
            let distance = 0;

            for (let index = 1; index < points.length; index++) {
                distance += points[index - 1].distanceTo(points[index]);
            }

            return distance;
        }

        function approximatePolygonArea(points) {
            if (!Array.isArray(points) || points.length < 3) return 0;

            const earthRadius = 6378137;
            const averageLatitude = points.reduce((sum, point) => sum + point.lat, 0) / points.length;
            const cosLatitude = Math.cos(averageLatitude * Math.PI / 180);
            const projected = points.map(point => ({
                x: point.lng * Math.PI / 180 * earthRadius * cosLatitude,
                y: point.lat * Math.PI / 180 * earthRadius
            }));

            let area = 0;
            for (let index = 0; index < projected.length; index++) {
                const next = (index + 1) % projected.length;
                area += projected[index].x * projected[next].y;
                area -= projected[index].y * projected[next].x;
            }

            return Math.abs(area) / 20000;
        }

        function formatArea(hectares) {
            if (!Number.isFinite(hectares) || hectares <= 0) return '0 ha';

            return `${hectares.toLocaleString('en-US', {
                maximumFractionDigits: 2
            })} ha`;
        }

        function updateGeometrySummary(type, coords = []) {
            const panel = document.getElementById('geometry-summary');
            const value = document.getElementById('geometry-summary-value');
            const badge = document.getElementById('geometry-type-badge');
            const raw = document.getElementById('geometry-raw');

            if (!panel || !value || !badge || !raw) return;

            panel.classList.add('active');

            if (type === 'marker') {
                const lat = document.getElementById('latitude').value;
                const lng = document.getElementById('longitude').value;
                badge.innerText = 'Point';
                value.innerText = `Point captured: ${lat}, ${lng}`;
                raw.value = JSON.stringify({ latitude: lat, longitude: lng }, null, 2);
                return;
            }

            const points = coords.map(coord => L.latLng(coord[0], coord[1]));

            if (type === 'polyline') {
                badge.innerText = 'Line';
                value.innerText = `Line captured: ${coords.length} points • ${formatDistance(totalDistance(points))}`;
                raw.value = JSON.stringify(coords, null, 2);
                return;
            }

            badge.innerText = 'Area';
            value.innerText = `Area captured: ${coords.length} points • ${formatArea(approximatePolygonArea(points))} approx.`;
            raw.value = JSON.stringify(coords, null, 2);
        }

        function resetGeometrySummary() {
            const panel = document.getElementById('geometry-summary');
            const raw = document.getElementById('geometry-raw');

            if (panel) panel.classList.remove('active');
            if (raw) {
                raw.classList.remove('active');
                raw.value = '';
            }
        }

        function clearCapturedGeometry() {
            if (activeDrawLayer) {
                drawnItems.removeLayer(activeDrawLayer);
                activeDrawLayer = null;
            }

            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            document.getElementById('coordinates').value = '';
            resetGeometrySummary();

            const selectedType = document.getElementById('feature_type').value;
            if (selectedType) handleFeatureTypeChange(selectedType);
        }

        function redrawGeometry() {
            clearCapturedGeometry();
        }

        function toggleRawGeometry() {
            const raw = document.getElementById('geometry-raw');
            if (raw) raw.classList.toggle('active');
        }

        function updateMeasureReadout(extraPoint = null) {
            const points = extraPoint ? [...measurePoints, extraPoint] : measurePoints;
            const distance = totalDistance(points);
            const distanceEl = document.getElementById('measure-distance');
            const hintEl = document.getElementById('measure-hint');
            const undoBtn = document.getElementById('measure-undo');

            if (distanceEl) distanceEl.innerText = formatDistance(distance);
            if (hintEl) {
                hintEl.innerText = measureMode
                    ? (measurePoints.length ? 'Click next point, Esc to clear' : 'Click first point on map')
                    : 'Click ruler, then click points';
            }
            if (undoBtn) undoBtn.disabled = measurePoints.length === 0;
        }

        function setMeasureMode(active) {
            measureMode = active;
            const btn = document.getElementById('measure-toggle');
            const widget = document.querySelector('.measure-widget');
            const mapEl = document.getElementById('map');
            const buttonLabel = btn ? btn.querySelector('span') : null;

            if (btn) btn.classList.toggle('active', measureMode);
            if (widget) widget.classList.toggle('is-active', measureMode);
            if (buttonLabel) buttonLabel.innerText = measureMode ? 'Measuring' : 'Measure';
            if (mapEl) mapEl.classList.toggle('measure-mode-active', measureMode);
            if (measureMode) {
                map.doubleClickZoom.disable();
            } else {
                map.doubleClickZoom.enable();
                if (measurePreviewLine) {
                    measureItems.removeLayer(measurePreviewLine);
                    measurePreviewLine = null;
                }
            }

            updateMeasureReadout();
        }

        function toggleMeasureMode() {
            setMeasureMode(!measureMode);
        }

        function clearMeasure() {
            measureItems.clearLayers();
            measurePoints = [];
            measurePointMarkers = [];
            measureLine = null;
            measurePreviewLine = null;
            setMeasureMode(false);
            updateMeasureReadout();
        }

        function createMeasureMarker(latlng, pointNumber) {
            return L.circleMarker(latlng, {
                radius: 5,
                color: '#0f172a',
                weight: 2,
                fillColor: '#38bdf8',
                fillOpacity: 1
            }).bindTooltip(String(pointNumber), {
                permanent: true,
                direction: 'center',
                className: 'measure-point-label'
            });
        }

        function redrawMeasureGeometry() {
            measurePointMarkers.forEach(marker => measureItems.removeLayer(marker));
            measurePointMarkers = measurePoints.map((point, index) => createMeasureMarker(point, index + 1));
            measurePointMarkers.forEach(marker => measureItems.addLayer(marker));

            if (measurePoints.length === 0) {
                if (measureLine) {
                    measureItems.removeLayer(measureLine);
                    measureLine = null;
                }
            } else if (!measureLine) {
                measureLine = L.polyline(measurePoints, {
                    color: '#38bdf8',
                    weight: 3,
                    opacity: 0.95
                });
                measureItems.addLayer(measureLine);
            } else {
                measureLine.setLatLngs(measurePoints);
            }

            if (measurePreviewLine) {
                measureItems.removeLayer(measurePreviewLine);
                measurePreviewLine = null;
            }

            updateMeasureReadout();
        }

        function addMeasurePoint(latlng) {
            measurePoints.push(latlng);
            redrawMeasureGeometry();
        }

        function undoMeasurePoint() {
            if (measurePoints.length === 0) return;

            measurePoints.pop();
            redrawMeasureGeometry();

            if (!measureMode) setMeasureMode(true);
        }

        function isMeasureIgnoredTarget(target) {
            if (!target || typeof target.closest !== 'function') return false;

            return target.closest('.leaflet-control')
                || target.closest('.leaflet-popup')
                || target.closest('.leaflet-tooltip');
        }

        function measureLatLngFromDomEvent(event) {
            return map.containerPointToLatLng(map.mouseEventToContainerPoint(event));
        }

        const mapContainer = map.getContainer();

        mapContainer.addEventListener('click', function(event) {
            if (!measureMode || isMeasureIgnoredTarget(event.target)) return;

            event.preventDefault();
            event.stopPropagation();
            addMeasurePoint(measureLatLngFromDomEvent(event));
        }, true);

        mapContainer.addEventListener('mousemove', function(event) {
            if (!measureMode || measurePoints.length === 0 || isMeasureIgnoredTarget(event.target)) return;

            const previewLatLng = measureLatLngFromDomEvent(event);
            const previewPoints = [measurePoints[measurePoints.length - 1], previewLatLng];

            if (!measurePreviewLine) {
                measurePreviewLine = L.polyline(previewPoints, {
                    color: '#38bdf8',
                    weight: 2,
                    opacity: 0.45,
                    dashArray: '5, 5'
                });
                measureItems.addLayer(measurePreviewLine);
            } else {
                measurePreviewLine.setLatLngs(previewPoints);
            }

            updateMeasureReadout(previewLatLng);
        }, true);

        map.on('dblclick', function() {
            if (measureMode) setMeasureMode(false);
        });

        map.on(L.Draw.Event.DRAWSTART, function() {
            setMeasureMode(false);
        });

        document.addEventListener('keydown', function(event) {
            if (!measureMode && measurePoints.length === 0) return;

            if (event.key === 'Backspace' || (event.ctrlKey && event.key.toLowerCase() === 'z')) {
                event.preventDefault();
                undoMeasurePoint();
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                clearMeasure();
            }
        });

        // ON DRAW EVENT
        map.on(L.Draw.Event.CREATED, function (event) {
            setMeasureMode(false);

            if (activeDrawLayer) drawnItems.removeLayer(activeDrawLayer);
            
            activeDrawLayer = event.layer;
            drawnItems.addLayer(activeDrawLayer);
            
            const type = event.layerType;
            
            if (type === 'marker') {
                const latlng = activeDrawLayer.getLatLng();
                document.getElementById('latitude').value = latlng.lat.toFixed(7);
                document.getElementById('longitude').value = latlng.lng.toFixed(7);
                document.getElementById('coordinates').value = '';
                updateGeometrySummary(type);
            } else if (type === 'polyline') {
                const latlngs = activeDrawLayer.getLatLngs();
                const coords = latlngs.map(ll => [ll.lat, ll.lng]);
                document.getElementById('coordinates').value = JSON.stringify(coords);
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                updateGeometrySummary(type, coords);
            } else if (type === 'polygon') {
                const latlngs = activeDrawLayer.getLatLngs()[0];
                const coords = latlngs.map(ll => [ll.lat, ll.lng]);
                document.getElementById('coordinates').value = JSON.stringify(coords);
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                updateGeometrySummary(type, coords);
            }

            // Update Draw Guide Banner to Success Green instantly!
            const banner = document.getElementById('draw-instruction-banner');
            if (banner) {
                banner.style.background = 'rgba(34, 197, 94, 0.08)';
                banner.style.borderColor = 'rgba(34, 197, 94, 0.25)';
                banner.style.color = '#86efac';
                
                const span = banner.querySelector('span');
                const icon = banner.querySelector('i');
                icon.className = 'fa-solid fa-circle-check';
                
                let geomLabel = 'Location coordinates';
                if (type === 'polyline') geomLabel = 'Road path';
                else if (type === 'polygon') geomLabel = 'Area boundary';
                
                span.innerHTML = `<strong>Success!</strong> ${geomLabel} captured. Click <strong>"Save Asset"</strong> below to publish!`;
            }
        });

        // SWITCH BARANGAY REDIRECT
        function switchBarangay(id) {
            window.location.href = "{{ route('admin.features.index') }}?barangay_id=" + id;
        }

        // GENERATE DYNAMIC FEATURE TYPES DICTIONARY FROM DATABASE
        const featureTypes = {};

        dbLayerTypes.forEach(type => {
            if (!featureTypes[type.category]) featureTypes[type.category] = [];

            let suffix = '';
            if (type.geom_type === 'polyline') suffix = ' (Polyline)';
            else if (type.geom_type === 'polygon') suffix = ' (Polygon)';

            featureTypes[type.category].push({
                value: type.code,
                label: type.name + suffix
            });
        });

        function updateFeatureTypes(category) {
            const select = document.getElementById('feature_type');
            select.innerHTML = '';
            
            const options = featureTypes[category] || [];
            options.forEach(opt => {
                const el = document.createElement('option');
                el.value = opt.value;
                el.textContent = opt.label;
                select.appendChild(el);
            });

            if (options.length > 0) {
                handleFeatureTypeChange(options[0].value);
            }
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function metadataSchemaFor(config) {
            if (Array.isArray(config.metadata_schema) && config.metadata_schema.length > 0) {
                return config.metadata_schema;
            }

            return [
                {
                    key: 'status',
                    label: 'Status',
                    type: 'select',
                    options: ['Operational', 'Needs Maintenance', 'Inactive']
                },
                {
                    key: 'description',
                    label: config.geom_type === 'polyline' ? 'Line Details' : (config.geom_type === 'polygon' ? 'Area Details' : 'Asset Details'),
                    type: 'textarea',
                    placeholder: 'Notes, condition, capacity, or other official details'
                }
            ];
        }

        function metadataInputHtml(field) {
            const key = escapeHtml(field.key);
            const label = escapeHtml(field.label || field.key);
            const placeholder = escapeHtml(field.placeholder || '');
            const required = field.required ? 'required' : '';
            const requiredMark = field.required ? ' *' : '';

            if (field.type === 'select') {
                const options = Array.isArray(field.options) ? field.options : [];
                return `
                    <div class="form-group">
                        <label>${label}${requiredMark}</label>
                        <select name="metadata[${key}]" ${required}>
                            ${options.map(option => `<option value="${escapeHtml(option)}">${escapeHtml(option)}</option>`).join('')}
                        </select>
                    </div>
                `;
            }

            if (field.type === 'textarea') {
                return `
                    <div class="form-group">
                        <label>${label}${requiredMark}</label>
                        <textarea name="metadata[${key}]" rows="3" placeholder="${placeholder}" ${required}></textarea>
                    </div>
                `;
            }

            if (field.type === 'boolean') {
                return `
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0;">
                            <input type="hidden" name="metadata[${key}]" value="0">
                            <input type="checkbox" name="metadata[${key}]" value="1" style="width:auto;">
                            ${label}${requiredMark}
                        </label>
                    </div>
                `;
            }

            const inputType = field.type === 'number' ? 'number' : (field.type === 'date' ? 'date' : 'text');
            const step = field.type === 'number' ? ' step="any"' : '';

            return `
                <div class="form-group">
                    <label>${label}${requiredMark}</label>
                    <input type="${inputType}" name="metadata[${key}]" placeholder="${placeholder}" ${step} ${required}>
                </div>
            `;
        }

        function renderMetadataFields(config) {
            const schema = metadataSchemaFor(config);
            const rows = [];

            for (let i = 0; i < schema.length; i += 2) {
                const first = metadataInputHtml(schema[i]);
                const second = schema[i + 1] ? metadataInputHtml(schema[i + 1]) : '';
                rows.push(second ? `<div class="grid-2">${first}${second}</div>` : first);
            }

            const description = config.description
                ? `<div class="section-desc">${escapeHtml(config.description)}</div>`
                : '<div class="section-desc"><strong>Standard Metadata Fields:</strong> These fields are controlled by the selected layer type.</div>';

            return `${description}${rows.join('')}`;
        }

        // DYNAMIC METADATA UI FIELDS DEPENDING ON LAYER TYPE SCHEMA
        function handleFeatureTypeChange(type) {
            const container = document.getElementById('metadata-fields');
            container.innerHTML = '';

            // DYNAMICALLY UPDATE THE DRAW INSTRUCTION BANNER
            const config = dbLayerTypes.find(t => t.code === type) || {};
            const geomType = config.geom_type || 'point';
            const color = config.color || '#3b82f6';
            const banner = document.getElementById('draw-instruction-banner');
            
            if (banner) {
                // Apply theme color border and background to matching config color
                banner.style.background = color + '15';
                banner.style.borderColor = color + '35';
                banner.style.color = color;
                
                const span = banner.querySelector('span');
                const icon = banner.querySelector('i');
                
                if (geomType === 'point') {
                    icon.className = 'fa-solid fa-location-dot';
                    span.innerHTML = `Use the <strong>Marker tool</strong> (pin icon on the map toolbar) and click on the map to set the <strong>${config.name}</strong> location.`;
                } else if (geomType === 'polyline') {
                    icon.className = 'fa-solid fa-route';
                    span.innerHTML = `Use the <strong>Line tool</strong> (diagonal line icon on the map toolbar) to draw the <strong>${config.name}</strong> road/route.`;
                } else if (geomType === 'polygon') {
                    icon.className = 'fa-solid fa-draw-polygon';
                    span.innerHTML = `Use the <strong>Polygon tool</strong> (pentagon icon on the map toolbar) to draw the <strong>${config.name}</strong> boundary/area.`;
                }
            }

            container.innerHTML = renderMetadataFields(config);
        }

        // MODAL OPEN/CLOSE FUNCTIONS
        function openLayerModal() {
            const modal = document.getElementById('layer-modal');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.querySelector('.modal-content-card').style.transform = 'scale(1)';
            }, 50);
        }

        function closeLayerModal() {
            const modal = document.getElementById('layer-modal');
            modal.style.opacity = '0';
            modal.querySelector('.modal-content-card').style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function openFeatureEditModal(feature) {
            const modal = document.getElementById('feature-edit-modal');
            const form = document.getElementById('feature-edit-form');
            const layerType = dbLayerTypes.find(type => type.code === feature.feature_type) || dbLayerTypes[0] || {};

            form.action = `/admin/features/${feature.id}`;
            document.getElementById('edit-feature-name').value = feature.name || '';
            document.getElementById('edit-feature-type').value = feature.feature_type || '';
            document.getElementById('edit-feature-status').value = feature.status || 'active';
            document.getElementById('edit-feature-public').checked = Boolean(feature.is_public);
            document.getElementById('edit-feature-latitude').value = feature.latitude || '';
            document.getElementById('edit-feature-longitude').value = feature.longitude || '';
            document.getElementById('edit-feature-coordinates').value = feature.coordinates ? JSON.stringify(feature.coordinates, null, 2) : '';
            document.getElementById('edit-feature-metadata').value = feature.metadata ? JSON.stringify(feature.metadata, null, 2) : '{}';
            document.getElementById('edit-feature-geom-note').textContent = `Expected geometry: ${layerType.geom_type || 'point'}`;

            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.querySelector('.modal-content-card').style.transform = 'scale(1)';
            }, 50);
        }

        function closeFeatureEditModal() {
            const modal = document.getElementById('feature-edit-modal');
            modal.style.opacity = '0';
            modal.querySelector('.modal-content-card').style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 250);
        }

        // HANDLE MODAL VISUAL ICON PICKER & COLOR LIVE PREVIEW
        document.addEventListener('DOMContentLoaded', function() {
            const modalIconInput = document.getElementById('modal-layer-icon');
            const modalColorInput = document.getElementById('modal-layer-color');
            const modalPreview = document.getElementById('modal-marker-preview');
            const modalIcon = document.getElementById('modal-marker-icon');
            const modalIconOptions = document.querySelectorAll('.modal-icon-option');

            // Handle Icon Selection Clicking in Modal
            modalIconOptions.forEach(opt => {
                opt.addEventListener('click', function() {
                    modalIconOptions.forEach(o => o.classList.remove('active'));
                    this.classList.add('active');
                    const selectedIcon = this.getAttribute('data-icon');
                    modalIconInput.value = selectedIcon;
                    modalIcon.className = selectedIcon;
                });
            });

            // Handle Live Theme Color Updating in Modal
            modalColorInput.addEventListener('input', function() {
                modalPreview.style.backgroundColor = this.value;
            });

            // HANDLE MODAL FORM AJAX SUBMISSION
            const ajaxForm = document.getElementById('ajax-layer-type-form');
            if (ajaxForm) {
                ajaxForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
                    
                    const formData = new FormData(this);
                    
                    fetch("{{ route('admin.layer-types.store') }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Failed to save layer');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.layer_type) {
                            // 1. Add to global dbLayerTypes dictionary
                            dbLayerTypes.push(data.layer_type);
                            
                            // 2. Refresh Feature Types dropdown
                            const activeCat = document.getElementById('layer_type').value;
                            updateFeatureTypes(activeCat);
                            
                            // 3. Set Feature Type to the newly created layer type code!
                            document.getElementById('feature_type').value = data.layer_type.code;
                            handleFeatureTypeChange(data.layer_type.code);
                            
                            // 4. Show success banner
                            const successBanner = document.createElement('div');
                            successBanner.className = 'alert-success';
                            successBanner.style.marginBottom = '16px';
                            successBanner.innerHTML = `<i class="fa-solid fa-circle-check"></i> Layer "${data.layer_type.name}" added successfully!`;
                            
                            const panel = document.querySelector('.form-panel');
                            panel.insertBefore(successBanner, panel.firstChild);
                            setTimeout(() => successBanner.remove(), 4000);
                            
                            // 5. Reset and close modal
                            ajaxForm.reset();
                            // Reset icon active state
                            modalIconOptions.forEach(o => o.classList.remove('active'));
                            modalIconOptions[0].classList.add('active');
                            modalIconInput.value = 'fa-solid fa-location-dot';
                            modalIcon.className = 'fa-solid fa-location-dot';
                            modalPreview.style.backgroundColor = '#3b82f6';
                            
                            closeLayerModal();
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Error: Failed to create new layer type. Please try again.');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Create Layer Type';
                    });
                });
            }
        });

        // Initialize Form triggers
        updateFeatureTypes(document.getElementById('layer_type').value);
    </script>

    <div id="feature-edit-modal" style="display: none; position: fixed; inset: 0; background: rgba(9, 13, 22, 0.86); backdrop-filter: blur(8px); z-index: 9998; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.25s ease; padding: 20px;">
        <div class="modal-content-card" style="background: #0f172a; border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 620px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); transform: scale(0.95); transition: transform 0.25s ease;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid var(--border-color);">
                <h3 style="margin:0; border:0; padding:0; font-size:16px;"><i class="fa-solid fa-pen-to-square" style="color:var(--accent-blue);"></i> Edit Map Feature</h3>
                <button type="button" onclick="closeFeatureEditModal()" style="background:none; border:0; color:var(--text-muted); cursor:pointer; font-size:18px;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="feature-edit-form" method="POST" style="padding:20px; margin:0;">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="edit-feature-name">Feature Name</label>
                    <input id="edit-feature-name" name="name" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="edit-feature-type">Layer Type</label>
                        <select id="edit-feature-type" name="feature_type" required>
                            @foreach($layerTypes as $type)
                                <option value="{{ $type->code }}">{{ $type->name }} ({{ $type->geom_type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-feature-status">Status</label>
                        <select id="edit-feature-status" name="status" required>
                            <option value="active">Active</option>
                            <option value="draft">Draft</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0;">
                        <input id="edit-feature-public" type="checkbox" name="is_public" value="1" style="width:auto;">
                        Visible on public map
                    </label>
                </div>
                <div id="edit-feature-geom-note" class="section-desc"></div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="edit-feature-latitude">Latitude</label>
                        <input id="edit-feature-latitude" name="latitude" type="number" step="0.0000001">
                    </div>
                    <div class="form-group">
                        <label for="edit-feature-longitude">Longitude</label>
                        <input id="edit-feature-longitude" name="longitude" type="number" step="0.0000001">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit-feature-coordinates">Line/Polygon Coordinates JSON</label>
                    <textarea id="edit-feature-coordinates" name="coordinates" rows="5" placeholder="[[15.8,120.4],[15.81,120.41]]"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit-feature-metadata">Metadata JSON</label>
                    <textarea id="edit-feature-metadata" name="metadata_json" rows="6" placeholder='{"status":"Operational"}'></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Feature Changes</button>
            </form>
        </div>
    </div>

    <!-- Premium Modal for Adding Layer Types directly inside Map Editor -->
    <div id="layer-modal" style="display: none; position: fixed; inset: 0; background: rgba(9, 13, 22, 0.85); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; padding: 20px;">
        <div class="modal-content-card" style="background: #0f172a; border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); overflow: hidden; transform: scale(0.95); transition: transform 0.3s ease;">
            <!-- Modal Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border-color); background: rgba(15, 23, 42, 0.6);">
                <h3 style="margin: 0; font-size: 16px; color: var(--text-heading); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-square-plus" style="color: var(--accent-blue);"></i> Add New Layer Type
                </h3>
                <button type="button" onclick="closeLayerModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 18px; transition: color 0.2s; outline: none;" onmouseover="this.style.color='white'" onmouseout="this.style.color='var(--text-muted)'">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <!-- Modal Form -->
            <form id="ajax-layer-type-form" style="padding: 20px; margin: 0;">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Layer Name *</label>
                    <input type="text" id="modal-layer-name" name="name" placeholder="e.g. Evacuation Center" required style="width: 100%; padding: 10px 12px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; color: var(--text-heading); outline: none;">
                </div>

                <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Category Group</label>
                        <select id="modal-layer-category" name="category" style="width: 100%; padding: 10px 12px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; color: var(--text-heading); outline: none; height: 38px;">
                            <option value="critical_facilities">Critical Facilities</option>
                            <option value="drrm">DRRM Group</option>
                            <option value="infrastructure">Infrastructure</option>
                            <option value="population">Population Data</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Geometry Type</label>
                        <select id="modal-layer-geom" name="geom_type" style="width: 100%; padding: 10px 12px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; color: var(--text-heading); outline: none; height: 38px;">
                            <option value="point">Point (Pin Location)</option>
                            <option value="polyline">Line (Road Path)</option>
                            <option value="polygon">Area (Zone Boundary)</option>
                        </select>
                    </div>
                </div>

                <!-- Visual Marker Selector Grid -->
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Select Marker Icon *</label>
                    <input type="hidden" id="modal-layer-icon" name="icon" value="fa-solid fa-location-dot">
                    
                    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px; max-height: 110px; overflow-y: auto;" id="modal-icon-grid">
                        @php
                            $presets = [
                                'fa-solid fa-location-dot' => 'Standard Pin',
                                'fa-solid fa-building-flag' => 'Barangay Hall',
                                'fa-solid fa-house-medical' => 'Health Center',
                                'fa-solid fa-building' => 'Building',
                                'fa-solid fa-basketball' => 'Covered Court',
                                'fa-solid fa-tent' => 'Evac Center',
                                'fa-solid fa-shield-halved' => 'Police Post',
                                'fa-solid fa-users-gear' => 'Responder',
                                'fa-solid fa-people-roof' => 'Household',
                                'fa-solid fa-faucet-drip' => 'Water Utility',
                                'fa-solid fa-bolt' => 'Power Station',
                                'fa-solid fa-fire-extinguisher' => 'Fire Hydrant',
                                'fa-solid fa-school' => 'School',
                                'fa-solid fa-tower-broadcast' => 'Cell Tower',
                                'fa-solid fa-truck-medical' => 'Ambulance'
                            ];
                        @endphp
                        @foreach($presets as $class => $label)
                            <div class="modal-icon-option {{ $class === 'fa-solid fa-location-dot' ? 'active' : '' }}" 
                                 data-icon="{{ $class }}" 
                                 title="{{ $label }}"
                                 style="display: flex; flex-direction: column; align-items: center; justify-content: center; aspect-ratio: 1; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02); cursor: pointer; transition: all 0.2s ease;">
                                <i class="{{ $class }}" style="font-size: 13px; color: var(--text-muted); margin-bottom: 2px;"></i>
                                <span style="font-size: 7.5px; color: var(--text-muted); text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; padding: 0 1px;">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 18px; display: grid; grid-template-columns: 2fr 1fr; gap: 12px; align-items: center; margin-top: 0;">
                    <div style="margin: 0;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-main); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Theme Color</label>
                        <input type="color" id="modal-layer-color" name="color" value="#3b82f6" style="width: 100%; height: 38px; padding: 2px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; outline: none;">
                    </div>
                    
                    <div style="margin: 0;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Preview</label>
                        <div style="display: flex; justify-content: center; align-items: center; height: 38px; background: rgba(15, 23, 42, 0.2); border: 1px dashed var(--border-color); border-radius: 8px;">
                            <div id="modal-marker-preview" style="background-color: #3b82f6; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: all 0.2s ease;">
                                <i class="fa-solid fa-location-dot" id="modal-marker-icon" style="font-size: 10px;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; background: var(--accent-blue); color: #090d16; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); transition: all 0.2s ease;">
                    <i class="fa-solid fa-plus"></i> Create Layer Type
                </button>
            </form>
        </div>
    </div>

    <style>
        .modal-icon-option {
            transition: all 0.2s ease;
        }
        .modal-icon-option:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }
        .modal-icon-option:hover i {
            color: var(--text-heading) !important;
        }
        .modal-icon-option.active {
            background: rgba(56, 189, 248, 0.12) !important;
            border-color: var(--accent-blue) !important;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.15);
        }
        .modal-icon-option.active i {
            color: var(--accent-blue) !important;
        }
        .modal-icon-option.active span {
            color: var(--accent-blue) !important;
        }
    </style>
</body>
</html>
