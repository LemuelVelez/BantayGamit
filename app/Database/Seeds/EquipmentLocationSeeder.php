<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EquipmentLocationSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['Barangay Hall Storage Room', 'Main secured equipment storage beside the barangay office.'],
            ['Covered Court Storage', 'Storage cage for sports and large event equipment.'],
            ['Disaster Response Room', 'Emergency and disaster-response equipment room.'],
            ['Maintenance Workshop', 'Tools and equipment currently queued for inspection or repair.'],
        ];

        foreach ($rows as [$name, $description]) {
            $this->upsertSeedRow('equipment_locations', ['name' => $name], [
                'description' => $description,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
