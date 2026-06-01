<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapLayerType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'icon',
        'color',
        'geom_type',
        'is_public',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
