<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTermsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'semester_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'term_number'   => ['type' => 'TINYINT', 'constraint' => 1], // 1 or 2
            'start_date'    => ['type' => 'DATE', 'null' => true],
            'end_date'      => ['type' => 'DATE', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('semester_id', 'semesters', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['semester_id', 'term_number']);
        $this->forge->createTable('terms');
    }

    public function down()
    {
        $this->forge->dropTable('terms');
    }
}

