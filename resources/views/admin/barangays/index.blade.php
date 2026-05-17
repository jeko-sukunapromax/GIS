@extends('layouts.admin')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 class="page-title">Barangay Management</h1>
    <a href="{{ route('admin.barangays.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add New Barangay
    </a>
</div>

<div class="card" style="padding: 24px;">
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
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(56, 189, 248, 0.12); border: 1px solid rgba(56, 189, 248, 0.3); color: var(--accent-blue); border-radius: 20px; font-size: 12px; font-weight: 600; text-shadow: 0 0 5px rgba(56,189,248,0.2);">
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
                                <a href="{{ route('admin.barangays.edit', $barangay) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                                    <i class="fa-solid fa-pen"></i> Edit
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
                            No barangays yet. Click "Add New Barangay" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
