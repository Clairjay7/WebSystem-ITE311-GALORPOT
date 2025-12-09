<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\TermModel;
use App\Models\CourseModel;
use App\Models\EnrollmentModel;
use App\Models\TeacherAssignmentModel;
use App\Models\SchoolYearModel;
use App\Models\SemesterModel;

class Dashboard extends BaseController
{
    public function index()
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $data = [];
        $role = strtolower((string) session()->get('role'));
        $userId = (int) session()->get('id');
        
        // Try to get current academic period and active school year (gracefully handle if tables don't exist yet)
        try {
            $termModel = new TermModel();
            $currentPeriod = $termModel->getCurrentAcademicPeriod();
            $data['current_period'] = $currentPeriod;
            
            // Also get active school year (even if no active term)
            $schoolYearModel = new SchoolYearModel();
            $activeSchoolYear = $schoolYearModel->getActiveSchoolYear();
            $data['active_school_year'] = $activeSchoolYear;
            
            // Get school year period (start = Sem 1 Term 1, end = Sem 2 Term 2)
            if ($activeSchoolYear) {
                $schoolYearPeriod = $schoolYearModel->getSchoolYearPeriod($activeSchoolYear['id']);
                $data['active_school_year_period'] = $schoolYearPeriod;
            }
            
            // If there's an active school year but no active term, show school year info
            if ($activeSchoolYear && !$currentPeriod) {
                // Get all semesters and terms for the active school year
                $semesterModel = new \App\Models\SemesterModel();
                $semesters = $semesterModel->getBySchoolYear($activeSchoolYear['id']);
                $data['active_school_year_semesters'] = $semesters;
            }
        } catch (\Exception $e) {
            // Tables might not exist yet - migrations need to be run
            $data['current_period'] = null;
            $data['active_school_year'] = null;
            $data['migration_warning'] = 'Database migrations need to be run. Please run: php spark migrate';
        }
        
        // If admin, load users for CRUD in unified dashboard and completed courses
        if ($role === 'admin') {
            $userModel = new UserModel();
            $data['users'] = $userModel->orderBy('created_at', 'DESC')->findAll();
            $data['deleted_users'] = $userModel->withDeleted()
                ->onlyDeleted()
                ->orderBy('deleted_at', 'DESC')
                ->findAll();
            
            // Get all completed courses (where term end date has passed)
            try {
                $courseModel = new CourseModel();
                $termModel = new TermModel();
                $semesterModel = new SemesterModel();
                $schoolYearModel = new SchoolYearModel();
                $today = date('Y-m-d');
                
                $allCourses = $courseModel->orderBy('created_at', 'DESC')->findAll();
                $completedCourses = [];
                
                foreach ($allCourses as $course) {
                    if (empty($course['school_year_id']) || empty($course['semester']) || empty($course['term'])) {
                        continue;
                    }
                    
                    $semester = $semesterModel
                        ->where('school_year_id', $course['school_year_id'])
                        ->where('semester_number', $course['semester'])
                        ->first();
                    
                    if ($semester) {
                        $term = $termModel
                            ->where('semester_id', $semester['id'])
                            ->where('term_number', $course['term'])
                            ->first();
                        
                        if ($term && $term['end_date'] && $term['end_date'] < $today) {
                            $course['term_end_date'] = $term['end_date'];
                            $sy = $schoolYearModel->find($course['school_year_id']);
                            $course['school_year'] = $sy ? $sy['school_year'] : null;
                            
                            // Get instructor name
                            if (!empty($course['instructor_id'])) {
                                $instructor = $userModel->find($course['instructor_id']);
                                $course['instructor_name'] = $instructor ? $instructor['name'] : 'N/A';
                            } else {
                                $course['instructor_name'] = 'N/A';
                            }
                            
                            $completedCourses[] = $course;
                        }
                    }
                }
                
                // Sort completed courses by school year (descending) then by end date (most recent first)
                usort($completedCourses, function($a, $b) use ($schoolYearModel) {
                    $syA = $schoolYearModel->find($a['school_year_id']);
                    $syB = $schoolYearModel->find($b['school_year_id']);
                    
                    $yearA = $syA ? (int) explode('-', $syA['school_year'])[0] : 0;
                    $yearB = $syB ? (int) explode('-', $syB['school_year'])[0] : 0;
                    
                    if ($yearA !== $yearB) {
                        return $yearB - $yearA; // Sort by school year descending
                    }
                    
                    $dateA = isset($a['term_end_date']) ? strtotime($a['term_end_date']) : 0;
                    $dateB = isset($b['term_end_date']) ? strtotime($b['term_end_date']) : 0;
                    return $dateB - $dateA; // Then by end date descending
                });
                
                $data['completed_courses'] = $completedCourses;
            } catch (\Exception $e) {
                log_message('error', 'Error loading admin completed courses: ' . $e->getMessage());
                $data['completed_courses'] = [];
            }
        }
        
