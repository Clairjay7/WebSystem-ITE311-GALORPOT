<?php

namespace App\Models;

use CodeIgniter\Model;

class TermModel extends Model
{
    protected $table = 'terms';
    protected $primaryKey = 'id';
    protected $allowedFields = ['semester_id', 'term_number', 'start_date', 'end_date', 'created_at', 'updated_at', 'deleted_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'start_date' => 'required|valid_date',
        'end_date'   => 'required|valid_date',
    ];

    protected $validationMessages = [
        'start_date' => [
            'required' => 'Term start date is required.',
            'valid_date' => 'Invalid start date format.',
        ],
        'end_date' => [
            'required' => 'Term end date is required.',
            'valid_date' => 'Invalid end date format.',
        ],
    ];

    /**
     * Get the currently active term based on today's date
     */
    public function getActiveTerm()
    {
        try {
            $today = date('Y-m-d');
            
            return $this->where('start_date IS NOT NULL')
                ->where('end_date IS NOT NULL')
                ->where('start_date <=', $today)
                ->where('end_date >=', $today)
                ->first();
        } catch (\Exception $e) {
            // Table might not exist yet
            return null;
        }
    }

    /**
     * Get term with full academic structure
     */
    public function getWithAcademicStructure($id)
    {
        $term = $this->find($id);
        if (!$term) {
            return null;
        }

        $semesterModel = new SemesterModel();
        $semester = $semesterModel->find($term['semester_id']);
        
        if ($semester) {
            $schoolYearModel = new SchoolYearModel();
            $schoolYear = $schoolYearModel->find($semester['school_year_id']);
            
            $term['semester'] = $semester;
            $term['school_year'] = $schoolYear;
        }

        return $term;
    }

    /**
     * Get current academic period (school year, semester, term)
     */
    public function getCurrentAcademicPeriod()
    {
        try {
            $activeTerm = $this->getActiveTerm();
            
            if (!$activeTerm) {
                return null;
            }

            $semesterModel = new SemesterModel();
            $semester = $semesterModel->find($activeTerm['semester_id']);
            
            if (!$semester) {
                return null;
            }

            $schoolYearModel = new SchoolYearModel();
            $schoolYear = $schoolYearModel->find($semester['school_year_id']);

            return [
                'school_year' => $schoolYear,
                'semester' => $semester,
                'term' => $activeTerm,
            ];
        } catch (\Exception $e) {
            // Tables might not exist yet
            return null;
        }
    }

    /**
     * Validate that start date is before end date
     */
    public function validateDates($startDate, $endDate)
    {
        if (strtotime($startDate) >= strtotime($endDate)) {
            return false;
        }
        return true;
    }
}

