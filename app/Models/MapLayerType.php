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
        'geom_type'
    ];
}
