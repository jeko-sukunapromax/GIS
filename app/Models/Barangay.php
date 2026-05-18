<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
