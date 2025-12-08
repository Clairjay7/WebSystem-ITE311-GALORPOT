<?php

namespace App\Models;

use CodeIgniter\Model;

class SemesterModel extends Model
{
    protected $table = 'semesters';
    protected $primaryKey = 'id';
    protected $allowedFields = ['school_year_id', 'semester_number', 'created_at', 'updated_at', 'deleted_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    /**
     * Get semester with its terms
     */
    public function getWithTerms($id)
    {
        $semester = $this->find($id);
        if (!$semester) {
            return null;
        }

        $termModel = new TermModel();
        $semester['terms'] = $termModel->where('semester_id', $id)
            ->orderBy('term_number', 'ASC')
            ->findAll();

        return $semester;
    }

    /**
     * Get semesters for a school year
     */
    public function getBySchoolYear($schoolYearId)
    {
        try {
            return $this->where('school_year_id', $schoolYearId)
                ->orderBy('semester_number', 'ASC')
                ->findAll();
        } catch (\Exception $e) {
            // Table might not exist
            return [];
        }
    }
}

