<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\BoundaryVersion;
use App\Services\ActivityLogger;
use App\Traits\RefreshesPostgisGeometry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BoundaryVersionController extends Controller
{
    use RefreshesPostgisGeometry;

    public function restore(Request $request, Barangay $barangay, BoundaryVersion $boundaryVersion): RedirectResponse
    {
        abort_unless((int) $boundaryVersion->barangay_id === (int) $barangay->id, 404);

        $barangay->snapshotBoundary('Before restoring version #'.$boundaryVersion->id, $request->user()?->name);

        $barangay->update(array_merge([
            'boundary' => $boundaryVersion->boundary,
            'latitude' => $boundaryVersion->latitude,
            'longitude' => $boundaryVersion->longitude,
            'total_area' => $boundaryVersion->total_area,
            'boundary_source' => $boundaryVersion->boundary_source ?: 'Restored boundary version #'.$boundaryVersion->id,
            'boundary_updated_at' => now(),
        ], array_filter($boundaryVersion->attributes ?? [], fn ($value) => $value !== null && $value !== '')));

        app(ActivityLogger::class)->log('boundary_version.restored', "Restored {$barangay->name} boundary from version #{$boundaryVersion->id}.", $boundaryVersion, [
            'barangay_id' => $barangay->id,
            'barangay_name' => $barangay->name,
        ], $request);

        $this->refreshPostgisGeometry('boundary_version.restored');

        return back()->with('success', "Restored {$barangay->name} boundary from version #{$boundaryVersion->id}.");
    }

    public function destroy(Request $request, Barangay $barangay, BoundaryVersion $boundaryVersion): RedirectResponse
    {
        abort_unless((int) $boundaryVersion->barangay_id === (int) $barangay->id, 404);

        app(ActivityLogger::class)->log('boundary_version.deleted', "Deleted {$barangay->name} boundary version #{$boundaryVersion->id}.", $boundaryVersion, [
            'barangay_id' => $barangay->id,
            'barangay_name' => $barangay->name,
            'label' => $boundaryVersion->label,
        ], $request);

        $boundaryVersion->delete();

        return back()->with('success', 'Boundary version deleted.');
    }
}
