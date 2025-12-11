<?php

namespace App\Controllers;

use App\Models\CourseModel;
use App\Models\EnrollmentModel;
use App\Models\SchoolYearModel;
use App\Models\TermModel;
use App\Models\SemesterModel;
use App\Models\NotificationModel;
use App\Models\UserModel;

class Student extends BaseController
{
    /**
     * Ensure user is logged in and is a student
     */
    protected function ensureStudent()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $role = strtolower((string) session()->get('role'));
        if ($role !== 'student') {
            session()->setFlashdata('error', 'Access denied. Student access only.');
            return redirect()->to('/dashboard');
        }

        return null;
    }

    /**
     * Student enrollment page - view available courses and enroll
     */
    public function enroll()
    {
        if ($redirect = $this->ensureStudent()) {
            return $redirect;
        }

        $userId = (int) session()->get('id');
        $courseModel = new CourseModel();
        $enrollmentModel = new EnrollmentModel();
        $termModel = new TermModel();
        $schoolYearModel = new SchoolYearModel();

        $data = [
            'available_courses' => [],
            'enrolled_courses' => [],
            'pending_enrollments' => [],
            'current_period' => null,
            'active_school_year' => null,
        ];

        try {
            // Get current academic period
            $currentPeriod = $termModel->getCurrentAcademicPeriod();
            $data['current_period'] = $currentPeriod;

            // If no active term, get active school year
            if (!$currentPeriod) {
                $activeSchoolYear = $schoolYearModel->getActiveSchoolYear();
                $data['active_school_year'] = $activeSchoolYear;
                
                // Get available courses for active school year (even without active term)
                if ($activeSchoolYear) {
                    $teacherAssignmentModel = new \App\Models\TeacherAssignmentModel();
                    
                    // Get courses with direct instructor assignment for this school year
                    $coursesWithDirectInstructor = $courseModel
                        ->select('courses.*, courses.time, courses.units, users.name as instructor_name')
                        ->join('users', 'users.id = courses.instructor_id', 'inner')
                        ->where('courses.school_year_id', $activeSchoolYear['id'])
                        ->where('courses.instructor_id IS NOT NULL')
                        ->findAll();
                    
                    // Get courses with instructor assigned via teacher_assignments table for this school year
                    $teacherAssignments = $teacherAssignmentModel
                        ->select('teacher_assignments.course_id, teacher_assignments.school_year_id, teacher_assignments.semester, teacher_assignments.term, users.name as instructor_name')
                        ->join('users', 'users.id = teacher_assignments.teacher_id')
                        ->where('teacher_assignments.school_year_id', $activeSchoolYear['id'])
                        ->findAll();
                    
                    // Get course IDs from teacher_assignments
                    $assignedCourseIds = array_column($teacherAssignments, 'course_id');
                    $assignedCoursesMap = [];
                    foreach ($teacherAssignments as $assignment) {
                        $assignedCoursesMap[$assignment['course_id']] = $assignment['instructor_name'];
                    }
                    
                    // Get courses from teacher_assignments
                    $coursesFromAssignments = [];
                    if (!empty($assignedCourseIds)) {
                        $coursesFromAssignments = $courseModel
                            ->select('courses.*, courses.time, courses.units')
                            ->whereIn('courses.id', $assignedCourseIds)
                            ->where('courses.school_year_id', $activeSchoolYear['id'])
                            ->findAll();
                        
                        // Add instructor name from teacher_assignments
                        foreach ($coursesFromAssignments as &$course) {
                            $course['instructor_name'] = $assignedCoursesMap[$course['id']] ?? 'Not assigned';
                        }
                    }
                    
                    // Combine both sets of courses, avoiding duplicates
                    $allAvailableCourses = [];
                    $addedCourseIds = [];
                    
                    // Add courses with direct instructor
                    foreach ($coursesWithDirectInstructor as $course) {
                        $allAvailableCourses[] = $course;
                        $addedCourseIds[] = $course['id'];
                    }
                    
                    // Add courses from teacher_assignments that aren't already added
                    foreach ($coursesFromAssignments as $course) {
                        if (!in_array($course['id'], $addedCourseIds)) {
                            $allAvailableCourses[] = $course;
                            $addedCourseIds[] = $course['id'];
                        }
                    }
                    
                    // Filter to only include courses with assigned instructors
                    $data['available_courses'] = [];
                    foreach ($allAvailableCourses as $course) {
                        if (!empty($course['instructor_name']) && $course['instructor_name'] !== 'Not assigned') {
                            $data['available_courses'][] = $course;
                        }
                    }
                }
            } else {
                // Get available courses for current term with instructor name
                // Include courses with instructors assigned via courses.instructor_id OR teacher_assignments
                $teacherAssignmentModel = new \App\Models\TeacherAssignmentModel();
                
                // Get courses with direct instructor assignment
                $coursesWithDirectInstructor = $courseModel
                    ->select('courses.*, courses.time, courses.units, users.name as instructor_name')
                    ->join('users', 'users.id = courses.instructor_id', 'inner')
                    ->where('courses.school_year_id', $currentPeriod['school_year']['id'])
                    ->where('courses.semester', $currentPeriod['semester']['semester_number'])
                    ->where('courses.term', $currentPeriod['term']['term_number'])
                    ->where('courses.instructor_id IS NOT NULL')
                    ->findAll();
                
                // Get courses with instructor assigned via teacher_assignments table
                $teacherAssignments = $teacherAssignmentModel
                    ->select('teacher_assignments.course_id, teacher_assignments.school_year_id, teacher_assignments.semester, teacher_assignments.term, users.name as instructor_name')
                    ->join('users', 'users.id = teacher_assignments.teacher_id')
                    ->where('teacher_assignments.school_year_id', $currentPeriod['school_year']['id'])
                    ->where('teacher_assignments.semester', $currentPeriod['semester']['semester_number'])
                    ->where('teacher_assignments.term', $currentPeriod['term']['term_number'])
                    ->findAll();
                
                // Get course IDs from teacher_assignments
                $assignedCourseIds = array_column($teacherAssignments, 'course_id');
                $assignedCoursesMap = [];
                foreach ($teacherAssignments as $assignment) {
                    $assignedCoursesMap[$assignment['course_id']] = $assignment['instructor_name'];
                }
                
                // Get courses from teacher_assignments
                $coursesFromAssignments = [];
                if (!empty($assignedCourseIds)) {
                    $coursesFromAssignments = $courseModel
                        ->select('courses.*, courses.time, courses.units')
                        ->whereIn('courses.id', $assignedCourseIds)
                        ->where('courses.school_year_id', $currentPeriod['school_year']['id'])
                        ->where('courses.semester', $currentPeriod['semester']['semester_number'])
                        ->where('courses.term', $currentPeriod['term']['term_number'])
                        ->findAll();
                    
                    // Add instructor name from teacher_assignments
                    foreach ($coursesFromAssignments as &$course) {
                        $course['instructor_name'] = $assignedCoursesMap[$course['id']] ?? 'Not assigned';
                    }
                }
                
                // Combine both sets of courses, avoiding duplicates
                $allAvailableCourses = [];
                $addedCourseIds = [];
                
                // Add courses with direct instructor
                foreach ($coursesWithDirectInstructor as $course) {
                    $allAvailableCourses[] = $course;
                    $addedCourseIds[] = $course['id'];
                }
                
                // Add courses from teacher_assignments that aren't already added
                foreach ($coursesFromAssignments as $course) {
                    if (!in_array($course['id'], $addedCourseIds)) {
                        $allAvailableCourses[] = $course;
                        $addedCourseIds[] = $course['id'];
                    }
                }
                
                // Filter out expired courses (where term end date has passed)
                $today = date('Y-m-d');
                $termModel = new TermModel();
                $semesterModel = new SemesterModel();
                $data['available_courses'] = [];
                
                foreach ($allAvailableCourses as $course) {
                    // Include courses that have an instructor (either via instructor_id or teacher_assignments)
                    // Check if course has instructor_name (from either source)
                    if (empty($course['instructor_name']) || $course['instructor_name'] === 'Not assigned') {
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
                        
                        // Only include if term exists and end date hasn't passed
                        if ($term && $term['end_date'] >= $today) {
                            $data['available_courses'][] = $course;
                        }
                    }
                }

                // Check if status column exists
                $hasStatusColumn = false;
                try {
                    $db = \Config\Database::connect();
                    $columns = $db->getFieldNames('enrollments');
                    $hasStatusColumn = in_array('status', $columns);
                } catch (\Exception $e) {
                    // Status column doesn't exist
                }

                // Get ALL approved enrollments (across all terms, not just current term)
                $approvedEnrollments = [];
                if ($hasStatusColumn) {
                    $allApprovedEnrollments = $enrollmentModel
                        ->where('user_id', $userId)
                        ->where('status', 'approved')
                        ->findAll();
                    
                    // Filter out expired courses
                    $today = date('Y-m-d');
                    $termModel = new TermModel();
                    $semesterModel = new SemesterModel();
                    
                    foreach ($allApprovedEnrollments as $enrollment) {
                        // Get course to check term end date
                        $course = $courseModel->find($enrollment['course_id']);
                        
                        if ($course && !empty($course['school_year_id']) && !empty($course['semester']) && !empty($course['term'])) {
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
                                    $approvedEnrollments[] = $enrollment;
                                }
                            }
                        } else {
                            // If course doesn't have academic structure, include it
                            $approvedEnrollments[] = $enrollment;
                        }
                    }
                }

                // Get pending enrollments (for pending display) and filter out expired courses
                $pendingEnrollments = [];
                if ($hasStatusColumn) {
                    $allPending = $enrollmentModel
                        ->select('enrollments.*, courses.title as course_title, courses.description, courses.control_number, courses.units, users.name as instructor_name')
                        ->join('courses', 'courses.id = enrollments.course_id')
                        ->join('users', 'users.id = courses.instructor_id', 'left')
                        ->where('enrollments.user_id', $userId)
                        ->where('enrollments.school_year_id', $currentPeriod['school_year']['id'])
                        ->where('enrollments.semester', $currentPeriod['semester']['semester_number'])
                        ->where('enrollments.term', $currentPeriod['term']['term_number'])
                        ->where('enrollments.status', 'pending')
                        ->orderBy('enrollments.enrollment_date', 'DESC')
                        ->findAll();
                    
                    // Filter out expired courses from pending enrollments
                    $today = date('Y-m-d');
                    $activePending = [];
                    foreach ($allPending as $pending) {
                        // Get course to check term end date
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
                }

                $data['pending_enrollments'] = $pendingEnrollments;

                // Get rejected enrollments (for rejected display)
                $rejectedEnrollments = [];
                if ($hasStatusColumn) {
                    $allRejected = $enrollmentModel
                        ->select('enrollments.*, courses.title as course_title, courses.description')
                        ->join('courses', 'courses.id = enrollments.course_id')
                        ->where('enrollments.user_id', $userId)
                        ->where('enrollments.school_year_id', $currentPeriod['school_year']['id'])
                        ->where('enrollments.semester', $currentPeriod['semester']['semester_number'])
                        ->where('enrollments.term', $currentPeriod['term']['term_number'])
                        ->where('enrollments.status', 'rejected')
                        ->orderBy('enrollments.enrollment_date', 'DESC')
                        ->findAll();
                    
                    // Filter out expired courses from rejected enrollments
                    $today = date('Y-m-d');
                    $activeRejected = [];
                    foreach ($allRejected as $rejected) {
                        // Get course to check term end date
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

                $data['rejected_enrollments'] = $rejectedEnrollments;

                // Get enrolled courses (approved only) with instructor name and term end date
                $approvedCourseIds = array_column($approvedEnrollments, 'course_id');
                if (!empty($approvedCourseIds)) {
                    $enrolledCourses = $courseModel->select('courses.*, users.name as instructor_name')
                        ->join('users', 'users.id = courses.instructor_id', 'left')
                        ->whereIn('courses.id', $approvedCourseIds)
                        ->findAll();
                    
                    // Add term end date for each course and filter out expired courses
                    $termModel = new TermModel();
                    $semesterModel = new SemesterModel();
                    $today = date('Y-m-d');
                    $activeCourses = [];
                    
                    foreach ($enrolledCourses as &$course) {
                        // Get semester
                        $semester = $semesterModel
                            ->where('school_year_id', $course['school_year_id'])
                            ->where('semester_number', $course['semester'])
                            ->first();
                        
                        if ($semester) {
                            // Get term
                            $term = $termModel
                                ->where('semester_id', $semester['id'])
                                ->where('term_number', $course['term'])
                                ->first();
                            
                            if ($term) {
                                $course['term_end_date'] = $term['end_date'];
                                
                                // Only include if end date hasn't passed
                                if ($term['end_date'] >= $today) {
                                    $activeCourses[] = $course;
                                }
                            }
                        }
                    }
                    
                    $data['enrolled_courses'] = $activeCourses;
                } else {
                    $data['enrolled_courses'] = [];
                }

                // Filter out approved AND pending enrollments from available courses
                // Rejected enrollments should still show "Enroll Now" button (can re-enroll)
                $pendingCourseIds = [];
                if ($hasStatusColumn && !empty($pendingEnrollments)) {
                    $pendingCourseIds = array_column($pendingEnrollments, 'course_id');
                }
                
                // Combine approved and pending course IDs to filter out from available courses
                $excludedCourseIds = array_merge($approvedCourseIds, $pendingCourseIds);
                
                if (!empty($excludedCourseIds)) {
                    $data['available_courses'] = array_filter($data['available_courses'], function($course) use ($excludedCourseIds) {
                        return !in_array($course['id'], $excludedCourseIds);
                    });
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error loading enrollment page: ' . $e->getMessage());
            $data['error'] = 'Error loading enrollment page. Please try again later.';
        }

        return view('student/enroll', $data);
    }

    /**
     * Student self-enrollment
     */
    public function selfEnroll()
    {
        if ($redirect = $this->ensureStudent()) {
            return $redirect;
        }

        // Check if request is POST using CodeIgniter's is() method (case-insensitive)
        if (!$this->request->is('post')) {
            log_message('error', 'SELF ENROLL - Method is not POST, redirecting... Method was: ' . $this->request->getMethod());
            return redirect()->to('/student/enroll');
        }

        log_message('info', 'SELF ENROLL - POST data: ' . json_encode($this->request->getPost()));

        $userId = (int) session()->get('id');
        $courseId = (int) $this->request->getPost('course_id');
        $schoolYearId = (int) $this->request->getPost('school_year_id');
        $semester = (int) $this->request->getPost('semester');
        $term = (int) $this->request->getPost('term');

        $courseModel = new CourseModel();
        $enrollmentModel = new EnrollmentModel();

        // Verify course exists for this academic period and has an assigned instructor
        $course = $courseModel
            ->where('id', $courseId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester', $semester)
            ->where('term', $term)
            ->where('instructor_id IS NOT NULL')
            ->first();

        if (!$course) {
            session()->setFlashdata('error', 'Course not available. This course does not have an assigned instructor yet.');
            return redirect()->to('/student/enroll');
        }

        // Check for time conflict: student cannot be enrolled in courses with same time, same semester, same term
        if (!empty($course['time'])) {
            // Get all approved enrollments for this student in the same semester and term with the same time
            $conflictingEnrollments = $enrollmentModel
                ->select('enrollments.*, courses.time, courses.title as course_title')
                ->join('courses', 'courses.id = enrollments.course_id')
                ->where('enrollments.user_id', $userId)
                ->where('enrollments.school_year_id', $schoolYearId)
                ->where('enrollments.semester', $semester)
                ->where('enrollments.term', $term)
                ->where('courses.time', $course['time'])
                ->where('courses.time IS NOT NULL')
                ->where('courses.time !=', '');
            
            // Only check approved enrollments
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                if (in_array('status', $columns)) {
                    $conflictingEnrollments->where('enrollments.status', 'approved');
                }
            } catch (\Exception $e) {
                // Status column doesn't exist, continue
            }
            
            $conflict = $conflictingEnrollments->first();
            
            if ($conflict) {
                session()->setFlashdata('error', 'Cannot enroll: You are already enrolled in "' . esc($conflict['course_title']) . '" at the same time (' . esc($course['time']) . ') for the same semester and term.');
                log_message('info', 'Time conflict detected on self-enroll: Student ' . $userId . ' already enrolled in course ' . $conflict['course_id'] . ' at time ' . $course['time']);
                return redirect()->to('/student/enroll');
            }
        }

        // Check if already enrolled (any status)
        // Rejected enrollments can be re-enrolled
        // Also check for expired pending enrollments
        $existing = $enrollmentModel
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester', $semester)
            ->where('term', $term)
            ->first();
        
        log_message('info', 'SELF ENROLL - Existing enrollment check: ' . json_encode($existing));

        if ($existing) {
            // Check if status column exists
            $hasStatusColumn = false;
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                $hasStatusColumn = in_array('status', $columns);
            } catch (\Exception $e) {
                // Status column doesn't exist
            }

            if ($hasStatusColumn && isset($existing['status'])) {
                // If status is rejected, allow re-enrollment (delete old record first)
                if ($existing['status'] === 'rejected') {
                    $enrollmentModel->delete($existing['id']);
                    // Continue with enrollment below
                } elseif ($existing['status'] === 'pending') {
                    // Check if the pending enrollment is for an expired course
                    $semesterModel = new SemesterModel();
                    $termModel = new TermModel();
                    $today = date('Y-m-d');
                    $isExpired = false;
                    
                    $semester = $semesterModel
                        ->where('school_year_id', $schoolYearId)
                        ->where('semester_number', $semester)
                        ->first();
                    
                    if ($semester) {
                        $term = $termModel
                            ->where('semester_id', $semester['id'])
                            ->where('term_number', $term)
                            ->first();
                        
                        // If term has expired, allow re-enrollment by deleting the old pending enrollment
                        if ($term && $term['end_date'] < $today) {
                            $isExpired = true;
                            $enrollmentModel->delete($existing['id']);
                            // Continue with enrollment below
                        }
                    }
                    
                    // If not expired, show error
                    if (!$isExpired) {
                        session()->setFlashdata('error', 'You already have a pending enrollment request for this course.');
                        return redirect()->to('/student/enroll');
                    }
                } elseif ($existing['status'] === 'approved') {
                    session()->setFlashdata('error', 'You are already enrolled in this course.');
                    return redirect()->to('/student/enroll');
                }
            } else {
                // If status column doesn't exist, treat as already enrolled
                session()->setFlashdata('error', 'You are already enrolled in this course.');
                return redirect()->to('/student/enroll');
            }
        }

        // Create enrollment with pending status (if status column exists)
        $enrollmentData = [
            'user_id' => $userId,
            'course_id' => $courseId,
            'school_year_id' => $schoolYearId,
            'semester' => $semester,
            'term' => $term,
            'enrollment_date' => date('Y-m-d H:i:s'),
        ];
        
        // Add status as 'pending' if column exists
        // IMPORTANT: If status column doesn't exist, we can't track approval status
        try {
            $db = \Config\Database::connect();
            $columns = $db->getFieldNames('enrollments');
            if (in_array('status', $columns)) {
                $enrollmentData['status'] = 'pending';
            } else {
                // Status column doesn't exist - show warning
                session()->setFlashdata('error', 'Enrollment approval system not set up. Please run database migration: php spark migrate');
                return redirect()->to('/student/enroll');
            }
        } catch (\Exception $e) {
            log_message('error', 'Error checking status column: ' . $e->getMessage());
            session()->setFlashdata('error', 'Database error. Please contact administrator.');
            return redirect()->to('/student/enroll');
        }

        if ($enrollmentModel->insert($enrollmentData)) {
            // Create notification for the student
            $notificationModel = new NotificationModel();
            $userModel = new UserModel();
            
            $message = 'You have enrolled in ' . esc($course['title']) . '. Waiting for instructor approval.';
            $notificationModel->createNotification($userId, $message);
            
            // Get student name for notifications
            $student = $userModel->find($userId);
            $studentName = $student ? $student['name'] : 'A student';
            
            // Create notification for the instructor
            if (!empty($course['instructor_id'])) {
                $instructorMessage = $studentName . ' has enrolled in ' . esc($course['title']) . ' and is waiting for your approval.';
                $notificationModel->createNotification($course['instructor_id'], $instructorMessage);
            }
            
            // Create notification for all admins
            $admins = $userModel->getAdmins();
            if (!empty($admins)) {
                $adminMessage = $studentName . ' has enrolled in ' . esc($course['title']) . ' and is waiting for instructor approval.';
                foreach ($admins as $admin) {
                    $notificationModel->createNotification($admin['id'], $adminMessage);
                }
            }
            
            session()->setFlashdata('success', 'Enrollment request submitted for ' . esc($course['title']) . '! Waiting for instructor approval.');
        } else {
            session()->setFlashdata('error', 'Failed to submit enrollment request. Please try again.');
        }

        return redirect()->to('/student/enroll');
    }

    /**
     * View all enrolled courses
     */
    public function courses()
    {
        if ($redirect = $this->ensureStudent()) {
            return $redirect;
        }

        $userId = (int) session()->get('id');
        $enrollmentModel = new EnrollmentModel();
        $courseModel = new CourseModel();
        $termModel = new TermModel();
        $semesterModel = new SemesterModel();
        $schoolYearModel = new SchoolYearModel();

        $data = [
            'courses' => [],
            'current_period' => null,
            'active_school_year' => null,
        ];

        try {
            // Get current academic period
            $currentPeriod = $termModel->getCurrentAcademicPeriod();
            $data['current_period'] = $currentPeriod;

            // Get active school year
            $activeSchoolYear = $schoolYearModel->getActiveSchoolYear();
            $data['active_school_year'] = $activeSchoolYear;

            // Check if status column exists
            $db = \Config\Database::connect();
            $columns = $db->getFieldNames('enrollments');
            $hasStatusColumn = in_array('status', $columns);

            // Get all approved enrollments (across all terms)
            $allEnrollments = [];
            if ($hasStatusColumn) {
                $allEnrollments = $enrollmentModel
                    ->select('enrollments.*, courses.title as course_title, courses.description, courses.control_number, courses.units, courses.school_year_id, courses.semester, courses.term, users.name as instructor_name')
                    ->join('courses', 'courses.id = enrollments.course_id')
                    ->join('users', 'users.id = courses.instructor_id', 'left')
                    ->where('enrollments.user_id', $userId)
                    ->where('enrollments.status', 'approved')
                    ->orderBy('enrollments.enrollment_date', 'DESC')
                    ->findAll();
            } else {
                // If no status column, get all enrollments
                $allEnrollments = $enrollmentModel
                    ->select('enrollments.*, courses.title as course_title, courses.description, courses.control_number, courses.units, courses.school_year_id, courses.semester, courses.term, users.name as instructor_name')
                    ->join('courses', 'courses.id = enrollments.course_id')
                    ->join('users', 'users.id = courses.instructor_id', 'left')
                    ->where('enrollments.user_id', $userId)
                    ->orderBy('enrollments.enrollment_date', 'DESC')
                    ->findAll();
            }

            // Add term end date and school year info for each enrollment
            $today = date('Y-m-d');
            $courses = [];
            
            foreach ($allEnrollments as $enrollment) {
                $course = [
                    'id' => $enrollment['course_id'],
                    'title' => $enrollment['course_title'],
                    'description' => $enrollment['description'],
                    'control_number' => $enrollment['control_number'],
                    'units' => $enrollment['units'],
                    'instructor_name' => $enrollment['instructor_name'],
                    'enrollment_date' => $enrollment['enrollment_date'],
                    'school_year_id' => $enrollment['school_year_id'],
                    'semester' => $enrollment['semester'],
                    'term' => $enrollment['term'],
                ];

                // Get school year
                $sy = $schoolYearModel->find($enrollment['school_year_id']);
                $course['school_year'] = $sy ? $sy['school_year'] : 'N/A';

                // Get term end date
                if ($enrollment['school_year_id'] && $enrollment['semester'] && $enrollment['term']) {
                    $semester = $semesterModel
                        ->where('school_year_id', $enrollment['school_year_id'])
                        ->where('semester_number', $enrollment['semester'])
                        ->first();
                    
                    if ($semester) {
                        $term = $termModel
                            ->where('semester_id', $semester['id'])
                            ->where('term_number', $enrollment['term'])
                            ->first();
                        
                        if ($term) {
                            $course['term_end_date'] = $term['end_date'];
                            $course['is_expired'] = ($term['end_date'] < $today);
                        }
                    }
                }

                $courses[] = $course;
            }

            $data['courses'] = $courses;
        } catch (\Exception $e) {
            log_message('error', 'Error loading courses: ' . $e->getMessage());
            $data['error'] = 'Error loading courses. Please try again later.';
        }

        return view('student/courses', $data);
    }

    /**
     * View a specific course
     */
    public function viewCourse($courseId = null)
    {
        if ($redirect = $this->ensureStudent()) {
            return $redirect;
        }

        if (!$courseId) {
            session()->setFlashdata('error', 'Course ID is required.');
            return redirect()->to('/dashboard');
        }

        $userId = (int) session()->get('id');
        $courseModel = new CourseModel();
        $enrollmentModel = new EnrollmentModel();

        try {
            $course = $courseModel->find($courseId);

            if (!$course) {
                session()->setFlashdata('error', 'Course not found.');
                return redirect()->to('/dashboard');
            }

            // Check if the student is enrolled in this course (for preview mode, allow viewing even if not enrolled)
            $enrollment = $enrollmentModel
                ->where('user_id', $userId)
                ->where('course_id', $courseId);
            
            // Check academic structure if course has it
            if (!empty($course['school_year_id']) && !empty($course['semester']) && !empty($course['term'])) {
                $enrollment->where('school_year_id', $course['school_year_id'])
                    ->where('semester', $course['semester'])
                    ->where('term', $course['term']);
            }
            
            // Check status if column exists
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                if (in_array('status', $columns)) {
                    $enrollment->where('status', 'approved');
                }
            } catch (\Exception $e) {
                // Status column might not exist
            }
            
            $enrollment = $enrollment->first();
            $isEnrolled = $enrollment !== null;

            // Get instructor name
            $userModel = new \App\Models\UserModel();
            $instructor = $userModel->find($course['instructor_id']);

            // Get school year
            $schoolYearModel = new SchoolYearModel();
            $schoolYear = $schoolYearModel->find($course['school_year_id']);

            // Get term start and end dates from Academic Structure
            $termModel = new TermModel();
            $semesterModel = new SemesterModel();
            $termStartDate = null;
            $termEndDate = null;
            
            if (!empty($course['school_year_id']) && !empty($course['semester']) && !empty($course['term'])) {
                $semester = $semesterModel
                    ->where('school_year_id', $course['school_year_id'])
                    ->where('semester_number', $course['semester'])
                    ->first();
                
                if ($semester) {
                    $term = $termModel
                        ->where('semester_id', $semester['id'])
                        ->where('term_number', $course['term'])
                        ->first();
                    
                    if ($term) {
                        $termStartDate = $term['start_date'] ?? null;
                        $termEndDate = $term['end_date'] ?? null;
                    }
                }
            }

            // Get course materials (only if enrolled)
            $materialModel = new \App\Models\MaterialModel();
            $materials = [];
            if ($isEnrolled) {
                $materials = $materialModel->getMaterialsByCourse($courseId);
            }

            $data = [
                'course' => $course,
                'enrollment' => $enrollment,
                'instructor' => $instructor,
                'school_year' => $schoolYear,
                'term_start_date' => $termStartDate,
                'term_end_date' => $termEndDate,
                'materials' => $materials,
                'is_enrolled' => $isEnrolled,
            ];

            return view('student/view_course', $data);
        } catch (\Exception $e) {
            log_message('error', 'Error loading course: ' . $e->getMessage());
            session()->setFlashdata('error', 'Error loading course.');
            return redirect()->to('/dashboard');
        }
    }
}

