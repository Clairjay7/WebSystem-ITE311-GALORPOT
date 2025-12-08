<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name', 'email', 'password', 'role', 'created_at', 'updated_at', 'deleted_at',
        // new registration fields
        'student_id', 'first_name', 'middle_name', 'last_name',
        'date_of_birth', 'gender', 'contact_number'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    public function getInstructors()
    {
        return $this->where('role', 'instructor')->findAll();
    }

    public function getStudents()
    {
        return $this->where('role', 'student')->findAll();
    }

    public function getAdmins()
    {
        return $this->where('role', 'admin')->findAll();
    }
}
