<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoBayambang - Public View</title>
    
    <!-- Tailwind CSS (CDN for rapid prototyping/standalone views) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tailwind Config for Custom Colors/Fonts -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cyber: {
                            primary: '#0099ff',
                            'primary-glow': 'rgba(0, 153, 255, 0.4)',
                            dark: '#050a0f',
                            panel: 'rgba(5, 10, 15, 0.6)',
                            text: '#b3d4e0',
                            muted: '#9aa0a6'
                        }
                    },
                    fontFamily: {
                        orbitron: ['Orbitron', 'sans-serif'],
                        rajdhani: ['Rajdhani', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Google Fonts for Sci-Fi Aesthetic -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Minimal custom CSS for elements that Tailwind doesn't handle natively without plugins */
        .scrollbar-cyber::-webkit-scrollbar { width: 4px; }
        .scrollbar-cyber::-webkit-scrollbar-track { background: rgba(0, 153, 255, 0.05); }
        .scrollbar-cyber::-webkit-scrollbar-thumb { background: #0099ff; }

        /* Dynamic Scope Tooltip on Map */
        .scope-box {
            box-shadow: 0 0 15px rgba(0, 153, 255, 0.4), inset 0 0 10px rgba(0, 153, 255, 0.1);
            backdrop-filter: blur(4px);
        }
        
        /* Required for Leaflet Map to sit in background */
        #map { background: #020508; }

        .left-map-panel,
        #barangayPanel,
        #layersPanel {
            min-height: 0;
        }

        .layers-panel-clean {
            background: rgba(6, 12, 20, 0.74);
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 8px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(10px);
            padding: 14px;
        }

        .layers-panel-title {
            color: #f8fafc;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .layer-category-group + .layer-category-group {
            margin-top: 12px;
        }

        .layer-category-heading {
            color: rgba(148, 163, 184, 0.88);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1.2;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .layer-toggle-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 10px;
            min-height: 32px;
            padding: 6px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        }

        .layer-toggle-row:last-child {
            border-bottom: 0;
        }

        .layer-toggle-label {
            min-width: 0;
            color: #dbeafe;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color 0.2s ease;
            white-space: nowrap;
        }

        .layer-toggle-label:hover {
            color: #ffffff;
        }

        .layer-count-badge {
            min-width: 24px;
            padding: 1px 6px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.64);
            color: rgba(203, 213, 225, 0.86);
            font-size: 11px;
            line-height: 18px;
            text-align: center;
        }

        .layer-checkbox {
            width: 15px;
            height: 15px;
            accent-color: #38bdf8;
            cursor: pointer;
        }

        .layer-feature-marker {
            background: transparent;
            border: 0;
        }

        .layer-feature-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border: 2px solid rgba(255, 255, 255, 0.92);
            border-radius: 999px;
            background: var(--marker-color, #38bdf8);
            color: #ffffff;
            box-shadow: 0 0 0 2px rgba(7, 17, 31, 0.86), 0 8px 18px rgba(0, 0, 0, 0.35);
        }

        .layer-feature-pin i {
            font-size: 12px;
            line-height: 1;
        }

        .sidebar-resize-handle {
            height: 18px;
            flex: 0 0 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: row-resize;
            touch-action: none;
        }

        .sidebar-resize-handle::before {
            content: "";
            width: 56px;
            height: 2px;
            background: rgba(0, 153, 255, 0.55);
            box-shadow: 0 0 10px rgba(0, 153, 255, 0.35);
            transition: background 0.2s ease, width 0.2s ease;
        }

        .sidebar-resize-handle:hover::before,
        body.resizing-sidebar .sidebar-resize-handle::before {
            width: 86px;
            background: #0099ff;
        }

        body.resizing-sidebar {
            cursor: row-resize;
            user-select: none;
        }

        @media (max-height: 760px) {
            .left-map-panel {
                height: calc(100vh - 92px) !important;
                margin-top: 12px !important;
            }
        }
    </style>
</head>
<body class="bg-cyber-dark text-cyber-text font-rajdhani h-screen overflow-hidden relative">

    <!-- Map Background -->
    <div id="map" class="absolute inset-0 z-0 pointer-events-auto"></div>

    <!-- UI Overlay (Pointer Events None so map is clickable) -->
    <div class="absolute inset-0 z-50 pointer-events-none flex flex-col">
        
        <!-- Black Fades -->
        <div class="absolute top-0 left-0 h-full w-[500px] -z-10 bg-gradient-to-r from-black/95 via-black/70 to-transparent"></div>
        <div class="absolute top-0 right-0 h-full w-[500px] -z-10 bg-gradient-to-l from-black/95 via-black/70 to-transparent"></div>
        
        <!-- Top Navbar -->
        <nav class="bg-cyber-dark/80 border-b border-cyber-primary/20 backdrop-blur-md pointer-events-auto">
            <div class="flex items-center justify-between px-6 py-3">
                <div class="flex items-center gap-3">
                    <img src="/images/logo.png" alt="Bayambang Logo" class="h-10 w-10 drop-shadow-[0_0_10px_rgba(0,153,255,0.6)]">
                    <span class="font-orbitron text-lg font-bold text-cyber-primary tracking-wider drop-shadow-[0_0_10px_rgba(0,153,255,0.4)]">GEOBAYAMBANG</span>
                </div>
                
                <div class="flex items-center gap-8 font-orbitron text-[11px] tracking-[2px] uppercase">
                    <a href="/" class="text-cyber-primary no-underline transition-all duration-300 hover:text-white hover:drop-shadow-[0_0_10px_#0099ff]">
                        <i class="fa-solid fa-map mr-2"></i>MAP
                    </a>
                    <a href="#" class="text-cyber-text no-underline transition-all duration-300 hover:text-cyber-primary hover:drop-shadow-[0_0_10px_#0099ff]">
                        <i class="fa-solid fa-info-circle mr-2"></i>ABOUT
                    </a>
                    <a href="#" class="text-cyber-text no-underline transition-all duration-300 hover:text-cyber-primary hover:drop-shadow-[0_0_10px_#0099ff]">
                        <i class="fa-solid fa-headset mr-2"></i>SUPPORT
                    </a>
                </div>
                
                <a href="/admin/barangays" class="font-orbitron text-[10px] text-cyber-primary no-underline tracking-[1px] px-3 py-1.5 border border-cyber-primary/30 transition-all duration-300 hover:bg-cyber-primary/10 hover:shadow-[0_0_10px_rgba(0,153,255,0.4)]">
                    <i class="fa-solid fa-lock"></i> ADMIN
                </a>
            </div>
        </nav>

        <!-- Main Content Layout -->
        <div class="flex-1 flex justify-between px-10">
            
            <!-- Left Panel - Barangays List + Layers -->
            <div id="leftMapPanel" class="left-map-panel w-[380px] flex flex-col h-[calc(100vh-120px)] mt-5 pointer-events-auto">
                
                <!-- Barangays Section -->
                <div id="barangayPanel" class="flex flex-col overflow-hidden">
                    <div class="font-orbitron text-[14px] font-bold text-cyber-primary tracking-[3px] mb-4 flex items-center gap-2.5 before:content-[''] before:block before:h-px before:bg-cyber-primary before:flex-1 before:opacity-50 after:content-[''] after:block after:h-px after:bg-cyber-primary after:flex-1 after:opacity-50">BARANGAYS</div>
                    
                    <!-- View Switcher -->
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <button onclick="switchView('all')" id="viewAllBtn" class="font-orbitron text-[10px] tracking-[2px] py-1.5 border border-cyber-primary bg-cyber-primary/15 text-white transition-all duration-300 shadow-[0_0_8px_rgba(0,153,255,0.3)]">
                            ALL BARANGAYS
                        </button>
                        <button onclick="switchView('district')" id="viewDistrictBtn" class="font-orbitron text-[10px] tracking-[2px] py-1.5 border border-cyber-primary/20 bg-transparent text-cyber-muted hover:text-cyber-primary hover:border-cyber-primary/50 transition-all duration-300">
                            BY DISTRICT
                        </button>
                    </div>

                    <div class="bg-cyber-dark/60 border border-cyber-primary/20 px-4 py-2.5 flex items-center gap-2.5 mb-4">
                        <input type="text" id="searchInput" placeholder="SEARCH BARANGAY..." onkeyup="filterBarangays()" class="bg-transparent border-none text-cyber-primary font-rajdhani text-[14px] w-full outline-none placeholder-cyber-primary/30 placeholder:tracking-[1px]">
                        <i class="fa-solid fa-magnifying-glass text-cyber-primary text-[12px]"></i>
                    </div>

                    <div class="text-[11px] text-cyber-muted mb-4 tracking-[1px] flex gap-1.5 justify-between items-center">
                        <span class="text-cyber-primary">BAYAMBANG</span>
                        <div class="flex gap-1.5">
                            <button onclick="toggleMultiSelectMode()" id="multiSelectBtn" class="font-orbitron text-[9px] text-cyber-muted px-2 py-1 border border-cyber-primary/20 transition-all duration-300 hover:bg-cyber-primary/10 hover:text-cyber-primary tracking-[1px]">
                                <i class="fa-solid fa-object-ungroup mr-1"></i> MULTI
                            </button>
                            <button onclick="selectAllBarangays()" id="selectAllBtn" class="font-orbitron text-[9px] text-cyber-primary px-2 py-1 border border-cyber-primary/30 transition-all duration-300 hover:bg-cyber-primary/10 hover:shadow-[0_0_10px_rgba(0,153,255,0.4)] tracking-[1px]">
                                <i class="fa-solid fa-layer-group mr-1"></i> SELECT ALL
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto flex flex-col gap-0.5 pr-2.5 scrollbar-cyber" id="barangayList">
                        <!-- Loaded dynamically via JS -->
                    </div>
                </div>

                <div id="panelResizeHandle" class="sidebar-resize-handle" title="Resize sidebar sections"></div>

                <!-- Layers Section -->
                <div id="layersPanel" class="layers-panel-clean flex flex-col overflow-hidden">
                    <div class="layers-panel-title flex-shrink-0">Map Layers</div>
                    <div class="flex-1 overflow-y-auto pr-2 scrollbar-cyber" id="layerToggles">
                        <!-- Loaded dynamically via JS -->
                    </div>
                </div>
            </div>

            <!-- Right Panel - Barangay Profile -->
            <div class="w-[320px] flex flex-col h-[calc(100vh-120px)] mt-5 pointer-events-auto">
                <div id="barangayProfile" class="bg-cyber-dark/60 border border-cyber-primary/20 p-5 hidden">
                    <div class="font-orbitron text-[14px] font-bold text-cyber-primary tracking-[3px] mb-4 flex items-center gap-2.5 before:content-[''] before:block before:h-px before:bg-cyber-primary before:flex-1 before:opacity-50 after:content-[''] after:block after:h-px after:bg-cyber-primary after:flex-1 after:opacity-50">PROFILE</div>
                    <div id="profileContent" class="text-[13px] space-y-3">
                        <!-- Loaded dynamically via JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="absolute bottom-5 w-full text-center font-orbitron text-[9px] tracking-[2px] text-cyber-primary/40 pointer-events-none">
            ALL RIGHTS RESERVED &copy; 2026 BAYAMBANG MUNICIPALITY
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const barangays = @json($barangays);
        const layerTypes = @json($layerTypes);
        let map;
        let barangayPolygons = {};
        let activeBarangayId = null;
        let selectedBarangayIds = new Set();
        let allSelected = false;
        let municipalPolygon = null;
        let layerGroups = {};
        let activeLayerTypes = new Set();
        let selectedFallbackMarkers = null;
        let currentView = 'all'; // 'all' or 'district'
        let expandedDistricts = new Set();
        let multiSelectMode = false;

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
                    <div style="display:flex; justify-content:space-between; gap:10px; font-size:11px; margin-top:4px;">
                        <span style="color:#94a3b8;">${escapeHtml(metadataLabel(key))}</span>
                        <strong style="text-align:right; max-width:170px; overflow-wrap:anywhere;">${escapeHtml(metadataValue(value))}</strong>
                    </div>
                `)
                .join('');
        }

        function numericValue(value) {
            if (value === null || value === undefined || value === '') return null;

            const parsed = parseFloat(String(value).replace(/,/g, ''));
            return Number.isFinite(parsed) ? parsed : null;
        }

        function formatHectares(value, zeroAsUnavailable = true) {
            const area = numericValue(value);
            if (area === null) return 'N/A';
            if (area === 0 && zeroAsUnavailable) return 'N/A';

            return `${area.toLocaleString('en-US', {
                maximumFractionDigits: 2
            })} ha`;
        }

        function formatPopulation(value) {
            const population = numericValue(value);
            if (population === null) return 'N/A';

            return Math.round(population).toLocaleString('en-US');
        }

        function isMunicipalBoundary(brgy) {
            return brgy.is_municipal_boundary || brgy.name.toLowerCase() === 'bayambang';
        }

        function drawMunicipalPolygon(boundaryCoords) {
            municipalPolygon = L.polygon(boundaryCoords, {
                color: '#0099ff',
                fillColor: '#0099ff',
                opacity: 1.0,
                fillOpacity: 0.08,
                weight: 4,
                dashArray: '',
                className: 'municipal-polygon'
            }).addTo(map);

            municipalPolygon.bringToBack();
        }

        function initMap() {
            // Keep the map around Bayambang, with enough buffer to center edge barangays.
            const bayambangBounds = L.latLngBounds(
                L.latLng(15.58, 120.12),
                L.latLng(16.02, 120.78)
            );

            map = L.map('map', {
                zoomControl: false,
                attributionControl: false,
                minZoom: 11,
                maxBounds: bayambangBounds,
                maxBoundsViscosity: 0.45
            }).setView([15.8287, 120.4173], 12);

            // Using Esri World Imagery (Satellite) for the basemap
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                maxNativeZoom: 18,
                attribution: 'Tiles &copy; Esri'
            }).addTo(map);

            selectedFallbackMarkers = L.layerGroup().addTo(map);
            renderBarangayList(barangays);
            drawBoundaries();
            renderLayerToggles();
            initResizableSidebar();

            // Show default SCOPE: BAYAMBANG HUD on load
            map.whenReady(() => {
                if (municipalPolygon) {
                    const center = municipalPolygon.getBounds().getCenter();
                    showHudScope('BAYAMBANG', center);
                }
            });
        }

        function initResizableSidebar() {
            const panel = document.getElementById('leftMapPanel');
            const barangayPanel = document.getElementById('barangayPanel');
            const layersPanel = document.getElementById('layersPanel');
            const handle = document.getElementById('panelResizeHandle');
            if (!panel || !barangayPanel || !layersPanel || !handle) return;

            const minBarangayHeight = 230;
            const minLayersHeight = 145;
            let startY = 0;
            let startBarangayHeight = 0;

            const getAvailableHeight = () => panel.clientHeight - handle.offsetHeight;

            const applyPanelHeights = (requestedBarangayHeight) => {
                const availableHeight = getAvailableHeight();
                const maxBarangayHeight = Math.max(minBarangayHeight, availableHeight - minLayersHeight);
                const barangayHeight = Math.min(Math.max(requestedBarangayHeight, minBarangayHeight), maxBarangayHeight);
                const layersHeight = Math.max(minLayersHeight, availableHeight - barangayHeight);

                barangayPanel.style.flex = `0 0 ${barangayHeight}px`;
                layersPanel.style.flex = `0 0 ${layersHeight}px`;
            };

            const savedHeight = Number(localStorage.getItem('geoSidebarBarangayHeight'));
            const defaultHeight = Math.round(getAvailableHeight() * 0.58);
            applyPanelHeights(Number.isFinite(savedHeight) && savedHeight > 0 ? savedHeight : defaultHeight);

            handle.addEventListener('pointerdown', (event) => {
                startY = event.clientY;
                startBarangayHeight = barangayPanel.getBoundingClientRect().height;
                document.body.classList.add('resizing-sidebar');
                handle.setPointerCapture(event.pointerId);
            });

            handle.addEventListener('pointermove', (event) => {
                if (!document.body.classList.contains('resizing-sidebar')) return;
                applyPanelHeights(startBarangayHeight + event.clientY - startY);
            });

            handle.addEventListener('pointerup', (event) => {
                if (!document.body.classList.contains('resizing-sidebar')) return;
                document.body.classList.remove('resizing-sidebar');
                localStorage.setItem('geoSidebarBarangayHeight', Math.round(barangayPanel.getBoundingClientRect().height));
                handle.releasePointerCapture(event.pointerId);
            });

            window.addEventListener('resize', () => {
                applyPanelHeights(barangayPanel.getBoundingClientRect().height);
            });
        }

        function renderLayerToggles() {
            const container = document.getElementById('layerToggles');
            container.innerHTML = '';

            const groupedLayers = layerTypes.reduce((groups, layer) => {
                const category = layer.category || 'other';
                groups[category] ||= [];
                groups[category].push(layer);
                return groups;
            }, {});

            Object.entries(groupedLayers).forEach(([category, layers]) => {
                const section = document.createElement('div');
                section.className = 'layer-category-group';
                section.innerHTML = `
                    <div class="layer-category-heading">
                        ${escapeHtml(category.replaceAll('_', ' '))}
                    </div>
                `;

                layers.forEach(layer => {
                    const div = document.createElement('div');
                    div.className = 'layer-toggle-row';
                    div.innerHTML = `
                        <label for="layer-${layer.id}" class="layer-toggle-label">
                            ${escapeHtml(layer.name)}
                        </label>
                        <span id="layer-count-${layer.id}" class="layer-count-badge">0</span>
                        <input type="checkbox" id="layer-${layer.id}" onchange="toggleLayer(${layer.id})" class="layer-checkbox" aria-label="${escapeHtml(layer.name)}">
                    `;
                    section.appendChild(div);

                    layerGroups[layer.id] = L.layerGroup();
                });

                container.appendChild(section);
            });
        }

        function toggleLayer(layerId) {
            const checkbox = document.getElementById(`layer-${layerId}`);
            
            if (checkbox.checked) {
                activeLayerTypes.add(layerId);
                layerGroups[layerId].addTo(map);
                
                // Load features for active barangay if any
                if (activeBarangayId) {
                    loadBarangayFeatures(activeBarangayId);
                }
            } else {
                activeLayerTypes.delete(layerId);
                layerGroups[layerId].clearLayers();
                map.removeLayer(layerGroups[layerId]);
            }
        }

        function loadBarangayFeatures(barangayId) {
            // Clear all layer groups first
            Object.values(layerGroups).forEach(group => group.clearLayers());
            layerTypes.forEach(layer => {
                const badge = document.getElementById(`layer-count-${layer.id}`);
                if (badge) badge.textContent = '0';
            });

            if (activeLayerTypes.size === 0) return;

            fetch(`/api/barangays/${barangayId}/features`)
                .then(res => res.json())
                .then(features => {
                    const counts = {};
                    layerTypes.forEach(layer => counts[layer.id] = 0);

                    features.forEach(feature => {
                        const layerType = layerTypes.find(l => l.code === feature.feature_type);
                        if (!layerType || !activeLayerTypes.has(layerType.id)) return;
                        counts[layerType.id]++;

                        const coords = typeof feature.coordinates === 'string'
                            ? JSON.parse(feature.coordinates)
                            : feature.coordinates;
                        const metadataHtml = featureMetadataHtml(feature.metadata || {});
                        const popup = `
                            <div style="min-width:180px;">
                                <strong style="color:${layerType.color};">${escapeHtml(feature.name)}</strong>
                                <div style="font-size:11px; color:#94a3b8; margin-top:2px;">${escapeHtml(layerType.name)}</div>
                                ${metadataHtml ? `<div style="margin-top:8px;">${metadataHtml}</div>` : ''}
                            </div>
                        `;

                        if (layerType.geom_type === 'point' && feature.latitude && feature.longitude) {
                            const markerColor = layerType.color || '#38bdf8';
                            const markerIcon = layerType.icon || 'fa-solid fa-location-dot';
                            const marker = L.marker([feature.latitude, feature.longitude], {
                                icon: L.divIcon({
                                    html: `<span class="layer-feature-pin" style="--marker-color: ${escapeHtml(markerColor)};"><i class="${escapeHtml(markerIcon)}"></i></span>`,
                                    className: 'layer-feature-marker',
                                    iconSize: [30, 30],
                                    iconAnchor: [15, 15]
                                })
                            });
                            marker.bindPopup(popup);
                            layerGroups[layerType.id].addLayer(marker);
                        } else if (Array.isArray(coords) && coords.length > 0) {
                            if (layerType.geom_type === 'polygon') {
                                const polygon = L.polygon(coords, {
                                    color: layerType.color,
                                    fillColor: layerType.color,
                                    fillOpacity: 0.15,
                                    weight: 2
                                }).bindPopup(popup);

                                layerGroups[layerType.id].addLayer(polygon);
                            } else {
                                const polyline = L.polyline(coords, {
                                    color: layerType.color,
                                    weight: 4,
                                    opacity: 0.85
                                }).bindPopup(popup);

                                layerGroups[layerType.id].addLayer(polyline);
                            }
                        }
                    });

                    Object.entries(counts).forEach(([layerId, count]) => {
                        const badge = document.getElementById(`layer-count-${layerId}`);
                        if (badge) badge.textContent = count;
                    });
                });
        }

        function drawBoundaries() {
            barangays.forEach(brgy => {
                if (brgy.boundary) {
                    try {
                        let boundaryCoords = typeof brgy.boundary === 'string' ? JSON.parse(brgy.boundary) : brgy.boundary;
                        if (Array.isArray(boundaryCoords) && boundaryCoords.length > 0) {
                            
                            if (isMunicipalBoundary(brgy)) {
                                // Draw municipal boundary — solid blue outline with light blue fill (matches screenshot)
                                drawMunicipalPolygon(boundaryCoords);
                                return;
                            }

                            // Futuristic neon cyan polygon
                            const polygon = L.polygon(boundaryCoords, {
                                color: '#0099ff',
                                fillColor: '#0099ff',
                                opacity: 0.15,
                                fillOpacity: 0.0,
                                weight: 1.5,
                                dashArray: '4 6',
                                className: 'cyber-polygon'
                            }).addTo(map);

                            polygon.on('click', () => selectBarangay(brgy.id));
                            
                            // Hover effects - only apply if not active or all selected
                            polygon.on('mouseover', () => {
                                if (!isBarangayCurrentlySelected(brgy.id) && !allSelected) {
                                    polygon.setStyle({ opacity: 0.6, fillOpacity: 0.05, dashArray: '' });
                                }
                            });
                            polygon.on('mouseout', () => {
                                if (!isBarangayCurrentlySelected(brgy.id) && !allSelected) {
                                    polygon.setStyle({ opacity: 0.15, fillOpacity: 0.0, dashArray: '4 6' });
                                }
                            });

                            barangayPolygons[brgy.id] = polygon;
                        }
                    } catch (e) {
                        console.error('Error drawing boundary for ' + brgy.name);
                    }
                }
            });

        }

        function renderBarangayList(list) {
            const container = document.getElementById('barangayList');
            container.innerHTML = '';

            const filteredList = list.filter(b => !isMunicipalBoundary(b));

            if (currentView === 'all') {
                filteredList.forEach((brgy, index) => {
                    const num = String(index + 1).padStart(2, '0');
                    const div = document.createElement('div');
                    div.className = `brgy-item px-4 py-2.5 text-[13px] font-semibold text-cyber-text tracking-[1px] cursor-pointer transition-all duration-200 flex justify-between items-center border-l-2 border-transparent uppercase hover:bg-gradient-to-r hover:from-cyber-primary/10 hover:to-transparent hover:border-cyber-primary hover:text-white hover:drop-shadow-[0_0_5px_#0099ff] group`;
                    div.id = `brgy-item-${brgy.id}`;
                    div.onclick = () => selectBarangay(brgy.id);
                    
                    div.innerHTML = `
                        <div class="flex items-center">
                            <span class="font-orbitron text-[9px] text-cyber-primary/30 mr-2.5 group-hover:text-cyber-primary transition-colors" id="brgy-num-${brgy.id}">${num}</span>
                            <span>${brgy.name}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-0 transition duration-200 text-cyber-primary group-hover:opacity-100" id="brgy-icon-${brgy.id}"></i>
                    `;
                    container.appendChild(div);
                });
            } else {
                // Group by district
                const districts = {};
                filteredList.forEach(b => {
                    const dist = b.district || 'Unassigned';
                    districts[dist] = districts[dist] || [];
                    districts[dist].push(b);
                });

                // Sort districts (District 1 to District 9)
                const sortedDistrictNames = Object.keys(districts).sort((a, b) => {
                    const numA = parseInt(a.replace(/^\D+/g, '')) || 999;
                    const numB = parseInt(b.replace(/^\D+/g, '')) || 999;
                    return numA - numB;
                });

                const query = document.getElementById('searchInput').value.trim();

                sortedDistrictNames.forEach(distName => {
                    const brgys = districts[distName];
                    if (brgys.length === 0) return;

                    const isExpanded = expandedDistricts.has(distName) || query.length > 0;
                    const cleanDistNameId = distName.replace(/\s+/g, '-');

                    const districtDiv = document.createElement('div');
                    districtDiv.className = 'mb-1.5 flex flex-col';

                    const header = document.createElement('div');
                    header.className = `district-header flex items-center justify-between px-3 py-2 bg-cyber-dark/45 border border-cyber-primary/20 hover:border-cyber-primary/45 transition-colors cursor-pointer text-[12px] font-bold text-cyber-primary font-orbitron uppercase`;
                    header.onclick = () => toggleDistrict(distName);

                    const arrowClass = isExpanded ? 'fa-chevron-down' : 'fa-chevron-right';
                    header.innerHTML = `
                        <div class="flex items-center gap-2">
                            <i class="fa-solid ${arrowClass} text-[10px] text-cyber-primary transition-transform duration-200" id="district-arrow-${cleanDistNameId}"></i>
                            <span>${distName}</span>
                        </div>
                        <span class="text-[10px] text-cyber-muted font-normal font-sans">${brgys.length} brgys</span>
                    `;

                    const contentDiv = document.createElement('div');
                    contentDiv.id = `district-content-${cleanDistNameId}`;
                    contentDiv.className = `district-content flex flex-col gap-0.5 mt-0.5 pl-2 transition-all duration-200 ${isExpanded ? '' : 'hidden'}`;

                    brgys.forEach(brgy => {
                        const itemDiv = document.createElement('div');
                        itemDiv.className = `brgy-item px-3 py-2 text-[12px] font-semibold text-cyber-text tracking-[0.5px] cursor-pointer transition-all duration-200 flex flex-col border-l-2 border-transparent uppercase hover:bg-gradient-to-r hover:from-cyber-primary/10 hover:to-transparent hover:border-cyber-primary hover:text-white hover:drop-shadow-[0_0_5px_#0099ff] group`;
                        itemDiv.id = `brgy-item-${brgy.id}`;
                        itemDiv.onclick = (e) => {
                            e.stopPropagation();
                            selectBarangay(brgy.id);
                        };

                        itemDiv.innerHTML = `
                            <div class="flex justify-between items-center w-full">
                                <span>${brgy.name}</span>
                                <i class="fa-solid fa-chevron-right text-[9px] opacity-0 group-hover:opacity-100 transition duration-200 text-cyber-primary" id="brgy-icon-${brgy.id}"></i>
                            </div>
                            <div class="flex flex-col text-[10px] text-cyber-muted font-normal lowercase tracking-[0.5px] mt-1 normal-case">
                                <span class="flex items-center gap-1"><i class="fa-solid fa-user-tie text-[9px] text-cyber-primary/50"></i> Chairman: <strong class="text-white/85 ml-1 capitalize">${(brgy.barangay_chairman || 'N/A').toLowerCase()}</strong></span>
                                <span class="flex items-center gap-1 mt-0.5"><i class="fa-solid fa-graduation-cap text-[9px] text-cyber-primary/50"></i> SK: <strong class="text-white/85 ml-1 capitalize">${(brgy.sk_chairman || 'N/A').toLowerCase()}</strong></span>
                            </div>
                        `;
                        contentDiv.appendChild(itemDiv);
                    });

                    districtDiv.appendChild(header);
                    districtDiv.appendChild(contentDiv);
                    container.appendChild(districtDiv);
                });
            }

            syncSelectedListState();
        }

        function toggleDistrict(distName) {
            const cleanDistNameId = distName.replace(/\s+/g, '-');
            const content = document.getElementById(`district-content-${cleanDistNameId}`);
            const arrow = document.getElementById(`district-arrow-${cleanDistNameId}`);

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                arrow.classList.remove('fa-chevron-right');
                arrow.classList.add('fa-chevron-down');
                expandedDistricts.add(distName);
            } else {
                content.classList.add('hidden');
                arrow.classList.remove('fa-chevron-down');
                arrow.classList.add('fa-chevron-right');
                expandedDistricts.delete(distName);
            }
        }

        function switchView(view) {
            currentView = view;
            
            const viewAllBtn = document.getElementById('viewAllBtn');
            const viewDistrictBtn = document.getElementById('viewDistrictBtn');
            
            if (view === 'all') {
                viewAllBtn.className = "font-orbitron text-[10px] tracking-[2px] py-1.5 border border-cyber-primary bg-cyber-primary/15 text-white transition-all duration-300 shadow-[0_0_8px_rgba(0, 153, 255, 0.3)]";
                viewDistrictBtn.className = "font-orbitron text-[10px] tracking-[2px] py-1.5 border border-cyber-primary/20 bg-transparent text-cyber-muted hover:text-cyber-primary hover:border-cyber-primary/50 transition-all duration-300";
            } else {
                viewDistrictBtn.className = "font-orbitron text-[10px] tracking-[2px] py-1.5 border border-cyber-primary bg-cyber-primary/15 text-white transition-all duration-300 shadow-[0_0_8px_rgba(0, 153, 255, 0.3)]";
                viewAllBtn.className = "font-orbitron text-[10px] tracking-[2px] py-1.5 border border-cyber-primary/20 bg-transparent text-cyber-muted hover:text-cyber-primary hover:border-cyber-primary/50 transition-all duration-300";
            }
            
            filterBarangays();
        }

        function filterBarangays() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const filtered = barangays.filter(b => b.name.toLowerCase().includes(query) && !isMunicipalBoundary(b));
            renderBarangayList(filtered);
            
            syncSelectedListState();
        }
        
        function setItemActiveState(id) {
            const activeItem = document.getElementById(`brgy-item-${id}`);
            if (activeItem) {
                activeItem.classList.remove('border-transparent');
                activeItem.classList.add('bg-gradient-to-r', 'from-cyber-primary/10', 'to-transparent', 'border-cyber-primary', 'text-white', 'drop-shadow-[0_0_5px_#0099ff]');
                
                const numEl = activeItem.querySelector('span:first-child');
                if (numEl && numEl.id === `brgy-num-${id}`) {
                    numEl.classList.remove('text-cyber-primary/30');
                    numEl.classList.add('text-cyber-primary');
                }
                const iconEl = activeItem.querySelector('i');
                if (iconEl) {
                    iconEl.classList.remove('opacity-0');
                    iconEl.classList.add('opacity-100');
                }
            }
        }

        function clearListActiveStates() {
            document.querySelectorAll('.brgy-item').forEach(el => {
                el.classList.remove('bg-gradient-to-r', 'from-cyber-primary/10', 'to-transparent', 'border-cyber-primary', 'text-white', 'drop-shadow-[0_0_5px_#0099ff]');
                el.classList.add('border-transparent');
                
                const numEl = el.querySelector('span:first-child');
                if (numEl && numEl.id && numEl.id.startsWith('brgy-num-')) {
                    numEl.classList.remove('text-cyber-primary');
                    numEl.classList.add('text-cyber-primary/30');
                }
                const iconEl = el.querySelector('i');
                if (iconEl) {
                    iconEl.classList.remove('opacity-100');
                    iconEl.classList.add('opacity-0');
                }
            });
        }

        function syncSelectedListState() {
            clearListActiveStates();
            selectedBarangayIds.forEach(id => setItemActiveState(id));
        }

        function isBarangayCurrentlySelected(id) {
            return selectedBarangayIds.has(parseInt(id));
        }

        function selectBarangay(id) {
            const brgy = barangays.find(b => b.id == id);
            if (!brgy) return;

            allSelected = false;
            const selectAllBtn = document.getElementById('selectAllBtn');
            if (selectAllBtn) {
                selectAllBtn.innerHTML = '<i class="fa-solid fa-layer-group mr-1"></i> SELECT ALL';
            }
            const numericId = parseInt(id);

            if (multiSelectMode) {
                // Multi-select: toggle individual barangay in/out
                selectedBarangayIds.has(numericId)
                    ? selectedBarangayIds.delete(numericId)
                    : selectedBarangayIds.add(numericId);
            } else {
                // Single-select (default): replace entire selection
                if (selectedBarangayIds.size === 1 && selectedBarangayIds.has(numericId)) {
                    // Clicking the already-selected one deselects it
                    selectedBarangayIds.clear();
                } else {
                    selectedBarangayIds.clear();
                    selectedBarangayIds.add(numericId);
                }
            }

            applySelectedBarangays();
        }

        function toggleMultiSelectMode() {
            multiSelectMode = !multiSelectMode;
            const btn = document.getElementById('multiSelectBtn');
            if (btn) {
                if (multiSelectMode) {
                    btn.classList.remove('text-cyber-muted', 'border-cyber-primary/20');
                    btn.classList.add('text-cyber-primary', 'bg-cyber-primary/15', 'border-cyber-primary', 'shadow-[0_0_10px_rgba(0,153,255,0.4)]');
                } else {
                    btn.classList.remove('text-cyber-primary', 'bg-cyber-primary/15', 'border-cyber-primary', 'shadow-[0_0_10px_rgba(0,153,255,0.4)]');
                    btn.classList.add('text-cyber-muted', 'border-cyber-primary/20');
                }
            }
        }

        function resetBarangaySelection(showDefaultHud = true) {
            activeBarangayId = null;
            selectedBarangayIds.clear();
            syncSelectedListState();

            Object.values(barangayPolygons).forEach(poly => {
                poly.setStyle({
                    opacity: 0.15,
                    fillOpacity: 0.0,
                    weight: 1.5,
                    dashArray: '4 6'
                });
            });

            if (municipalPolygon) {
                municipalPolygon.setStyle({
                    opacity: 1.0,
                    fillOpacity: 0.08,
                    weight: 2.5
                });
            }

            map.setView([15.8287, 120.4173], 12);

            if (hudMarker) map.removeLayer(hudMarker);
            if (showDefaultHud && municipalPolygon) {
                const center = municipalPolygon.getBounds().getCenter();
                showHudScope('BAYAMBANG', center);
            }

            document.getElementById('barangayProfile').classList.add('hidden');
            Object.values(layerGroups).forEach(group => group.clearLayers());
            selectedFallbackMarkers?.clearLayers();
        }

        function closeHudScope(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            resetBarangaySelection(false);
        }

        function applySelectedBarangays() {
            const selectedIds = Array.from(selectedBarangayIds);

            if (selectedIds.length === 0) {
                resetBarangaySelection();
                return;
            }

            activeBarangayId = selectedIds.length === 1 ? selectedIds[0] : null;
            syncSelectedListState();

            if (municipalPolygon) {
                municipalPolygon.setStyle({
                    opacity: 0.0,
                    fillOpacity: 0.0,
                    weight: 0.0
                });
            }

            const selectedPolygons = [];
            const selectedBoundsLayers = [];
            selectedFallbackMarkers?.clearLayers();
            Object.keys(barangayPolygons).forEach(polyId => {
                const poly = barangayPolygons[polyId];
                if (selectedBarangayIds.has(parseInt(polyId))) {
                    poly.setStyle({
                        color: '#0099ff',
                        fillColor: '#0099ff',
                        opacity: 1.0,
                        fillOpacity: 0.16,
                        weight: 3.0,
                        dashArray: ''
                    });
                    poly.bringToFront();
                    selectedPolygons.push(poly);
                    selectedBoundsLayers.push(poly);
                } else {
                    poly.setStyle({
                        opacity: 0.0,  // Hide other barangay outlines completely
                        fillOpacity: 0.0,
                        weight: 1.5,
                        dashArray: '4 6'
                    });
                }
            });

            selectedIds
                .filter(id => !barangayPolygons[id])
                .forEach(id => {
                    const brgy = barangays.find(b => parseInt(b.id) === parseInt(id));
                    const lat = parseFloat(brgy?.latitude);
                    const lng = parseFloat(brgy?.longitude);

                    if (!isNaN(lat) && !isNaN(lng)) {
                        const marker = L.marker([lat, lng], {
                            icon: L.divIcon({
                                html: `
                                    <div style="display:flex; flex-direction:column; align-items:center; gap:4px;">
                                        <div style="width:14px; height:14px; border-radius:999px; background:#0099ff; border:2px solid #07111f; box-shadow:0 0 10px rgba(0,153,255,0.75);"></div>
                                        <div style="font-family:Orbitron, sans-serif; font-size:10px; color:#ffffff; text-shadow:0 2px 6px #000; white-space:nowrap;">${escapeHtml(brgy.name)}</div>
                                    </div>
                                `,
                                className: 'selected-brgy-marker',
                                iconSize: [120, 42],
                                iconAnchor: [60, 7]
                            })
                        }).addTo(selectedFallbackMarkers);

                        selectedBoundsLayers.push(marker);
                    }
                });

            if (selectedBoundsLayers.length > 0) {
                const group = L.featureGroup(selectedBoundsLayers);
                flyToSelectionBounds(group.getBounds());

                if (selectedIds.length === 1) {
                    const selectedBrgy = barangays.find(b => parseInt(b.id) === selectedIds[0]);
                    const center = group.getBounds().getCenter();
                    showHudScope(selectedBrgy.name, center);
                    showBarangayProfile(selectedBrgy);
                    loadBarangayFeatures(selectedBrgy.id);
                } else {
                    if (hudMarker) map.removeLayer(hudMarker);
                    showHudScope(`${selectedIds.length} BARANGAYS`, group.getBounds().getCenter());
                    document.getElementById('barangayProfile').classList.add('hidden');
                    Object.values(layerGroups).forEach(group => group.clearLayers());
                }
            }
        }

        function flyToSelectionBounds(bounds) {
            map.flyToBounds(bounds, {
                paddingTopLeft: [430, 130],
                paddingBottomRight: [360, 130],
                maxZoom: 13,
                duration: 1.2,
                easeLinearity: 0.25
            });
        }

        let hudMarker = null;
        function showHudScope(name, latlng) {
            if (hudMarker) {
                map.removeLayer(hudMarker);
            }

            const icon = L.divIcon({
                html: `
                    <div class="flex items-center pointer-events-none">
                        <div class="scope-box font-orbitron text-[13px] font-bold text-cyber-primary tracking-[2px] uppercase px-5 py-2.5 bg-cyber-dark/85 border border-cyber-primary pointer-events-auto whitespace-nowrap inline-flex items-center gap-2 relative">
                            SCOPE: <span class="text-white ml-1.5">${name}</span>
                            <button type="button" onclick="closeHudScope(event)" title="Clear selection" class="absolute -top-2 -right-2 inline-flex items-center justify-center w-4.5 h-4.5 border border-cyber-primary/60 bg-[#07111f] text-cyber-primary hover:bg-cyber-primary hover:text-[#07111f] transition-colors rounded-full shadow-[0_0_8px_rgba(0,153,255,0.4)]" style="width: 18px; height: 18px; font-size: 13px; line-height: 16px; padding-bottom: 1px;">×</button>
                        </div>
                        <svg width="100" height="20" class="-ml-1.5 pointer-events-none">
                            <line x1="0" y1="10" x2="100" y2="10" stroke="#0099ff" stroke-width="1.5" />
                            <circle cx="100" cy="10" r="3" fill="#0099ff" />
                        </svg>
                    </div>
                `,
                className: 'custom-hud-icon',
                iconSize: [0, 0],
                iconAnchor: [0, 10]
            });

            hudMarker = L.marker(latlng, { icon: icon }).addTo(map);
        }

        function selectAllBarangays() {
            allSelected = !allSelected;
            activeBarangayId = null;
            selectedBarangayIds.clear();
            
            const btn = document.getElementById('selectAllBtn');
            
            // Clear active states from list
            clearListActiveStates();
            
            if (allSelected) {
                // Update button text
                btn.innerHTML = '<i class="fa-solid fa-layer-group mr-1"></i> DESELECT ALL';
                barangays
                    .filter(b => !isMunicipalBoundary(b))
                    .forEach(brgy => selectedBarangayIds.add(parseInt(brgy.id)));
                syncSelectedListState();
                
                // Keep municipal boundary visible until an individual barangay is selected.
                if (municipalPolygon) {
                    municipalPolygon.setStyle({
                        opacity: 1.0,
                        fillOpacity: 0.08,
                        weight: 2.5
                    });
                }
                
                // Show all boundaries
                Object.values(barangayPolygons).forEach(poly => {
                    poly.setStyle({
                        color: '#0099ff',
                        fillColor: '#0099ff',
                        opacity: 0.8,
                        fillOpacity: 0.1,
                        weight: 2,
                        dashArray: ''
                    });
                });
                
                // Fit map to show all boundaries
                const group = L.featureGroup(Object.values(barangayPolygons));
                map.flyToBounds(group.getBounds(), {
                    padding: [50, 50],
                    maxZoom: 13,
                    duration: 1.5
                });
                
                if (hudMarker) map.removeLayer(hudMarker);
            } else {
                // Update button text
                btn.innerHTML = '<i class="fa-solid fa-layer-group mr-1"></i> SELECT ALL';
                selectedBarangayIds.clear();
                syncSelectedListState();
                
                // Show municipal boundary again with fill (matches screenshot)
                if (municipalPolygon) {
                    municipalPolygon.setStyle({
                        opacity: 1.0,
                        fillOpacity: 0.08,
                        weight: 2.5
                    });
                }
                
                // Reset to default state
                Object.values(barangayPolygons).forEach(poly => {
                    poly.setStyle({
                        opacity: 0.15,
                        fillOpacity: 0.0,
                        weight: 1.5,
                        dashArray: '4 6'
                    });
                });
                
                map.setView([15.8287, 120.4173], 12);

                // Restore SCOPE: BAYAMBANG HUD
                if (hudMarker) map.removeLayer(hudMarker);
                if (municipalPolygon) {
                    const center = municipalPolygon.getBounds().getCenter();
                    showHudScope('BAYAMBANG', center);
                }
                
                // Hide barangay profile
                document.getElementById('barangayProfile').classList.add('hidden');
                
                // Clear all layer features
                Object.values(layerGroups).forEach(group => group.clearLayers());
            }
        }

        function showBarangayProfile(brgy) {
            const profile = document.getElementById('barangayProfile');
            const content = document.getElementById('profileContent');
            
            profile.classList.remove('hidden');
            
            content.innerHTML = `
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">Barangay</div>
                    <div class="text-white font-semibold">${brgy.name}</div>
                </div>
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">District</div>
                    <div class="text-white">${brgy.district || 'N/A'}</div>
                </div>
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">Barangay Chairman</div>
                    <div class="text-white">${brgy.barangay_chairman || 'N/A'}</div>
                </div>
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">SK Chairman</div>
                    <div class="text-white">${brgy.sk_chairman || 'N/A'}</div>
                </div>
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">Population</div>
                    <div class="text-white">${formatPopulation(brgy.population)}</div>
                </div>
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">Area</div>
                    <div class="text-white">${formatHectares(brgy.total_area)}</div>
                </div>
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">Hazard Level</div>
                    <div class="text-white">${brgy.hazard_level || 'N/A'}</div>
                </div>
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">Land Use</div>
                    <div class="text-white">${brgy.land_use || 'N/A'}</div>
                </div>
            `;
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', initMap);

    </script>
</body>
</html>
