<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barangay extends Model
{
    protected $fillable = [
        'name',
        'municipality',
        'province',
        'total_area',
        'population',
        'land_use',
        'hazard_level',
        'status',
        'is_visible',
        'is_municipal_boundary',
        'agri_area',
        'residential_area',
        'commercial_area',
        'unidentified_area',
        'latitude',
        'longitude',
        'boundary',
        'boundary_source',
        'boundary_updated_at',
        'description'
    ];
    
    protected $casts = [
        'boundary' => 'array',
        'is_visible' => 'boolean',
        'is_municipal_boundary' => 'boolean',
        'boundary_updated_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get the map features for this barangay.
     */
    public function features()
    {
        return $this->hasMany(MapFeature::class);
    }

    public function boundaryVersions(): HasMany
    {
        return $this->hasMany(BoundaryVersion::class)->latest();
    }

    public function snapshotBoundary(?string $label = null, ?string $createdBy = null): ?BoundaryVersion
    {
        if (empty($this->boundary)) {
            return null;
        }

        return $this->boundaryVersions()->create([
            'label' => $label,
            'boundary' => $this->boundary,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'total_area' => $this->total_area,
            'boundary_source' => $this->boundary_source,
            'boundary_updated_at' => $this->boundary_updated_at,
            'attributes' => [
                'population' => $this->population,
                'land_use' => $this->land_use,
                'hazard_level' => $this->hazard_level,
                'agri_area' => $this->agri_area,
                'residential_area' => $this->residential_area,
                'commercial_area' => $this->commercial_area,
                'unidentified_area' => $this->unidentified_area,
                'description' => $this->description,
            ],
            'created_by' => $createdBy,
        ]);
    }
}
