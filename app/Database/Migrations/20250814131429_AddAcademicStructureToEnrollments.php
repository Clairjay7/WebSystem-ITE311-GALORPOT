<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAcademicStructureToEnrollments extends Migration
{
    public function up()
    {
        $fields = [
            'school_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'course_id'],
            'semester'       => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'school_year_id'], // 1 or 2
            'term'           => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'semester'], // 1 or 2
        ];
        
        $this->forge->addColumn('enrollments', $fields);
        
        // Add foreign key constraint
        $this->forge->addForeignKey('school_year_id', 'school_years', 'id', 'CASCADE', 'CASCADE', 'enrollments');
    }

    public function down()
    {
        // Drop foreign key first
        $this->forge->dropForeignKey('enrollments', 'enrollments_school_year_id_foreign');
        
        // Drop columns
        $this->forge->dropColumn('enrollments', ['school_year_id', 'semester', 'term']);
    }
}

