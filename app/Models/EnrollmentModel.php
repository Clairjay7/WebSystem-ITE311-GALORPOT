<?php

namespace App\Models;

use CodeIgniter\Model;

class EnrollmentModel extends Model
{
    protected $table = 'enrollments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 
        'course_id', 
        'enrollment_date',
        'school_year_id',
        'semester',
        'term',
        'status',
        'rejection_reason',
        'deleted_at'
    ];
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    public function getEnrollmentCount()
    {
        return $this->countAll();
    }

    public function getEnrollmentsWithDetails()
    {
        return $this->select('enrollments.*, users.name as student_name, courses.title as course_title')
                    ->join('users', 'users.id = enrollments.user_id')
                    ->join('courses', 'courses.id = enrollments.course_id')
                    ->findAll();
    }

    public function getRecentEnrollments($limit = 5)
    {
        return $this->select('enrollments.*, users.name as student_name, courses.title as course_title')
                    ->join('users', 'users.id = enrollments.user_id')
                    ->join('courses', 'courses.id = enrollments.course_id')
                    ->orderBy('enrollments.enrollment_date', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Insert a new enrollment record
     * @param array $data - Array containing user_id, course_id, and enrollment_date
     * @return int|bool - Returns the insert ID on success, false on failure
     */
    public function enrollUser($data)
    {
        // Set enrollment_date to current datetime if not provided
        if (!isset($data['enrollment_date'])) {
            $data['enrollment_date'] = date('Y-m-d H:i:s');
        }
        
        return $this->insert($data);
    }

    /**
     * Fetch all courses a user is enrolled in
     * @param int $user_id - The user ID
     * @return array - Array of enrollment records with course details
     */
    public function getUserEnrollments($user_id)
    {
        return $this->select('enrollments.*, courses.title as course_title, courses.description, courses.instructor_id')
                    ->join('courses', 'courses.id = enrollments.course_id')
                    ->where('enrollments.user_id', $user_id)
                    ->orderBy('enrollments.enrollment_date', 'DESC')
                    ->findAll();
    }

    /**
     * Check if a user is already enrolled in a specific course to prevent duplicates
     * @param int $user_id - The user ID
     * @param int $course_id - The course ID
     * @param int $school_year_id - The school year ID
     * @param int $semester - The semester number
     * @param int $term - The term number
     * @return bool - Returns true if already enrolled, false otherwise
     */
    public function isAlreadyEnrolled($user_id, $course_id, $school_year_id = null, $semester = null, $term = null)
    {
        $builder = $this->where('user_id', $user_id)
                        ->where('course_id', $course_id);
        
        if ($school_year_id) {
            $builder->where('school_year_id', $school_year_id);
        }
        
        if ($semester) {
            $builder->where('semester', $semester);
        }
        
        if ($term) {
            $builder->where('term', $term);
        }
        
        $enrollment = $builder->first();
        
        return $enrollment !== null;
    }

    /**
     * Get enrollments for a specific academic period
     */
    public function getEnrollmentsByAcademicPeriod($schoolYearId, $semester, $term)
    {
        return $this->select('enrollments.*, users.name as student_name, courses.title as course_title')
                    ->join('users', 'users.id = enrollments.user_id')
                    ->join('courses', 'courses.id = enrollments.course_id')
                    ->where('enrollments.school_year_id', $schoolYearId)
                    ->where('enrollments.semester', $semester)
                    ->where('enrollments.term', $term)
                    ->findAll();
    }

    /**
     * Get student enrollments for the current active term
     */
    public function getStudentEnrollmentsForActiveTerm($userId)
    {
        try {
            $termModel = new \App\Models\TermModel();
            $currentPeriod = $termModel->getCurrentAcademicPeriod();
            
            if (!$currentPeriod) {
                return [];
            }

            $builder = $this->select('enrollments.*, courses.title as course_title, courses.description, courses.instructor_id, courses.school_year_id as course_school_year_id, courses.semester as course_semester, courses.term as course_term')
                        ->join('courses', 'courses.id = enrollments.course_id')
                        ->where('enrollments.user_id', $userId)
                        ->where('enrollments.school_year_id', $currentPeriod['school_year']['id'])
                        ->where('enrollments.semester', $currentPeriod['semester']['semester_number'])
                        ->where('enrollments.term', $currentPeriod['term']['term_number']);
            
            // Only filter by status if the column exists (migration has been run)
            // IMPORTANT: If status column doesn't exist, return empty array to prevent showing unapproved enrollments
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                if (in_array('status', $columns)) {
                    $builder->where('enrollments.status', 'approved');
                } else {
                    // If status column doesn't exist, return empty - don't show enrollments until migration is run
                    return [];
                }
            } catch (\Exception $e) {
                // Table might not exist or column check failed, return empty to be safe
                return [];
            }
            
            return $builder->orderBy('enrollments.enrollment_date', 'DESC')
                        ->findAll();
        } catch (\Exception $e) {
            // Tables might not exist yet
            return [];
        }
    }
}
