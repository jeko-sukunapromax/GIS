<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoundaryVersion extends Model
{
    protected $fillable = [
        'barangay_id',
        'label',
        'boundary',
        'latitude',
        'longitude',
        'total_area',
        'boundary_source',
        'boundary_updated_at',
        'attributes',
        'created_by',
    ];

    protected $casts = [
        'boundary' => 'array',
        'attributes' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'total_area' => 'float',
        'boundary_updated_at' => 'datetime',
    ];

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }
}
