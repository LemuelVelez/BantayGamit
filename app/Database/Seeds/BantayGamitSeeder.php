<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BantayGamitSeeder extends Seeder
{
    public const SEQUENCE = [
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
    ];

    public function run(): void
    {
        foreach (self::SEQUENCE as $seeder) {
            $this->call($seeder);
        }
    }
}
