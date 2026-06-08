<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapLayerType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'icon',
        'color',
        'geom_type',
        'metadata_schema',
        'is_public',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata_schema' => 'array',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(MapFeature::class, 'feature_type', 'code');
    }
}
