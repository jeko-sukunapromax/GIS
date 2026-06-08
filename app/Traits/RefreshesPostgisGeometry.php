<?php

namespace App\Traits;

use App\Services\PostgisGeometryRefresher;

trait RefreshesPostgisGeometry
{
    protected function refreshPostgisGeometry(string $reason): void
    {
        app(PostgisGeometryRefresher::class)->refresh($reason);
    }
}
