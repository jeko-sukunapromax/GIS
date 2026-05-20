@extends('layouts.admin')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 class="page-title">Barangay Management</h1>
</div>

<div class="card" style="padding: 24px;">
    <div style="margin-bottom: 18px; display: flex; justify-content: flex-end;">
        <div style="position: relative; width: 300px;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 12px; color: var(--text-muted);"></i>
            <input type="text" id="adminSearchInput" placeholder="Search barangay..." onkeyup="filterAdminBarangays()" style="width: 100%; padding: 10px 14px 10px 40px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; color: #f8fafc; outline: none; transition: all 0.2s ease;">
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Barangay Name</th>
                    <th>Coordinates</th>
                    <th>Boundary</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($barangays as $barangay)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $barangay->name }}</strong></td>
                        <td>
                            @if($barangay->latitude && $barangay->longitude)
                                {{ number_format($barangay->latitude, 4) }}, {{ number_format($barangay->longitude, 4) }}
                            @else
                                <em style="color: var(--text-muted);">Not set</em>
                            @endif
                        </td>
                        <td>
                            @if($barangay->boundary)
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(0, 153, 255, 0.12); border: 1px solid rgba(0, 153, 255, 0.3); color: var(--accent-blue); border-radius: 20px; font-size: 12px; font-weight: 600; text-shadow: 0 0 5px rgba(0,153,255,0.2);">
                                    <i class="fa-solid fa-draw-polygon"></i> Digitized
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                    <i class="fa-solid fa-circle-exclamation"></i> No Boundary
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="{{ route('admin.barangays.manage', $barangay) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                                    <i class="fa-solid fa-gear"></i> Manage
                                </a>
                                <form action="{{ route('admin.barangays.destroy', $barangay) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this barangay?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 13px;">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            No barangays found. Upload GeoJSON or Shapefiles in the Upload Data center to automatically add barangays.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function filterAdminBarangays() {
    const input = document.getElementById('adminSearchInput');
    const filter = input.value.toLowerCase().trim();
    const table = document.querySelector('table');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td')[1]; // Barangay Name is index 1
        if (td) {
            const txtValue = td.textContent || td.innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>
@endsection
