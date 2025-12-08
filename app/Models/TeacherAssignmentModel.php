<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherAssignmentModel extends Model
{
    protected $table = 'teacher_assignments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'teacher_id', 
        'course_id', 
        'school_year_id', 
        'semester', 
        'term',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    /**
     * Get courses assigned to a teacher for a specific academic period
     */
    public function getTeacherCourses($teacherId, $schoolYearId = null, $semester = null, $term = null)
    {
        $builder = $this->where('teacher_id', $teacherId);
        
        if ($schoolYearId) {
            $builder->where('school_year_id', $schoolYearId);
        }
        
        if ($semester) {
            $builder->where('semester', $semester);
        }
        
        if ($term) {
            $builder->where('term', $term);
        }

        return $builder->findAll();
    }

    /**
     * Get courses assigned to a teacher for the current active term
     */
    public function getTeacherCoursesForActiveTerm($teacherId)
    {
        try {
            $termModel = new TermModel();
            $currentPeriod = $termModel->getCurrentAcademicPeriod();
            
            if (!$currentPeriod) {
                return [];
            }

            return $this->where('teacher_id', $teacherId)
                ->where('school_year_id', $currentPeriod['school_year']['id'])
                ->where('semester', $currentPeriod['semester']['semester_number'])
                ->where('term', $currentPeriod['term']['term_number'])
                ->findAll();
        } catch (\Exception $e) {
            // Tables might not exist yet
            return [];
        }
    }

    /**
     * Check if teacher is assigned to a course
     * @param int $excludeId Optional ID to exclude from check (useful when updating)
     */
    public function isAssigned($teacherId, $courseId, $schoolYearId, $semester, $term, $excludeId = null)
    {
        $builder = $this->where('teacher_id', $teacherId)
            ->where('course_id', $courseId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester', $semester)
            ->where('term', $term);
        
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->first() !== null;
    }
}

