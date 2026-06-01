<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;

class DataCompletenessController extends Controller
{
    public function index()
    {
        $barangays = Barangay::query()
            ->where('is_municipal_boundary', false)
            ->with('features')
            ->orderBy('name')
            ->get();

        $rows = $barangays->map(function (Barangay $barangay) {
            $checks = [
                'boundary' => ! empty($barangay->boundary),
                'population' => filled($barangay->population),
                'area' => filled($barangay->total_area),
                'hazard_level' => filled($barangay->hazard_level),
                'facilities' => $barangay->features->where('layer_type', 'critical_facilities')->isNotEmpty(),
                'evacuation_center' => $barangay->features->where('feature_type', 'evac_center')->isNotEmpty(),
            ];

            $complete = collect($checks)->filter()->count();
            $total = count($checks);

            return [
                'barangay' => $barangay,
                'checks' => $checks,
                'complete' => $complete,
                'missing' => $total - $complete,
                'score' => (int) round(($complete / $total) * 100),
            ];
        });

        return view('admin.data_completeness.index', [
            'rows' => $rows,
            'barangayCount' => $rows->count(),
            'completeCount' => $rows->where('missing', 0)->count(),
            'needsWorkCount' => $rows->where('missing', '>', 0)->count(),
            'averageScore' => $rows->count() ? (int) round($rows->avg('score')) : 0,
        ]);
    }
}
