<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Activitylog\Contracts\Activity;

class ActivityLogger
{
    public function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?Request $request = null
    ): ?Activity {
        $request ??= request();
        $user = $request->user();

        $properties = array_filter(array_merge($properties, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]), fn ($value) => $value !== null && $value !== '');

        $logger = activity('audit')
            ->event($event)
            ->withProperties($properties);

        if ($subject) {
            $logger->performedOn($subject);
        }

        if ($user) {
            $logger->causedBy($user);
        }

        return $logger->log($description);
    }
}
