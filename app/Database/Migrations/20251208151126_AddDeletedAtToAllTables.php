<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeletedAtToAllTables extends Migration
{
    public function up()
    {
        // Add deleted_at column to all tables
        $tables = [
            'users',
            'courses',
            'enrollments',
            'teacher_assignments',
            'school_years',
            'semesters',
            'terms'
        ];

        foreach ($tables as $table) {
            // Check if column already exists
            if (!$this->db->fieldExists('deleted_at', $table)) {
                $this->forge->addColumn($table, [
                    'deleted_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        // Remove deleted_at column from all tables
        $tables = [
            'users',
            'courses',
            'enrollments',
            'teacher_assignments',
            'school_years',
            'semesters',
            'terms'
        ];

        foreach ($tables as $table) {
            // Check if column exists before dropping
            if ($this->db->fieldExists('deleted_at', $table)) {
                $this->forge->dropColumn($table, 'deleted_at');
            }
        }
    }
}
