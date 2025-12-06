<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolYearModel extends Model
{
    protected $table = 'school_years';
    protected $primaryKey = 'id';
    protected $allowedFields = ['school_year', 'is_active', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_year' => 'required|regex_match[/^\d{4}-\d{4}$/]|is_unique[school_years.school_year,id,{id}]',
    ];

    protected $validationMessages = [
        'school_year' => [
            'required' => 'School Year is required.',
            'regex_match' => 'Invalid School Year format. Use YYYY-YYYY and numbers only.',
            'is_unique' => 'This School Year already exists.',
        ],
    ];

    /**
     * Get the active school year
     */
    public function getActiveSchoolYear()
    {
        return $this->where('is_active', 1)->first();
    }

    /**
     * Set a school year as active (and deactivate others)
     */
    public function setActive($id)
    {
        try {
            // Deactivate all - use update with where clause
            $this->where('id >=', 0)->set('is_active', 0)->update();
            
            // Activate the selected one
            $result = $this->update($id, ['is_active' => 1]);
            return $result !== false;
        } catch (\Exception $e) {
            log_message('error', 'Error setting active school year: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get school year with its semesters
     */
    public function getWithSemesters($id)
    {
        $schoolYear = $this->find($id);
        if (!$schoolYear) {
            return null;
        }

        $semesterModel = new SemesterModel();
        $schoolYear['semesters'] = $semesterModel->where('school_year_id', $id)
            ->orderBy('semester_number', 'ASC')
            ->findAll();

        return $schoolYear;
    }

    /**
     * Get school year period (start and end dates)
     * Start = Semester 1 Term 1 Start Date
     * End = Semester 2 Term 2 End Date
     */
    public function getSchoolYearPeriod($id)
    {
        try {
            $semesterModel = new SemesterModel();
            $termModel = new TermModel();
            
            // Get Semester 1
            $semester1 = $semesterModel->where('school_year_id', $id)
                ->where('semester_number', 1)
                ->first();
            
            // Get Semester 2
            $semester2 = $semesterModel->where('school_year_id', $id)
                ->where('semester_number', 2)
                ->first();
            
            if (!$semester1 || !$semester2) {
                return null;
            }
            
            // Get Semester 1 Term 1 (start date)
            $sem1Term1 = $termModel->where('semester_id', $semester1['id'])
                ->where('term_number', 1)
                ->first();
            
            // Get Semester 2 Term 2 (end date)
            $sem2Term2 = $termModel->where('semester_id', $semester2['id'])
                ->where('term_number', 2)
                ->first();
            
            if (!$sem1Term1 || !$sem2Term2) {
                return null;
            }
            
            return [
                'start_date' => $sem1Term1['start_date'],
                'end_date' => $sem2Term2['end_date'],
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error getting school year period: ' . $e->getMessage());
            return null;
        }
    }
}

