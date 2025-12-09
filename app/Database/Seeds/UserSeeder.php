<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $newPassword = password_hash('123123', PASSWORD_DEFAULT);
        
        $users = [
            [
                'name'     => 'Admin User',
                'email'    => 'admin@example.com',
                'password' => $newPassword,
                'role'     => 'admin',
            ],
            [
                'name'     => 'John Student',
                'email'    => 'student@example.com',
                'password' => $newPassword,
                'role'     => 'student',
            ],
            [
                'name'     => 'Jane Instructor',
                'email'    => 'instructor@example.com',
                'password' => $newPassword,
                'role'     => 'instructor',
            ],
        ];

        // Get list of emails from seeder
        $seederEmails = array_column($users, 'email');
        
        // Permanently delete all instructors that are not in the seeder
        $allInstructors = $this->db->table('users')
            ->where('role', 'instructor')
            ->get()
            ->getResultArray();
        
        foreach ($allInstructors as $instructor) {
            if (!in_array($instructor['email'], $seederEmails)) {
                // Permanently delete instructors not in seeder
                $this->db->table('users')
                    ->where('id', $instructor['id'])
                    ->delete();
            }
        }

        // Update existing users or insert new ones
        foreach ($users as $user) {
            $existing = $this->db->table('users')->where('email', $user['email'])->get()->getRowArray();
            
            if ($existing) {
                // Update existing user's password and restore if deleted
                $this->db->table('users')
                    ->where('email', $user['email'])
                    ->update([
                        'password' => $user['password'],
                        'deleted_at' => null // Restore if previously deleted
                    ]);
            } else {
                // Insert new user
                $this->db->table('users')->insert($user);
            }
        }
    }
}
