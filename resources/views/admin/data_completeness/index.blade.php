@extends('layouts.admin')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 24px;">
    <div>
        <h1 class="page-title" style="margin-bottom: 8px;">Data Completeness</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Quick check for barangays missing required GIS and BDRRMC readiness data.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 20px;">
        <div style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 800;">Barangays</div>
        <div style="color: var(--text-heading); font-size: 28px; font-weight: 900; margin-top: 8px;">{{ $barangayCount }}</div>
    </div>
    <div class="card" style="padding: 20px;">
        <div style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 800;">Complete</div>
        <div style="color: #86efac; font-size: 28px; font-weight: 900; margin-top: 8px;">{{ $completeCount }}</div>
    </div>
    <div class="card" style="padding: 20px;">
        <div style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 800;">Needs Work</div>
        <div style="color: #fbbf24; font-size: 28px; font-weight: 900; margin-top: 8px;">{{ $needsWorkCount }}</div>
    </div>
    <div class="card" style="padding: 20px;">
        <div style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 800;">Average Score</div>
        <div style="color: #7dd3fc; font-size: 28px; font-weight: 900; margin-top: 8px;">{{ $averageScore }}%</div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 18px;">
        <h3>Barangay Checklist</h3>
        <input id="completenessSearch" class="form-control" style="max-width: 300px;" placeholder="Search barangay" onkeyup="filterCompletenessRows()">
    </div>

    <div class="table-responsive">
        <table id="completenessTable">
            <thead>
                <tr>
                    <th>Barangay</th>
                    <th>Score</th>
                    <th>Boundary</th>
                    <th>Population</th>
                    <th>Area</th>
                    <th>Hazard</th>
                    <th>Facilities</th>
                    <th>Evacuation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php($barangay = $row['barangay'])
                    <tr data-name="{{ strtolower($barangay->name) }}">
                        <td>
                            <div style="font-weight: 800; color: var(--text-heading);">{{ $barangay->name }}</div>
                            <div style="color: var(--text-muted); font-size: 12px;">Missing {{ $row['missing'] }} of 6</div>
                        </td>
                        <td>
                            <div style="height: 8px; width: 110px; background: rgba(148,163,184,0.14); border-radius: 999px; overflow: hidden;">
                                <div style="height: 100%; width: {{ $row['score'] }}%; background: {{ $row['score'] === 100 ? '#22c55e' : '#38bdf8' }};"></div>
                            </div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">{{ $row['score'] }}%</div>
                        </td>
                        @foreach(['boundary', 'population', 'area', 'hazard_level', 'facilities', 'evacuation_center'] as $check)
                            <td>
                                @if($row['checks'][$check])
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; border-radius: 999px; background: rgba(34,197,94,0.12); color: #86efac; border: 1px solid rgba(34,197,94,0.24); font-size: 12px; font-weight: 800;">
                                        <i class="fa-solid fa-check"></i> OK
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; border-radius: 999px; background: rgba(251,191,36,0.12); color: #fde68a; border: 1px solid rgba(251,191,36,0.24); font-size: 12px; font-weight: 800;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Missing
                                    </span>
                                @endif
                            </td>
                        @endforeach
                        <td>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="{{ route('admin.barangays.manage', $barangay) }}" class="btn btn-secondary" style="padding: 7px 10px; font-size: 12px;">
                                    <i class="fa-solid fa-gear"></i> Manage
                                </a>
                                <a href="{{ route('admin.features.index', ['barangay_id' => $barangay->id]) }}" class="btn btn-secondary" style="padding: 7px 10px; font-size: 12px;">
                                    <i class="fa-solid fa-location-dot"></i> Features
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 38px;">No barangays found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    function filterCompletenessRows() {
        const needle = document.getElementById('completenessSearch').value.toLowerCase().trim();
        document.querySelectorAll('#completenessTable tbody tr[data-name]').forEach(row => {
            row.style.display = row.dataset.name.includes(needle) ? '' : 'none';
        });
    }
</script>
@endsection
