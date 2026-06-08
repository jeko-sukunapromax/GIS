<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\BoundaryVersion;
use App\Services\GeoJsonExporter;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BoundaryDownloadController extends Controller
{
    public function __construct(private GeoJsonExporter $exporter) {}

    public function current(Barangay $barangay): Response
    {
        abort_if(empty($barangay->boundary), 404, 'No boundary available to download.');

        $geoJson = $this->exporter->barangayBoundary($barangay, [
            'name' => $barangay->name,
            'boundary_type' => $barangay->is_municipal_boundary ? 'municipal' : 'barangay',
            'municipality' => $barangay->municipality,
            'province' => $barangay->province,
            'total_area' => $barangay->total_area,
            'source' => $barangay->boundary_source,
            'updated_at' => $barangay->boundary_updated_at?->toISOString(),
        ]);

        abort_if(empty($geoJson['features']), 404, 'Boundary is not valid enough to download.');

        return $this->exporter->downloadResponse(
            $geoJson,
            Str::slug($barangay->name).'-current-boundary.geojson'
        );
    }

    public function version(Barangay $barangay, BoundaryVersion $boundaryVersion): Response
    {
        abort_unless((int) $boundaryVersion->barangay_id === (int) $barangay->id, 404);
        abort_if(empty($boundaryVersion->boundary), 404, 'No boundary version available to download.');

        $geoJson = $this->exporter->boundaryVersion($barangay, $boundaryVersion, [
            'name' => $barangay->name,
            'boundary_type' => $barangay->is_municipal_boundary ? 'municipal' : 'barangay',
            'version_id' => $boundaryVersion->id,
            'label' => $boundaryVersion->label,
            'total_area' => $boundaryVersion->total_area,
            'source' => $boundaryVersion->boundary_source,
            'boundary_updated_at' => $boundaryVersion->boundary_updated_at?->toISOString(),
            'snapshot_created_at' => $boundaryVersion->created_at?->toISOString(),
            'snapshot_created_by' => $boundaryVersion->created_by,
        ]);

        abort_if(empty($geoJson['features']), 404, 'Boundary version is not valid enough to download.');

        return $this->exporter->downloadResponse(
            $geoJson,
            Str::slug($barangay->name).'-boundary-version-'.$boundaryVersion->id.'.geojson'
        );
    }
}
