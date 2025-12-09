<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimeToCourses extends Migration
{
    public function up()
    {
        $fields = [
            'time' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'description',
            ],
        ];
        
        $this->forge->addColumn('courses', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('courses', ['time']);
    }
}
