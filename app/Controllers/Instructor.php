<?php

namespace App\Controllers;

use App\Models\CourseModel;
use App\Models\TermModel;
use App\Models\SchoolYearModel;
use App\Models\SemesterModel;
use App\Models\TeacherAssignmentModel;
use App\Models\EnrollmentModel;
use App\Models\UserModel;
use App\Models\NotificationModel;

class Instructor extends BaseController
{
    /**
     * Ensure user is logged in and is an instructor
     */
    protected function ensureInstructor()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $role = strtolower((string) session()->get('role'));
        // Allow both instructor and admin access
        if ($role !== 'instructor' && $role !== 'admin') {
            session()->setFlashdata('error', 'Access denied. Instructor or Admin access required.');
            return redirect()->to('/dashboard');
        }

        return null;
    }

    /**
     * View and manage assigned courses
     */
    public function myCourses()
    {
        if ($redirect = $this->ensureInstructor()) {
            return $redirect;
        }

        $userId = (int) session()->get('id');
        $courseModel = new CourseModel();
        $termModel = new TermModel();

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
            $schoolYearModel = new SchoolYearModel();
            $activeSchoolYear = $schoolYearModel->getActiveSchoolYear();
            $data['active_school_year'] = $activeSchoolYear;

            $teacherAssignmentModel = new TeacherAssignmentModel();
            
            // Get all courses where instructor is assigned via instructor_id
            $instructorCourses = $courseModel
                ->where('instructor_id', $userId)
                ->findAll();
            
            // Get ALL courses from teacher_assignments table (not just active term)
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
            
            if (!empty($allCourseIds)) {
                $courses = $courseModel->whereIn('id', $allCourseIds)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
                $data['courses'] = $courses;
            } else {
                $data['courses'] = [];
            }
        } catch (\Exception $e) {
            log_message('error', 'Error loading instructor courses: ' . $e->getMessage());
            $data['error'] = 'Error loading courses. Please try again later.';
        }

        return view('instructor/my_courses', $data);
    }

    /**
     * View all completed courses
     */
    public function completedCourses()
    {
        if ($redirect = $this->ensureInstructor()) {
            return $redirect;
        }

        $userId = (int) session()->get('id');
        $courseModel = new CourseModel();
        $termModel = new TermModel();
        $semesterModel = new SemesterModel();
        $schoolYearModel = new SchoolYearModel();
        $teacherAssignmentModel = new TeacherAssignmentModel();

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

            $today = date('Y-m-d');
            $completedCourses = [];
            $processedCourseIds = []; // Track processed course IDs to avoid duplicates

            // Get all courses assigned to instructor via instructor_id (ALL school years)
            $allInstructorCourses = $courseModel
                ->where('instructor_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            // Process courses from instructor_id
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
                        // Course is completed - add term info
                        $course['term_end_date'] = $term['end_date'];
                        $course['term_start_date'] = $term['start_date'];
                        $sy = $schoolYearModel->find($course['school_year_id']);
                        $course['school_year'] = $sy ? $sy['school_year'] : null;
                        $completedCourses[] = $course;
                        $processedCourseIds[] = $course['id'];
                    }
                }
            }

            // Also get ALL courses from teacher_assignments (ALL school years)
            $allAssignments = $teacherAssignmentModel
                ->select('teacher_assignments.course_id, teacher_assignments.school_year_id, teacher_assignments.semester, teacher_assignments.term')
                ->where('teacher_assignments.teacher_id', $userId)
                ->findAll();

            foreach ($allAssignments as $assignment) {
                $courseId = $assignment['course_id'] ?? null;
                if (!$courseId || in_array($courseId, $processedCourseIds)) {
                    continue; // Skip if already processed
                }
                
                $course = $courseModel->find($courseId);
                if (!$course) {
                    continue;
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
                        // Course is completed - add term info
                        $course['term_end_date'] = $term['end_date'];
                        $course['term_start_date'] = $term['start_date'];
                        $sy = $schoolYearModel->find($course['school_year_id']);
                        $course['school_year'] = $sy ? $sy['school_year'] : null;
                        $completedCourses[] = $course;
                        $processedCourseIds[] = $course['id'];
                    }
                }
            }

            // Sort completed courses: first by school year (descending), then by end date (most recent first)
            usort($completedCourses, function($a, $b) {
                // First sort by school year
                $syA = $a['school_year'] ?? '';
                $syB = $b['school_year'] ?? '';
                if ($syA !== $syB) {
                    return strcmp($syB, $syA); // Descending
                }
                // Then by end date
                $dateA = isset($a['term_end_date']) ? strtotime($a['term_end_date']) : 0;
                $dateB = isset($b['term_end_date']) ? strtotime($b['term_end_date']) : 0;
                return $dateB - $dateA; // Most recent first
            });

            $data['courses'] = $completedCourses;
        } catch (\Exception $e) {
            log_message('error', 'Error loading completed courses: ' . $e->getMessage());
            $data['error'] = 'Error loading completed courses. Please try again later.';
        }

        return view('instructor/completed_courses', $data);
    }

    /**
     * View a specific course
     */
    public function viewCourse($courseId = null)
    {
        if ($redirect = $this->ensureInstructor()) {
            return $redirect;
        }

        if (!$courseId) {
            session()->setFlashdata('error', 'Course ID is required.');
            return redirect()->to('/instructor/my-courses');
        }

        $userId = (int) session()->get('id');
        $courseModel = new CourseModel();
        $termModel = new TermModel();

        try {
            $course = $courseModel->find($courseId);

            if (!$course) {
                session()->setFlashdata('error', 'Course not found.');
                return redirect()->to('/instructor/my-courses');
            }

            // Verify that the instructor owns this course (check both instructor_id and teacher_assignments)
            $hasAccess = false;
            if ($course['instructor_id'] == $userId) {
                $hasAccess = true;
            } else {
                // Check if assigned via teacher_assignments
                $teacherAssignmentModel = new TeacherAssignmentModel();
                $assignment = $teacherAssignmentModel
                    ->where('teacher_id', $userId)
                    ->where('course_id', $courseId)
                    ->first();
                if ($assignment) {
                    $hasAccess = true;
                }
            }
            
            if (!$hasAccess) {
                session()->setFlashdata('error', 'You do not have access to this course.');
                return redirect()->to('/instructor/my-courses');
            }

            // Get enrolled students for this course (approved only)
            $enrollmentModel = new EnrollmentModel();
            $enrollmentsBuilder = $enrollmentModel->select('enrollments.*, users.name as student_name, users.email as student_email')
                ->join('users', 'users.id = enrollments.user_id')
                ->where('enrollments.course_id', $courseId)
                ->where('enrollments.school_year_id', $course['school_year_id'])
                ->where('enrollments.semester', $course['semester'])
                ->where('enrollments.term', $course['term']);
            
            // Only filter by status if the column exists
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                if (in_array('status', $columns)) {
                    $enrollmentsBuilder->where('enrollments.status', 'approved');
                }
            } catch (\Exception $e) {
                // Table might not exist or column check failed, continue without status filter
            }
            
            $enrollments = $enrollmentsBuilder->orderBy('enrollments.enrollment_date', 'DESC')
                ->findAll();
            
            // Get term start and end dates from Academic Structure (admin's terms table)
            // Based on the course's School Year, Semester, and Term
            // Example: For Semester 1 Term 1, get dates from terms table where semester_id matches and term_number = 1
            $termStartDate = null;
            $termEndDate = null;
            if (!empty($course['school_year_id']) && !empty($course['semester']) && !empty($course['term'])) {
                $semesterModel = new SemesterModel();
                // Find the semester record for this school year and semester number
                $semester = $semesterModel
                    ->where('school_year_id', $course['school_year_id'])
                    ->where('semester_number', $course['semester'])
                    ->first();
                
                if ($semester) {
                    // Find the term record for this semester and term number
                    // This gets the start_date and end_date from the admin's Academic Structure Management
                    $term = $termModel
                        ->where('semester_id', $semester['id'])
                        ->where('term_number', $course['term'])
                        ->first();
                    
                    if ($term) {
                        // Get the dates from the terms table (set by admin in Academic Structure Management)
                        $termStartDate = $term['start_date'] ?? null;  // e.g., 2025-12-08
                        $termEndDate = $term['end_date'] ?? null;      // e.g., 2026-02-01
                    }
                }
            }

            // Get pending enrollment requests
            $pendingBuilder = $enrollmentModel->select('enrollments.*, users.name as student_name, users.email as student_email')
                ->join('users', 'users.id = enrollments.user_id')
                ->where('enrollments.course_id', $courseId)
                ->where('enrollments.school_year_id', $course['school_year_id'])
                ->where('enrollments.semester', $course['semester'])
                ->where('enrollments.term', $course['term']);
            
            // Only filter by status if the column exists
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                if (in_array('status', $columns)) {
                    $pendingBuilder->where('enrollments.status', 'pending');
                }
            } catch (\Exception $e) {
                // Table might not exist or column check failed, continue without status filter
            }
            
            $pendingEnrollments = $pendingBuilder->orderBy('enrollments.enrollment_date', 'DESC')
                ->findAll();

            // Get all students for enrollment dropdown
            $userModel = new UserModel();
            $students = $userModel->where('role', 'student')->orderBy('name', 'ASC')->findAll();

            // Get course materials
            $materialModel = new \App\Models\MaterialModel();
            $materials = $materialModel->getMaterialsByCourse($courseId);

            $data = [
                'course' => $course,
                'enrollments' => $enrollments,
                'pending_enrollments' => $pendingEnrollments,
                'students' => $students,
                'term_start_date' => $termStartDate,
                'term_end_date' => $termEndDate,
                'materials' => $materials,
            ];

            return view('instructor/view_course', $data);
        } catch (\Exception $e) {
            log_message('error', 'Error loading course: ' . $e->getMessage());
            session()->setFlashdata('error', 'Error loading course.');
            return redirect()->to('/instructor/my-courses');
        }
    }

    /**
     * Enroll a student in a course
     */
    public function enrollStudent()
    {
        if ($redirect = $this->ensureInstructor()) {
            return $redirect;
        }

        if (!$this->request->is('post')) {
            return redirect()->to('/instructor/my-courses');
        }

        $userId = (int) session()->get('id');
        $courseId = $this->request->getPost('course_id');
        $studentId = $this->request->getPost('student_id');

        if (!$courseId || !$studentId) {
            session()->setFlashdata('error', 'Course ID and Student ID are required.');
            return redirect()->back();
        }

        $courseModel = new CourseModel();
        $course = $courseModel->find($courseId);

        if (!$course) {
            session()->setFlashdata('error', 'Course not found.');
            return redirect()->back();
        }

        // Verify that the instructor owns this course
        if ($course['instructor_id'] != $userId) {
            session()->setFlashdata('error', 'You do not have access to this course.');
            return redirect()->back();
        }

        $enrollmentModel = new EnrollmentModel();

        // Check if student is already enrolled (any status)
        $existing = $enrollmentModel
            ->where('user_id', $studentId)
            ->where('course_id', $courseId)
            ->where('school_year_id', $course['school_year_id'])
            ->where('semester', $course['semester'])
            ->where('term', $course['term'])
            ->first();
        
        if ($existing) {
            // If already enrolled with pending status, approve it instead of creating duplicate
            if (isset($existing['status']) && $existing['status'] === 'pending') {
                try {
                    if ($enrollmentModel->update($existing['id'], ['status' => 'approved'])) {
                        // Create notification for the student
                        $notificationModel = new NotificationModel();
                        $message = 'Your enrollment request for ' . esc($course['title']) . ' has been approved!';
                        $notificationModel->createNotification($studentId, $message);
                        
                        session()->setFlashdata('success', 'Student enrollment request approved!');
                        return redirect()->to('/instructor/course/' . $courseId);
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Error approving existing enrollment: ' . $e->getMessage());
                }
            }
            // If already enrolled with approved status, show error
            session()->setFlashdata('error', 'Student is already enrolled in this course.');
            return redirect()->back();
        }

        // Check for time conflict: student cannot be enrolled in courses with same time, same semester, same term
        if (!empty($course['time'])) {
            // Get all approved enrollments for this student in the same semester and term
            $conflictingEnrollments = $enrollmentModel
                ->select('enrollments.*, courses.time, courses.title as course_title')
                ->join('courses', 'courses.id = enrollments.course_id')
                ->where('enrollments.user_id', $studentId)
                ->where('enrollments.school_year_id', $course['school_year_id'])
                ->where('enrollments.semester', $course['semester'])
                ->where('enrollments.term', $course['term'])
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
                session()->setFlashdata('error', 'Cannot enroll student: The student is already enrolled in "' . esc($conflict['course_title']) . '" at the same time (' . esc($course['time']) . ') for the same semester and term.');
                log_message('info', 'Time conflict detected: Student ' . $studentId . ' already enrolled in course ' . $conflict['course_id'] . ' at time ' . $course['time']);
                return redirect()->back();
            }
        }

        // Create enrollment (direct enrollment by teacher is automatically approved)
        $enrollmentData = [
            'user_id' => $studentId,
            'course_id' => $courseId,
            'school_year_id' => $course['school_year_id'],
            'semester' => $course['semester'],
            'term' => $course['term'],
            'enrollment_date' => date('Y-m-d H:i:s'),
        ];
        
        // Add status as 'approved' if column exists (direct enrollment by teacher is automatically approved)
        try {
            $db = \Config\Database::connect();
            $columns = $db->getFieldNames('enrollments');
            if (in_array('status', $columns)) {
                $enrollmentData['status'] = 'approved';
            }
        } catch (\Exception $e) {
            // Status column doesn't exist yet, continue without it
        }

        try {
            if ($enrollmentModel->insert($enrollmentData)) {
                // Create notification for the student
                $notificationModel = new NotificationModel();
                $message = 'You have been enrolled in ' . esc($course['title']);
                $notificationModel->createNotification($studentId, $message);
                
                // Create notification for all admins
                $userModel = new UserModel();
                $admins = $userModel->getAdmins();
                if (!empty($admins)) {
                    $student = $userModel->find($studentId);
                    $studentName = $student ? $student['name'] : 'A student';
                    $instructor = $userModel->find($userId);
                    $instructorName = $instructor ? $instructor['name'] : 'An instructor';
                    
                    $adminMessage = $instructorName . ' has enrolled ' . $studentName . ' in ' . esc($course['title']);
                    foreach ($admins as $admin) {
                        $notificationModel->createNotification($admin['id'], $adminMessage);
                    }
                }
                
                session()->setFlashdata('success', 'Student enrolled successfully!');
            } else {
                $errors = $enrollmentModel->errors();
                session()->setFlashdata('error', 'Failed to enroll student: ' . implode(', ', $errors));
            }
        } catch (\Exception $e) {
            log_message('error', 'Error enrolling student: ' . $e->getMessage());
            session()->setFlashdata('error', 'Failed to enroll student: ' . $e->getMessage());
        }

        return redirect()->to('/instructor/course/' . $courseId);
    }

    /**
     * Unenroll a student from a course
     */
    public function unenrollStudent()
    {
        if ($redirect = $this->ensureInstructor()) {
            return $redirect;
        }

        if (!$this->request->is('post')) {
            return redirect()->to('/instructor/my-courses');
        }

        $userId = (int) session()->get('id');
        $enrollmentId = $this->request->getPost('enrollment_id');
        $courseId = $this->request->getPost('course_id');

        if (!$enrollmentId || !$courseId) {
            session()->setFlashdata('error', 'Enrollment ID and Course ID are required.');
            return redirect()->back();
        }

        $courseModel = new CourseModel();
        $course = $courseModel->find($courseId);

        if (!$course) {
            session()->setFlashdata('error', 'Course not found.');
            return redirect()->back();
        }

        // Verify that the instructor owns this course
        if ($course['instructor_id'] != $userId) {
            session()->setFlashdata('error', 'You do not have access to this course.');
            return redirect()->back();
        }

        $enrollmentModel = new EnrollmentModel();
        $enrollment = $enrollmentModel->find($enrollmentId);

        if (!$enrollment) {
            session()->setFlashdata('error', 'Enrollment not found.');
            return redirect()->back();
        }

        // Verify enrollment belongs to the course
        if ($enrollment['course_id'] != $courseId) {
            session()->setFlashdata('error', 'Invalid enrollment.');
            return redirect()->back();
        }

        try {
            if ($enrollmentModel->delete($enrollmentId)) {
                session()->setFlashdata('success', 'Student enrollment marked as deleted successfully!');
            } else {
                session()->setFlashdata('error', 'Failed to mark enrollment as deleted.');
            }
        } catch (\Exception $e) {
            log_message('error', 'Error unenrolling student: ' . $e->getMessage());
            session()->setFlashdata('error', 'Failed to unenroll student: ' . $e->getMessage());
        }

        return redirect()->to('/instructor/course/' . $courseId);
    }

    /**
     * Approve enrollment request
     */
    public function approveEnrollment()
    {
        if ($redirect = $this->ensureInstructor()) {
            return $redirect;
        }

        // Check if request is POST using CodeIgniter's is() method (case-insensitive)
        if (!$this->request->is('post')) {
            log_message('error', 'APPROVE ENROLLMENT - Method is not POST, redirecting... Method was: ' . $this->request->getMethod());
            return redirect()->to('/instructor/my-courses');
        }

        log_message('info', 'APPROVE ENROLLMENT - POST data: ' . json_encode($this->request->getPost()));

        $userId = (int) session()->get('id');
        $enrollmentId = $this->request->getPost('enrollment_id');
        $courseId = $this->request->getPost('course_id');

        if (!$enrollmentId || !$courseId) {
            session()->setFlashdata('error', 'Enrollment ID and Course ID are required.');
            return redirect()->back();
        }

        $courseModel = new CourseModel();
        $course = $courseModel->find($courseId);

        if (!$course) {
            session()->setFlashdata('error', 'Course not found.');
            return redirect()->back();
        }

        // Verify that the instructor owns this course
        if ($course['instructor_id'] != $userId) {
            session()->setFlashdata('error', 'You do not have access to this course.');
            return redirect()->back();
        }

        $enrollmentModel = new EnrollmentModel();
        $enrollment = $enrollmentModel->find($enrollmentId);

        if (!$enrollment) {
            session()->setFlashdata('error', 'Enrollment not found.');
            return redirect()->back();
        }

        // Verify enrollment belongs to the course
        if ($enrollment['course_id'] != $courseId) {
            session()->setFlashdata('error', 'Invalid enrollment.');
            return redirect()->back();
        }

        // Check for time conflict: student cannot be enrolled in courses with same time, same semester, same term
        if (!empty($course['time'])) {
            $studentId = $enrollment['user_id'];
            
            // Get all approved enrollments for this student in the same semester and term with the same time
            $conflictingEnrollments = $enrollmentModel
                ->select('enrollments.*, courses.time, courses.title as course_title')
                ->join('courses', 'courses.id = enrollments.course_id')
                ->where('enrollments.user_id', $studentId)
                ->where('enrollments.school_year_id', $course['school_year_id'])
                ->where('enrollments.semester', $course['semester'])
                ->where('enrollments.term', $course['term'])
                ->where('courses.time', $course['time'])
                ->where('courses.time IS NOT NULL')
                ->where('courses.time !=', '')
                ->where('enrollments.id !=', $enrollmentId); // Exclude current enrollment
            
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
                session()->setFlashdata('error', 'Cannot approve enrollment: The student is already enrolled in "' . esc($conflict['course_title']) . '" at the same time (' . esc($course['time']) . ') for the same semester and term.');
                log_message('info', 'Time conflict detected on approval: Student ' . $studentId . ' already enrolled in course ' . $conflict['course_id'] . ' at time ' . $course['time']);
                return redirect()->back();
            }
        }

        try {
            // Update status to approved if column exists
            $updateData = [];
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                if (in_array('status', $columns)) {
                    $updateData['status'] = 'approved';
                }
            } catch (\Exception $e) {
                // Status column doesn't exist yet
            }

            if (!empty($updateData)) {
                if ($enrollmentModel->update($enrollmentId, $updateData)) {
                    // Create notification for the student
                    $notificationModel = new NotificationModel();
                    $studentId = $enrollment['user_id'];
                    $message = 'Your enrollment request for ' . esc($course['title']) . ' has been approved!';
                    $notificationModel->createNotification($studentId, $message);
                    
                    session()->setFlashdata('success', 'Enrollment approved successfully!');
                } else {
                    $errors = $enrollmentModel->errors();
                    session()->setFlashdata('error', 'Failed to approve enrollment: ' . implode(', ', $errors));
                }
            } else {
                // If status column doesn't exist, enrollment is already considered approved
                // Still create notification
                $notificationModel = new NotificationModel();
                $studentId = $enrollment['user_id'];
                $message = 'Your enrollment request for ' . esc($course['title']) . ' has been approved!';
                $notificationModel->createNotification($studentId, $message);
                
                session()->setFlashdata('success', 'Enrollment approved successfully!');
            }
        } catch (\Exception $e) {
            log_message('error', 'Error approving enrollment: ' . $e->getMessage());
            session()->setFlashdata('error', 'Failed to approve enrollment: ' . $e->getMessage());
        }

        return redirect()->to('/instructor/course/' . $courseId);
    }

    /**
     * Reject enrollment request
     */
    public function rejectEnrollment()
    {
        if ($redirect = $this->ensureInstructor()) {
            return $redirect;
        }

        // Check if request is POST using CodeIgniter's is() method (case-insensitive)
        if (!$this->request->is('post')) {
            log_message('error', 'REJECT ENROLLMENT - Method is not POST, redirecting... Method was: ' . $this->request->getMethod());
            return redirect()->to('/instructor/my-courses');
        }

        log_message('info', 'REJECT ENROLLMENT - POST data: ' . json_encode($this->request->getPost()));

        $userId = (int) session()->get('id');
        $enrollmentId = $this->request->getPost('enrollment_id');
        $courseId = $this->request->getPost('course_id');
        $rejectionReason = $this->request->getPost('rejection_reason');

        if (!$enrollmentId || !$courseId) {
            session()->setFlashdata('error', 'Enrollment ID and Course ID are required.');
            return redirect()->back();
        }

        if (empty($rejectionReason) || trim($rejectionReason) === '') {
            session()->setFlashdata('error', 'Rejection reason is required.');
            return redirect()->back();
        }

        $courseModel = new CourseModel();
        $course = $courseModel->find($courseId);

        if (!$course) {
            session()->setFlashdata('error', 'Course not found.');
            return redirect()->back();
        }

        // Verify that the instructor owns this course
        if ($course['instructor_id'] != $userId) {
            session()->setFlashdata('error', 'You do not have access to this course.');
            return redirect()->back();
        }

        $enrollmentModel = new EnrollmentModel();
        $enrollment = $enrollmentModel->find($enrollmentId);

        if (!$enrollment) {
            session()->setFlashdata('error', 'Enrollment not found.');
            return redirect()->back();
        }

        // Verify enrollment belongs to the course
        if ($enrollment['course_id'] != $courseId) {
            session()->setFlashdata('error', 'Invalid enrollment.');
            return redirect()->back();
        }

        try {
            // Update status to rejected if column exists, otherwise delete the enrollment
            $updateData = [];
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                if (in_array('status', $columns)) {
                    $updateData['status'] = 'rejected';
                    
                    // Add rejection reason if column exists
                    if (in_array('rejection_reason', $columns)) {
                        $updateData['rejection_reason'] = trim($rejectionReason);
                    }
                    
                    if ($enrollmentModel->update($enrollmentId, $updateData)) {
                        session()->setFlashdata('success', 'Enrollment rejected successfully.');
                    } else {
                        $errors = $enrollmentModel->errors();
                        session()->setFlashdata('error', 'Failed to reject enrollment: ' . implode(', ', $errors));
                    }
                } else {
                    // If status column doesn't exist, delete the enrollment
                    if ($enrollmentModel->delete($enrollmentId)) {
                        session()->setFlashdata('success', 'Enrollment rejected.');
                    } else {
                        session()->setFlashdata('error', 'Failed to reject enrollment.');
                    }
                }
            } catch (\Exception $e) {
                // Status column doesn't exist, delete the enrollment
                if ($enrollmentModel->delete($enrollmentId)) {
                    session()->setFlashdata('success', 'Enrollment rejected.');
                } else {
                    session()->setFlashdata('error', 'Failed to reject enrollment.');
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error rejecting enrollment: ' . $e->getMessage());
            session()->setFlashdata('error', 'Failed to reject enrollment: ' . $e->getMessage());
        }

        return redirect()->to('/instructor/course/' . $courseId);
    }
}

