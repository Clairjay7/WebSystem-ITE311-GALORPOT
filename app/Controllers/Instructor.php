<?php

namespace App\Controllers;

use App\Models\CourseModel;
use App\Models\TermModel;
use App\Models\SchoolYearModel;
use App\Models\EnrollmentModel;
use App\Models\UserModel;

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
        if ($role !== 'instructor') {
            session()->setFlashdata('error', 'Access denied. Instructor access required.');
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

            if ($currentPeriod) {
                // Get courses where instructor is assigned for the active term
                $courses = $courseModel
                    ->where('instructor_id', $userId)
                    ->where('school_year_id', $currentPeriod['school_year']['id'])
                    ->where('semester', $currentPeriod['semester']['semester_number'])
                    ->where('term', $currentPeriod['term']['term_number'])
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
                
                $data['courses'] = $courses;
            } else {
                // No active term, show all courses assigned to instructor
                $courses = $courseModel
                    ->where('instructor_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
                
                $data['courses'] = $courses;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error loading instructor courses: ' . $e->getMessage());
            $data['error'] = 'Error loading courses. Please try again later.';
        }

        return view('instructor/my_courses', $data);
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

        try {
            $course = $courseModel->find($courseId);

            if (!$course) {
                session()->setFlashdata('error', 'Course not found.');
                return redirect()->to('/instructor/my-courses');
            }

            // Verify that the instructor owns this course
            if ($course['instructor_id'] != $userId) {
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

            $data = [
                'course' => $course,
                'enrollments' => $enrollments,
                'pending_enrollments' => $pendingEnrollments,
                'students' => $students,
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
                session()->setFlashdata('success', 'Student unenrolled successfully!');
            } else {
                session()->setFlashdata('error', 'Failed to unenroll student.');
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
                    session()->setFlashdata('success', 'Enrollment approved successfully!');
                } else {
                    $errors = $enrollmentModel->errors();
                    session()->setFlashdata('error', 'Failed to approve enrollment: ' . implode(', ', $errors));
                }
            } else {
                // If status column doesn't exist, enrollment is already considered approved
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

