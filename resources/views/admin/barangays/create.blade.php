<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Barangay</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.css" />
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
            width: 380px;
            background: var(--bg-panel);
            padding: 24px;
            overflow-y: auto;
            border-right: 1px solid var(--border-color);
            backdrop-filter: var(--glass-blur);
            z-index: 10;
        }
        
        .map-panel { flex: 1; position: relative; }
        #map { height: 100%; width: 100%; background: #090d16; }
        
        .form-group { margin-bottom: 20px; }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-main);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-heading);
            outline: none;
            transition: all 0.2s ease;
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: var(--accent-blue);
            background: rgba(15, 23, 42, 0.6);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.15);
        }
        
        textarea { resize: vertical; min-height: 100px; }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
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
            border-color: rgba(255,255,255,0.2);
        }
        
        .instructions {
            background: rgba(56, 189, 248, 0.08);
            border: 1px solid rgba(56, 189, 248, 0.2);
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #93c5fd;
            line-height: 1.5;
        }
        
        .instructions strong { 
            display: block; 
            margin-bottom: 4px; 
            color: var(--accent-blue);
            font-family: 'Outfit', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fa-solid fa-plus"></i> Add New Barangay</h1>
        <a href="{{ route('admin.barangays.index') }}" class="btn btn-secondary" style="width: auto; margin: 0; padding: 8px 16px;">
            <i class="fa-solid fa-arrow-left"></i> Back to List
        </a>
    </div>
    
    <div class="container">
        <div class="form-panel">
            <div class="instructions">
                <strong><i class="fa-solid fa-info-circle"></i> Quick Map Guide</strong>
                Use the drawing tools on the map to define the Barangay boundary. The centroid, latitude, and longitude will be auto-calculated.
            </div>

            <form action="{{ route('admin.barangays.store') }}" method="POST" id="barangayForm">
                @csrf
                
                <div class="form-group">
                    <label for="name">Barangay Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g. Tococ East">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="municipality">Municipality</label>
                        <input type="text" id="municipality" name="municipality" value="Bayambang">
                    </div>
                    <div class="form-group">
                        <label for="province">Province</label>
                        <input type="text" id="province" name="province" value="Pangasinan">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hazard_level">Hazard Level</label>
                        <input type="text" id="hazard_level" name="hazard_level" placeholder="e.g. Moderate">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="population">Population</label>
                        <input type="text" id="population" name="population" placeholder="e.g. 2,841">
                    </div>
                    <div class="form-group">
                        <label for="land_use">Primary Land Use</label>
                        <input type="text" id="land_use" name="land_use" placeholder="e.g. Agricultural">
                    </div>
                </div>

                <h3 style="margin-top: 18px; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px; font-size: 11px; color: var(--text-muted);">Land Area & Cover (Hectares)</h3>

                <div class="form-group">
                    <label for="total_area">Total Land Area</label>
                    <input type="number" step="0.00001" id="total_area" name="total_area" placeholder="e.g. 345.2">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="agri_area">Agriculture</label>
                        <input type="number" step="0.00001" id="agri_area" name="agri_area">
                    </div>
                    <div class="form-group">
                        <label for="residential_area">Residential</label>
                        <input type="number" step="0.00001" id="residential_area" name="residential_area">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="commercial_area">Commercial</label>
                        <input type="number" step="0.00001" id="commercial_area" name="commercial_area">
                    </div>
                    <div class="form-group">
                        <label for="unidentified_area">Not Identified</label>
                        <input type="number" step="0.00001" id="unidentified_area" name="unidentified_area">
                    </div>
                </div>

                <h3 style="margin-top: 18px; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px; font-size: 11px; color: var(--text-muted);">Additional Info</h3>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Enter barangay profile description..."></textarea>
                </div>
                
                <!-- Hidden geographical inputs populated by Leaflet Draw -->
                <input type="hidden" id="latitude" name="latitude" value="15.8287">
                <input type="hidden" id="longitude" name="longitude" value="120.4173">
                <input type="hidden" id="boundary" name="boundary" value="">
                
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fa-solid fa-save"></i> Save Barangay
                </button>
                <a href="{{ route('admin.barangays.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
        
        <div class="map-panel">
            <div id="map"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.min.js"></script>
    <script>
        // Center map on municipal default center (Bayambang)
        const defaultLat = 15.8287;
        const defaultLng = 120.4173;

        // Bounding box strictly around Bayambang
        const bayambangBounds = L.latLngBounds(
            L.latLng(15.70, 120.28), // Southwest corner
            L.latLng(15.92, 120.58)  // Northeast corner
        );

        const map = L.map('map', { 
            minZoom: 12,
            maxZoom: 20,
            maxBounds: bayambangBounds,
            maxBoundsViscosity: 1.0
        }).setView([defaultLat, defaultLng], 14);

        // Dark Basemap matching edit page premium styling
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            maxZoom: 20,
            maxNativeZoom: 20
        }).addTo(map);

        let currentPolygon = null;

        // Initialize Geoman Controls
        map.pm.addControls({
            position: 'topleft',
            drawMarker: false,
            drawCircleMarker: false,
            drawPolyline: false,
            drawRectangle: false,
            drawPolygon: true,
            drawCircle: false,
            editMode: true,
            dragMode: true,
            removalMode: true,
        });

        // Set global snapping options
        map.pm.setGlobalOptions({
            snapping: true,
            snapDistance: 20, // Snaps magnetically when within 20px of neighboring vertex
            allowSelfIntersection: false
        });

        // Capture newly drawn polygon and auto-populate fields
        map.on('pm:create', function (event) {
            if (currentPolygon) {
                map.removeLayer(currentPolygon);
            }
            currentPolygon = event.layer;
            updateFormData(currentPolygon);

            // Listen to edit changes
            currentPolygon.on('pm:edit', function() {
                updateFormData(currentPolygon);
            });
            
            // Listen to drag changes
            currentPolygon.on('pm:dragend', function() {
                updateFormData(currentPolygon);
            });
        });

        // Handle deletion of boundary polygon
        map.on('pm:remove', function (event) {
            if (event.layer === currentPolygon) {
                document.getElementById('latitude').value = defaultLat;
                document.getElementById('longitude').value = defaultLng;
                document.getElementById('boundary').value = '';
                currentPolygon = null;
            }
        });

        function updateFormData(layer) {
            const latlngs = layer.getLatLngs()[0];
            const boundary = latlngs.map(ll => [ll.lat, ll.lng]);
            const center = layer.getBounds().getCenter();
            
            document.getElementById('latitude').value = center.lat;
            document.getElementById('longitude').value = center.lng;
            document.getElementById('boundary').value = JSON.stringify(boundary);
        }
    </script>
</body>
</html>
