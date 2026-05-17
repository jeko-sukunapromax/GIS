<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapFeature extends Model
{
    protected $fillable = [
        'barangay_id',
        'name',
        'layer_type',
        'feature_type',
        'latitude',
        'longitude',
        'coordinates',
        'metadata'
    ];

    protected $casts = [
        'coordinates' => 'array',
        'metadata' => 'array'
    ];

    /**
     * Get the barangay that this feature belongs to.
     */
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }
}
