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
        'agri_area',
        'residential_area',
        'commercial_area',
        'unidentified_area',
        'latitude',
        'longitude',
        'boundary',
        'description'
    ];
    
    protected $casts = ['boundary' => 'array'];

    /**
     * Get the map features for this barangay.
     */
    public function features()
    {
        return $this->hasMany(MapFeature::class);
    }
}
