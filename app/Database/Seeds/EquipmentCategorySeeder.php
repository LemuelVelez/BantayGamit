<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EquipmentCategorySeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['Audio Equipment', 'Speakers, microphones, mixers, and related sound equipment.'],
            ['Sports Equipment', 'Reusable sports and recreation equipment for barangay activities.'],
            ['Tables and Chairs', 'Tables, chairs, and event seating/furniture.'],
            ['Emergency Equipment', 'Emergency response and first-aid equipment.'],
            ['Cleaning Equipment', 'Cleaning tools and sanitation equipment.'],
            ['Tools', 'Hand tools and powered maintenance tools.'],
            ['Event Equipment', 'Equipment commonly used for meetings and barangay events.'],
        ];

        foreach ($rows as [$name, $description]) {
            $this->upsertSeedRow('equipment_categories', ['name' => $name], [
                'description' => $description,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
