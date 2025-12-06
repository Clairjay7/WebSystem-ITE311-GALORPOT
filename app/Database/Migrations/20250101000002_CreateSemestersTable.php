<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSemestersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'school_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'semester_number' => ['type' => 'TINYINT', 'constraint' => 1], // 1 or 2
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('school_year_id', 'school_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['school_year_id', 'semester_number']);
        $this->forge->createTable('semesters');
    }

    public function down()
    {
        $this->forge->dropTable('semesters');
    }
}

