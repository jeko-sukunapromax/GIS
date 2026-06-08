<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PostgisGeometryRefresher
{
    public function refresh(string $reason): void
    {
        if (app()->environment('testing')) {
            return;
        }

        try {
            $sync = app(PostgisGeometrySync::class);

            if (! $sync->tableExists()) {
                return;
            }

            $stats = $sync->sync(truncate: true);

            if ($stats['skipped'] > 0 || count($stats['errors']) > 0) {
                Log::warning('PostGIS geometry refresh finished with warnings.', [
                    'reason' => $reason,
                    'skipped' => $stats['skipped'],
                    'errors' => $stats['errors'],
                    'skipped_details' => array_slice($stats['skipped_details'], 0, 5),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('PostGIS geometry refresh skipped.', [
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
