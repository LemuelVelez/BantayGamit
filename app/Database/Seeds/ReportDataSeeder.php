<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReportDataSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $official = $this->idBy('users', 'username', 'official');
        $borrowerUsernames = ['borrower', 'juan', 'ana', 'mario'];
        $equipmentCodes = ['EQ-0003', 'EQ-0004', 'EQ-0005', 'EQ-0001', 'EQ-0010', 'EQ-0015'];

        for ($i = 0; $i < 12; $i++) {
            $monthsAgo = 11 - $i;
            $borrower = $this->idBy('users', 'username', $borrowerUsernames[$i % count($borrowerUsernames)]);
            $code = $equipmentCodes[$i % count($equipmentCodes)];
            $requestNumber = sprintf('BR-RPT-%06d', $i + 1);
            $base = strtotime("first day of -{$monthsAgo} months +" . (5 + ($i % 10)) . ' days');
            $createdAt = date('Y-m-d 09:00:00', $base);
            $requestedDate = date('Y-m-d', strtotime('+1 day', $base));
            $returnDate = date('Y-m-d', strtotime('+3 days', $base));
            $returnedAt = date('Y-m-d 16:00:00', strtotime('+3 days', $base));
            $quantity = 1 + ($i % 4);
            $damaged = $i === 8;

            $requestId = $this->upsertSeedRow('borrow_requests', ['request_number' => $requestNumber], [
                'borrower_id' => $borrower,
                'purpose' => ['Community assembly', 'Youth activity', 'Barangay seminar', 'Purok meeting'][$i % 4],
                'requested_date' => $requestedDate,
                'expected_return_date' => $returnDate,
                'status' => 'returned',
                'approved_by' => $official,
                'approved_at' => date('Y-m-d 10:00:00', strtotime('+6 hours', $base)),
                'rejection_reason' => null,
                'released_by' => $official,
                'released_at' => date('Y-m-d 08:00:00', strtotime('+1 day', $base)),
                'returned_to' => $official,
                'returned_at' => $returnedAt,
                'notes' => 'Historical development data for reports and dashboard charts.',
                'created_at' => $createdAt,
                'updated_at' => $returnedAt,
            ]);

            $this->db->table('borrow_request_items')->where('borrow_request_id', $requestId)->delete();
            $this->db->table('borrow_request_items')->insert([
                'borrow_request_id' => $requestId,
                'equipment_id' => $this->idBy('equipment', 'asset_code', $code),
                'quantity_requested' => $quantity,
                'quantity_released' => $quantity,
                'quantity_returned' => $quantity,
                'condition_on_release' => 'good',
                'condition_on_return' => $damaged ? 'damaged' : 'good',
                'damage_notes' => $damaged ? 'Minor casing damage documented during seeded return inspection.' : null,
                'created_at' => $createdAt,
                'updated_at' => $returnedAt,
            ]);
        }
    }
}
