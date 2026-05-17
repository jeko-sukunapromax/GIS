<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barangay;
use App\Models\MapFeature;

class MapFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $tococ = Barangay::where('name', 'Tococ East')->first();
        $talibaew = Barangay::where('name', 'Talibaew')->first();

        if ($tococ) {
            $this->seedTococEastFeatures($tococ->id);
        }

        if ($talibaew) {
            $this->seedTalibaewFeatures($talibaew->id);
        }
    }

    private function seedTococEastFeatures(int $barangayId): void
    {
        $features = [
            // === CRITICAL FACILITIES ===
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East Barangay Hall',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'barangay_hall',
                'latitude' => 15.8287000,
                'longitude' => 120.4173000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Public Administration',
                    'contact' => '0912-345-6789',
                    'official' => 'Brgy. Captain Juan Ramos'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East Health Center',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'health_center',
                'latitude' => 15.8295000,
                'longitude' => 120.4180000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Health Care',
                    'nurse' => 'Maria Santos, RN',
                    'hours' => '8:00 AM - 5:00 PM'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East Multi-purpose Hall A',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'multipurpose_bldg',
                'latitude' => 15.8278000,
                'longitude' => 120.4165000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Community Center',
                    'capacity' => '200 persons',
                    'evac_ready' => true
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East Multi-purpose Hall B',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'multipurpose_bldg',
                'latitude' => 15.8282000,
                'longitude' => 120.4159000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Community Center',
                    'capacity' => '150 persons',
                    'evac_ready' => true
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East Covered Court',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'covered_court',
                'latitude' => 15.8291000,
                'longitude' => 120.4170000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Sports & Recreation',
                    'roof_type' => 'Steel Truss',
                    'lighting' => 'LED Floodlights'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East Barangay Tanod Outpost',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'police_post',
                'latitude' => 15.8270000,
                'longitude' => 120.4185000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Security',
                    'on_duty' => '2 officers',
                    'contact' => 'Hotline 911 / Local 104'
                ]
            ],

            // === DRRM ===
            [
                'barangay_id' => $barangayId,
                'name' => 'BERT Member: Juan Dela Cruz (Leader)',
                'layer_type' => 'drrm',
                'feature_type' => 'bert_member',
                'latitude' => 15.8272000,
                'longitude' => 120.4150000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Active',
                    'role' => 'Team Leader',
                    'phone' => '0999-111-2222',
                    'skills' => 'First Aid, Search & Rescue'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'BERT Member: Pedro Penduko',
                'layer_type' => 'drrm',
                'feature_type' => 'bert_member',
                'latitude' => 15.8302000,
                'longitude' => 120.4162000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Active',
                    'role' => 'Rescue Responder',
                    'phone' => '0999-333-4444',
                    'skills' => 'Flood Rescue, Navigation'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'BERT Member: Maria Clara',
                'layer_type' => 'drrm',
                'feature_type' => 'bert_member',
                'latitude' => 15.8310000,
                'longitude' => 120.4190000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Active',
                    'role' => 'First Aider / Medic',
                    'phone' => '0999-555-6666',
                    'skills' => 'EMT, Triage, BLS'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'BERT Member: Crisostomo Ibarra',
                'layer_type' => 'drrm',
                'feature_type' => 'bert_member',
                'latitude' => 15.8260000,
                'longitude' => 120.4180000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Active',
                    'role' => 'Logistics Responder',
                    'phone' => '0999-777-8888',
                    'skills' => 'Communications, Supply'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'BERT Member: Elias Salvacion',
                'layer_type' => 'drrm',
                'feature_type' => 'bert_member',
                'latitude' => 15.8290000,
                'longitude' => 120.4200000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Active',
                    'role' => 'Rescue Ambulance Driver',
                    'phone' => '0999-999-0000',
                    'skills' => 'Defensive Driving, BLS'
                ]
            ],

            // === INFRASTRUCTURE ===
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East Main Barangay Road',
                'layer_type' => 'infrastructure',
                'feature_type' => 'road_network',
                'latitude' => null,
                'longitude' => null,
                'coordinates' => [
                    [15.8260000, 120.4150000],
                    [15.8272000, 120.4158000],
                    [15.8287000, 120.4173000],
                    [15.8295000, 120.4180000],
                    [15.8315000, 120.4200000]
                ],
                'metadata' => [
                    'status' => 'Good Condition',
                    'type' => 'Concrete Highway',
                    'width' => '6.0 meters',
                    'length' => '1.2 km'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East Secondary Access Road',
                'layer_type' => 'infrastructure',
                'feature_type' => 'road_network',
                'latitude' => null,
                'longitude' => null,
                'coordinates' => [
                    [15.8278000, 120.4165000],
                    [15.8285000, 120.4178000],
                    [15.8291000, 120.4170000],
                    [15.8302000, 120.4162000]
                ],
                'metadata' => [
                    'status' => 'Needs Maintenance',
                    'type' => 'Asphalt Road',
                    'width' => '4.5 meters',
                    'length' => '0.6 km'
                ]
            ],

            // === POPULATION DENSITY ZONE ===
            [
                'barangay_id' => $barangayId,
                'name' => 'Tococ East High-Density Residential Zone',
                'layer_type' => 'population',
                'feature_type' => 'population_density',
                'latitude' => null,
                'longitude' => null,
                'coordinates' => [
                    [15.8275000, 120.4155000],
                    [15.8300000, 120.4155000],
                    [15.8300000, 120.4185000],
                    [15.8275000, 120.4185000]
                ],
                'metadata' => [
                    'density_level' => 'High',
                    'color' => '#3b82f6',
                    'fill_opacity' => 0.25,
                    'est_households' => '180 households'
                ]
            ],

            // === HOUSEHOLD DISTRIBUTION ===
            [
                'barangay_id' => $barangayId,
                'name' => 'Household #104 (Dela Cruz Res.)',
                'layer_type' => 'population',
                'feature_type' => 'household_distribution',
                'latitude' => 15.8283000,
                'longitude' => 120.4162000,
                'coordinates' => null,
                'metadata' => [
                    'house_no' => '104',
                    'head' => 'Juan Dela Cruz Jr.',
                    'members' => 5,
                    'hazard_risk' => 'Low'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Household #112 (Santos Res.)',
                'layer_type' => 'population',
                'feature_type' => 'household_distribution',
                'latitude' => 15.8285000,
                'longitude' => 120.4168000,
                'coordinates' => null,
                'metadata' => [
                    'house_no' => '112',
                    'head' => 'Rogelio Santos',
                    'members' => 4,
                    'hazard_risk' => 'Moderate'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Household #115 (Alvarez Res.)',
                'layer_type' => 'population',
                'feature_type' => 'household_distribution',
                'latitude' => 15.8288000,
                'longitude' => 120.4161000,
                'coordinates' => null,
                'metadata' => [
                    'house_no' => '115',
                    'head' => 'Alona Alvarez',
                    'members' => 6,
                    'hazard_risk' => 'Low'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Household #122 (Perez Res.)',
                'layer_type' => 'population',
                'feature_type' => 'household_distribution',
                'latitude' => 15.8290000,
                'longitude' => 120.4175000,
                'coordinates' => null,
                'metadata' => [
                    'house_no' => '122',
                    'head' => 'Felipe Perez',
                    'members' => 3,
                    'hazard_risk' => 'Low'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Household #135 (Villanueva Res.)',
                'layer_type' => 'population',
                'feature_type' => 'household_distribution',
                'latitude' => 15.8292000,
                'longitude' => 120.4182000,
                'coordinates' => null,
                'metadata' => [
                    'house_no' => '135',
                    'head' => 'Simeon Villanueva',
                    'members' => 7,
                    'hazard_risk' => 'Moderate'
                ]
            ]
        ];

        foreach ($features as $feat) {
            MapFeature::create($feat);
        }
    }

    private function seedTalibaewFeatures(int $barangayId): void
    {
        $features = [
            // Critical Facilities
            [
                'barangay_id' => $barangayId,
                'name' => 'Talibaew Barangay Hall',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'barangay_hall',
                'latitude' => 15.7340000,
                'longitude' => 120.5595000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Public Administration',
                    'contact' => '0915-777-8888',
                    'official' => 'Brgy. Captain Ricardo Diaz'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Talibaew Health Center',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'health_center',
                'latitude' => 15.7345000,
                'longitude' => 120.5602000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Health Care',
                    'midwife' => 'Elena Perez, RM',
                    'hours' => '8:00 AM - 3:00 PM'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'Talibaew Multi-purpose Court',
                'layer_type' => 'critical_facilities',
                'feature_type' => 'covered_court',
                'latitude' => 15.7335000,
                'longitude' => 120.5588000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Operational',
                    'type' => 'Sports & Recreation',
                    'flooring' => 'Concrete'
                ]
            ],

            // BERT Members
            [
                'barangay_id' => $barangayId,
                'name' => 'BERT Member: Andres Bonifacio',
                'layer_type' => 'drrm',
                'feature_type' => 'bert_member',
                'latitude' => 15.7338000,
                'longitude' => 120.5590000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Active',
                    'role' => 'Team Leader',
                    'phone' => '0917-111-2222',
                    'skills' => 'First Aid, Disaster Preparedness'
                ]
            ],
            [
                'barangay_id' => $barangayId,
                'name' => 'BERT Member: Gregoria De Jesus',
                'layer_type' => 'drrm',
                'feature_type' => 'bert_member',
                'latitude' => 15.7342000,
                'longitude' => 120.5610000,
                'coordinates' => null,
                'metadata' => [
                    'status' => 'Active',
                    'role' => 'Medic',
                    'phone' => '0917-333-4444',
                    'skills' => 'Nursing Support, CPR'
                ]
            ],

            // Road Network
            [
                'barangay_id' => $barangayId,
                'name' => 'Talibaew Access Highway',
                'layer_type' => 'infrastructure',
                'feature_type' => 'road_network',
                'latitude' => null,
                'longitude' => null,
                'coordinates' => [
                    [15.7320000, 120.5570000],
                    [15.7340000, 120.5595000],
                    [15.7355000, 120.5620000]
                ],
                'metadata' => [
                    'status' => 'Excellent',
                    'type' => 'National Road',
                    'width' => '8.0 meters'
                ]
            ]
        ];

        foreach ($features as $feat) {
            MapFeature::create($feat);
        }
    }
}
