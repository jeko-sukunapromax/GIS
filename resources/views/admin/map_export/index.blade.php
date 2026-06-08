@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    .export-shell {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 22px;
        align-items: start;
    }

    .export-report {
        background: #f8fafc;
        color: #0f172a;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.30);
    }

    .report-header {
        padding: 24px 28px;
        background: #0f172a;
        color: white;
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
    }

    .report-title {
        font-size: 24px;
        font-weight: 900;
        font-family: 'Outfit', sans-serif;
    }

    .report-subtitle {
        color: #bae6fd;
        font-size: 13px;
        margin-top: 5px;
    }

    .report-body {
        padding: 24px 28px 28px;
    }

    .report-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 20px;
        align-items: start;
    }

    .map-canvas-wrap {
        height: 430px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        overflow: hidden;
        background: #e2e8f0;
    }

    #exportLeafletMap {
        width: 100%;
        height: 100%;
        background: #0f172a;
    }

    #exportLeafletMap .leaflet-tile {
        filter: saturate(1.05) contrast(1.02);
    }

    #exportLeafletMap .leaflet-control-container {
        display: none;
    }

    #exportLeafletMap .report-map-label {
        background: rgba(15, 23, 42, 0.86);
        border: 1px solid rgba(255, 255, 255, 0.55);
        color: #ffffff;
        font-weight: 900;
        letter-spacing: 0.4px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
    }

    .report-stat {
        padding: 13px 14px;
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        margin-bottom: 10px;
        background: white;
    }

    .report-stat-label {
        color: #64748b;
        text-transform: uppercase;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.8px;
    }

    .report-stat-value {
        color: #0f172a;
        font-size: 17px;
        font-weight: 900;
        margin-top: 5px;
    }

    .facility-list {
        margin-top: 20px;
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        overflow: hidden;
    }

    .facility-row {
        display: grid;
        grid-template-columns: 1fr 170px;
        gap: 12px;
        padding: 11px 14px;
        border-bottom: 1px solid #e2e8f0;
        background: white;
        font-size: 13px;
    }

    .facility-row:last-child {
        border-bottom: none;
    }

    .report-footer {
        margin-top: 18px;
        color: #64748b;
        font-size: 11px;
        display: flex;
        justify-content: space-between;
        gap: 18px;
    }

    @media print {
        body {
            display: block !important;
            height: auto !important;
            overflow: visible !important;
            background: white !important;
        }

        .sidebar,
        .topbar,
        .export-controls,
        .page-title {
            display: none !important;
        }

        .main-wrapper,
        .content {
            display: block !important;
            height: auto !important;
            overflow: visible !important;
            padding: 0 !important;
            background: white !important;
        }

        .export-shell {
            display: block !important;
        }

        .export-report {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
        }
    }
</style>

<div class="export-controls" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 24px;">
    <div>
        <h1 class="page-title" style="margin-bottom: 8px;">Map Export / Print</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Generate a printable barangay report with boundary, area, population, and mapped facilities.</p>
    </div>
</div>

