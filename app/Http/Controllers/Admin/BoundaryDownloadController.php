<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\BoundaryVersion;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BoundaryDownloadController extends Controller
{
    public function current(Barangay $barangay): Response
    {
        abort_if(empty($barangay->boundary), 404, 'No boundary available to download.');

        return $this->geoJsonResponse(
            $barangay->boundary,
            [
                'name' => $barangay->name,
                'boundary_type' => $barangay->is_municipal_boundary ? 'municipal' : 'barangay',
                'municipality' => $barangay->municipality,
                'province' => $barangay->province,
                'total_area' => $barangay->total_area,
                'source' => $barangay->boundary_source,
                'updated_at' => $barangay->boundary_updated_at?->toISOString(),
            ],
            Str::slug($barangay->name).'-current-boundary.geojson'
        );
    }

    public function version(Barangay $barangay, BoundaryVersion $boundaryVersion): Response
    {
        abort_unless((int) $boundaryVersion->barangay_id === (int) $barangay->id, 404);
        abort_if(empty($boundaryVersion->boundary), 404, 'No boundary version available to download.');

        return $this->geoJsonResponse(
            $boundaryVersion->boundary,
            [
                'name' => $barangay->name,
                'boundary_type' => $barangay->is_municipal_boundary ? 'municipal' : 'barangay',
                'version_id' => $boundaryVersion->id,
                'label' => $boundaryVersion->label,
                'total_area' => $boundaryVersion->total_area,
                'source' => $boundaryVersion->boundary_source,
                'boundary_updated_at' => $boundaryVersion->boundary_updated_at?->toISOString(),
                'snapshot_created_at' => $boundaryVersion->created_at?->toISOString(),
                'snapshot_created_by' => $boundaryVersion->created_by,
            ],
            Str::slug($barangay->name).'-boundary-version-'.$boundaryVersion->id.'.geojson'
        );
    }

    private function geoJsonResponse(array $boundary, array $properties, string $filename): Response
    {
        $coordinates = collect($boundary)
            ->map(fn ($point) => $this->toGeoJsonCoordinate($point))
            ->filter()
            ->values()
            ->all();

        abort_if(count($coordinates) < 3, 404, 'Boundary is not valid enough to download.');

        if ($coordinates[0] !== $coordinates[count($coordinates) - 1]) {
            $coordinates[] = $coordinates[0];
        }

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => array_filter($properties, fn ($value) => $value !== null && $value !== ''),
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [$coordinates],
                    ],
                ],
            ],
        ];

        return response()->make(
            json_encode($geoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/geo+json',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    private function toGeoJsonCoordinate(array $point): ?array
    {
        $lat = $point['lat'] ?? $point[0] ?? null;
        $lng = $point['lng'] ?? $point[1] ?? null;

        if ($lat === null || $lng === null) {
            return null;
        }

        return [(float) $lng, (float) $lat];
    }
}
