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
            <div class="w-[380px] flex flex-col h-[calc(100vh-120px)] mt-5 pointer-events-auto gap-6">
                
                <!-- Barangays Section -->
                <div class="flex-1 flex flex-col overflow-hidden">
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

                <!-- Layers Section -->
                <div class="bg-cyber-dark/60 border border-cyber-primary/20 p-4">
                    <div class="font-orbitron text-[12px] font-bold text-cyber-primary tracking-[2px] mb-3 uppercase">Map Layers</div>
                    <div class="space-y-2" id="layerToggles">
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script> -->

    <script>
        const barangays = {!! json_encode($barangays) !!};
        const layerTypes = {!! json_encode($layerTypes) !!};
        let map;
        let barangayPolygons = {};
        let activeBarangayId = null;
        let allSelected = false;
        let municipalPolygon = null;
        let layerGroups = {};
        let activeLayerTypes = new Set();

        function initMap() {
            // Strict bounds for Bayambang
            const bayambangBounds = L.latLngBounds(
                L.latLng(15.70, 120.28),
                L.latLng(15.92, 120.58)
            );

            map = L.map('map', {
                zoomControl: false,
                attributionControl: false,
                minZoom: 11,
                maxBounds: bayambangBounds
            }).setView([15.8287, 120.4173], 12);

            // Using Esri World Imagery (Satellite) for the basemap
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Tiles &copy; Esri'
            }).addTo(map);

            renderBarangayList(barangays);
            drawBoundaries();
            renderLayerToggles();

            // Show default SCOPE: BAYAMBANG HUD on load
            map.whenReady(() => {
                if (municipalPolygon) {
                    const center = municipalPolygon.getBounds().getCenter();
                    showHudScope('BAYAMBANG', center);
                }
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
                        if (!activeLayerTypes.has(feature.layer_type_id)) return;

                        const coords = typeof feature.coordinates === 'string' ? JSON.parse(feature.coordinates) : feature.coordinates;
                        const layerType = layerTypes.find(l => l.id === feature.layer_type_id);

                        if (feature.feature_type === 'point') {
                            const marker = L.marker(coords, {
                                icon: L.divIcon({
                                    html: `<i class="${layerType.icon}" style="color: ${layerType.color}; font-size: 20px;"></i>`,
                                    className: 'custom-marker',
                                    iconSize: [20, 20]
                                })
                            });
                            marker.bindPopup(`<strong>${feature.name}</strong>`);
                            layerGroups[feature.layer_type_id].addLayer(marker);
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
                            
                            if (brgy.name.toLowerCase() === 'bayambang') {
                                // Draw municipal boundary — solid blue outline with light blue fill (matches screenshot)
                                municipalPolygon = L.polygon(boundaryCoords, {
                                    color: '#0099ff',
                                    fillColor: '#0099ff',
                                    opacity: 1.0,
                                    fillOpacity: 0.08,
                                    weight: 2.5,
                                    dashArray: '',
                                    className: 'municipal-polygon'
                                }).addTo(map);
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

            const filteredList = list.filter(b => b.name.toLowerCase() !== 'bayambang');

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
        }

        function filterBarangays() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const filtered = barangays.filter(b => b.name.toLowerCase().includes(query) && b.name.toLowerCase() !== 'bayambang');
            renderBarangayList(filtered);
            
            // Re-apply active state if the active item is still in the list
            if (activeBarangayId) {
                const activeItem = document.getElementById(`brgy-item-${activeBarangayId}`);
                if (activeItem) setItemActiveState(activeBarangayId);
            }
        }
        
        function setItemActiveState(id) {
            // Remove active state from all items manually via class toggling since we are using Tailwind utilities
            document.querySelectorAll('.brgy-item').forEach(el => {
                el.classList.remove('bg-gradient-to-r', 'from-cyber-primary/10', 'to-transparent', 'border-cyber-primary', 'text-white', 'drop-shadow-[0_0_5px_#0099ff]');
                el.classList.add('border-transparent');
                el.querySelector('span:first-child').classList.remove('text-cyber-primary');
                el.querySelector('span:first-child').classList.add('text-cyber-primary/30');
                el.querySelector('i').classList.remove('opacity-100');
                el.querySelector('i').classList.add('opacity-0');
            });
            
            // Add active state to target item
            const activeItem = document.getElementById(`brgy-item-${id}`);
            if (activeItem) {
                activeItem.classList.remove('border-transparent');
                activeItem.classList.add('bg-gradient-to-r', 'from-cyber-primary/10', 'to-transparent', 'border-cyber-primary', 'text-white', 'drop-shadow-[0_0_5px_#0099ff]');
                
                // Update children manually for persistent active state without relying on hover
                activeItem.querySelector('span:first-child').classList.remove('text-cyber-primary/30');
                activeItem.querySelector('span:first-child').classList.add('text-cyber-primary');
                
                activeItem.querySelector('i').classList.remove('opacity-0');
                activeItem.querySelector('i').classList.add('opacity-100');
                
                activeItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function selectBarangay(id) {
            const brgy = barangays.find(b => b.id == id);
            if (!brgy) return;
            
            // If clicking the same barangay, deselect it
            if (activeBarangayId === id) {
                activeBarangayId = null;
                allSelected = false;
                
                // Clear active states from list
                document.querySelectorAll('.brgy-item').forEach(el => {
                    el.classList.remove('bg-gradient-to-r', 'from-cyber-primary/10', 'to-transparent', 'border-cyber-primary', 'text-white', 'drop-shadow-[0_0_5px_#0099ff]');
                    el.classList.add('border-transparent');
                    el.querySelector('span:first-child').classList.remove('text-cyber-primary');
                    el.querySelector('span:first-child').classList.add('text-cyber-primary/30');
                    el.querySelector('i').classList.remove('opacity-100');
                    el.querySelector('i').classList.add('opacity-0');
                });
                
                // Reset all polygons
                Object.values(barangayPolygons).forEach(poly => {
                    poly.setStyle({
                        opacity: 0.15,
                        fillOpacity: 0.0,
                        weight: 1.5,
                        dashArray: '4 6'
                    });
                });
                
                // Show municipal boundary again with fill (matches screenshot)
                if (municipalPolygon) {
                    municipalPolygon.setStyle({
                        opacity: 1.0,
                        fillOpacity: 0.08,
                        weight: 2.5
                    });
                }
                
                // Reset map view
                map.setView([15.8287, 120.4173], 12);

                // Show SCOPE: BAYAMBANG HUD again
                if (hudMarker) map.removeLayer(hudMarker);
                if (municipalPolygon) {
                    const center = municipalPolygon.getBounds().getCenter();
                    showHudScope('BAYAMBANG', center);
                }
                
                // Hide barangay profile
                document.getElementById('barangayProfile').classList.add('hidden');
                
                // Clear all layer features
                Object.values(layerGroups).forEach(group => group.clearLayers());
                return;
            }
            
            allSelected = false;
            activeBarangayId = brgy.id;

            // Completely hide municipal boundary (outline + fill) when a barangay is selected
            if (municipalPolygon) {
                municipalPolygon.setStyle({
                    opacity: 0.0,
                    fillOpacity: 0.0,
                    weight: 0.0
                });
            }

            // Update List Active State visually
            setItemActiveState(id);

            // Update Polygons (Glowing Effect)
            Object.keys(barangayPolygons).forEach(polyId => {
                const poly = barangayPolygons[polyId];
                if (polyId == id) {
                    poly.setStyle({
                        color: '#0099ff',
                        fillColor: '#0099ff',
                        opacity: 1.0,
                        fillOpacity: 0.0,  // Outline only — no fill
                        weight: 2.5,
                        dashArray: ''
                    });
                    poly.bringToFront();
                    
                    // Fly to polygon
                    map.flyToBounds(poly.getBounds(), {
                        padding: [50, 50],
                        duration: 1.5,
                        easeLinearity: 0.25
                    });

                    // Show HUD Scope exactly in center of polygon
                    const center = poly.getBounds().getCenter();
                    showHudScope(brgy.name, center);
                    
                    // Show barangay profile
                    showBarangayProfile(brgy);
                    
                    // Load features for active layers
                    loadBarangayFeatures(brgy.id);

                } else {
                    poly.setStyle({
                        opacity: 0.0,  // Hide other barangay outlines completely
                        fillOpacity: 0.0,
                        weight: 1.5,
                        dashArray: '4 6'
                    });
                }
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
                        <div class="scope-box font-orbitron text-[13px] font-bold text-cyber-primary tracking-[2px] uppercase px-5 py-2.5 bg-cyber-dark/85 border border-cyber-primary pointer-events-auto whitespace-nowrap">
                            SCOPE: <span class="text-white ml-1.5">${name}</span>
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
            
            const btn = document.getElementById('selectAllBtn');
            
            // Clear active states from list
            document.querySelectorAll('.brgy-item').forEach(el => {
                el.classList.remove('bg-gradient-to-r', 'from-cyber-primary/10', 'to-transparent', 'border-cyber-primary', 'text-white', 'drop-shadow-[0_0_5px_#0099ff]');
                el.classList.add('border-transparent');
            });
            
            if (allSelected) {
                // Update button text
                btn.innerHTML = '<i class="fa-solid fa-layer-group mr-1"></i> DESELECT ALL';
                
                // Hide municipal boundary
                if (municipalPolygon) {
                    municipalPolygon.setStyle({
                        opacity: 0.0,
                        weight: 0.0
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
                    duration: 1.5
                });
                
                if (hudMarker) map.removeLayer(hudMarker);
            } else {
                // Update button text
                btn.innerHTML = '<i class="fa-solid fa-layer-group mr-1"></i> SELECT ALL';
                
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
                    <div class="text-white">${brgy.area_hectares ? brgy.area_hectares + ' ha' : 'N/A'}</div>
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
