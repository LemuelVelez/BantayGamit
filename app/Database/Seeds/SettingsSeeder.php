<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingsSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $admin = $this->idBy('users', 'username', 'admin');
        $now = date('Y-m-d H:i:s');
        $settings = [
            'barangay_name' => 'Sample Barangay',
            'due_soon_days' => '2',
            'contact_email' => 'barangay@bantaygamit.local',
            'contact_number' => '(02) 8123-4567',
        ];

        foreach ($settings as $key => $value) {
            $this->upsertSeedRow('settings', ['setting_key' => $key], [
                'setting_value' => $value,
                'updated_by' => $admin,
                'updated_at' => $now,
            ]);
        }
    }
}
