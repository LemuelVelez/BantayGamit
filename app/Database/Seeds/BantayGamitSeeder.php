<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BantayGamitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            UserSeeder::class,
            EquipmentCategorySeeder::class,
            EquipmentLocationSeeder::class,
            EquipmentSeeder::class,
            BorrowingSeeder::class,
            MaintenanceSeeder::class,
            ReportDataSeeder::class,
            NotificationSeeder::class,
            SettingsSeeder::class,
            AuditLogSeeder::class,
        ] as $seeder) {
            $this->call($seeder);
        }
    }
}
