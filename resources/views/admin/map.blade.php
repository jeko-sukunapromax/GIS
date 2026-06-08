<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoBayambang</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-panel: rgba(30, 41, 59, 0.7);
            --bg-card: rgba(15, 23, 42, 0.6);
            --accent-blue: #0099ff;
            --accent-blue-glow: rgba(0, 153, 255, 0.3);
            --text-heading: #f8fafc;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: rgba(148, 163, 184, 0.1);
            --glass-blur: blur(12px);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Remove high-contrast browser focus outline from clicked map shapes */
        .leaflet-interactive:focus,
        path:focus,
        svg:focus {
            outline: none !important;
        }

        /* Keep plugin-generated map vectors in the GIS blue theme.
           Leaflet Measure/Geoman otherwise inject default black/red SVG strokes. */
        #map .layer-measurearea,
        #map .layer-measureboundary,
        #map .layer-measure-resultarea,
        #map .layer-measure-resultline,
        #map .leaflet-pm-temp-layer,
        #map .leaflet-pm-hint-line {
            stroke: #0099ff !important;
            stroke-opacity: 0.95 !important;
            stroke-width: 3px !important;
        }

        #map .layer-measurearea,
        #map .layer-measure-resultarea,
        #map .leaflet-pm-temp-layer {
            fill: #0099ff !important;
            fill-opacity: 0.12 !important;
        }

        #map .layer-measuredrag,
        #map .layer-measurevertex,
        #map .layer-measure-resultpoint {
            stroke: #07111f !important;
            fill: #0099ff !important;
            stroke-width: 2px !important;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Navbar */
        .navbar {
            height: 64px;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: var(--glass-blur);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 1000;
        }

        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-area i { font-size: 24px; color: var(--accent-blue); text-shadow: 0 0 10px var(--accent-blue-glow); }
        .logo-text { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; color: var(--text-heading); letter-spacing: -0.5px; }
        .logo-tagline { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }

        .nav-links { display: flex; gap: 24px; }
        .nav-link { color: var(--text-main); text-decoration: none; font-size: 14px; font-weight: 500; transition: 0.2s; }
        .nav-link:hover { color: var(--accent-blue); }

        .nav-right { display: flex; align-items: center; gap: 16px; }
        .search-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 6px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 280px;
        }
        .search-container input {
            background: transparent;
            border: none;
            color: white;
            font-size: 13px;
            width: 100%;
            outline: none;
        }
        .search-container i { color: var(--text-muted); font-size: 14px; }

        /* Main Layout */
        .main-container { flex: 1; display: flex; position: relative; overflow: hidden; }

        /* Sidebar Left: Layers & Tools */
        .sidebar-left {
            width: 320px;
            background: var(--bg-panel);
            backdrop-filter: var(--glass-blur);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            z-index: 900;
            overflow-y: auto;
        }

        .sidebar-section { padding: 20px; border-bottom: 1px solid var(--border-color); }
        .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }

        .layer-item { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .layer-info { display: flex; align-items: center; gap: 12px; font-size: 14px; }
        .layer-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--bg-card); display: flex; align-items: center; justify-content: center; color: var(--accent-blue); }
        
        /* Switch UI */
        .switch { position: relative; display: inline-block; width: 36px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--accent-blue); }
        input:checked + .slider:before { transform: translateX(16px); }

        .basemap-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .basemap-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 8px; cursor: pointer; transition: 0.2s; }
        .basemap-card:hover, .basemap-card.active { border-color: var(--accent-blue); box-shadow: 0 0 15px var(--accent-blue-glow); }
        .basemap-img { height: 60px; border-radius: 8px; margin-bottom: 6px; background-size: cover; }
        .basemap-label { font-size: 11px; text-align: center; font-weight: 600; }
        .bg-street { background-image: url('https://tiles.stadiamaps.com/tiles/osm_bright/14/13672/7476.png'); }
        .bg-satellite { background-image: url('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/14/7476/13672'); }
        .bg-terrain { background-image: url('https://tiles.stadiamaps.com/tiles/stamen_terrain/14/13672/7476.png'); }
        .bg-dark { background-image: url('https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/14/13672/7476.png'); }

        /* Map Container */
        .map-container { flex: 1; position: relative; background: #000; }
        #map { width: 100%; height: 100%; }

        .map-toolbar {
            position: absolute;
            top: 280px;
            left: 20px;
            background: var(--bg-panel);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 6px;
            z-index: 800;
        }
        .toolbar-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--text-main);
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toolbar-btn:hover, .toolbar-btn.active { background: var(--accent-blue); color: white; }
        .toolbar-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            color: var(--text-muted);
        }
        .toolbar-btn:disabled:hover {
            background: var(--bg-card) !important;
            color: var(--text-muted) !important;
        }

        /* Sidebar Right: Data Viz */
        .sidebar-right {
            width: 380px;
            background: var(--bg-panel);
            backdrop-filter: var(--glass-blur);
            border-left: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            z-index: 900;
            overflow-y: auto;
        }

        .data-header { padding: 24px; }
        .data-title { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; color: var(--text-heading); margin-bottom: 4px; }
        .data-subtitle { font-size: 13px; color: var(--text-muted); }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 24px 24px; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
        .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; }
        .stat-value { font-size: 18px; font-weight: 700; color: var(--text-heading); }
        .stat-trend { font-size: 11px; color: #10b981; margin-top: 4px; }

        .viz-section { padding: 24px; background: rgba(15, 23, 42, 0.4); border-top: 1px solid var(--border-color); }
        .chart-placeholder { height: 160px; border-radius: 12px; background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0, 153, 255, 0.05) 10px, rgba(0, 153, 255, 0.05) 20px); border: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 10px; }
        .population-insight {
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.42);
            border: 1px solid var(--border-color);
            padding: 14px;
        }
        .insight-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .insight-row strong {
            color: var(--text-heading);
            font-weight: 800;
            text-align: right;
        }
        .insight-bar {
            height: 8px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.18);
            overflow: hidden;
            margin: 12px 0 8px;
        }
        .insight-bar-fill {
            height: 100%;
            width: 0%;
            border-radius: inherit;
            background: linear-gradient(90deg, #10b981, #38bdf8);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.28);
            transition: width 0.25s ease;
        }
        .insight-caption {
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.45;
        }
        .spatial-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .spatial-status {
            border-radius: 999px;
            border: 1px solid rgba(56, 189, 248, 0.28);
            background: rgba(56, 189, 248, 0.1);
            color: #7dd3fc;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            padding: 4px 8px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .spatial-status.warning {
            border-color: rgba(245, 158, 11, 0.28);
            background: rgba(245, 158, 11, 0.1);
            color: #fbbf24;
        }
        .spatial-grid {
            margin-top: 12px;
        }
        .spatial-caption {
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.45;
            margin-top: 12px;
        }
        
        .distribution-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-top: 16px; }
        .dist-item { display: flex; align-items: center; gap: 12px; }
        .dist-bar-bg { flex: 1; height: 8px; background: #334155; border-radius: 4px; position: relative; }
        .dist-bar-fill { position: absolute; height: 100%; border-radius: 4px; background: var(--accent-blue); }
        .dist-label { font-size: 12px; width: 100px; }
        .dist-value { font-size: 12px; font-weight: 600; width: 60px; text-align: right; }

        /* Floating Tooltips & Legends */
        .legend-card {
            position: absolute;
            bottom: 24px;
            right: 24px;
            background: var(--bg-panel);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 16px;
            z-index: 800;
            width: 200px;
        }
        .legend-item { display: flex; align-items: center; gap: 10px; font-size: 12px; margin-bottom: 8px; }
        .legend-color { width: 12px; height: 12px; border-radius: 3px; }
        .legend-empty { color: var(--text-muted); font-size: 12px; line-height: 1.4; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }

        /* Detail Rows */
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
        .detail-row span:first-child { color: var(--text-muted); }
        .detail-row span:last-child { color: var(--text-heading); font-weight: 500; }
        
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .grid-item { background: var(--bg-card); border-radius: 8px; padding: 10px; border: 1px solid var(--border-color); }
        .item-label { font-size: 10px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
        .item-value { font-size: 13px; font-weight: 600; color: var(--accent-blue); }
        
        /* Barangay List Items */
        .brgy-list-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .brgy-list-item:hover {
            border-color: var(--accent-blue);
            background: rgba(0, 153, 255, 0.1);
        }
        .brgy-list-item.active {
            border-color: var(--accent-blue);
            background: rgba(0, 153, 255, 0.15);
            box-shadow: 0 0 10px var(--accent-blue-glow);
        }
        .brgy-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-heading);
            margin-bottom: 4px;
        }
        .brgy-info {
            font-size: 11px;
            color: var(--text-muted);
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Floating Coordinate Display */
        .coord-display {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: var(--bg-panel);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: var(--text-main);
            z-index: 800;
            display: flex;
            gap: 15px;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }
        .coord-item span {
            color: var(--accent-blue);
            font-weight: 600;
        }

        /* Premium Leaflet Popup Styling */
        .leaflet-popup-content-wrapper {
            background-color: rgba(15, 23, 42, 0.85) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            color: var(--text-heading) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important;
        }
        .leaflet-popup-tip {
            background-color: rgba(15, 23, 42, 0.85) !important;
            backdrop-filter: blur(12px) !important;
        }
        .leaflet-popup-content {
            margin: 16px !important;
            line-height: 1.5 !important;
            font-family: 'Inter', sans-serif !important;
        }
        .leaflet-container a.leaflet-popup-close-button {
            color: var(--text-muted) !important;
            padding: 8px !important;
            font-size: 18px !important;
            transition: 0.2s !important;
        }
        .leaflet-container a.leaflet-popup-close-button:hover {
            color: var(--accent-blue) !important;
            background: transparent !important;
        }

        /* Custom DivIcon for Map Facilities */
        .custom-map-icon {
            background: transparent !important;
            border: none !important;
        }
        .custom-map-icon div {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .custom-map-icon div:hover {
            transform: scale(1.15);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.8) !important;
        }

        /* Style for the dynamic badges */
        .badge {
            transition: all 0.3s ease;
        }

        /* Custom Tooltip Styling */
        .custom-tooltip {
            background-color: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(8px) !important;
            color: var(--text-heading) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3) !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            padding: 4px 8px !important;
        }
        .leaflet-tooltip-top:before {
            border-top-color: rgba(15, 23, 42, 0.9) !important;
        }

        /* Permanent Map Labels Styling */
        .brgy-centroid-icon-container {
            background: transparent !important;
            border: none !important;
        }
        .brgy-map-label {
            color: rgba(255, 255, 255, 0.82);
            font-family: 'Outfit', 'Inter', sans-serif;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.95), 0 0 8px rgba(0, 153, 255, 0.3);
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .brgy-centroid-icon-container:hover .brgy-map-label {
            color: #0099ff;
            transform: scale(1.08);
            text-shadow: 0 2px 6px rgba(0, 0, 0, 1.0), 0 0 12px rgba(0, 153, 255, 0.7);
        }
        .map-labels-muted .brgy-map-label {
            opacity: 0;
            transform: translateY(-3px) scale(0.92);
            pointer-events: none;
        }
        .map-labels-muted .brgy-centroid-icon-container:hover .brgy-map-label,
        .map-labels-muted .brgy-centroid-icon-container.selected .brgy-map-label {
            opacity: 1;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo-area">
            <i class="fa-solid fa-earth-philippines"></i>
            <div>
                <div class="logo-text">GeoBayambang</div>
                <div class="logo-tagline">Geographic Information Systems</div>
            </div>
        </div>
        <div class="nav-links">
            <a href="#" class="nav-link">Dashboard</a>
            <a href="#" class="nav-link">Planning</a>
            <a href="#" class="nav-link">Analysis</a>
            <a href="#" class="nav-link">Reports</a>
            <a href="#" class="nav-link">Help</a>
            @hasanyrole('admin|super-admin')
                <a href="{{ route('admin.barangays.index') }}" class="nav-link" style="color: var(--accent-blue); font-weight: 600;"><i class="fa-solid fa-user-shield"></i> Admin Dashboard</a>
            @else
                <a href="{{ route('admin.features.index') }}" class="nav-link" style="color: var(--accent-blue); font-weight: 600;"><i class="fa-solid fa-draw-polygon"></i> Features</a>
            @endhasanyrole
        </div>
        <div class="nav-right">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search barangays..." onkeyup="searchBarangays()">
                <button onclick="clearSearch()" id="clearBtn" style="display:none; background:none; border:none; color:var(--text-muted); cursor:pointer; padding:0;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <button class="toolbar-btn" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 50%;"><i class="fa-solid fa-bell"></i></button>
            <button class="toolbar-btn" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 50%;"><i class="fa-solid fa-user"></i></button>
        </div>
    </nav>

    <div class="main-container">
        <!-- Sidebar Left -->
        <aside class="sidebar-left">
            <div class="sidebar-section" style="flex-shrink: 0;">
                <div class="section-title">Barangay List <span id="resultCount" style="font-weight:400; color:var(--accent-blue);"></span>
                    <button onclick="selectAllBarangays()" id="selectAllBtn" style="font-size:9px; padding:3px 8px; background:var(--bg-card); border:1px solid var(--border-color); color:var(--accent-blue); border-radius:4px; cursor:pointer; transition:0.2s; text-transform:uppercase; letter-spacing:0.5px;" onmouseover="this.style.background='rgba(56,189,248,0.1)'; this.style.borderColor='var(--accent-blue)';" onmouseout="this.style.background='var(--bg-card)'; this.style.borderColor='var(--border-color)';"><i class="fa-solid fa-layer-group" style="font-size:8px;"></i> Select All</button>
                </div>
                <div id="barangayList" style="max-height: 180px; overflow-y: auto;">
                    <!-- Barangay list will be populated here -->
                </div>
            </div>

            <div class="sidebar-section" style="flex: 1; overflow-y: auto; min-height: 0;">
                <div class="section-title" style="margin-bottom: 8px;">
                    <div>Layers — <span id="active-brgy-name" style="color: var(--accent-blue);">Select barangay</span></div>
                </div>
                <div id="active-brgy-subtitle" style="font-size: 11px; color: var(--text-muted); margin-bottom: 16px; margin-top: -6px; text-transform: none; letter-spacing: 0;">Choose one from the barangay list</div>

                <!-- BOUNDARY SECTION -->
                <div style="margin-bottom: 14px;">
                    <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 6px;">Boundary</div>
                    <div class="layer-item" style="margin-bottom: 0;">
                        <div class="layer-info">
                            <div class="layer-icon" style="color: var(--accent-blue); width: 26px; height: 26px; border-radius: 6px; font-size: 11px;"><i class="fa-solid fa-draw-polygon"></i></div>
                            <span style="font-size: 13px;">Brgy. Boundary</span>
                        </div>
                        <label class="switch" style="width: 32px; height: 18px;">
                            <input type="checkbox" id="layer-boundary" checked>
                            <span class="slider" style="border-radius: 18px;"></span>
                        </label>
                    </div>
                </div>

                <!-- DYNAMIC SECTIONS LOADED FROM DATABASE -->
                @php
                    $categories = $layerTypes
                        ->groupBy('category')
                        ->map(fn ($items, $category) => ucwords(str_replace('_', ' ', $category)));
                @endphp

                @foreach($categories as $catCode => $catName)
                    @php
                        $filteredTypes = $layerTypes->where('category', $catCode);
                    @endphp

                    @if($filteredTypes->count() > 0)
                        <div style="margin-bottom: 14px;">
                            <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 6px;">{{ $catName }}</div>
                            
                            @foreach($filteredTypes as $type)
                                <div class="layer-item" style="margin-bottom: 8px;">
                                    <div class="layer-info">
                                        <div class="layer-icon" style="color: {{ $type->color }}; width: 26px; height: 26px; border-radius: 6px; font-size: 11px; background: {{ $type->color }}12;">
                                            <i class="{{ $type->icon }}"></i>
                                        </div>
                                        <span style="font-size: 13px;">{{ $type->name }}</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span id="badge-{{ $type->code }}" class="badge" style="font-size: 10px; padding: 1px 6px; border-radius: 8px; background: rgba(255,255,255,0.05); color: var(--text-muted); font-weight: bold;">0</span>
                                        <label class="switch" style="width: 32px; height: 18px;">
                                            <input type="checkbox" id="layer-{{ $type->code }}" checked>
                                            <span class="slider" style="border-radius: 18px;"></span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="sidebar-section" style="flex-shrink: 0;">
                <div class="section-title">Basemap Selection</div>
                <div class="basemap-grid">
                    <div class="basemap-card" onclick="setBasemap('roadmap', this)">
                        <div class="basemap-img bg-street"></div>
                        <div class="basemap-label">Street</div>
                    </div>
                    <div class="basemap-card" onclick="setBasemap('satellite', this)">
                        <div class="basemap-img bg-satellite"></div>
                        <div class="basemap-label">Satellite</div>
                    </div>
                    <div class="basemap-card" onclick="setBasemap('terrain', this)">
                        <div class="basemap-img bg-terrain"></div>
                        <div class="basemap-label">Terrain</div>
                    </div>
                    <div class="basemap-card active" onclick="setBasemap('dark', this)">
                        <div class="basemap-img bg-dark"></div>
                        <div class="basemap-label">Dark Matter</div>
                    </div>
                </div>
            </div>

            <div class="sidebar-section" style="flex-shrink: 0;">
                <div class="section-title">Analysis Tools</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <button class="toolbar-btn" onclick="startMeasure()" style="width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); font-size: 12px; gap: 8px;"><i class="fa-solid fa-ruler"></i> Measure</button>
                    <button class="toolbar-btn" onclick="clearMeasure()" style="width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); font-size: 12px; gap: 8px;"><i class="fa-solid fa-eraser"></i> Clear</button>
                    @hasanyrole('admin|super-admin')
                        <button id="editModeBtn" type="button" class="toolbar-btn" disabled title="Boundary editing is disabled until the save workflow is ready." style="grid-column: span 2; width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); font-size: 12px; gap: 8px;"><i class="fa-solid fa-pen-ruler"></i> Edit Boundary Mode</button>
                    @endhasanyrole
                </div>
            </div>
        </aside>

        <!-- Map Container -->
        <main class="map-container">
            <div id="map"></div>
            
            <!-- Dynamic Map HUD Title (Scope) styled exactly like the picture! -->
            <div id="map-hud-scope" style="display: none; position: absolute; top: 24px; left: 50%; transform: translateX(-50%); z-index: 1000; pointer-events: none; text-align: center;">
                <div style="position: relative; font-family: 'Outfit', 'Inter', sans-serif; font-size: 15px; font-weight: 800; color: #0099ff; text-transform: uppercase; letter-spacing: 2px; text-shadow: 0 0 10px rgba(0, 153, 255, 0.6), 0 2px 4px rgba(0,0,0,0.95); padding: 8px 20px; background: rgba(9, 13, 22, 0.85); border: 1.5px solid rgba(0, 153, 255, 0.45); border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.6); backdrop-filter: blur(8px); display: inline-flex; align-items: center; justify-content: center; pointer-events: auto;">
                    SCOPE: <span id="hud-scope-name" style="margin-left: 6px;">NONE</span>
                    <button type="button" onclick="closeHudScope(event)" title="Clear selection" onmouseover="this.style.background='#0099ff'; this.style.color='#07111f'; this.style.transform='scale(1.08)';" onmouseout="this.style.background='#07111f'; this.style.color='#0099ff'; this.style.transform='scale(1)';" style="position: absolute; top: -8px; right: -8px; width: 18px; height: 18px; display: inline-grid; place-items: center; border: 1px solid rgba(0,153,255,0.65); border-radius: 999px; background: #07111f; color: #0099ff; font-size: 13px; font-weight: 900; line-height: 1; cursor: pointer; box-shadow: 0 0 8px rgba(0,153,255,0.4), 0 2px 6px rgba(0,0,0,0.55); transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;">×</button>
                </div>
            </div>
            
            <!-- Coordinate Display -->
            <div class="coord-display">
                <div class="coord-item">LAT: <span id="lat-val">0.000000</span></div>
                <div class="coord-item">LNG: <span id="lng-val">0.000000</span></div>
                <div class="coord-item">ZOOM: <span id="zoom-val">14</span></div>
            </div>
            
            <div class="map-toolbar">
                <button class="toolbar-btn" onclick="map.zoomIn()" title="Zoom In"><i class="fa-solid fa-plus"></i></button>
                <button class="toolbar-btn" onclick="map.zoomOut()" title="Zoom Out"><i class="fa-solid fa-minus"></i></button>
                <div style="height: 1px; background: var(--border-color);"></div>
                <button class="toolbar-btn" onclick="map.setView([15.8287, 120.4173], 14)" title="Default Extent"><i class="fa-solid fa-house"></i></button>
                <button class="toolbar-btn" onclick="findMyLocation()" title="Find my location"><i class="fa-solid fa-crosshairs"></i></button>
                <div style="height: 1px; background: var(--border-color);"></div>
                <button class="toolbar-btn" onclick="toggleIdentify(this)" title="Identify (Click map for coordinates)"><i class="fa-solid fa-arrow-pointer"></i></button>
            </div>

            <div class="legend-card">
                <div class="section-title" style="margin-bottom: 12px; font-size: 10px;">Legend</div>
                <div id="dynamicLegend">
                    <div class="legend-empty">Select a barangay and enable layers to view symbols.</div>
                </div>
            </div>
        </main>

        <!-- Sidebar Right -->
        <aside class="sidebar-right">
            <div class="data-header">
                <div class="data-title">Barangay Profile</div>
                <div class="data-subtitle">Statistical data visualization and records</div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Barangay Name</div>
                    <div class="stat-value" id="profile-name">Select barangay</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Land Area</div>
                    <div class="stat-value" id="profile-total-area">N/A</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Population</div>
                    <div class="stat-value" id="profile-population">N/A</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Hazard Level</div>
                    <div class="stat-value" id="profile-hazard">N/A</div>
                </div>
            </div>

            <div class="viz-section">
                <div class="section-title">General Information</div>
                <div class="detail-row"><span>Official Name</span><span id="profile-official-name">N/A</span></div>
                <div class="detail-row"><span>Municipality</span><span id="profile-municipality">Bayambang</span></div>
                <div class="detail-row"><span>Province</span><span id="profile-province">Pangasinan</span></div>
                <div class="detail-row"><span>Land Area</span><span id="profile-land-area">N/A</span></div>
                <div class="detail-row"><span>Primary Land Use</span><span id="profile-land-use">N/A</span></div>
                <div class="detail-row"><span>Status</span><span id="profile-status">N/A</span></div>
                <div class="detail-row"><span>Coordinates</span><span id="profile-coordinates">N/A</span></div>
            </div>

            <div class="viz-section">
                <div class="spatial-section-title">
                    <div class="section-title" style="margin-bottom: 0;">PostGIS Measurements</div>
                    <span class="spatial-status warning" id="postgis-status">Waiting</span>
                </div>
                <div class="grid-layout spatial-grid">
                    <div class="grid-item">
                        <div class="item-label">Computed Area</div>
                        <div class="item-value" id="postgis-computed-area">N/A</div>
                    </div>
                    <div class="grid-item">
                        <div class="item-label">Perimeter</div>
                        <div class="item-value" id="postgis-perimeter">N/A</div>
                    </div>
                    <div class="grid-item">
                        <div class="item-label">Area Difference</div>
                        <div class="item-value" id="postgis-area-diff">N/A</div>
                    </div>
                    <div class="grid-item">
                        <div class="item-label">Road Length</div>
                        <div class="item-value" id="postgis-road-length">N/A</div>
                    </div>
                    <div class="grid-item">
                        <div class="item-label">Features Inside</div>
                        <div class="item-value" id="postgis-contained-features">N/A</div>
                    </div>
                    <div class="grid-item">
                        <div class="item-label">Nearest Feature</div>
                        <div class="item-value" id="postgis-nearest-feature">N/A</div>
                    </div>
                </div>
                <div class="spatial-caption" id="postgis-caption">
                    Select a barangay to load measurements computed from PostGIS.
                </div>
            </div>

            <div class="viz-section">
                <div class="section-title">Land Cover Distribution</div>
                <div class="grid-layout">
                    <div class="grid-item">
                        <div class="item-label">Agriculture</div>
                        <div class="item-value" id="profile-agri-area">N/A</div>
                    </div>
                    <div class="grid-item">
                        <div class="item-label">Residential</div>
                        <div class="item-value" id="profile-residential-area">N/A</div>
                    </div>
                    <div class="grid-item">
                        <div class="item-label">Commercial</div>
                        <div class="item-value" id="profile-commercial-area">N/A</div>
                    </div>
                    <div class="grid-item">
                        <div class="item-label">Not Identified</div>
                        <div class="item-value" id="profile-unidentified-area">N/A</div>
                    </div>
                </div>
            </div>

            <div class="viz-section">
                <div class="section-title">Population Insight</div>
                <div class="population-insight">
                    <div class="insight-row">
                        <span>Density</span>
                        <strong id="population-density-value">0 people/ha</strong>
                    </div>
                    <div class="insight-row">
                        <span>Municipal avg.</span>
                        <strong id="population-average-value">0 people/ha</strong>
                    </div>
                    <div class="insight-row">
                        <span>Barangay rank</span>
                        <strong id="population-rank-value">N/A</strong>
                    </div>
                    <div class="insight-bar" aria-hidden="true">
                        <div class="insight-bar-fill" id="population-density-bar"></div>
                    </div>
                    <div class="insight-caption" id="population-insight-caption">
                        Select a barangay to compare its population density against the rest of Bayambang.
                    </div>
                </div>
            </div>
        </aside>

    </div>

    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    @hasanyrole('admin|super-admin')
    <!-- Leaflet Geoman (Drawing Tools for Admins) -->
    <link rel="stylesheet" href="https://unpkg.com/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.css" />
    <script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.min.js"></script>
    @endhasanyrole
    <!-- Leaflet Measure Plugin -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-measure@3.1.0/dist/leaflet-measure.css">
    <script src="https://cdn.jsdelivr.net/npm/leaflet-measure@3.1.0/dist/leaflet-measure.js"></script>

    <script>
        let map;
        let currentLayer;
        let markers = {};
        let barangayPolygons = {}; // Holds preloaded interactive boundaries
        let activeBarangayId = null; // Tracks active selected Barangay
        let selectedBarangayIds = new Set();
        let filteredBarangays = [];
        let identifyActive = false;
        let editModeActive = false;
        let spatialAnalysisToken = 0;
        const boundaryEditingEnabled = false;
        function createMeasureControl() {
            return L.control.measure({
                position: 'topright',
                activeColor: '#0099ff',
                completedColor: '#0099ff',
                primaryLengthUnit: 'meters',
                secondaryLengthUnit: 'kilometers',
                primaryAreaUnit: 'hectares',
                secondaryAreaUnit: 'sqmeters'
            });
        }

        let measureControl = createMeasureControl();
        let measureControlAdded = false;
        const barangays = @json($barangays);
        const dbLayerTypes = @json($layerTypes);
        let allSelected = false;
        let municipalPolygon = null;

        function isMunicipalBoundary(brgy) {
            return brgy.is_municipal_boundary || brgy.name.toLowerCase() === 'bayambang';
        }

        function hasDisplayValue(value) {
            return value !== null && value !== undefined && value !== '';
        }

        function formatHectares(value, zeroAsUnavailable = true) {
            if (!hasDisplayValue(value)) return 'N/A';

            const area = numericValue(value);
            if (area < 0 || (area === 0 && zeroAsUnavailable)) return 'N/A';

            return `${area.toLocaleString('en-US', {
                maximumFractionDigits: 2
            })} ha`;
        }

        function formatPopulation(value) {
            if (!hasDisplayValue(value)) return 'N/A';

            const population = numericValue(value);
            if (population <= 0) return 'N/A';

            return Math.round(population).toLocaleString('en-US');
        }

        function formatMeters(value, zeroAsUnavailable = true) {
            if (!hasDisplayValue(value)) return 'N/A';

            const meters = numericValue(value);
            if (meters < 0 || (meters === 0 && zeroAsUnavailable)) return 'N/A';
            if (meters === 0) return '0 m';

            if (meters >= 1000) {
                return `${(meters / 1000).toLocaleString('en-US', {
                    maximumFractionDigits: 2
                })} km`;
            }

            return `${meters.toLocaleString('en-US', {
                maximumFractionDigits: 1
            })} m`;
        }

        function formatPlainNumber(value) {
            if (!hasDisplayValue(value)) return 'N/A';

            const number = numericValue(value);
            if (number < 0) return 'N/A';

            return number.toLocaleString('en-US', {
                maximumFractionDigits: 0
            });
        }

        function setProfileText(id, value) {
            const element = document.getElementById(id);
            if (element) element.innerText = value;
        }

        function hazardColor(value) {
            const normalized = String(value || '').toLowerCase();
            if (normalized.includes('high')) return '#ef4444';
            if (normalized.includes('moderate') || normalized.includes('medium')) return '#f59e0b';
            if (normalized.includes('low')) return '#22c55e';
            return 'var(--text-heading)';
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function metadataLabel(key) {
            return String(key || '')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, letter => letter.toUpperCase());
        }

        function collectMetadataRows(metadata) {
            const rows = [];

            Object.entries(metadata || {}).forEach(([key, value]) => {
                if (value === null || value === undefined || value === '') return;

                if (key === 'import_properties' && value && typeof value === 'object' && !Array.isArray(value)) {
                    Object.entries(value).forEach(([importKey, importValue]) => {
                        if (importValue === null || importValue === undefined || importValue === '') return;
                        rows.push([importKey, importValue]);
                    });
                    return;
                }

                rows.push([key, value]);
            });

            return rows;
        }

        function metadataValue(value) {
            if (typeof value === 'boolean') return value ? 'Yes' : 'No';
            if (typeof value === 'object') return JSON.stringify(value);
            return String(value);
        }

        function featureMetadataHtml(metadata) {
            return collectMetadataRows(metadata)
                .slice(0, 8)
                .map(([key, value]) => `
                    <div style="display:grid; grid-template-columns: minmax(88px, 1fr) auto; gap:10px; align-items:start;">
                        <span style="color: var(--text-muted);">${escapeHtml(metadataLabel(key))}</span>
                        <span style="font-weight: 600; color:#e2e8f0; text-align:right; max-width:180px; overflow-wrap:anywhere;">${escapeHtml(metadataValue(value))}</span>
                    </div>
                `)
                .join('');
        }

        function drawMunicipalPolygon(boundaryCoords) {
            municipalPolygon = L.polygon(boundaryCoords, {
                color: '#1d74c9',
                fillColor: '#0099ff',
                opacity: 0.58,
                fillOpacity: 0.0,
                weight: 2.2,
                dashArray: '',
                className: 'municipal-polygon'
            }).addTo(featureLayers.boundary);

            municipalPolygon.bringToBack();
        }

        // Dynamic operational layers matching the sidebar checkboxes
        let featureLayers = {
            boundary: L.layerGroup()
        };
        dbLayerTypes.forEach(type => {
            featureLayers[type.code] = L.layerGroup();
        });

        function initMap() {
            // Keep the map around Bayambang, with enough buffer to center edge barangays.
            const bayambangBounds = L.latLngBounds(
                L.latLng(15.58, 120.12), // Southwest corner
                L.latLng(16.02, 120.78)  // Northeast corner
            );

            // Initialize map centered on Bayambang with strict zoom and pan restrictions
            map = L.map('map', { 
                zoomControl: false, 
                minZoom: 12,
                maxZoom: 20,
                maxBounds: bayambangBounds,
                maxBoundsViscosity: 0.45
            }).setView([15.8287, 120.4173], 14);

            // Set default dark basemap
            setBasemap('dark');

            // Add all operational layer groups to the map by default
            Object.keys(featureLayers).forEach(type => {
                const checkbox = document.getElementById(`layer-${type}`);
                // Only add to map if the checkbox is checked initially
                if (!checkbox || checkbox.checked) {
                    featureLayers[type].addTo(map);
                }
            });

            @hasanyrole('admin|super-admin')
            if (boundaryEditingEnabled && map.pm) {
                map.pm.setGlobalOptions({
                    pathOptions: {
                        color: '#0099ff',
                        fillColor: '#0099ff',
                        fillOpacity: 0.12,
                        weight: 3
                    },
                    templineStyle: { color: '#0099ff' },
                    hintlineStyle: { color: '#0099ff', dashArray: [5, 5] }
                });

                // Handle Drawn Shapes (Admin digitized boundaries helper)
                map.on('pm:create', function(e) {
                    const layer = e.layer;
                    const shape = e.shape;

                    if (shape === 'Polygon' || shape === 'Rectangle') {
                        const latlngs = layer.getLatLngs()[0];
                        const formattedCoords = latlngs.map(ll => [ll.lat, ll.lng]);

                        const popupContent = `
                            <div style="padding:10px; min-width:200px; font-family: 'Inter', sans-serif;">
                                <b style="color:var(--accent-blue); font-size: 13px;"><i class="fa-solid fa-draw-polygon"></i> Boundary Created!</b><br>
                                <p style="font-size:11px; margin:5px 0; color:#ccc;">Copy the coordinates below to use in the Admin panel:</p>
                                <textarea readonly style="width:100%; height:60px; font-size:10px; background:#1e293b; color:#fff; border:1px solid rgba(255,255,255,0.1); border-radius:4px; padding:5px; font-family: monospace;">${JSON.stringify(formattedCoords)}</textarea>
                                <div style="font-size:10px; color:var(--text-muted); margin-top:5px;">Paste this in the "Boundary Data" field of the Barangay creation page.</div>
                            </div>
                        `;
                        layer.bindPopup(popupContent).openPopup();
                    }
                });
            }
            @endhasanyrole

            // Draw boundaries and centroid markers for each barangay from the database
            barangays.forEach(brgy => {
                // 1. Draw boundary polygon first
                if (brgy.boundary) {
                    try {
                        let boundaryCoords = typeof brgy.boundary === 'string' ? JSON.parse(brgy.boundary) : brgy.boundary;
                        if (Array.isArray(boundaryCoords) && boundaryCoords.length > 0) {
                            if (isMunicipalBoundary(brgy)) {
                                // Draw municipal boundary — solid blue outline, no fill (matches screenshot)
	                                drawMunicipalPolygon(boundaryCoords);
	                                return;
	                            }

                            const polygon = L.polygon(boundaryCoords, {
                                color: '#0099ff',
                                fillColor: '#0099ff',
                                opacity: 0.0, // Completely invisible initially
                                fillOpacity: 0.0,
                                weight: 0,
                                className: 'brgy-polygon'
                            }).addTo(featureLayers.boundary);

                             // Bind standard hover tooltip
                             polygon.bindTooltip(brgy.name, {
                                 sticky: true,
                                 direction: 'top',
                                 className: 'custom-tooltip'
                             });

                             // Add interactive events
                             polygon.on('mouseover', function() {
                                 if (isBarangayCurrentlySelected(brgy.id)) return;
                                 polygon.setStyle({
                                     color: '#0099ff',
                                     opacity: 0.0,
                                     fillOpacity: 0.06,
                                     weight: 0
                                 });
                             });

                             polygon.on('mouseout', function() {
                                 if (isBarangayCurrentlySelected(brgy.id)) return;
                                 // Return to completely invisible state
                                 polygon.setStyle({
                                     opacity: 0.0,
                                     fillOpacity: 0.0,
                                     weight: 0
                                 });
                             });

                             polygon.on('click', function() {
                                 selectBarangay(brgy.id);
                             });

                             barangayPolygons[brgy.id] = polygon;
                        }
                    } catch (e) {
                        console.error("Error drawing preloaded boundary for " + brgy.name, e);
                    }
                }

                // 2. Draw centroid marker (Skip for the whole municipality)
                if (isMunicipalBoundary(brgy)) return;

                const lat = parseFloat(brgy.latitude);
                const lng = parseFloat(brgy.longitude);
                
                if (!isNaN(lat) && !isNaN(lng)) {
                    const brgyIcon = L.divIcon({
                        html: `
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer;">
                                <div style="background-color: var(--accent-blue); width: 14px; height: 14px; border-radius: 50%; border: 2.5px solid rgba(15, 23, 42, 0.9); box-shadow: 0 0 8px var(--accent-blue-glow);"></div>
                                <div class="brgy-map-label" style="margin-top: 4px;">${brgy.name}</div>
                            </div>
                        `,
                        className: 'brgy-centroid-icon-container',
                        iconSize: [120, 42],
                        iconAnchor: [60, 7]
                    });

                    const marker = L.marker([lat, lng], { icon: brgyIcon }).addTo(map);
                    markers[brgy.id] = marker;
                    
                    const infoWindowContent = `
                        <div style="padding: 4px; color: var(--text-heading); font-family: 'Inter', sans-serif;">
                            <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700; color:var(--accent-blue);">${brgy.name}</h4>
                            <div style="display:flex; flex-direction:column; gap:4px; font-size:11px; margin-top:8px;">
                                <div><span style="color:var(--text-muted);">Pop:</span> <b>${formatPopulation(brgy.population)}</b></div>
                                <div><span style="color:var(--text-muted);">Area:</span> <b>${formatHectares(brgy.total_area)}</b></div>
                                <div><span style="color:var(--text-muted);">⚠️ Hazard:</span> <b style="color:#ef4444;">${brgy.hazard_level || 'Low'}</b></div>
                            </div>
                            <button onclick="selectBarangay(${brgy.id})" style="margin-top:12px; width:100%; padding:6px; font-size:11px; font-weight:600; background:var(--accent-blue); border:none; color:white; border-radius:6px; cursor:pointer; transition:0.2s;">Focus & View Layers</button>
                        </div>
                    `;
                    
                    marker.bindPopup(infoWindowContent);
                    marker.on('click', () => selectBarangay(brgy.id));
	                }
	            });

	            // Mouse Move Coordinates (bottom left display)
            map.on('mousemove', function(e) {
                const latVal = document.getElementById('lat-val');
                const lngVal = document.getElementById('lng-val');
                if (latVal) latVal.innerText = e.latlng.lat.toFixed(6);
                if (lngVal) lngVal.innerText = e.latlng.lng.toFixed(6);
            });

            map.on('zoomend', function() {
                const zoomVal = document.getElementById('zoom-val');
                if (zoomVal) zoomVal.innerText = map.getZoom();
                updateLabelVisibility();
            });

            map.on('locationfound', function(e) {
                L.circleMarker(e.latlng, {
                    radius: 7,
                    color: '#0099ff',
                    fillColor: '#0099ff',
                    fillOpacity: 0.8,
                    weight: 2
                }).addTo(map).bindPopup('Current location').openPopup();
            });

            map.on('locationerror', function() {
                alert('Unable to detect your location. Please allow location access in the browser.');
            });

            // Map Click (Identify Coordinate Tool)
            map.on('click', function(e) {
                if(identifyActive) {
                    L.popup()
                        .setLatLng(e.latlng)
                        .setContent(`
                            <div style="padding:4px; font-family:Inter; color:var(--text-heading); min-width: 160px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent-blue); box-shadow: 0 0 8px var(--accent-blue);"></div>
                                    <b style="color:white; font-size:14px; letter-spacing: 0.5px;">Location Picked</b>
                                </div>
                                <div style="background:rgba(0,0,0,0.3); padding:10px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05);">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                        <span style="color:var(--text-muted); font-size:11px; font-weight:600;">LAT</span>
                                        <span style="color:#fff; font-size:12px; font-family:monospace;">${e.latlng.lat.toFixed(7)}</span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between;">
                                        <span style="color:var(--text-muted); font-size:11px; font-weight:600;">LNG</span>
                                        <span style="color:#fff; font-size:12px; font-family:monospace;">${e.latlng.lng.toFixed(7)}</span>
                                    </div>
                                </div>
                                <div style="font-size:10px; color:var(--text-muted); margin-top:12px; text-align: center;">Click anywhere to pick again.</div>
                            </div>
                        `)
                        .openOn(map);
                }
            });

            // Hook up switch toggles dynamically
            const layerTypes = ['boundary', ...dbLayerTypes.map(type => type.code)];

            layerTypes.forEach(type => {
                const checkbox = document.getElementById(`layer-${type}`);
                if (checkbox) {
                    checkbox.addEventListener('change', function(e) {
                        handleLayerToggle(type, e.target.checked);
                    });
                }
            });

            filteredBarangays = barangays.filter(b => !isMunicipalBoundary(b));
            renderBarangayList();
            updateLabelVisibility();

            const defaultBarangay = filteredBarangays.find(brgy => brgy.name.toLowerCase() === 'tococ east') || filteredBarangays[0];
            if (defaultBarangay) {
                selectBarangay(defaultBarangay.id);
            }
        }

        function createCustomIcon(iconClass, color) {
            return L.divIcon({
                html: `<div style="background-color: ${color}; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.5);"><i class="${iconClass}" style="color: white; font-size: 11px;"></i></div>`,
                className: 'custom-map-icon',
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });
        }

        function updateLabelVisibility() {
            const mapEl = document.getElementById('map');
            if (!map || !mapEl) return;

            mapEl.classList.toggle('map-labels-muted', map.getZoom() < 14);
        }

        function updateSelectedMarkerState() {
            Object.entries(markers).forEach(([id, marker]) => {
                const markerEl = marker.getElement();
                if (!markerEl) return;

                markerEl.classList.toggle('selected', selectedBarangayIds.has(parseInt(id)));
            });
        }

        function updateDynamicLegend(counts = {}) {
            const legend = document.getElementById('dynamicLegend');
            if (!legend) return;

            const visibleLayers = dbLayerTypes.filter(type => {
                const checkbox = document.getElementById(`layer-${type.code}`);
                return checkbox?.checked && (counts[type.code] || 0) > 0;
            });

            if (visibleLayers.length === 0) {
                legend.innerHTML = '<div class="legend-empty">Select a barangay and enable layers to view symbols.</div>';
                return;
            }

            legend.innerHTML = visibleLayers.map(type => `
                <div class="legend-item">
                    <div class="legend-color" style="background:${type.color};"></div>
                    <span>${type.name}</span>
                    <span style="margin-left:auto; color:var(--text-muted); font-size:11px;">${counts[type.code] || 0}</span>
                </div>
            `).join('');
        }

        function resetLayerBadges() {
            dbLayerTypes.forEach(t => {
                const badge = document.getElementById(`badge-${t.code}`);
                if (badge) {
                    badge.innerText = '0';
                    badge.style.background = 'rgba(255, 255, 255, 0.05)';
                    badge.style.color = 'var(--text-muted)';
                }
            });
        }

        // Toggles a layer on/off the Leaflet canvas
        function handleLayerToggle(type, isChecked) {
            if (isChecked) {
                if (!map.hasLayer(featureLayers[type])) {
                    map.addLayer(featureLayers[type]);
                }
            } else {
                if (map.hasLayer(featureLayers[type])) {
                    map.removeLayer(featureLayers[type]);
                }
            }

            const counts = {};
            dbLayerTypes.forEach(layer => {
                const badge = document.getElementById(`badge-${layer.code}`);
                counts[layer.code] = badge ? parseInt(badge.innerText || '0') : 0;
            });
            updateDynamicLegend(counts);
        }

        // Fetches operational layers/features from database for the active Barangay
        function fetchBarangayFeatures(id) {
            const brgy = barangays.find(b => b.id == id);
            if (!brgy) return;

            // Clear previous features from the canvas groups
            // Clear previous operational features from the canvas groups (excluding boundary layer which is preloaded and persistent)
            Object.keys(featureLayers).forEach(type => {
                if (type !== 'boundary') {
                    featureLayers[type].clearLayers();
                }
            });
            resetLayerBadges();
            updateDynamicLegend();

            // 1. Clear previous operational markers/vectors (boundaries are preloaded globally and persistent)
            // (No need to redraw boundary here since we handle active styles in selectBarangay)

            // 2. Fetch facilities, DRRM, infrastructure, and population data from Laravel API
            fetch(`/admin/barangays/${id}/features`)
                .then(res => res.json())
                .then(features => {
                    // Reset counts dynamically for badges
                    const counts = {};
                    dbLayerTypes.forEach(t => {
                        counts[t.code] = 0;
                    });

                    features.forEach(feat => {
                        const type = feat.feature_type;
                        if (!featureLayers[type]) return;

                        if (counts[type] !== undefined) {
                            counts[type]++;
                        }

                        const metadataHtml = featureMetadataHtml(feat.metadata || {});
                        let popupHtml = `
                            <div style="padding: 4px; font-family: 'Inter', sans-serif; min-width: 180px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 6px;">
                                    <b style="font-size: 13px; color: var(--accent-blue);">${escapeHtml(feat.name)}</b>
                                </div>
                                <div style="font-size: 11px; display: flex; flex-direction: column; gap: 4px; color:#cbd5e1;">
                                    ${metadataHtml || '<div style="color: var(--text-muted);">No metadata available.</div>'}
                                </div>
                            </div>
                        `;

                        // Render feature to its specific Leaflet LayerGroup
                        if (feat.latitude && feat.longitude) {
                            const lat = parseFloat(feat.latitude);
                            const lng = parseFloat(feat.longitude);
                            
                            const config = dbLayerTypes.find(t => t.code === type) || {};
                            const iconClass = config.icon || 'fa-solid fa-location-dot';
                            const color = config.color || '#0099ff';
                            
                            const markerIcon = createCustomIcon(iconClass, color);
                            
                            L.marker([lat, lng], { icon: markerIcon })
                                .bindPopup(popupHtml)
                                .addTo(featureLayers[type]);
                        } else if (feat.coordinates) {
                            // Render polyline or polygon vectors dynamically
                            let coords = typeof feat.coordinates === 'string' ? JSON.parse(feat.coordinates) : feat.coordinates;
                            const config = dbLayerTypes.find(t => t.code === type) || {};
                            const color = config.color || '#8b5cf6';
                            const geomType = config.geom_type || 'polyline';
                            
                            if (geomType === 'polyline') {
                                L.polyline(coords, {
                                    color: color,
                                    weight: 4,
                                    opacity: 0.8
                                }).bindPopup(popupHtml).addTo(featureLayers[type]);
                            } else {
                                L.polygon(coords, {
                                    color: color,
                                    fillColor: color,
                                    fillOpacity: 0.15,
                                    weight: 1.5,
                                    dashArray: '5, 5'
                                }).bindPopup(popupHtml).addTo(featureLayers[type]);
                            }
                        }
                    });

                    // Update count badges dynamically
                    Object.keys(counts).forEach(type => {
                        const badge = document.getElementById(`badge-${type}`);
                        if (badge) {
                            badge.innerText = counts[type];
                            // If 0, mute the colors, else highlight
                            const config = dbLayerTypes.find(t => t.code === type) || {};
                            const color = config.color || '#0099ff';
                            if (counts[type] === 0) {
                                badge.style.background = 'rgba(255, 255, 255, 0.05)';
                                badge.style.color = 'var(--text-muted)';
                            } else {
                                badge.style.background = color + '20';
                                badge.style.color = color;
                            }
                        }
                    });
                    updateDynamicLegend(counts);
                })
                .catch(err => console.error("Error loading features:", err));
        }

        function renderBarangayList() {
            const listContainer = document.getElementById('barangayList');
            const resultCount = document.getElementById('resultCount');
            
            if (filteredBarangays.length === 0) {
                listContainer.innerHTML = '<div class="no-results"><i class="fa-solid fa-magnifying-glass"></i><br>No barangays found</div>';
                resultCount.textContent = '(0)';
                return;
            }
            
            resultCount.textContent = `(${filteredBarangays.length})`;
            
            listContainer.innerHTML = filteredBarangays.map(brgy => `
                <div class="brgy-list-item" id="brgy-item-${brgy.id}" onclick="selectBarangay(${brgy.id})">
                    <div class="brgy-name">${brgy.name}</div>
                    <div class="brgy-info">
                        <i class="fa-solid fa-location-dot"></i> ${brgy.latitude ? parseFloat(brgy.latitude).toFixed(4) : 'N/A'}, ${brgy.longitude ? parseFloat(brgy.longitude).toFixed(4) : 'N/A'}
                    </div>
                </div>
            `).join('');

            syncSelectedListState();
        }

        function selectBarangay(id) {
            const brgy = barangays.find(b => parseInt(b.id) === parseInt(id));
            if (!brgy) return;

            allSelected = false;
            const selectAllBtn = document.getElementById('selectAllBtn');
            if (selectAllBtn) {
                selectAllBtn.innerHTML = '<i class="fa-solid fa-layer-group" style="font-size:8px;"></i> Select All';
            }
            const numericId = parseInt(id);
            selectedBarangayIds.has(numericId)
                ? selectedBarangayIds.delete(numericId)
                : selectedBarangayIds.add(numericId);

            applySelectedBarangays();
        }

        function syncSelectedListState() {
            document.querySelectorAll('.brgy-list-item').forEach(el => el.classList.remove('active'));
            selectedBarangayIds.forEach(id => {
                document.getElementById(`brgy-item-${id}`)?.classList.add('active');
            });
        }

        function isBarangayCurrentlySelected(id) {
            return selectedBarangayIds.has(parseInt(id));
        }

        function resetBarangaySelection() {
            activeBarangayId = null;
            selectedBarangayIds.clear();
            syncSelectedListState();

            const hudScope = document.getElementById('map-hud-scope');
            if (hudScope) hudScope.style.display = 'none';

            Object.values(barangayPolygons).forEach(poly => {
                poly.setStyle({
                    opacity: 0.0,
                    fillOpacity: 0.0,
                    weight: 0
                });
            });

            if (municipalPolygon) {
                municipalPolygon.setStyle({
                    color: '#1d74c9',
                    opacity: 0.58,
                    weight: 2.2
                });
            }

            Object.keys(featureLayers).forEach(type => {
                if (type !== 'boundary') {
                    featureLayers[type].clearLayers();
                }
            });
            resetLayerBadges();
            updateSelectedMarkerState();
            updateDynamicLegend();
            resetSpatialAnalysis('Select a barangay to load measurements computed from PostGIS.');

            map.setView([15.8287, 120.4173], 14);
        }

        function closeHudScope(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            resetBarangaySelection();
        }

        function applySelectedBarangays() {
            const selectedIds = Array.from(selectedBarangayIds);

            if (selectedIds.length === 0) {
                resetBarangaySelection();
                return;
            }

            activeBarangayId = selectedIds.length === 1 ? selectedIds[0] : null;
            syncSelectedListState();

            // Hide municipal boundary when a barangay is selected
            if (municipalPolygon) {
                municipalPolygon.setStyle({
                    opacity: 0.0,
                    weight: 0.0
                });
            }
 
            // Robust UX: Auto-enable and check the boundary layer checkbox if it was hidden
            const boundaryCheckbox = document.getElementById('layer-boundary');
            if (boundaryCheckbox && !boundaryCheckbox.checked) {
                boundaryCheckbox.checked = true;
                if (!map.hasLayer(featureLayers.boundary)) {
                    map.addLayer(featureLayers.boundary);
                }
            }
 
            const selectedPolygons = [];
            const selectedBoundsLayers = [];
 
            // Update all boundaries' styling dynamically (Glowing Active Highlight vs completely transparent inactives)
            Object.keys(barangayPolygons).forEach(brgyId => {
                const poly = barangayPolygons[brgyId];
                if (selectedBarangayIds.has(parseInt(brgyId))) {
	                    poly.setStyle({
	                        opacity: 1.0, // Fully visible border!
	                        color: '#0099ff', // bright cyan
	                        fillColor: '#0099ff',
	                        fillOpacity: 0.10,
	                        weight: 3.2,
	                        dashArray: ''
                    });
                    poly.bringToFront();
                    selectedPolygons.push(poly);
                    selectedBoundsLayers.push(poly);
                } else {
                    poly.setStyle({
                        opacity: 0.0, // Completely invisible!
                        fillOpacity: 0.0,
                        weight: 0
                    });
                }
            });
            updateSelectedMarkerState();

            selectedIds
                .filter(id => !barangayPolygons[id] && markers[id])
                .forEach(id => selectedBoundsLayers.push(markers[id]));

            // Update HUD Scope Title exactly like the picture!
            const hudScope = document.getElementById('map-hud-scope');
            const hudScopeName = document.getElementById('hud-scope-name');
            if (hudScope && hudScopeName) {
                hudScopeName.innerText = selectedIds.length === 1
                    ? barangays.find(b => parseInt(b.id) === selectedIds[0])?.name
                    : `${selectedIds.length} BARANGAYS`;
                hudScope.style.display = 'block';
            }

            if (selectedIds.length === 1) {
                const selectedBrgy = barangays.find(b => parseInt(b.id) === selectedIds[0]);
                updateSidebar(selectedBrgy.id);
                document.getElementById('active-brgy-name').innerText = selectedBrgy.name;
                document.getElementById('active-brgy-subtitle').innerText = `${selectedBrgy.municipality || 'Bayambang'}, ${selectedBrgy.province || 'Pangasinan'}`;
                fetchBarangayFeatures(selectedBrgy.id);
            } else {
                document.getElementById('active-brgy-name').innerText = `${selectedIds.length} Selected`;
                document.getElementById('active-brgy-subtitle').innerText = 'Multiple barangays selected';
                Object.keys(featureLayers).forEach(type => {
                    if (type !== 'boundary') {
                        featureLayers[type].clearLayers();
                    }
                });
                resetLayerBadges();
                updateDynamicLegend();
                resetSpatialAnalysis('PostGIS measurements are available for one selected barangay at a time.');
            }
            
            // Premium smooth transition: fit map bounds strictly to the active boundary
            if (selectedBoundsLayers.length > 0) {
                const group = L.featureGroup(selectedBoundsLayers);
                fitSelectionBounds(group.getBounds());
            } else if (selectedIds.length === 1 && markers[selectedIds[0]]) {
                const selectedBrgy = barangays.find(b => parseInt(b.id) === selectedIds[0]);
                const lat = parseFloat(selectedBrgy.latitude);
                const lng = parseFloat(selectedBrgy.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    map.setView([lat, lng], 14);
                    markers[selectedIds[0]].openPopup();
                }
            }
        }

        function fitSelectionBounds(bounds) {
            map.fitBounds(bounds, {
                paddingTopLeft: [380, 120],
                paddingBottomRight: [430, 120],
                maxZoom: 13,
                animate: true,
                duration: 1.0
            });
        }

        function searchBarangays() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const clearBtn = document.getElementById('clearBtn');
            
            if (clearBtn) clearBtn.style.display = query ? 'block' : 'none';
            
            if (query === '') {
                filteredBarangays = barangays.filter(b => !isMunicipalBoundary(b));
            } else {
                filteredBarangays = barangays.filter(brgy => 
                    brgy.name.toLowerCase().includes(query) && !isMunicipalBoundary(brgy)
                );
            }
            
            renderBarangayList();
        }

        function clearSearch() {
            const queryInput = document.getElementById('searchInput');
            if (queryInput) queryInput.value = '';
            searchBarangays();
        }

        function toggleIdentify(btn) {
            identifyActive = !identifyActive;
            btn.classList.toggle('active', identifyActive);
            document.getElementById('map').style.cursor = identifyActive ? 'crosshair' : '';
        }

        function startMeasure() {
            identifyActive = false;
            document.querySelectorAll('.toolbar-btn.active').forEach(btn => btn.classList.remove('active'));
            if(!measureControlAdded) {
                map.addControl(measureControl);
                measureControlAdded = true;
            }
            measureControl._startMeasure();
        }

        function clearMeasure() {
            if (measureControl) {
                try { map.removeControl(measureControl); } catch (e) {}
            }

            document.querySelectorAll('.layer-measurearea, .layer-measureboundary, .layer-measure-resultarea, .layer-measure-resultline, .layer-measuredrag, .layer-measurevertex, .layer-measure-resultpoint')
                .forEach(el => el.remove());

            measureControl = createMeasureControl();
            measureControlAdded = false;
        }

        function toggleEditMode(btn) {
            if (!boundaryEditingEnabled) {
                editModeActive = false;
                if (btn) btn.classList.remove('active');
                if (map && map.pm) {
                    map.pm.disableGlobalEditMode();
                    map.pm.disableGlobalDragMode();
                    map.pm.disableGlobalRemovalMode();
                    map.pm.removeControls();
                }
                return;
            }

            editModeActive = !editModeActive;
            btn.classList.toggle('active', editModeActive);

            if (editModeActive) {
                clearMeasure();
                identifyActive = false;
                document.getElementById('map').style.cursor = '';

                map.pm.addControls({
                    position: 'topleft',
                    drawMarker: false,
                    drawCircleMarker: false,
                    drawPolyline: false,
                    drawRectangle: true,
                    drawPolygon: true,
                    drawCircle: false,
                    editMode: true,
                    dragMode: true,
                    removalMode: true,
                    cutPolygon: false,
                    rotateMode: false,
                });
            } else {
                map.pm.disableGlobalEditMode();
                map.pm.disableGlobalDragMode();
                map.pm.disableGlobalRemovalMode();
                map.pm.removeControls();
            }
        }

        function findMyLocation() {
            map.locate({ setView: true, maxZoom: 16 });
        }

        function updateSidebar(id) {
            const brgy = barangays.find(b => b.id == id);
            if (!brgy) return;

            const hazard = brgy.hazard_level || 'N/A';
            const lat = parseFloat(brgy.latitude);
            const lng = parseFloat(brgy.longitude);
            const coordinates = (!isNaN(lat) && !isNaN(lng))
                ? `${lat.toFixed(6)}, ${lng.toFixed(6)}`
                : 'N/A';

            setProfileText('profile-name', brgy.name || 'N/A');
            setProfileText('profile-total-area', formatHectares(brgy.total_area));
            setProfileText('profile-population', formatPopulation(brgy.population));
            setProfileText('profile-hazard', hazard);
            setProfileText('profile-official-name', brgy.name || 'N/A');
            setProfileText('profile-municipality', brgy.municipality || 'Bayambang');
            setProfileText('profile-province', brgy.province || 'Pangasinan');
            setProfileText('profile-land-area', formatHectares(brgy.total_area));
            setProfileText('profile-land-use', brgy.land_use || 'N/A');
            setProfileText('profile-status', brgy.status || 'Active');
            setProfileText('profile-coordinates', coordinates);
            setProfileText('profile-agri-area', formatHectares(brgy.agri_area, false));
            setProfileText('profile-residential-area', formatHectares(brgy.residential_area, false));
            setProfileText('profile-commercial-area', formatHectares(brgy.commercial_area, false));
            setProfileText('profile-unidentified-area', formatHectares(brgy.unidentified_area, false));

            const hazardElement = document.getElementById('profile-hazard');
            if (hazardElement) hazardElement.style.color = hazardColor(hazard);

            updatePopulationInsight(brgy);
            loadSpatialAnalysis(brgy.id);
        }

        function resetSpatialAnalysis(message) {
            spatialAnalysisToken++;
            setSpatialStatus('Waiting', true);
            setProfileText('postgis-computed-area', 'N/A');
            setProfileText('postgis-perimeter', 'N/A');
            setProfileText('postgis-area-diff', 'N/A');
            setProfileText('postgis-road-length', 'N/A');
            setProfileText('postgis-contained-features', 'N/A');
            setProfileText('postgis-nearest-feature', 'N/A');
            setProfileText('postgis-caption', message || 'Select a barangay to load measurements computed from PostGIS.');
        }

        function setSpatialStatus(text, warning = false) {
            const status = document.getElementById('postgis-status');
            if (!status) return;

            status.innerText = text;
            status.classList.toggle('warning', warning);
        }

        function loadSpatialAnalysis(barangayId) {
            const token = ++spatialAnalysisToken;

            setSpatialStatus('Loading', true);
            setProfileText('postgis-caption', 'Loading PostGIS measurements...');

            fetch(`/admin/barangays/${barangayId}/spatial-analysis`)
                .then(response => {
                    if (!response.ok) throw new Error('Unable to load PostGIS measurements.');
                    return response.json();
                })
                .then(data => {
                    if (token !== spatialAnalysisToken) return;

                    if (data.status !== 'ready') {
                        setSpatialStatus('Offline', true);
                        setProfileText('postgis-computed-area', 'N/A');
                        setProfileText('postgis-perimeter', 'N/A');
                        setProfileText('postgis-area-diff', 'N/A');
                        setProfileText('postgis-road-length', 'N/A');
                        setProfileText('postgis-contained-features', 'N/A');
                        setProfileText('postgis-nearest-feature', 'N/A');
                        setProfileText('postgis-caption', data.message || 'PostGIS measurements are not available.');
                        return;
                    }

                    const nearest = data.nearest_feature
                        ? `${data.nearest_feature.name} (${formatMeters(data.nearest_feature.distance_meters)})`
                        : 'N/A';

                    setSpatialStatus('Synced');
                    setProfileText('postgis-computed-area', formatHectares(data.computed_area_hectares));
                    setProfileText('postgis-perimeter', formatMeters(data.perimeter_meters));
                    setProfileText('postgis-area-diff', formatHectares(data.area_difference_hectares, false));
                    setProfileText('postgis-road-length', formatMeters(data.road_length_meters, false));
                    setProfileText('postgis-contained-features', formatPlainNumber(data.contained_features));
                    setProfileText('postgis-nearest-feature', nearest);
                    setProfileText('postgis-caption', data.synced_at
                        ? `Computed from PostGIS geometry. Last synced ${new Date(data.synced_at).toLocaleString()}.`
                        : data.message || 'Computed from PostGIS geometry.');
                })
                .catch(error => {
                    if (token !== spatialAnalysisToken) return;

                    setSpatialStatus('Offline', true);
                    setProfileText('postgis-caption', error.message || 'PostGIS measurements are not available.');
                });
        }

        function numericValue(value) {
            if (value === null || value === undefined || value === '') return 0;

            const parsed = parseFloat(String(value).replace(/,/g, ''));
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function updatePopulationInsight(brgy) {
            const population = numericValue(brgy.population);
            const area = numericValue(brgy.total_area);
            const density = area > 0 ? population / area : 0;

            const densities = barangays
                .filter(item => !isMunicipalBoundary(item))
                .map(item => {
                    const itemPopulation = numericValue(item.population);
                    const itemArea = numericValue(item.total_area);

                    return {
                        id: item.id,
                        density: itemArea > 0 ? itemPopulation / itemArea : 0,
                    };
                })
                .filter(item => item.density > 0)
                .sort((a, b) => b.density - a.density);

            const averageDensity = densities.length
                ? densities.reduce((sum, item) => sum + item.density, 0) / densities.length
                : 0;
            const maxDensity = densities.length ? densities[0].density : density;
            const rankIndex = densities.findIndex(item => parseInt(item.id) === parseInt(brgy.id));
            const rankText = rankIndex >= 0 ? `#${rankIndex + 1} of ${densities.length}` : 'N/A';
            const percentage = maxDensity > 0 ? Math.min((density / maxDensity) * 100, 100) : 0;
            const comparison = averageDensity > 0 ? ((density - averageDensity) / averageDensity) * 100 : 0;
            const comparisonText = Math.abs(comparison) < 1
                ? 'about equal to'
                : `${Math.abs(comparison).toFixed(0)}% ${comparison > 0 ? 'above' : 'below'}`;

            document.getElementById('population-density-value').innerText = density > 0
                ? `${density.toFixed(1)} people/ha`
                : 'N/A';
            document.getElementById('population-average-value').innerText = averageDensity > 0
                ? `${averageDensity.toFixed(1)} people/ha`
                : 'N/A';
            document.getElementById('population-rank-value').innerText = rankText;
            document.getElementById('population-density-bar').style.width = `${percentage}%`;
            document.getElementById('population-insight-caption').innerText = density > 0 && averageDensity > 0
                ? `${brgy.name} is ${comparisonText} the barangay density average.`
                : 'Population or land area is incomplete for this barangay.';
        }

        function setBasemap(type, element = null) {
            if (element) {
                document.querySelectorAll('.basemap-card').forEach(el => el.classList.remove('active'));
                element.classList.add('active');
            }
            if(currentLayer) map.removeLayer(currentLayer);
            
            const layers = {
                'dark': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap &copy; CARTO',
                    maxZoom: 20,
                    maxNativeZoom: 20
                }),
                'roadmap': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 20,
                    maxNativeZoom: 19
                }),
                'satellite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Esri',
                    maxZoom: 20,
                    maxNativeZoom: 18
                }),
                'terrain': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                    attribution: 'OpenTopoMap',
                    maxZoom: 20,
                    maxNativeZoom: 17
                })
            };
            currentLayer = layers[type] || layers['dark'];
            currentLayer.addTo(map);
        }

        function selectAllBarangays() {
            allSelected = !allSelected;
            activeBarangayId = null;
            selectedBarangayIds.clear();
            
            const btn = document.getElementById('selectAllBtn');
            
            // Clear active states from list
            syncSelectedListState();
            
            // Hide HUD scope
            const hudScope = document.getElementById('map-hud-scope');
            if (hudScope) hudScope.style.display = 'none';
            
            if (allSelected) {
                // Update button text
                btn.innerHTML = '<i class="fa-solid fa-layer-group" style="font-size:8px;"></i> Deselect All';
                barangays
                    .filter(b => !isMunicipalBoundary(b))
                    .forEach(brgy => selectedBarangayIds.add(parseInt(brgy.id)));
                syncSelectedListState();
                
                // Keep municipal boundary visible until an individual barangay is selected.
                if (municipalPolygon) {
                    municipalPolygon.setStyle({
                        color: '#1d74c9',
                        opacity: 0.48,
                        weight: 1.8
                    });
                }
                
                // Show all boundaries
                Object.values(barangayPolygons).forEach(poly => {
                    poly.setStyle({
                        color: '#0099ff',
                        fillColor: '#0099ff',
                        opacity: 0.55,
                        fillOpacity: 0.06,
                        weight: 1.6,
                        dashArray: ''
                    });
                });
                
                // Fit map to show all boundaries
                const group = L.featureGroup(Object.values(barangayPolygons));
                map.fitBounds(group.getBounds(), {
                    padding: [50, 50],
                    maxZoom: 13,
                    animate: true,
                    duration: 1.2
                });
                
                // Clear operational layers
                Object.keys(featureLayers).forEach(type => {
                    if (type !== 'boundary') {
                        featureLayers[type].clearLayers();
                    }
                });
                updateSelectedMarkerState();
                updateDynamicLegend();
            } else {
                // Update button text
                btn.innerHTML = '<i class="fa-solid fa-layer-group" style="font-size:8px;"></i> Select All';
                selectedBarangayIds.clear();
                syncSelectedListState();
                
                // Show municipal boundary again
                if (municipalPolygon) {
                    municipalPolygon.setStyle({
                        color: '#1d74c9',
                        opacity: 0.58,
                        weight: 2.2
                    });
                }
                
                // Reset to default state
                Object.values(barangayPolygons).forEach(poly => {
                    poly.setStyle({
                        opacity: 0.0,
                        fillOpacity: 0.0,
                        weight: 0
                    });
                });
                updateSelectedMarkerState();
                updateDynamicLegend();
                
                map.setView([15.8287, 120.4173], 14);
            }
        }

        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>
