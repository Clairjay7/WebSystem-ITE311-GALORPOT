<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRejectionReasonToEnrollments extends Migration
{
    public function up()
    {
        $fields = [
            'rejection_reason' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'status',
            ],
        ];
        
        $this->forge->addColumn('enrollments', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('enrollments', ['rejection_reason']);
    }
}

