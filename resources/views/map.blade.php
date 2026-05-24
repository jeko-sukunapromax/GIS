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
                    
                    <div class="bg-cyber-dark/60 border border-cyber-primary/20 px-4 py-2.5 flex items-center gap-2.5 mb-4">
                        <input type="text" id="searchInput" placeholder="SEARCH BARANGAY..." onkeyup="filterBarangays()" class="bg-transparent border-none text-cyber-primary font-rajdhani text-[14px] w-full outline-none placeholder-cyber-primary/30 placeholder:tracking-[1px]">
                        <i class="fa-solid fa-magnifying-glass text-cyber-primary text-[12px]"></i>
                    </div>

                    <div class="text-[11px] text-cyber-muted mb-4 tracking-[1px] flex gap-1.5 justify-between items-center">
                        <span class="text-cyber-primary">BAYAMBANG</span>
                        <button onclick="selectAllBarangays()" id="selectAllBtn" class="font-orbitron text-[9px] text-cyber-primary px-2 py-1 border border-cyber-primary/30 transition-all duration-300 hover:bg-cyber-primary/10 hover:shadow-[0_0_10px_rgba(0,153,255,0.4)] tracking-[1px]">
                            <i class="fa-solid fa-layer-group mr-1"></i> SELECT ALL
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto flex flex-col gap-0.5 pr-2.5 scrollbar-cyber" id="barangayList">
                        <!-- Loaded dynamically via JS -->
                    </div>
                </div>

                <div id="panelResizeHandle" class="sidebar-resize-handle" title="Resize sidebar sections"></div>

                <!-- Layers Section -->
                <div id="layersPanel" class="bg-cyber-dark/60 border border-cyber-primary/20 p-4 flex flex-col overflow-hidden">
                    <div class="font-orbitron text-[12px] font-bold text-cyber-primary tracking-[2px] mb-3 uppercase flex-shrink-0">Map Layers</div>
                    <div class="flex-1 space-y-2 overflow-y-auto pr-2 scrollbar-cyber" id="layerToggles">
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
                attribution: 'Tiles &copy; Esri'
            }).addTo(map);

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

            layerTypes.forEach(layer => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between text-[12px] py-1';
                div.innerHTML = `
                    <label for="layer-${layer.id}" class="text-cyber-text cursor-pointer hover:text-cyber-primary transition-colors flex-1">
                        ${layer.name}
                    </label>
                    <input type="checkbox" id="layer-${layer.id}" onchange="toggleLayer(${layer.id})" class="w-4 h-4 accent-cyber-primary cursor-pointer">
                `;
                container.appendChild(div);

                // Initialize layer group
                layerGroups[layer.id] = L.layerGroup();
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

            if (activeLayerTypes.size === 0) return;

            fetch(`/api/barangays/${barangayId}/features`)
                .then(res => res.json())
                .then(features => {
                    features.forEach(feature => {
                        const layerType = layerTypes.find(l => l.code === feature.feature_type);
                        if (!layerType || !activeLayerTypes.has(layerType.id)) return;

                        const coords = typeof feature.coordinates === 'string'
                            ? JSON.parse(feature.coordinates)
                            : feature.coordinates;

                        if (layerType.geom_type === 'point' && feature.latitude && feature.longitude) {
                            const marker = L.marker([feature.latitude, feature.longitude], {
                                icon: L.divIcon({
                                    html: `<i class="${layerType.icon}" style="color: ${layerType.color}; font-size: 20px;"></i>`,
                                    className: 'custom-marker',
                                    iconSize: [20, 20]
                                })
                            });
                            marker.bindPopup(`<strong>${feature.name}</strong>`);
                            layerGroups[layerType.id].addLayer(marker);
                        } else if (Array.isArray(coords) && coords.length > 0) {
                            const popup = `<strong>${feature.name}</strong>`;

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
                                if (activeBarangayId !== brgy.id && !allSelected) {
                                    polygon.setStyle({ opacity: 0.6, fillOpacity: 0.05, dashArray: '' });
                                }
                            });
                            polygon.on('mouseout', () => {
                                if (activeBarangayId !== brgy.id && !allSelected) {
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

            filteredList.forEach((brgy, index) => {
                // Pad index with leading zero
                const num = String(index + 1).padStart(2, '0');
                
                const div = document.createElement('div');
                // Added "group" class for targeting child elements on hover, and active states
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

            syncSelectedListState();
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
                activeItem.querySelector('span:first-child').classList.remove('text-cyber-primary/30');
                activeItem.querySelector('span:first-child').classList.add('text-cyber-primary');
                activeItem.querySelector('i').classList.remove('opacity-0');
                activeItem.querySelector('i').classList.add('opacity-100');
            }
        }

        function clearListActiveStates() {
            document.querySelectorAll('.brgy-item').forEach(el => {
                el.classList.remove('bg-gradient-to-r', 'from-cyber-primary/10', 'to-transparent', 'border-cyber-primary', 'text-white', 'drop-shadow-[0_0_5px_#0099ff]');
                el.classList.add('border-transparent');
                el.querySelector('span:first-child').classList.remove('text-cyber-primary');
                el.querySelector('span:first-child').classList.add('text-cyber-primary/30');
                el.querySelector('i').classList.remove('opacity-100');
                el.querySelector('i').classList.add('opacity-0');
            });
        }

        function syncSelectedListState() {
            clearListActiveStates();
            selectedBarangayIds.forEach(id => setItemActiveState(id));
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
            selectedBarangayIds.has(numericId)
                ? selectedBarangayIds.delete(numericId)
                : selectedBarangayIds.add(numericId);

            applySelectedBarangays();
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
                } else {
                    poly.setStyle({
                        opacity: 0.0,  // Hide other barangay outlines completely
                        fillOpacity: 0.0,
                        weight: 1.5,
                        dashArray: '4 6'
                    });
                }
            });

            if (selectedPolygons.length > 0) {
                const group = L.featureGroup(selectedPolygons);
                flyToSelectionBounds(group.getBounds());

                if (selectedIds.length === 1) {
                    const selectedBrgy = barangays.find(b => parseInt(b.id) === selectedIds[0]);
                    const center = selectedPolygons[0].getBounds().getCenter();
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
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">Population</div>
                    <div class="text-white">${brgy.population || 'N/A'}</div>
                </div>
                <div class="mb-3">
                    <div class="text-cyber-muted text-[10px] uppercase tracking-[1px] mb-1">Area</div>
                    <div class="text-white">${brgy.total_area ? brgy.total_area + ' ha' : 'N/A'}</div>
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
