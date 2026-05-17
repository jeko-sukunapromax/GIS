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
            @if(session('success'))
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif

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
                            <option value="critical_facilities">Critical Facilities</option>
                            <option value="drrm">DRRM Group</option>
                            <option value="infrastructure">Infrastructure</option>
                            <option value="population">Population Data</option>
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
                            </div>
                        </div>
                        <form action="{{ route('admin.features.destroy', $feat) }}" method="POST" onsubmit="return confirm('Delete this asset from map?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
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
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script>
        const activeBarangay = @json($selectedBarangay);
        const activeFeatures = @json($features);
        const dbLayerTypes = @json($layerTypes);
        
        // Initial setup for Map centering
        const centerLat = activeBarangay && activeBarangay.latitude ? activeBarangay.latitude : 15.8287;
        const centerLng = activeBarangay && activeBarangay.longitude ? activeBarangay.longitude : 120.4173;
        
        const map = L.map('map', { maxZoom: 20 }).setView([centerLat, centerLng], 14);

        // Premium Dark basemap matching dashboard style
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            maxZoom: 20,
            maxNativeZoom: 20
        }).addTo(map);

        const drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

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
                    }).addTo(map).bindPopup(`<strong>${feat.name}</strong><br><span style="font-size: 11px; color:#94a3b8;">${config.name || 'Road Network'}</span>`);
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
                            .addTo(map).bindPopup(`<strong>${feat.name}</strong><br><span style="font-size: 11px; color:#94a3b8;">${config.name}</span>`);
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

        // ON DRAW EVENT
        map.on(L.Draw.Event.CREATED, function (event) {
            if (activeDrawLayer) drawnItems.removeLayer(activeDrawLayer);
            
            activeDrawLayer = event.layer;
            drawnItems.addLayer(activeDrawLayer);
            
            const type = event.layerType;
            
            if (type === 'marker') {
                const latlng = activeDrawLayer.getLatLng();
                document.getElementById('latitude').value = latlng.lat.toFixed(7);
                document.getElementById('longitude').value = latlng.lng.toFixed(7);
                document.getElementById('coordinates').value = '';
            } else if (type === 'polyline') {
                const latlngs = activeDrawLayer.getLatLngs();
                const coords = latlngs.map(ll => [ll.lat, ll.lng]);
                document.getElementById('coordinates').value = JSON.stringify(coords);
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
            } else if (type === 'polygon') {
                const latlngs = activeDrawLayer.getLatLngs()[0];
                const coords = latlngs.map(ll => [ll.lat, ll.lng]);
                document.getElementById('coordinates').value = JSON.stringify(coords);
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
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
        const featureTypes = {
            critical_facilities: [],
            drrm: [],
            infrastructure: [],
            population: []
        };

        dbLayerTypes.forEach(type => {
            if (featureTypes[type.category] !== undefined) {
                let suffix = '';
                if (type.geom_type === 'polyline') suffix = ' (Polyline)';
                else if (type.geom_type === 'polygon') suffix = ' (Polygon)';

                featureTypes[type.category].push({
                    value: type.code,
                    label: type.name + suffix
                });
            }
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

        // DYNAMIC METADATA UI FIELDS DEPENDING ON TYPE
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
                    span.innerHTML = `Use the <strong>Marker tool</strong> (Pin icon 📍 in upper right of map) and click on the map to set the <strong>${config.name}</strong> location.`;
                } else if (geomType === 'polyline') {
                    icon.className = 'fa-solid fa-route';
                    span.innerHTML = `Use the <strong>Line tool</strong> (diagonal line icon 🛣️ in upper right) to draw the <strong>${config.name}</strong> road/route.`;
                } else if (geomType === 'polygon') {
                    icon.className = 'fa-solid fa-draw-polygon';
                    span.innerHTML = `Use the <strong>Polygon tool</strong> (pentagon icon ⬡ in upper right) to draw the <strong>${config.name}</strong> boundary/area.`;
                }
            }

            let html = '';

            if (type === 'barangay_hall') {
                html = `
                    <div class="section-desc"><strong>Critical Facility Details:</strong></div>
                    <div class="form-group">
                        <label>Brgy. Captain / Official Name</label>
                        <input type="text" name="metadata[official]" placeholder="e.g. Capt. Juan Ramos">
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="metadata[status]">
                                <option value="Operational">Operational</option>
                                <option value="Under Maintenance">Under Maintenance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Contact No.</label>
                            <input type="text" name="metadata[contact]" placeholder="09XX-XXX-XXXX">
                        </div>
                    </div>
                `;
            } else if (type === 'health_center') {
                html = `
                    <div class="section-desc"><strong>Health Care Details:</strong></div>
                    <div class="form-group">
                        <label>Nurse / Midwife Name</label>
                        <input type="text" name="metadata[nurse]" placeholder="e.g. Maria Santos, RN">
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Operating Hours</label>
                            <input type="text" name="metadata[hours]" placeholder="8:00 AM - 5:00 PM">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="metadata[status]">
                                <option value="Operational">Operational</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </div>
                `;
            } else if (type === 'multipurpose_bldg' || type === 'covered_court') {
                html = `
                    <div class="section-desc"><strong>Assembly Details:</strong></div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Holding Capacity</label>
                            <input type="text" name="metadata[capacity]" placeholder="e.g. 200 persons">
                        </div>
                        <div class="form-group">
                            <label>Evacuation Ready?</label>
                            <select name="metadata[evac_ready]">
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>
                `;
            } else if (type === 'police_post') {
                html = `
                    <div class="section-desc"><strong>Tanod Outpost Details:</strong></div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Active Officers on Duty</label>
                            <input type="text" name="metadata[on_duty]" placeholder="e.g. 2 officers">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact</label>
                            <input type="text" name="metadata[contact]" placeholder="e.g. Hotline 911">
                        </div>
                    </div>
                `;
            } else if (type === 'bert_member') {
                html = `
                    <div class="section-desc"><strong>BERT Responder Info:</strong></div>
                    <div class="form-group">
                        <label>Responder Role</label>
                        <input type="text" name="metadata[role]" placeholder="e.g. Team Leader, First Aider">
                    </div>
                    <div class="form-group">
                        <label>Special Skills</label>
                        <input type="text" name="metadata[skills]" placeholder="e.g. Flood Rescue, First Aid, CPR">
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="metadata[phone]" placeholder="09XX-XXX-XXXX">
                        </div>
                        <div class="form-group">
                            <label>Deployment Status</label>
                            <select name="metadata[status]">
                                <option value="Active">Active Responder</option>
                                <option value="Inactive">On Leave / Inactive</option>
                            </select>
                        </div>
                    </div>
                `;
            } else if (type === 'road_network') {
                html = `
                    <div class="section-desc"><strong>Infrastructure Details:</strong></div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Road Type</label>
                            <input type="text" name="metadata[type]" placeholder="e.g. Concrete Highway">
                        </div>
                        <div class="form-group">
                            <label>Condition</label>
                            <select name="metadata[status]">
                                <option value="Good Condition">Good Condition</option>
                                <option value="Needs Maintenance">Needs Maintenance</option>
                                <option value="Damaged / Closed">Damaged / Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Average Width</label>
                            <input type="text" name="metadata[width]" placeholder="e.g. 6.0 meters">
                        </div>
                        <div class="form-group">
                            <label>Length</label>
                            <input type="text" name="metadata[length]" placeholder="e.g. 1.2 km">
                        </div>
                    </div>
                `;
            } else if (type === 'population_density') {
                html = `
                    <div class="section-desc"><strong>Zone Details:</strong></div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Density Level</label>
                            <select name="metadata[density_level]">
                                <option value="High">High Density</option>
                                <option value="Medium">Medium Density</option>
                                <option value="Low">Low Density</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Est. Households</label>
                            <input type="text" name="metadata[est_households]" placeholder="e.g. 150 households">
                        </div>
                    </div>
                `;
            } else if (type === 'household_distribution') {
                html = `
                    <div class="section-desc"><strong>Household Details:</strong></div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Household No.</label>
                            <input type="text" name="metadata[house_no]" placeholder="e.g. 104">
                        </div>
                        <div class="form-group">
                            <label>Household Head</label>
                            <input type="text" name="metadata[head]" placeholder="e.g. Juan Dela Cruz">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Family Members Count</label>
                            <input type="number" name="metadata[members]" placeholder="e.g. 5">
                        </div>
                        <div class="form-group">
                            <label>Flood/Landslide Risk</label>
                            <select name="metadata[hazard_risk]">
                                <option value="Low">Low Risk</option>
                                <option value="Moderate">Moderate Risk</option>
                                <option value="High">High Risk</option>
                            </select>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
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
