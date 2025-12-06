<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseModel extends Model
{
    protected $table = 'courses';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'title', 
        'description', 
        'instructor_id', 
        'school_year_id',
        'semester',
        'term',
        'created_at', 
        'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getCoursesWithInstructor()
    {
        return $this->select('courses.*, users.name as instructor_name')
                    ->join('users', 'users.id = courses.instructor_id')
                    ->findAll();
    }

    public function getCourseCount()
    {
        return $this->countAll();
    }

    public function getRecentCourses($limit = 5)
    {
        return $this->select('courses.*, users.name as instructor_name')
                    ->join('users', 'users.id = courses.instructor_id')
                    ->orderBy('courses.created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get courses for a specific academic period
     */
    public function getCoursesByAcademicPeriod($schoolYearId, $semester, $term)
    {
        return $this->select('courses.*, users.name as instructor_name, school_years.school_year')
                    ->join('users', 'users.id = courses.instructor_id')
                    ->join('school_years', 'school_years.id = courses.school_year_id')
                    ->where('courses.school_year_id', $schoolYearId)
                    ->where('courses.semester', $semester)
                    ->where('courses.term', $term)
                    ->findAll();
    }

    /**
     * Get courses for the current active term
     */
    public function getCoursesForActiveTerm()
    {
        $termModel = new \App\Models\TermModel();
        $currentPeriod = $termModel->getCurrentAcademicPeriod();
        
        if (!$currentPeriod) {
            return [];
        }

        return $this->getCoursesByAcademicPeriod(
            $currentPeriod['school_year']['id'],
            $currentPeriod['semester']['semester_number'],
            $currentPeriod['term']['term_number']
        );
    }

    /**
     * Validate that course has all required academic structure fields
     */
    public function validateAcademicStructure($data)
    {
        if (empty($data['school_year_id']) || empty($data['semester']) || empty($data['term'])) {
            return false;
        }
        return true;
    }
}
