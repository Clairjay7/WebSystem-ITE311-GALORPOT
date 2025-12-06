<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeInstructorIdNullableInCourses extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Get the foreign key constraint name
        $fkName = null;
        $constraints = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'courses' 
            AND COLUMN_NAME = 'instructor_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->getResultArray();
        
        if (!empty($constraints)) {
            $fkName = $constraints[0]['CONSTRAINT_NAME'];
            // Drop foreign key constraint
            $this->forge->dropForeignKey('courses', $fkName);
        }
        
        // Modify column to allow NULL
        $fields = [
            'instructor_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ];
        
        $this->forge->modifyColumn('courses', $fields);
    }

    public function down()
    {
        // Modify column back to NOT NULL
        $fields = [
            'instructor_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
        ];
        
        $this->forge->modifyColumn('courses', $fields);
        
        // Re-add foreign key constraint
        $this->forge->addForeignKey('instructor_id', 'users', 'id', 'CASCADE', 'CASCADE');
    }
}
