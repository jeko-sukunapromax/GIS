<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasSpatialGeometry;

class MapFeature extends Model
{
    use HasSpatialGeometry;

    protected $fillable = [
        'barangay_id',
        'name',
        'layer_type',
        'feature_type',
        'latitude',
        'longitude',
        'coordinates',
        'metadata',
        'is_public',
        'status',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the barangay that this feature belongs to.
     */
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }
}
