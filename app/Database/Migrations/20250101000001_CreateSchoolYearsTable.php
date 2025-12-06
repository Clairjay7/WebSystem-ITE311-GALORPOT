<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSchoolYearsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 9, 'unique' => true], // Format: YYYY-YYYY
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('school_years');
    }

    public function down()
    {
        $this->forge->dropTable('school_years');
    }
}

