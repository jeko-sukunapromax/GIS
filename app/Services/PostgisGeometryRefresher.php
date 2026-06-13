<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PostgisGeometryRefresher
{
    public function refresh(string $reason): void
    {
        // No-op: Geometries are now updated natively on the model saving lifecycle.
    }
}
