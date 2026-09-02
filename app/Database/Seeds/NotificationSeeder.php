<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class NotificationSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $rows = [
            ['admin', 'request_submitted', 'New borrowing request BR-SEED-000001 is awaiting review.', 0, '-20 minutes'],
            ['official', 'request_submitted', 'New borrowing request BR-SEED-000001 is awaiting review.', 0, '-20 minutes'],
            ['borrower', 'welcome', 'Welcome to BantayGamit. You can request available barangay equipment.', 0, '-1 day'],
            ['juan', 'request_approved', 'Your request BR-SEED-000002 was approved.', 0, '-1 day'],
            ['ana', 'equipment_released', 'Equipment for BR-SEED-000003 has been released.', 1, '-1 day'],
            ['mario', 'equipment_overdue', 'Borrowing request BR-SEED-000007 is overdue.', 0, '-2 hours'],
            ['official2', 'maintenance_due', 'Emergency Generator maintenance is currently in progress.', 0, '-4 hours'],
        ];

        foreach ($rows as [$username, $type, $message, $isRead, $createdOffset]) {
            $userId = $this->idBy('users', 'username', $username);
            $existing = $this->db->table('notifications')->where(['user_id' => $userId, 'message' => $message])->get()->getRowArray();
            $data = ['type' => $type, 'is_read' => $isRead, 'created_at' => $this->dateOffset($createdOffset, 'Y-m-d H:i:s')];
            if ($existing) {
                $this->db->table('notifications')->where('id', (int) $existing['id'])->update($data);
            } else {
                $this->db->table('notifications')->insert(array_merge(['user_id' => $userId, 'message' => $message], $data));
            }
        }
    }
}
