<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BorrowingSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $official = $this->idBy('users', 'username', 'official');
        $official2 = $this->idBy('users', 'username', 'official2');
        $borrowers = [
            'borrower' => $this->idBy('users', 'username', 'borrower'),
            'juan' => $this->idBy('users', 'username', 'juan'),
            'ana' => $this->idBy('users', 'username', 'ana'),
            'mario' => $this->idBy('users', 'username', 'mario'),
        ];

        $requests = [
            [
                'number' => 'BR-SEED-000001', 'borrower' => 'borrower', 'purpose' => 'Barangay youth sports activity',
                'requested' => '+2 days', 'expected' => '+3 days', 'status' => 'pending', 'created' => '-1 day',
                'items' => [['EQ-0005', 3, 0, 0, null, null, null], ['EQ-0007', 1, 0, 0, null, null, null]],
            ],
            [
                'number' => 'BR-SEED-000002', 'borrower' => 'juan', 'purpose' => 'Purok community assembly',
                'requested' => '+1 day', 'expected' => '+2 days', 'status' => 'approved', 'created' => '-2 days',
                'approved_by' => $official, 'approved_at' => '-1 day',
                'items' => [['EQ-0003', 25, 0, 0, null, null, null], ['EQ-0004', 4, 0, 0, null, null, null]],
            ],
            [
                'number' => 'BR-SEED-000003', 'borrower' => 'ana', 'purpose' => 'Senior citizens information session',
                'requested' => '-1 day', 'expected' => '+2 days', 'status' => 'released', 'created' => '-3 days',
                'approved_by' => $official, 'approved_at' => '-2 days', 'released_by' => $official2, 'released_at' => '-1 day',
                'items' => [['EQ-0001', 1, 1, 0, 'good', null, null], ['EQ-0002', 2, 2, 0, 'excellent', null, null], ['EQ-0003', 15, 15, 0, 'good', null, null]],
            ],
            [
                'number' => 'BR-SEED-000004', 'borrower' => 'mario', 'purpose' => 'Neighborhood clean-up drive',
                'requested' => '-8 days', 'expected' => '-7 days', 'status' => 'returned', 'created' => '-10 days',
                'approved_by' => $official, 'approved_at' => '-9 days', 'released_by' => $official, 'released_at' => '-8 days', 'returned_to' => $official2, 'returned_at' => '-7 days',
                'items' => [['EQ-0012', 1, 1, 1, 'excellent', 'excellent', null], ['EQ-0007', 2, 2, 2, 'good', 'good', null]],
            ],
            [
                'number' => 'BR-SEED-000005', 'borrower' => 'juan', 'purpose' => 'Private commercial event request',
                'requested' => '+4 days', 'expected' => '+4 days', 'status' => 'rejected', 'created' => '-4 days',
                'approved_by' => $official2, 'approved_at' => '-3 days', 'rejection_reason' => 'Requested purpose is outside the barangay equipment lending policy.',
                'items' => [['EQ-0010', 2, 0, 0, null, null, null]],
            ],
            [
                'number' => 'BR-SEED-000006', 'borrower' => 'borrower', 'purpose' => 'Practice session',
                'requested' => '+5 days', 'expected' => '+5 days', 'status' => 'cancelled', 'created' => '-3 days',
                'items' => [['EQ-0015', 1, 0, 0, null, null, null]],
            ],
            [
                'number' => 'BR-SEED-000007', 'borrower' => 'mario', 'purpose' => 'Emergency preparedness seminar',
                'requested' => '-6 days', 'expected' => '-2 days', 'status' => 'overdue', 'created' => '-8 days',
                'approved_by' => $official, 'approved_at' => '-7 days', 'released_by' => $official, 'released_at' => '-6 days',
                'items' => [['EQ-0009', 1, 1, 0, 'good', null, null], ['EQ-0013', 1, 1, 0, 'fair', null, null]],
            ],
        ];

        foreach ($requests as $seed) {
            $requestId = $this->upsertRequest($seed, $borrowers);
            $this->db->table('borrow_request_items')->where('borrow_request_id', $requestId)->delete();
            foreach ($seed['items'] as [$code, $requested, $released, $returned, $releaseCondition, $returnCondition, $damageNotes]) {
                $this->db->table('borrow_request_items')->insert([
                    'borrow_request_id' => $requestId,
                    'equipment_id' => $this->idBy('equipment', 'asset_code', $code),
                    'quantity_requested' => $requested,
                    'quantity_released' => $released,
                    'quantity_returned' => $returned,
                    'condition_on_release' => $releaseCondition,
                    'condition_on_return' => $returnCondition,
                    'damage_notes' => $damageNotes,
                    'created_at' => $this->dateOffset($seed['created'], 'Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function upsertRequest(array $seed, array $borrowers): int
    {
        $createdAt = $this->dateOffset($seed['created'], 'Y-m-d H:i:s');
        $data = [
            'borrower_id' => $borrowers[$seed['borrower']],
            'purpose' => $seed['purpose'],
            'requested_date' => $this->dateOffset($seed['requested']),
            'expected_return_date' => $this->dateOffset($seed['expected']),
            'status' => $seed['status'],
            'approved_by' => $seed['approved_by'] ?? null,
            'approved_at' => isset($seed['approved_at']) ? $this->dateOffset($seed['approved_at'], 'Y-m-d H:i:s') : null,
            'rejection_reason' => $seed['rejection_reason'] ?? null,
            'released_by' => $seed['released_by'] ?? null,
            'released_at' => isset($seed['released_at']) ? $this->dateOffset($seed['released_at'], 'Y-m-d H:i:s') : null,
            'returned_to' => $seed['returned_to'] ?? null,
            'returned_at' => isset($seed['returned_at']) ? $this->dateOffset($seed['returned_at'], 'Y-m-d H:i:s') : null,
            'notes' => 'Development seed borrowing request.',
            'created_at' => $createdAt,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->upsertSeedRow('borrow_requests', ['request_number' => $seed['number']], $data);
    }
}
