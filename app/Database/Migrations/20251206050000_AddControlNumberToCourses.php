<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddControlNumberToCourses extends Migration
{
    public function up()
    {
        $fields = [
            'control_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'title',
            ],
            'units' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'unsigned' => true,
                'null' => true,
                'default' => 0,
                'after' => 'control_number',
            ],
        ];
        
        $this->forge->addColumn('courses', $fields);
        
        // Add unique index on control_number to ensure uniqueness
        $this->forge->addKey('control_number', false, false, 'control_number_unique');
    }

    public function down()
    {
        // Drop the unique key first
        $this->forge->dropKey('courses', 'control_number_unique');
        
        // Drop the columns
        $this->forge->dropColumn('courses', ['control_number', 'units']);
    }
}

