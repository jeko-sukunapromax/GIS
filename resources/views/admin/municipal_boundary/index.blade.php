@extends('layouts.admin')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 24px;">
    <div>
        <h1 class="page-title" style="margin-bottom: 8px;">Bayambang Boundary</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Manage the main municipal outline shown behind barangay boundaries.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr); gap: 24px;">
    <div class="card">
        <h3 style="margin-bottom: 18px;">Current Boundary</h3>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px;">
            <div style="padding: 14px; border-radius: 10px; background: rgba(15,23,42,0.38); border: 1px solid rgba(148,163,184,0.12);">
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">Status</div>
                <div style="margin-top: 7px; color: {{ $municipalBoundary->boundary ? '#86efac' : '#fca5a5' }}; font-weight: 800;">
                    {{ $municipalBoundary->boundary ? 'Ready' : 'No Boundary' }}
                </div>
            </div>
            <div style="padding: 14px; border-radius: 10px; background: rgba(15,23,42,0.38); border: 1px solid rgba(148,163,184,0.12);">
                <div style="font-size: 11px; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">Updated</div>
                <div style="margin-top: 7px; color: var(--text-heading); font-weight: 800;">{{ $municipalBoundary->boundary_updated_at?->format('M d, Y') ?? 'N/A' }}</div>
            </div>
        </div>

        <div style="padding: 16px; background: rgba(15,23,42,0.38); border: 1px solid rgba(148,163,184,0.12); border-radius: 10px; margin-bottom: 20px;">
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Source</div>
            <div style="color: #38bdf8; font-weight: 700; font-family: monospace;">{{ $municipalBoundary->boundary_source ?? 'No uploaded source yet' }}</div>
        </div>

        @if($municipalBoundary->boundary)
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">Current Outline Preview</div>
                    <div style="font-size: 11px; color: #38bdf8; font-weight: 800;">Bayambang</div>
                </div>
                <div style="height: 280px; border-radius: 12px; background: radial-gradient(circle at 25% 25%, rgba(56,189,248,0.10), transparent 38%), rgba(15,23,42,0.50); border: 1px solid rgba(56,189,248,0.22); overflow: hidden;">
                    <canvas id="municipalBoundaryPreview" style="display: block; width: 100%; height: 100%;"></canvas>
                </div>
            </div>
        @endif

        @if($municipalBoundary->boundary)
            <div style="margin-bottom: 20px;">
                <a href="{{ route('admin.barangays.boundary.download', $municipalBoundary) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-download"></i> Download Current GeoJSON
                </a>
            </div>
        @endif

        <form action="{{ route('admin.municipal-boundary.upload') }}" method="POST" enctype="multipart/form-data" style="display: grid; gap: 14px;">
            @csrf
            <div>
                <label style="display: block; color: var(--text-muted); font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 8px;">Replace Boundary</label>
                <input type="file" name="boundary_file" accept=".geojson,.json,.zip,.kml,.shp" required style="width: 100%; padding: 11px; border-radius: 10px; background: rgba(15,23,42,0.52); color: #f8fafc; border: 1px solid rgba(148,163,184,0.16);">
            </div>
            <div>
                <label style="display: block; color: var(--text-muted); font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 8px;">Source Label</label>
                <input type="text" name="boundary_source" placeholder="Example: Bayambang municipal GeoJSON 2026" style="width: 100%; padding: 11px; border-radius: 10px; background: rgba(15,23,42,0.52); color: #f8fafc; border: 1px solid rgba(148,163,184,0.16);">
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Replace Boundary
                </button>
            </div>
        </form>

        @if($municipalBoundary->boundary)
            <form action="{{ route('admin.municipal-boundary.reset') }}" method="POST" onsubmit="return confirm('Reset the current Bayambang boundary? The current boundary will be saved as a version first.')" style="margin-top: 14px;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-trash-can"></i> Reset Current Boundary
                </button>
            </form>
        @endif
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Municipal Profile & Settings Card -->
        <div class="card">
            <h3 style="margin-bottom: 18px;">Municipal Profile & Settings</h3>
            
            <form action="{{ route('admin.municipal-boundary.update') }}" method="POST" style="display: grid; gap: 14px;">
                @csrf
                @method('PUT')
                
                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px;">Municipal Mayor</label>
                    <input type="text" name="barangay_chairman" value="{{ old('barangay_chairman', $municipalBoundary->barangay_chairman) }}" placeholder="e.g. Hon. Niña Jose-Quiambao" style="width: 100%; padding: 11px; border-radius: 10px; background: rgba(15,23,42,0.52); color: #f8fafc; border: 1px solid rgba(148,163,184,0.16);">
                </div>

                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px;">SK Federation President</label>
                    <input type="text" name="sk_chairman" value="{{ old('sk_chairman', $municipalBoundary->sk_chairman) }}" placeholder="e.g. Hon. John Doe" style="width: 100%; padding: 11px; border-radius: 10px; background: rgba(15,23,42,0.52); color: #f8fafc; border: 1px solid rgba(148,163,184,0.16);">
                </div>

                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px;">Official Population</label>
                    <input type="text" name="population" value="{{ old('population', $municipalBoundary->population) }}" placeholder="e.g. 139,468" style="width: 100%; padding: 11px; border-radius: 10px; background: rgba(15,23,42,0.52); color: #f8fafc; border: 1px solid rgba(148,163,184,0.16);">
                    <small style="color: var(--text-muted); font-size: 11px; display: block; margin-top: 4px;">Leave blank to calculate sum of barangays dynamically.</small>
                </div>

                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px;">Official Land Area (Hectares)</label>
                    <input type="number" step="any" name="total_area" value="{{ old('total_area', $municipalBoundary->total_area) }}" placeholder="e.g. 33658.94" style="width: 100%; padding: 11px; border-radius: 10px; background: rgba(15,23,42,0.52); color: #f8fafc; border: 1px solid rgba(148,163,184,0.16);">
                    <small style="color: var(--text-muted); font-size: 11px; display: block; margin-top: 4px;">Leave blank to calculate sum of barangays dynamically.</small>
                </div>

                <div style="margin-top: 8px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Details
                    </button>
                </div>
            </form>
        </div>

        <!-- Saved Versions Card -->
        <div class="card">
            <h3 style="margin-bottom: 18px;">Saved Versions</h3>

            @forelse($boundaryVersions as $version)
                <div style="padding: 14px; background: rgba(15,23,42,0.35); border: 1px solid rgba(148,163,184,0.12); border-radius: 10px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
                        <div>
                            <div style="color: #f8fafc; font-weight: 800;">Version #{{ $version->id }}</div>
                            <div style="color: #94a3b8; font-size: 12px; margin-top: 3px;">{{ $version->label ?? 'Boundary snapshot' }}</div>
                            <div style="color: #64748b; font-size: 11px; margin-top: 3px;">
                                {{ $version->created_at?->format('M d, Y h:i A') }}
                                @if($version->created_by)
                                    · {{ $version->created_by }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="{{ route('admin.barangays.boundary-versions.download', [$municipalBoundary, $version]) }}" class="btn btn-secondary" style="padding: 8px 11px; font-size: 12px;">
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                        <form method="POST" action="{{ route('admin.barangays.boundary-versions.restore', [$municipalBoundary, $version]) }}" onsubmit="return confirm('Restore this Bayambang boundary version?')">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="padding: 8px 11px; font-size: 12px;">
                                <i class="fa-solid fa-rotate-left"></i> Restore
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.barangays.boundary-versions.destroy', [$municipalBoundary, $version]) }}" onsubmit="return confirm('Delete this saved version?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding: 8px 11px; font-size: 12px;">
                                <i class="fa-solid fa-trash-can"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="color: #94a3b8; font-size: 13px; padding: 16px; background: rgba(15,23,42,0.35); border-radius: 10px;">
                    No versions yet. A snapshot is saved before replacing or resetting the Bayambang boundary.
                </div>
            @endforelse
        </div>
    </div>
</div>

@if($municipalBoundary->boundary)
    <script>
        const municipalBoundary = @json($municipalBoundary->boundary);
        const previewCanvas = document.getElementById('municipalBoundaryPreview');

        function drawMunicipalPreview() {
            if (!previewCanvas || !municipalBoundary || municipalBoundary.length < 3) return;

            const ratio = window.devicePixelRatio || 1;
            const rect = previewCanvas.getBoundingClientRect();
            previewCanvas.width = rect.width * ratio;
            previewCanvas.height = rect.height * ratio;

            const ctx = previewCanvas.getContext('2d');
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.clearRect(0, 0, rect.width, rect.height);

            const lats = municipalBoundary.map(point => Number(point[0]));
            const lngs = municipalBoundary.map(point => Number(point[1]));
            const minLat = Math.min(...lats);
            const maxLat = Math.max(...lats);
            const minLng = Math.min(...lngs);
            const maxLng = Math.max(...lngs);
            const padding = 28;
            const width = Math.max(maxLng - minLng, 0.000001);
            const height = Math.max(maxLat - minLat, 0.000001);
            const scale = Math.min((rect.width - padding * 2) / width, (rect.height - padding * 2) / height);
            const offsetX = (rect.width - width * scale) / 2;
            const offsetY = (rect.height - height * scale) / 2;

            const project = ([lat, lng]) => [
                offsetX + (Number(lng) - minLng) * scale,
                offsetY + (maxLat - Number(lat)) * scale,
            ];

            ctx.strokeStyle = 'rgba(56, 189, 248, 0.08)';
            ctx.lineWidth = 1;
            for (let x = 0; x < rect.width; x += 32) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, rect.height);
                ctx.stroke();
            }
            for (let y = 0; y < rect.height; y += 32) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(rect.width, y);
                ctx.stroke();
            }

            ctx.beginPath();
            municipalBoundary.forEach((point, index) => {
                const [x, y] = project(point);
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.closePath();
            ctx.fillStyle = 'rgba(56, 189, 248, 0.14)';
            ctx.strokeStyle = '#38bdf8';
            ctx.lineWidth = 3;
            ctx.fill();
            ctx.stroke();
        }

        drawMunicipalPreview();
        window.addEventListener('resize', drawMunicipalPreview);
    </script>
@endif
@endsection
