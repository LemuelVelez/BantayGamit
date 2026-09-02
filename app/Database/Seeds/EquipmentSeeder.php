<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['EQ-0001', 'Portable PA Speaker', 'Audio Equipment', 'Barangay Hall Storage Room', 4, 'unit', 'good', 'available', '-20 months', 'Portable rechargeable PA speaker for meetings and events.'],
            ['EQ-0002', 'Wireless Microphone', 'Audio Equipment', 'Barangay Hall Storage Room', 8, 'unit', 'excellent', 'available', '-14 months', 'Wireless handheld microphone with receiver.'],
            ['EQ-0003', 'Plastic Monobloc Chair', 'Tables and Chairs', 'Covered Court Storage', 120, 'piece', 'good', 'available', '-30 months', 'Stackable plastic chair for barangay programs.'],
            ['EQ-0004', 'Folding Table', 'Tables and Chairs', 'Covered Court Storage', 24, 'piece', 'good', 'available', '-24 months', 'Six-foot folding table for public activities.'],
            ['EQ-0005', 'Basketball', 'Sports Equipment', 'Covered Court Storage', 12, 'piece', 'fair', 'available', '-10 months', 'Official-size basketball for community sports programs.'],
            ['EQ-0006', 'First Aid Kit', 'Emergency Equipment', 'Disaster Response Room', 10, 'kit', 'excellent', 'available', '-8 months', 'Portable first-aid kit for barangay events and response teams.'],
            ['EQ-0007', 'Extension Cord 20m', 'Event Equipment', 'Barangay Hall Storage Room', 6, 'piece', 'good', 'available', '-16 months', 'Heavy-duty twenty-meter extension cord.'],
            ['EQ-0008', 'Power Drill', 'Tools', 'Maintenance Workshop', 3, 'unit', 'good', 'available', '-22 months', 'Corded drill used for barangay maintenance work.'],
            ['EQ-0009', 'Megaphone', 'Emergency Equipment', 'Disaster Response Room', 5, 'unit', 'good', 'available', '-12 months', 'Battery-powered megaphone for announcements and emergency response.'],
            ['EQ-0010', 'Pop-up Canopy Tent', 'Event Equipment', 'Covered Court Storage', 6, 'unit', 'good', 'available', '-18 months', 'Three-by-three-meter pop-up canopy tent.'],
            ['EQ-0011', 'Pressure Washer', 'Cleaning Equipment', 'Maintenance Workshop', 2, 'unit', 'damaged', 'maintenance', '-28 months', 'Portable pressure washer currently under repair.'],
            ['EQ-0012', 'Wet and Dry Vacuum', 'Cleaning Equipment', 'Barangay Hall Storage Room', 2, 'unit', 'excellent', 'available', '-6 months', 'Wet/dry vacuum for facility cleanup.'],
            ['EQ-0013', 'Emergency Generator', 'Emergency Equipment', 'Disaster Response Room', 2, 'unit', 'fair', 'available', '-36 months', 'Portable generator reserved for emergency and public-service use.'],
            ['EQ-0014', 'Old Analog Mixer', 'Audio Equipment', 'Maintenance Workshop', 1, 'unit', 'damaged', 'retired', '-72 months', 'Retired analog audio mixer retained for inventory history.'],
            ['EQ-0015', 'Volleyball Set', 'Sports Equipment', 'Covered Court Storage', 4, 'set', 'good', 'available', '-11 months', 'Volleyball, net, and boundary marker set.'],
            ['EQ-0016', 'LED Flood Light', 'Event Equipment', 'Barangay Hall Storage Room', 8, 'unit', 'excellent', 'available', '-5 months', 'Rechargeable LED flood light for evening activities.'],
        ];

        foreach ($rows as [$code, $name, $category, $location, $quantity, $unit, $condition, $status, $acquiredOffset, $description]) {
            $this->upsertSeedRow('equipment', ['asset_code' => $code], [
                'category_id' => $this->idBy('equipment_categories', 'name', $category),
                'location_id' => $this->idBy('equipment_locations', 'name', $location),
                'name' => $name,
                'description' => $description,
                'total_quantity' => $quantity,
                'unit' => $unit,
                'condition' => $condition,
                'status' => $status,
                'acquired_date' => $this->dateOffset($acquiredOffset),
                'notes' => 'Development seed equipment.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