<div class="export-shell">
    <div class="card export-controls">
        <h3 style="margin-bottom: 18px;">Export Setup</h3>

        <form method="GET" action="{{ route('admin.map-export.index') }}" style="display: grid; gap: 14px;">
            <div>
                <label class="form-label" for="barangay_id">Barangay</label>
                <select class="form-control" id="barangay_id" name="barangay_id" onchange="this.form.submit()">
                    @foreach($barangays as $barangay)
                        <option value="{{ $barangay->id }}" @selected($selectedBarangay?->id === $barangay->id)>{{ $barangay->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="basemap">Basemap</label>
                <select class="form-control" id="basemap" name="basemap" onchange="this.form.submit()">
                    <option value="satellite" @selected($basemap === 'satellite')>Satellite</option>
                    <option value="light" @selected($basemap === 'light')>Light Map</option>
                    <option value="street" @selected($basemap === 'street')>Street Map</option>
                </select>
            </div>
        </form>

        @if($selectedBarangay)
            <div style="display: grid; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-primary" onclick="downloadReportPng()">
                    <i class="fa-solid fa-image"></i> Download PNG
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Print / Save PDF
                </button>
                @if($selectedBarangay->boundary)
                    <a href="{{ route('admin.barangays.boundary.download', $selectedBarangay) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-download"></i> Boundary GeoJSON
                    </a>
                @endif
                <a href="{{ route('admin.map-export.geojson', ['barangay_id' => $selectedBarangay->id, 'scope' => 'selected']) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-file-code"></i> Selected GeoJSON
                </a>
                <a href="{{ route('admin.map-export.geojson', ['scope' => 'public']) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-earth-asia"></i> Public Layers GeoJSON
                </a>
            </div>
        @endif

        <div style="margin-top: 22px; color: var(--text-muted); font-size: 13px; line-height: 1.6;">
            Satellite is used for preview and print. GeoJSON downloads include boundaries and mapped features in standard FeatureCollection format.
        </div>
    </div>

    @if($selectedBarangay)
        <div class="export-report" id="exportReport">
            <div class="report-header">
                <div>
                    <div class="report-title">{{ $selectedBarangay->name }} Barangay Map Report</div>
                    <div class="report-subtitle">Bayambang, Pangasinan · BDRRMC GIS</div>
                </div>
                <div style="text-align: right; font-size: 12px; color: #cbd5e1;">
                    Generated<br>
                    <strong style="color: white;">{{ now()->format('M d, Y h:i A') }}</strong>
                </div>
            </div>

            <div class="report-body">
                <div class="report-grid">
                    <div>
                        <div class="map-canvas-wrap">
                            <div id="exportLeafletMap"></div>
                        </div>
                    </div>
                    <div>
                        <div class="report-stat">
                            <div class="report-stat-label">Population</div>
                            <div class="report-stat-value">{{ $selectedBarangay->population ?: 'N/A' }}</div>
                        </div>
                        <div class="report-stat">
                            <div class="report-stat-label">Total Area</div>
                            <div class="report-stat-value">{{ $selectedBarangay->total_area ? number_format($selectedBarangay->total_area, 2).' ha' : 'N/A' }}</div>
                        </div>
                        <div class="report-stat">
                            <div class="report-stat-label">Hazard Level</div>
                            <div class="report-stat-value">{{ $selectedBarangay->hazard_level ?: 'N/A' }}</div>
                        </div>
                        <div class="report-stat">
                            <div class="report-stat-label">Facilities</div>
                            <div class="report-stat-value">{{ $facilityFeatures->count() }}</div>
                        </div>
                    </div>
                </div>

                <div class="facility-list">
                    <div class="facility-row" style="background: #e2e8f0; font-weight: 900; text-transform: uppercase; font-size: 11px; letter-spacing: 0.6px;">
                        <div>Mapped Facility</div>
                        <div>Type</div>
                    </div>
                    @forelse($facilityFeatures as $feature)
                        @php($layer = $layerTypes->firstWhere('code', $feature->feature_type))
                        <div class="facility-row">
                            <div style="font-weight: 800;">{{ $feature->name }}</div>
                            <div style="color: #475569;">{{ $layer?->name ?? ucwords(str_replace('_', ' ', $feature->feature_type)) }}</div>
                        </div>
                    @empty
                        <div class="facility-row">
                            <div style="color: #64748b;">No critical facilities plotted yet.</div>
                            <div></div>
                        </div>
                    @endforelse
                </div>

                <div class="report-footer">
                    <span>Boundary source: {{ $selectedBarangay->boundary_source ?: 'N/A' }}</span>
                    <span>{{ $features->count() }} total mapped feature(s)</span>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div style="color: var(--text-muted);">No barangays available for export.</div>
        </div>
    @endif
</div>

@if($selectedBarangay)
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        const exportBarangay = @json($selectedBarangay);
        const exportFeatures = @json($features->values());
        const exportLayerTypes = @json($layerTypes->keyBy('code'));
        const selectedBasemap = @json($basemap);
        let reportMap = null;

        function featureCoordinates(feature) {
            if (feature.latitude && feature.longitude) {
                return [[Number(feature.latitude), Number(feature.longitude)]];
            }

            if (feature.coordinates && feature.coordinates.length) {
                return feature.coordinates.map(point => [Number(point[0]), Number(point[1])]);
            }

            return [];
        }

        function collectMapCoordinates() {
            const coords = [];
            if (exportBarangay.boundary && exportBarangay.boundary.length) {
                exportBarangay.boundary.forEach(point => coords.push([Number(point[0]), Number(point[1])]));
            }
            exportFeatures.forEach(feature => {
                featureCoordinates(feature).forEach(point => coords.push(point));
            });

            return coords.length ? coords : [[15.8287, 120.4173]];
        }

        function initLeafletReportMap() {
            const mapEl = document.getElementById('exportLeafletMap');
            if (!mapEl || !window.L) return;

            const basemaps = {
                satellite: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                light: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                street: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            };

            reportMap = L.map(mapEl, {
                zoomControl: false,
                attributionControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
            });

            L.tileLayer(basemaps[selectedBasemap] || basemaps.satellite, {
                maxZoom: 20,
                crossOrigin: true,
            }).addTo(reportMap);

            const bounds = [];

            if (exportBarangay.boundary && exportBarangay.boundary.length > 2) {
                const polygon = L.polygon(exportBarangay.boundary, {
                    color: '#0284c7',
                    fillColor: '#38bdf8',
                    fillOpacity: selectedBasemap === 'satellite' ? 0.18 : 0.24,
                    weight: 3,
                }).addTo(reportMap);

                polygon.bindTooltip(exportBarangay.name, {
                    permanent: true,
                    direction: 'center',
                    className: 'report-map-label',
                });

                exportBarangay.boundary.forEach(point => bounds.push(point));
            }

            exportFeatures.forEach(feature => {
                const layer = exportLayerTypes[feature.feature_type] || {};
                const color = layer.color || '#0f172a';
                const coords = featureCoordinates(feature);
                if (!coords.length) return;

                coords.forEach(point => bounds.push(point));

                if (feature.latitude && feature.longitude) {
                    L.circleMarker(coords[0], {
                        radius: 7,
                        color: '#ffffff',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 1,
                    }).addTo(reportMap).bindTooltip(feature.name);
                    return;
                }

                if (layer.geom_type === 'polygon' || feature.feature_type === 'population_density') {
                    L.polygon(coords, {
                        color,
                        fillColor: color,
                        fillOpacity: 0.18,
                        weight: 2,
                    }).addTo(reportMap).bindTooltip(feature.name);
                    return;
                }

                L.polyline(coords, {
                    color,
                    weight: feature.feature_type === 'road_network' ? 4 : 3,
                    opacity: 0.9,
                }).addTo(reportMap).bindTooltip(feature.name);
            });

            if (bounds.length > 0) {
                reportMap.fitBounds(bounds, {
                    padding: [34, 34],
                    maxZoom: 17,
                });
            } else {
                reportMap.setView([15.8287, 120.4173], 13);
            }

            setTimeout(() => reportMap.invalidateSize(), 150);
        }

        function renderVectorMap(targetCanvas, width, height, pixelRatio = 1) {
            targetCanvas.width = width * pixelRatio;
            targetCanvas.height = height * pixelRatio;

            const ctx = targetCanvas.getContext('2d');
            ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
            ctx.clearRect(0, 0, width, height);
            ctx.fillStyle = '#e2e8f0';
            ctx.fillRect(0, 0, width, height);

            const coords = collectMapCoordinates();
            const lats = coords.map(point => point[0]);
            const lngs = coords.map(point => point[1]);
            const minLat = Math.min(...lats);
            const maxLat = Math.max(...lats);
            const minLng = Math.min(...lngs);
            const maxLng = Math.max(...lngs);
            const padding = 44;
            const boundsWidth = Math.max(maxLng - minLng, 0.000001);
            const boundsHeight = Math.max(maxLat - minLat, 0.000001);
            const scale = Math.min((width - padding * 2) / boundsWidth, (height - padding * 2) / boundsHeight);
            const offsetX = (width - boundsWidth * scale) / 2;
            const offsetY = (height - boundsHeight * scale) / 2;
            const project = ([lat, lng]) => [
                offsetX + (lng - minLng) * scale,
                offsetY + (maxLat - lat) * scale,
            ];

            ctx.strokeStyle = '#cbd5e1';
            ctx.lineWidth = 1;
            for (let x = 0; x <= width; x += 36) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, height);
                ctx.stroke();
            }
            for (let y = 0; y <= height; y += 36) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(width, y);
                ctx.stroke();
            }

            if (exportBarangay.boundary && exportBarangay.boundary.length > 2) {
                ctx.beginPath();
                exportBarangay.boundary.forEach((point, index) => {
                    const [x, y] = project([Number(point[0]), Number(point[1])]);
                    index === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
                });
                ctx.closePath();
                ctx.fillStyle = 'rgba(14, 165, 233, 0.20)';
                ctx.strokeStyle = '#0284c7';
                ctx.lineWidth = 4;
                ctx.fill();
                ctx.stroke();
            }

            exportFeatures.forEach(feature => {
                const layer = exportLayerTypes[feature.feature_type] || {};
                const color = layer.color || '#0f172a';
                const coords = featureCoordinates(feature);
                if (!coords.length) return;

                if (feature.latitude && feature.longitude) {
                    const [x, y] = project(coords[0]);
                    ctx.beginPath();
                    ctx.fillStyle = color;
                    ctx.strokeStyle = '#ffffff';
                    ctx.lineWidth = 3;
                    ctx.arc(x, y, 8, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.stroke();
                    return;
                }

                ctx.beginPath();
                coords.forEach((point, index) => {
                    const [x, y] = project(point);
                    index === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
                });
                ctx.strokeStyle = color;
                ctx.lineWidth = feature.feature_type === 'road_network' ? 5 : 3;
                ctx.stroke();
            });

            ctx.fillStyle = 'rgba(15, 23, 42, 0.86)';
            ctx.fillRect(18, 18, Math.min(360, width - 36), 54);
            ctx.fillStyle = '#ffffff';
            ctx.font = '700 18px Inter, Arial, sans-serif';
            ctx.fillText(exportBarangay.name, 32, 43);
            ctx.font = '500 12px Inter, Arial, sans-serif';
            ctx.fillStyle = '#bae6fd';
            ctx.fillText('BDRRMC GIS barangay map export', 32, 61);
        }

        function fileSafeName() {
            return exportBarangay.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        }

        function downloadCanvas(canvas, suffix = 'map-report') {
            const link = document.createElement('a');
            link.download = `${fileSafeName()}-${suffix}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }

        function downloadVectorPng() {
            const exportCanvas = document.createElement('canvas');
            renderVectorMap(exportCanvas, 1400, 900, 1);
            downloadCanvas(exportCanvas, 'vector-map-report');
        }

        async function downloadReportPng() {
            const report = document.getElementById('exportReport');

            if (reportMap) {
                reportMap.invalidateSize();
            }

            if (window.html2canvas && report) {
                try {
                    await new Promise(resolve => setTimeout(resolve, 350));
                    const captured = await html2canvas(report, {
                        backgroundColor: '#f8fafc',
                        scale: 2,
                        useCORS: true,
                        allowTaint: false,
                    });
                    downloadCanvas(captured, 'map-report');
                    return;
                } catch (error) {
                    console.warn('Satellite report capture failed; falling back to vector PNG.', error);
                }
            }

            downloadVectorPng();
        }

        initLeafletReportMap();
        window.addEventListener('resize', () => reportMap?.invalidateSize());
        window.addEventListener('beforeprint', () => reportMap?.invalidateSize());
    </script>
@endif
@endsection
