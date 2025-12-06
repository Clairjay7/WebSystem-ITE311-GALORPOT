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
        
        // If admin, load users for CRUD in unified dashboard
        if ($role === 'admin') {
            $userModel = new UserModel();
            $data['users'] = $userModel->orderBy('created_at', 'DESC')->findAll();
        }
        
        // If instructor, load assigned courses for active term
        if ($role === 'instructor') {
            try {
                $courseModel = new CourseModel();
                $termModel = new TermModel();
                $currentPeriod = $termModel->getCurrentAcademicPeriod();
                
                if ($currentPeriod) {
                    // Get courses where instructor is assigned via instructor_id for the active term
                    $instructorCourses = $courseModel
                        ->where('instructor_id', $userId)
                        ->where('school_year_id', $currentPeriod['school_year']['id'])
                        ->where('semester', $currentPeriod['semester']['semester_number'])
                        ->where('term', $currentPeriod['term']['term_number'])
                        ->findAll();
                    
                    // Also get courses from teacher_assignments table
                    $teacherAssignmentModel = new TeacherAssignmentModel();
                    $assignedCourses = $teacherAssignmentModel->getTeacherCoursesForActiveTerm($userId);
                    
                    $courseIds = [];
                    if (!empty($assignedCourses)) {
                        $courseIds = array_column($assignedCourses, 'course_id');
                    }
                    
                    // Combine both: courses where instructor_id matches AND courses from teacher_assignments
                    $allCourseIds = array_unique(array_merge(
                        array_column($instructorCourses, 'id'),
                        $courseIds
                    ));
                    
                    if (empty($allCourseIds)) {
                        $data['instructor_message'] = 'You have no assigned courses for this term.';
                    } else {
                        $data['assigned_courses'] = $courseModel->whereIn('id', $allCourseIds)->findAll();
                    }
                } else {
                    // No active term, but show courses assigned to instructor anyway
                    $instructorCourses = $courseModel
                        ->where('instructor_id', $userId)
                        ->orderBy('created_at', 'DESC')
                        ->findAll();
                    
                    if (empty($instructorCourses)) {
                        $data['instructor_message'] = 'You have no assigned courses.';
                    } else {
                        $data['assigned_courses'] = $instructorCourses;
                    }
                }
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
                
                if ($currentPeriod) {
                    // Get approved enrollments (only these show in enrolled courses)
                    $enrollments = $enrollmentModel->getStudentEnrollmentsForActiveTerm($userId);
                    
                    // Add term end date to each enrollment and filter out expired courses
                    $termModel = new TermModel();
                    $semesterModel = new SemesterModel();
                    $today = date('Y-m-d');
                    $activeEnrollments = [];
                    
                    foreach ($enrollments as &$enrollment) {
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
                                    
                                    // Only include if end date hasn't passed
                                    if ($term['end_date'] >= $today) {
                                        $activeEnrollments[] = $enrollment;
                                    }
                                }
                            }
                        }
                    }
                    
                    $enrollments = $activeEnrollments;
                    
                    // Get pending enrollments separately
                    $pendingBuilder = $enrollmentModel->select('enrollments.*, courses.title as course_title, courses.description, courses.instructor_id')
                        ->join('courses', 'courses.id = enrollments.course_id')
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
