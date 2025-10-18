<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'title' => 'Welcome to the New Academic Year!',
                'content' => 'We are excited to welcome all students to the new academic year. Please make sure to check your class schedules and prepare for an amazing learning journey ahead. Don\'t forget to attend the orientation session scheduled for next week.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'title' => 'Important: Midterm Examination Schedule',
                'content' => 'The midterm examinations will be conducted from October 25-30, 2025. Please review the examination guidelines and ensure you have all necessary materials. Study groups are encouraged, and the library will have extended hours during this period.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'title' => 'New Online Learning Platform Available',
                'content' => 'We have launched a new online learning platform to enhance your educational experience. All course materials, assignments, and resources are now available online. Please log in with your student credentials to access the platform.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Insert the announcement data
        $this->db->table('announcements')->insertBatch($data);
    }
}