        // If instructor, load assigned courses for active term
        if ($role === 'instructor') {
            try {
                $courseModel = new CourseModel();
                $termModel = new TermModel();
                $currentPeriod = $termModel->getCurrentAcademicPeriod();
                
                $termModel = new TermModel();
                $semesterModel = new SemesterModel();
                $today = date('Y-m-d');
                
                // Get all courses where instructor is assigned via instructor_id (not just active term)
                $instructorCourses = $courseModel
                    ->where('instructor_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
                
                // Also get ALL courses from teacher_assignments table (not just active term)
                $teacherAssignmentModel = new TeacherAssignmentModel();
                $allAssignments = $teacherAssignmentModel
                    ->where('teacher_id', $userId)
                    ->findAll();
                
                $courseIds = [];
                if (!empty($allAssignments)) {
                    $courseIds = array_column($allAssignments, 'course_id');
                }
                
                // Combine both: courses where instructor_id matches AND courses from teacher_assignments
                $allCourseIds = array_unique(array_merge(
                    array_column($instructorCourses, 'id'),
                    $courseIds
                ));
                
                if (empty($allCourseIds)) {
                    $data['instructor_message'] = 'You have no assigned courses.';
                } else {
                    $allCourses = $courseModel->whereIn('id', $allCourseIds)
                        ->orderBy('created_at', 'DESC')
                        ->findAll();
                    
                    // Filter out expired courses from assigned_courses (they'll be in completed_courses)
                    $assignedCourses = [];
                    foreach ($allCourses as $course) {
                        if (empty($course['school_year_id']) || empty($course['semester']) || empty($course['term'])) {
                            // If course doesn't have academic structure, include it
                            $assignedCourses[] = $course;
                            continue;
                        }
                        
                        $semester = $semesterModel
                            ->where('school_year_id', $course['school_year_id'])
                            ->where('semester_number', $course['semester'])
                            ->first();
                        
                        if ($semester) {
                            $term = $termModel
                                ->where('semester_id', $semester['id'])
                                ->where('term_number', $course['term'])
                                ->first();
                            
                            if ($term && $term['end_date']) {
                                // Only include if end date hasn't passed
                                if ($term['end_date'] >= $today) {
                                    $assignedCourses[] = $course;
                                }
                            } else {
                                // If term not found or no end date, include it
                                $assignedCourses[] = $course;
                            }
                        } else {
                            // If semester not found, include it
                            $assignedCourses[] = $course;
                        }
                    }
                    
                    $data['assigned_courses'] = $assignedCourses;
                }
                
                // Get all completed courses (where term end date has passed)
                $allInstructorCourses = $courseModel
                    ->where('instructor_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
                
                $completedCourses = [];
                foreach ($allInstructorCourses as $course) {
                    if (empty($course['school_year_id']) || empty($course['semester']) || empty($course['term'])) {
                        continue;
                    }
                    
                    $semester = $semesterModel
                        ->where('school_year_id', $course['school_year_id'])
                        ->where('semester_number', $course['semester'])
                        ->first();
                    
                    if ($semester) {
                        $term = $termModel
                            ->where('semester_id', $semester['id'])
                            ->where('term_number', $course['term'])
                            ->first();
                        
                        if ($term && $term['end_date'] && $term['end_date'] < $today) {
                            // Course is completed
                            $course['term_end_date'] = $term['end_date'];
                            $schoolYearModel = new SchoolYearModel();
                            $sy = $schoolYearModel->find($course['school_year_id']);
                            $course['school_year'] = $sy ? $sy['school_year'] : null;
                            $completedCourses[] = $course;
                        }
                    }
                }
                
                // Also check courses from teacher_assignments
                $teacherAssignmentModel = new TeacherAssignmentModel();
                $allAssignments = $teacherAssignmentModel
                    ->select('teacher_assignments.*, courses.*')
                    ->join('courses', 'courses.id = teacher_assignments.course_id')
                    ->where('teacher_assignments.teacher_id', $userId)
                    ->findAll();
                
                foreach ($allAssignments as $assignment) {
                    $course = $courseModel->find($assignment['course_id']);
                    if (!$course || $course['instructor_id'] != $userId) {
                        continue; // Skip if already processed or not assigned
                    }
                    
                    if (empty($course['school_year_id']) || empty($course['semester']) || empty($course['term'])) {
                        continue;
                    }
                    
                    $semester = $semesterModel
                        ->where('school_year_id', $course['school_year_id'])
                        ->where('semester_number', $course['semester'])
                        ->first();
                    
                    if ($semester) {
                        $term = $termModel
                            ->where('semester_id', $semester['id'])
                            ->where('term_number', $course['term'])
                            ->first();
                        
                        if ($term && $term['end_date'] && $term['end_date'] < $today) {
                            // Check if already in completed courses
                            $exists = false;
                            foreach ($completedCourses as $comp) {
                                if ($comp['id'] == $course['id']) {
                                    $exists = true;
                                    break;
                                }
                            }
                            
                            if (!$exists) {
                                $course['term_end_date'] = $term['end_date'];
                                $schoolYearModel = new SchoolYearModel();
                                $sy = $schoolYearModel->find($course['school_year_id']);
                                $course['school_year'] = $sy ? $sy['school_year'] : null;
                                $completedCourses[] = $course;
                            }
                        }
                    }
                }
                
                // Sort completed courses by end date (most recent first)
                usort($completedCourses, function($a, $b) {
                    return strtotime($b['term_end_date']) - strtotime($a['term_end_date']);
                });
                
                $data['completed_courses'] = $completedCourses;
            } catch (\Exception $e) {
                log_message('error', 'Error loading instructor courses: ' . $e->getMessage());
                $data['instructor_message'] = 'Academic structure not set up yet.';
            }
        }
        
        // If student, load enrollments for active term
        if ($role === 'student') {
            try {
                $enrollmentModel = new EnrollmentModel();
                $termModel = new TermModel();
                $currentPeriod = $termModel->getCurrentAcademicPeriod();
                
                // Get all approved enrollments (across all terms, not just current term)
                $termModel = new TermModel();
                $semesterModel = new SemesterModel();
                $today = date('Y-m-d');
                
                // Check if status column exists
                $hasStatusColumn = false;
                try {
                    $db = \Config\Database::connect();
                    $columns = $db->getFieldNames('enrollments');
                    if (in_array('status', $columns)) {
                        $hasStatusColumn = true;
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Error checking for status column in enrollments table: ' . $e->getMessage());
                }
                
                $allEnrollments = [];
                if ($hasStatusColumn) {
                    // Get all approved enrollments (across all terms)
                    $allEnrollments = $enrollmentModel
                        ->select('enrollments.*, courses.title as course_title, courses.description, courses.control_number, courses.units, courses.time, courses.school_year_id as course_school_year_id, courses.semester as course_semester, courses.term as course_term, users.name as instructor_name')
                        ->join('courses', 'courses.id = enrollments.course_id')
                        ->join('users', 'users.id = courses.instructor_id', 'left')
                        ->where('enrollments.user_id', $userId)
                        ->where('enrollments.status', 'approved')
                        ->orderBy('enrollments.enrollment_date', 'DESC')
                        ->findAll();
                } else {
                    // If no status column, get all enrollments
                    $allEnrollments = $enrollmentModel
                        ->select('enrollments.*, courses.title as course_title, courses.description, courses.control_number, courses.units, courses.time, courses.school_year_id as course_school_year_id, courses.semester as course_semester, courses.term as course_term, users.name as instructor_name')
                        ->join('courses', 'courses.id = enrollments.course_id')
                        ->join('users', 'users.id = courses.instructor_id', 'left')
                        ->where('enrollments.user_id', $userId)
                        ->orderBy('enrollments.enrollment_date', 'DESC')
                        ->findAll();
                }
                
                // Add term end date to each enrollment and filter out expired courses
                $enrollments = [];
                foreach ($allEnrollments as $enrollment) {
                    if (isset($enrollment['course_school_year_id']) && isset($enrollment['course_semester']) && isset($enrollment['course_term'])) {
                        $semester = $semesterModel
                            ->where('school_year_id', $enrollment['course_school_year_id'])
                            ->where('semester_number', $enrollment['course_semester'])
                            ->first();
                        
                        if ($semester) {
                            $term = $termModel
                                ->where('semester_id', $semester['id'])
                                ->where('term_number', $enrollment['course_term'])
                                ->first();
                            
                            if ($term) {
                                $enrollment['term_end_date'] = $term['end_date'];
                                
                                // Only include if end date hasn't passed (remove expired courses)
                                if ($term['end_date'] >= $today) {
                                    $enrollments[] = $enrollment;
                                }
                            } else {
                                // If term not found, include it anyway
                                $enrollments[] = $enrollment;
                            }
                        } else {
                            // If semester not found, include it anyway
                            $enrollments[] = $enrollment;
                        }
                    } else {
                        // If course doesn't have academic structure, include it anyway
                        $enrollments[] = $enrollment;
                    }
                }
                
                if ($currentPeriod) {
                    
                    // Get pending enrollments separately
                    $pendingBuilder = $enrollmentModel->select('enrollments.*, courses.title as course_title, courses.description, courses.instructor_id, courses.control_number, courses.units, users.name as instructor_name')
                        ->join('courses', 'courses.id = enrollments.course_id')
                        ->join('users', 'users.id = courses.instructor_id', 'left')
                        ->where('enrollments.user_id', $userId)
                        ->where('enrollments.school_year_id', $currentPeriod['school_year']['id'])
                        ->where('enrollments.semester', $currentPeriod['semester']['semester_number'])
                        ->where('enrollments.term', $currentPeriod['term']['term_number']);
                    
                    // Only filter by status if the column exists
                    // IMPORTANT: If status column doesn't exist, we can't distinguish pending from approved
                    $pendingEnrollments = [];
                    try {
                        $db = \Config\Database::connect();
                        $columns = $db->getFieldNames('enrollments');
                        if (in_array('status', $columns)) {
                            $pendingBuilder->where('enrollments.status', 'pending');
                            $allPending = $pendingBuilder->orderBy('enrollments.enrollment_date', 'DESC')
                                ->findAll();
                            
                            // Filter out expired courses from pending enrollments
                            $activePending = [];
                            foreach ($allPending as $pending) {
                                // Get course to check term end date
                                $courseModel = new CourseModel();
                                $course = $courseModel->find($pending['course_id']);
                                
                                if ($course) {
                                    $semester = $semesterModel
                                        ->where('school_year_id', $course['school_year_id'])
                                        ->where('semester_number', $course['semester'])
                                        ->first();
                                    
                                    if ($semester) {
                                        $term = $termModel
                                            ->where('semester_id', $semester['id'])
                                            ->where('term_number', $course['term'])
                                            ->first();
                                        
                                        // Only include if term exists and end date hasn't passed
                                        if ($term && $term['end_date'] >= $today) {
                                            $activePending[] = $pending;
                                        }
                                    }
                                }
                            }
                            
                            $pendingEnrollments = $activePending;
                        } else {
                            // If status column doesn't exist, return empty - migration needs to be run
                            $pendingEnrollments = [];
                        }
                    } catch (\Exception $e) {
                        // Table might not exist, return empty
                        $pendingEnrollments = [];
                    }
                    
                    $data['pending_enrollments'] = $pendingEnrollments;
                    
                    // Get rejected enrollments separately
                    $rejectedBuilder = $enrollmentModel->select('enrollments.*, courses.title as course_title, courses.description, courses.instructor_id')
                        ->join('courses', 'courses.id = enrollments.course_id')
                        ->where('enrollments.user_id', $userId)
                        ->where('enrollments.school_year_id', $currentPeriod['school_year']['id'])
                        ->where('enrollments.semester', $currentPeriod['semester']['semester_number'])
                        ->where('enrollments.term', $currentPeriod['term']['term_number']);
                    
                    $rejectedEnrollments = [];
                    try {
                        $db = \Config\Database::connect();
                        $columns = $db->getFieldNames('enrollments');
                        if (in_array('status', $columns)) {
                            $rejectedBuilder->where('enrollments.status', 'rejected');
                            $allRejected = $rejectedBuilder->orderBy('enrollments.enrollment_date', 'DESC')
                                ->findAll();
                            
                            // Filter out expired courses from rejected enrollments
                            $activeRejected = [];
                            foreach ($allRejected as $rejected) {
                                $courseModel = new CourseModel();
                                $course = $courseModel->find($rejected['course_id']);
                                
                                if ($course) {
                                    $semester = $semesterModel
                                        ->where('school_year_id', $course['school_year_id'])
                                        ->where('semester_number', $course['semester'])
                                        ->first();
                                    
                                    if ($semester) {
                                        $term = $termModel
                                            ->where('semester_id', $semester['id'])
                                            ->where('term_number', $course['term'])
                                            ->first();
                                        
                                        // Only include if term exists and end date hasn't passed
                                        if ($term && $term['end_date'] >= $today) {
                                            $activeRejected[] = $rejected;
                                        }
                                    }
                                }
                            }
                            
                            $rejectedEnrollments = $activeRejected;
                        }
                    } catch (\Exception $e) {
                        // Table might not exist, return empty
                        $rejectedEnrollments = [];
                    }
                    
                    $data['rejected_enrollments'] = $rejectedEnrollments;
                    
                    if (empty($enrollments) && empty($pendingEnrollments) && empty($rejectedEnrollments)) {
                        $data['student_message'] = 'You are not enrolled in any courses for this term.';
                    } else {
                        $data['enrollments'] = $enrollments;
                    }
                } else {
                    $data['student_message'] = 'No active academic period.';
                }
            } catch (\Exception $e) {
                log_message('error', 'Error loading student enrollments: ' . $e->getMessage());
                $data['student_message'] = 'Academic structure not set up yet.';
            }
        }

        return view('auth/dashboard', $data);
    }
}
