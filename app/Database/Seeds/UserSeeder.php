<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    use SeedSupport;

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $users = [
            ['admin', 'Admin@12345', 'System Administrator', 'admin@bantaygamit.local', '0917 100 0001', 'Barangay Hall', 'admin', 'active'],
            ['official', 'Official@12345', 'Barangay Official', 'official@bantaygamit.local', '0917 100 0002', 'Barangay Hall', 'barangay_official', 'active'],
            ['official2', 'Official2@12345', 'Maria Santos', 'official2@bantaygamit.local', '0917 100 0003', 'Barangay Hall', 'barangay_official', 'active'],
            ['borrower', 'Borrower@12345', 'Demo Borrower', 'borrower@bantaygamit.local', '0917 200 0001', 'Purok 1', 'borrower', 'active'],
            ['juan', 'Borrower@12345', 'Juan Dela Cruz', 'juan@bantaygamit.local', '0917 200 0002', 'Purok 2', 'borrower', 'active'],
            ['ana', 'Borrower@12345', 'Ana Reyes', 'ana@bantaygamit.local', '0917 200 0003', 'Purok 3', 'borrower', 'active'],
            ['mario', 'Borrower@12345', 'Mario Garcia', 'mario@bantaygamit.local', '0917 200 0004', 'Purok 4', 'borrower', 'active'],
            ['inactive.borrower', 'Borrower@12345', 'Inactive Borrower', 'inactive@bantaygamit.local', null, 'Purok 5', 'borrower', 'inactive'],
        ];

        foreach ($users as [$username, $password, $displayName, $email, $contact, $address, $role, $status]) {
            $this->upsertSeedRow('users', ['username' => $username], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'display_name' => $displayName,
                'email' => $email,
                'contact_number' => $contact,
                'address' => $address,
                'role' => $role,
                'status' => $status,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }
}
