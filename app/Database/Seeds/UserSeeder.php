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

        // Update existing users or insert new ones
        foreach ($users as $user) {
            $existing = $this->db->table('users')->where('email', $user['email'])->get()->getRowArray();
            
            if ($existing) {
                // Update existing user's password
                $this->db->table('users')
                    ->where('email', $user['email'])
                    ->update(['password' => $user['password']]);
            } else {
                // Insert new user
                $this->db->table('users')->insert($user);
            }
        }
    }
}
