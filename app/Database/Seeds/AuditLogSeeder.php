<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $this->resetSeedStats();
        $admin = $this->idBy('users', 'username', 'admin');
        $official = $this->idBy('users', 'username', 'official');
        $entries = [
            [$admin, 'seed_users', 'user', null, 'Loaded development user accounts.'],
            [$admin, 'seed_equipment', 'equipment', null, 'Loaded development equipment inventory.'],
            [$official, 'seed_borrowing', 'borrow_request', null, 'Loaded borrowing workflow demonstration records.'],
            [$official, 'seed_maintenance', 'maintenance_record', null, 'Loaded maintenance demonstration records.'],
            [$admin, 'seed_reports', 'report_data', null, 'Loaded historical records for operational reports.'],
        ];

        foreach ($entries as [$actor, $action, $entityType, $entityId, $message]) {
            $data = [
                'actor_user_id' => $actor,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'message' => $message,
                'metadata' => json_encode(['source' => 'BantayGamitSeeder'], JSON_UNESCAPED_SLASHES),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $this->upsertSeedRow('audit_logs', ['action' => $action], $data);
        }
    }
}
