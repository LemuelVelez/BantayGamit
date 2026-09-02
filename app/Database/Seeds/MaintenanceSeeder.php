<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MaintenanceSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $official = $this->idBy('users', 'username', 'official');
        $official2 = $this->idBy('users', 'username', 'official2');
        $rows = [
            ['Power Drill', 'Preventive inspection', 'Routine chuck, cable, and safety inspection.', 1, 'scheduled', '+1 day', null, null, 'seed:maintenance-drill', $official],
            ['Emergency Generator', 'Oil and load test', 'Quarterly generator oil inspection and load test.', 1, 'in_progress', '-1 day', null, 850.00, 'seed:maintenance-generator', $official2],
            ['Pressure Washer', 'Pump repair', 'Repair leaking pump assembly and replace damaged hose fitting.', 2, 'in_progress', '-3 days', null, 2450.00, 'seed:maintenance-pressure-washer', $official],
            ['Portable PA Speaker', 'Battery replacement', 'Replaced degraded internal battery and completed charging test.', 1, 'completed', '-25 days', '-23 days', 1800.00, 'seed:maintenance-speaker', $official2],
            ['Wireless Microphone', 'Signal inspection', 'Intermittent RF issue reported for inspection.', 1, 'reported', null, null, null, 'seed:maintenance-microphone', $official],
            ['LED Flood Light', 'Charging-port inspection', 'Inspection was cancelled after retest showed normal operation.', 1, 'cancelled', '-12 days', null, 0.00, 'seed:maintenance-flood-light', $official2],
        ];

        foreach ($rows as [$equipmentName, $type, $description, $quantity, $status, $startOffset, $completionOffset, $cost, $seedKey, $reportedBy]) {
            $equipmentId = $this->idBy('equipment', 'name', $equipmentName);
            $existing = $this->db->table('maintenance_records')->where('notes', $seedKey)->get()->getRowArray();
            $data = [
                'equipment_id' => $equipmentId,
                'reported_by' => $reportedBy,
                'maintenance_type' => $type,
                'description' => $description,
                'quantity' => $quantity,
                'status' => $status,
                'start_date' => $startOffset ? $this->dateOffset($startOffset) : null,
                'completion_date' => $completionOffset ? $this->dateOffset($completionOffset) : null,
                'cost' => $cost,
                'notes' => $seedKey,
                'created_at' => $this->dateOffset('-30 days', 'Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($existing) {
                $this->db->table('maintenance_records')->where('id', (int) $existing['id'])->update($data);
            } else {
                $this->db->table('maintenance_records')->insert($data);
            }
        }
    }
}
