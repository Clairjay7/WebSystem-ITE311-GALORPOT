<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeacherAssignmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'teacher_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'course_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'school_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'semester'      => ['type' => 'TINYINT', 'constraint' => 1], // 1 or 2
            'term'          => ['type' => 'TINYINT', 'constraint' => 1], // 1 or 2
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('teacher_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('course_id', 'courses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('school_year_id', 'school_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['teacher_id', 'course_id', 'school_year_id', 'semester', 'term']);
        $this->forge->createTable('teacher_assignments');
    }

    public function down()
    {
        $this->forge->dropTable('teacher_assignments');
    }
}

