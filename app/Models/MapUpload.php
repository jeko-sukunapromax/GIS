<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapUpload extends Model
{
    protected $fillable = [
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
        'status',
    ];
}
