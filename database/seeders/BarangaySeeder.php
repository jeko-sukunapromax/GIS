<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barangay;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            [
                'name' => 'Tococ East',
                'latitude' => 15.8287,
                'longitude' => 120.4173,
                'population' => '2,841',
                'total_area' => 345.20,
                'land_use' => 'Agricultural',
                'hazard_level' => 'Moderate',
                'agri_area' => 245.50,
                'residential_area' => 82.10,
                'commercial_area' => 12.40,
                'unidentified_area' => 5.20,
                'description' => 'A primarily agricultural barangay located in the southern part of Bayambang.',
                'boundary' => [
                    [15.8350, 120.4100],
                    [15.8350, 120.4250],
                    [15.8250, 120.4250],
                    [15.8210, 120.4150],
                    [15.8250, 120.4100]
                ]
            ],
            [
                'name' => 'Talibaew',
                'latitude' => 15.7340,
                'longitude' => 120.5595,
                'population' => '1,250',
                'total_area' => 185.30,
                'land_use' => 'Residential',
                'hazard_level' => 'Low',
                'agri_area' => 45.20,
                'residential_area' => 120.10,
                'commercial_area' => 15.50,
                'unidentified_area' => 4.50,
                'description' => 'A growing residential community with emerging commercial areas.',
                'boundary' => [
                    [15.7400, 120.5500],
                    [15.7400, 120.5700],
                    [15.7300, 120.5700],
                    [15.7270, 120.5600],
                    [15.7300, 120.5500]
                ]
            ],
        ];

        foreach ($barangays as $brgy) {
            Barangay::create($brgy);
        }
    }
}
