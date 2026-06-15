<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $events = Activity::query()
            ->select('event')
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $logs = Activity::query()
            ->with(['causer'])
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->string('event')->toString()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';

                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', $search)
                        ->orWhere('event', 'like', $search)
                        ->orWhere('properties', 'like', $search)
                        ->orWhereHas('causer', fn ($userQuery) => $userQuery
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->latest()
            ->limit(250)
            ->get();

        return view('admin.activity_logs.index', compact('logs', 'events'));
    }
}
