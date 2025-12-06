<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CourseModel;
use App\Models\EnrollmentModel;
use App\Models\SchoolYearModel;
use App\Models\SemesterModel;
use App\Models\TermModel;
use App\Models\TeacherAssignmentModel;

class Admin extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    private function ensureAdmin()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        $role = strtolower((string) session('role'));
        if ($role !== 'admin') {
            session()->setFlashdata('error', 'Unauthorized. Admin access only.');
            return redirect()->to('/dashboard');
        }
        return null;
    }

    public function dashboard()
    {
        // Redirect to unified dashboard
        return redirect()->to('/dashboard');
    }

    // TEST METHOD - Direct insert without form (NO AUTH CHECK FOR TESTING)
    public function testCreateUser()
    {
        echo "<h2>Testing User Creation...</h2>";
        echo "Session Data: <pre>";
        print_r([
            'isLoggedIn' => session()->get('isLoggedIn'),
            'role' => session()->get('role'),
            'name' => session()->get('name')
        ]);
        echo "</pre><hr>";

        $testData = [
            'name' => 'Test User ' . time(),
            'email' => 'test' . time() . '@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'student',
            'first_name' => 'Test',
            'last_name' => 'User'
        ];

        echo "Attempting to insert user...<br>";
        
        try {
            $result = $this->userModel->insert($testData);
            
            if ($result) {
                echo "<h3 style='color:green'>SUCCESS! User created with ID: " . $result . "</h3>";
                echo "Name: " . $testData['name'] . "<br>";
                echo "Email: " . $testData['email'] . "<br>";
                echo "<br><a href='/ITE311-GALORPOT/dashboard'>Go to Dashboard</a>";
            } else {
                echo "<h3 style='color:red'>FAILED!</h3>";
                echo "Errors: <pre>";
                print_r($this->userModel->errors());
                echo "</pre>";
            }
        } catch (\Exception $e) {
            echo "<h3 style='color:red'>ERROR:</h3>";
            echo $e->getMessage();
            echo "<br><pre>" . $e->getTraceAsString() . "</pre>";
        }
    }

    // User Management - Redirect to unified dashboard
    public function users()
    {
        return redirect()->to('/dashboard');
    }

    public function createUser()
    {
        // WRITE TO FILE FOR ABSOLUTE PROOF
        $method = $this->request->getMethod();
        file_put_contents(WRITEPATH . 'createuser_debug.txt', date('Y-m-d H:i:s') . " - CREATE USER CALLED\n", FILE_APPEND);
        file_put_contents(WRITEPATH . 'createuser_debug.txt', "Method: " . $method . " (type: " . gettype($method) . ")\n", FILE_APPEND);
        file_put_contents(WRITEPATH . 'createuser_debug.txt', "Method === 'post': " . ($method === 'post' ? 'YES' : 'NO') . "\n", FILE_APPEND);
        file_put_contents(WRITEPATH . 'createuser_debug.txt', "Method !== 'post': " . ($method !== 'post' ? 'YES' : 'NO') . "\n", FILE_APPEND);
        file_put_contents(WRITEPATH . 'createuser_debug.txt', "POST: " . json_encode($this->request->getPost()) . "\n\n", FILE_APPEND);
        
        // IMMEDIATE DEBUG OUTPUT
        echo "<!-- CREATE USER METHOD REACHED -->";
        
        // Log everything for debugging
        log_message('info', '=== CREATE USER STARTED ===');
        log_message('info', 'Method: ' . $this->request->getMethod());
        log_message('info', 'POST Data: ' . json_encode($this->request->getPost()));
        log_message('info', 'Session: ' . json_encode([
            'isLoggedIn' => session()->get('isLoggedIn'),
            'role' => session()->get('role')
        ]));
        
        if ($redirect = $this->ensureAdmin()) {
            log_message('info', 'Not admin, redirecting');
            file_put_contents(WRITEPATH . 'createuser_debug.txt', "NOT ADMIN - REDIRECTING\n\n", FILE_APPEND);
            return $redirect;
        }
        
        file_put_contents(WRITEPATH . 'createuser_debug.txt', "PASSED ADMIN CHECK\n", FILE_APPEND);

        // Only allow POST requests
        $method = $this->request->getMethod();
        file_put_contents(WRITEPATH . 'createuser_debug.txt', "Checking method: " . $method . " (lowercase: " . strtolower($method) . ")\n", FILE_APPEND);
        
        if (strtolower($method) !== 'post') {
            log_message('info', 'Not POST method, redirecting');
            file_put_contents(WRITEPATH . 'createuser_debug.txt', "NOT POST - REDIRECTING (method was: " . $method . ")\n\n", FILE_APPEND);
            return redirect()->to('/dashboard');
        }
        
        file_put_contents(WRITEPATH . 'createuser_debug.txt', "PASSED POST CHECK\n", FILE_APPEND);

        $rules = [
            'name' => 'required|min_length[3]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'role' => 'required|in_list[student,instructor,admin]'
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            log_message('error', 'Validation failed: ' . json_encode($errors));
            file_put_contents(WRITEPATH . 'createuser_debug.txt', "VALIDATION FAILED: " . json_encode($errors) . "\n\n", FILE_APPEND);
            session()->setFlashdata('error', 'Validation failed: ' . implode(', ', $errors));
            return redirect()->to('/dashboard');
        }
        
        file_put_contents(WRITEPATH . 'createuser_debug.txt', "PASSED VALIDATION\n", FILE_APPEND);

        $data = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => $this->request->getPost('role'),
            'first_name' => $this->request->getPost('name'),
            'last_name' => $this->request->getPost('name')
        ];

        log_message('info', 'Data to insert: ' . json_encode([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role']
        ]));

        try {
            file_put_contents(WRITEPATH . 'createuser_debug.txt', "ATTEMPTING INSERT...\n", FILE_APPEND);
            $result = $this->userModel->insert($data);
            
            if ($result) {
                log_message('info', 'User created successfully with ID: ' . $result);
                file_put_contents(WRITEPATH . 'createuser_debug.txt', "SUCCESS! User ID: " . $result . "\n\n", FILE_APPEND);
                session()->setFlashdata('success', 'User created successfully! User ID: ' . $result);
            } else {
                $errors = $this->userModel->errors();
                log_message('error', 'Insert failed: ' . json_encode($errors));
                file_put_contents(WRITEPATH . 'createuser_debug.txt', "INSERT FAILED: " . json_encode($errors) . "\n\n", FILE_APPEND);
                session()->setFlashdata('error', 'Failed to create user. Errors: ' . json_encode($errors));
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            file_put_contents(WRITEPATH . 'createuser_debug.txt', "EXCEPTION: " . $e->getMessage() . "\n\n", FILE_APPEND);
            session()->setFlashdata('error', 'Database error: ' . $e->getMessage());
        }

        log_message('info', '=== CREATE USER ENDED ===');
        return redirect()->to('/dashboard');
    }

    public function updateUser()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        // Only allow POST requests
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/dashboard');
        }

        $id = $this->request->getPost('id');
        
        $rules = [
            'name' => 'required|min_length[3]',
            'email' => 'required|valid_email',
            'role' => 'required|in_list[student,instructor,admin]'
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('error', 'Validation failed: ' . implode(', ', $this->validator->getErrors()));
            return redirect()->to('/dashboard');
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'role' => $this->request->getPost('role')
        ];

        // Update password only if provided
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($this->userModel->update($id, $data)) {
            session()->setFlashdata('success', 'User updated successfully!');
        } else {
            session()->setFlashdata('error', 'Failed to update user.');
        }

        return redirect()->to('/dashboard');
    }

    public function deleteUser()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        // Only allow POST requests
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/dashboard');
        }

        $id = $this->request->getPost('id');

        // Prevent deleting yourself
        if ($id == session()->get('id')) {
            session()->setFlashdata('error', 'You cannot delete your own account!');
            return redirect()->to('/dashboard');
        }

        if ($this->userModel->delete($id)) {
            session()->setFlashdata('success', 'User deleted successfully!');
        } else {
            session()->setFlashdata('error', 'Failed to delete user.');
        }

        return redirect()->to('/dashboard');
    }

    public function reports()
    {
        return redirect()->to('/dashboard');
    }

    public function settings()
    {
        return redirect()->to('/dashboard');
    }

    // Course Management
    public function courses()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $courseModel = new CourseModel();
        $schoolYearModel = new SchoolYearModel();
        $userModel = new UserModel();

        $data = [
            'courses' => $courseModel->select('courses.*, school_years.school_year')
                ->join('school_years', 'school_years.id = courses.school_year_id', 'left')
                ->orderBy('courses.created_at', 'DESC')
                ->findAll(),
            'school_years' => $schoolYearModel->orderBy('school_year', 'DESC')->findAll(),
        ];

        return view('admin/courses', $data);
    }

    public function createCourse()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        // Check if request is POST using CodeIgniter's is() method (case-insensitive)
        if (!$this->request->is('post')) {
            log_message('error', 'Method is not POST, redirecting... Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/courses');
        }

        log_message('info', 'CREATE COURSE - Method: POST confirmed');

        log_message('info', 'CREATE COURSE - POST data: ' . json_encode($this->request->getPost()));

        $courseModel = new CourseModel();

        $rules = [
            'title' => 'required|min_length[3]|max_length[150]',
            'description' => 'permit_empty|max_length[5000]',
            'school_year_id' => 'required|integer',
            'semester' => 'required|in_list[1,2]',
            'term' => 'required|in_list[1,2]',
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            log_message('error', 'CREATE COURSE - Validation failed: ' . json_encode($errors));
            session()->setFlashdata('error', 'Validation failed: ' . implode(', ', $errors));
            return redirect()->to('/admin/courses')->withInput();
        }

        // Validate that academic structure is provided
        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'instructor_id' => null, // Instructor will be assigned via Teacher Assignment Management
            'school_year_id' => $this->request->getPost('school_year_id'),
            'semester' => $this->request->getPost('semester'),
            'term' => $this->request->getPost('term'),
        ];

        if (!$courseModel->validateAcademicStructure($data)) {
            log_message('error', 'CREATE COURSE - Academic structure validation failed');
            session()->setFlashdata('error', 'Course must be assigned to a School Year, Semester, and Term.');
            return redirect()->to('/admin/courses')->withInput();
        }

        // Verify that the term exists and has dates set
        $termModel = new TermModel();
        $semesterModel = new SemesterModel();
        $semesters = $semesterModel->where('school_year_id', $data['school_year_id'])
            ->where('semester_number', $data['semester'])
            ->findAll();
        
        if (empty($semesters)) {
            log_message('error', 'CREATE COURSE - Semester does not exist for school year: ' . $data['school_year_id']);
            session()->setFlashdata('error', 'Selected semester does not exist for this school year.');
            return redirect()->to('/admin/courses')->withInput();
        }

        $semester = $semesters[0];
        $term = $termModel->where('semester_id', $semester['id'])
            ->where('term_number', $data['term'])
            ->first();

        if (!$term || empty($term['start_date']) || empty($term['end_date'])) {
            log_message('error', 'CREATE COURSE - Term dates not set for term: ' . $data['term']);
            session()->setFlashdata('error', 'Term dates must be set before creating courses. Please set term dates in Academic Management.');
            return redirect()->to('/admin/courses')->withInput();
        }

        try {
            if ($courseModel->insert($data)) {
                log_message('info', 'CREATE COURSE - Success: Course created with ID ' . $courseModel->getInsertID());
                session()->setFlashdata('success', 'Course created successfully!');
            } else {
                $errors = $courseModel->errors();
                log_message('error', 'CREATE COURSE - Insert failed: ' . json_encode($errors));
                session()->setFlashdata('error', 'Failed to create course: ' . implode(', ', $errors));
            }
        } catch (\Exception $e) {
            log_message('error', 'CREATE COURSE - Exception: ' . $e->getMessage());
            session()->setFlashdata('error', 'Failed to create course: ' . $e->getMessage());
        }

        return redirect()->to('/admin/courses');
    }

    public function updateCourse()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/admin/courses');
        }

        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');

        $rules = [
            'title' => 'required|min_length[3]|max_length[150]',
            'description' => 'permit_empty|max_length[5000]',
            'school_year_id' => 'required|integer',
            'semester' => 'required|in_list[1,2]',
            'term' => 'required|in_list[1,2]',
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('error', 'Validation failed: ' . implode(', ', $this->validator->getErrors()));
            return redirect()->to('/admin/courses');
        }

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'instructor_id' => null, // Instructor will be assigned via Teacher Assignment Management
            'school_year_id' => $this->request->getPost('school_year_id'),
            'semester' => $this->request->getPost('semester'),
            'term' => $this->request->getPost('term'),
        ];

        if ($courseModel->update($id, $data)) {
            session()->setFlashdata('success', 'Course updated successfully!');
        } else {
            session()->setFlashdata('error', 'Failed to update course.');
        }

        return redirect()->to('/admin/courses');
    }

    public function deleteCourse()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/admin/courses');
        }

        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');

        if ($courseModel->delete($id)) {
            session()->setFlashdata('success', 'Course deleted successfully!');
        } else {
            session()->setFlashdata('error', 'Failed to delete course.');
        }

        return redirect()->to('/admin/courses');
    }

    // Enrollment Management
    public function enrollments()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $enrollmentModel = new EnrollmentModel();
        $courseModel = new CourseModel();
        $schoolYearModel = new SchoolYearModel();
        $userModel = new UserModel();

        $data = [
            'enrollments' => $enrollmentModel->select('enrollments.*, users.name as student_name, courses.title as course_title, school_years.school_year')
                ->join('users', 'users.id = enrollments.user_id')
                ->join('courses', 'courses.id = enrollments.course_id')
                ->join('school_years', 'school_years.id = enrollments.school_year_id', 'left')
                ->orderBy('enrollments.enrollment_date', 'DESC')
                ->findAll(),
            'students' => $userModel->where('role', 'student')->findAll(),
            'courses' => $courseModel->where('school_year_id IS NOT NULL')
                ->where('semester IS NOT NULL')
                ->where('term IS NOT NULL')
                ->findAll(),
            'school_years' => $schoolYearModel->orderBy('school_year', 'DESC')->findAll(),
        ];

        return view('admin/enrollments', $data);
    }

    public function createEnrollment()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/admin/enrollments');
        }

        $enrollmentModel = new EnrollmentModel();
        $courseModel = new CourseModel();

        $rules = [
            'user_id' => 'required|integer',
            'course_id' => 'required|integer',
            'school_year_id' => 'required|integer',
            'semester' => 'required|in_list[1,2]',
            'term' => 'required|in_list[1,2]',
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('error', 'Validation failed: ' . implode(', ', $this->validator->getErrors()));
            return redirect()->to('/admin/enrollments');
        }

        $userId = $this->request->getPost('user_id');
        $courseId = $this->request->getPost('course_id');
        $schoolYearId = $this->request->getPost('school_year_id');
        $semester = $this->request->getPost('semester');
        $term = $this->request->getPost('term');

        // Verify course exists for this academic period
        $course = $courseModel->where('id', $courseId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester', $semester)
            ->where('term', $term)
            ->first();

        if (!$course) {
            session()->setFlashdata('error', 'No available courses for this School Year, Semester, and Term.');
            return redirect()->to('/admin/enrollments');
        }

        // Check if already enrolled
        if ($enrollmentModel->isAlreadyEnrolled($userId, $courseId, $schoolYearId, $semester, $term)) {
            session()->setFlashdata('error', 'Student is already enrolled in this course for this academic period.');
            return redirect()->to('/admin/enrollments');
        }

        $data = [
            'user_id' => $userId,
            'course_id' => $courseId,
            'school_year_id' => $schoolYearId,
            'semester' => $semester,
            'term' => $term,
            'enrollment_date' => date('Y-m-d H:i:s'),
        ];

        if ($enrollmentModel->insert($data)) {
            session()->setFlashdata('success', 'Student enrolled successfully!');
        } else {
            session()->setFlashdata('error', 'Failed to enroll student.');
        }

        return redirect()->to('/admin/enrollments');
    }

    public function deleteEnrollment()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/admin/enrollments');
        }

        $enrollmentModel = new EnrollmentModel();
        $id = $this->request->getPost('id');

        if ($enrollmentModel->delete($id)) {
            session()->setFlashdata('success', 'Enrollment deleted successfully!');
        } else {
            session()->setFlashdata('error', 'Failed to delete enrollment.');
        }

        return redirect()->to('/admin/enrollments');
    }

    // Teacher Assignment Management
    public function teacherAssignments()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $teacherAssignmentModel = new TeacherAssignmentModel();
        $courseModel = new CourseModel();
        $schoolYearModel = new SchoolYearModel();
        $userModel = new UserModel();

        $data = [
            'assignments' => $teacherAssignmentModel->select('teacher_assignments.*, users.name as teacher_name, courses.title as course_title, school_years.school_year')
                ->join('users', 'users.id = teacher_assignments.teacher_id')
                ->join('courses', 'courses.id = teacher_assignments.course_id')
                ->join('school_years', 'school_years.id = teacher_assignments.school_year_id')
                ->orderBy('teacher_assignments.created_at', 'DESC')
                ->findAll(),
            'teachers' => $userModel->where('role', 'instructor')->findAll(),
            'courses' => $courseModel->select('courses.*, school_years.school_year')
                ->join('school_years', 'school_years.id = courses.school_year_id', 'left')
                ->where('courses.school_year_id IS NOT NULL')
                ->where('courses.semester IS NOT NULL')
                ->where('courses.term IS NOT NULL')
                ->orderBy('courses.created_at', 'DESC')
                ->findAll(),
            'school_years' => $schoolYearModel->orderBy('school_year', 'DESC')->findAll(),
        ];

        return view('admin/teacher_assignments', $data);
    }

    public function createTeacherAssignment()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        // Check if request is POST using CodeIgniter's is() method (case-insensitive)
        if (!$this->request->is('post')) {
            log_message('error', 'CREATE TEACHER ASSIGNMENT - Method is not POST, redirecting... Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/teacher-assignments');
        }

        log_message('info', 'CREATE TEACHER ASSIGNMENT - POST data: ' . json_encode($this->request->getPost()));

        $teacherAssignmentModel = new TeacherAssignmentModel();
        $courseModel = new CourseModel();

        $rules = [
            'teacher_id' => 'required|integer',
            'course_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('error', 'Validation failed: ' . implode(', ', $this->validator->getErrors()));
            return redirect()->to('/admin/teacher-assignments');
        }

        $teacherId = $this->request->getPost('teacher_id');
        $courseId = $this->request->getPost('course_id');

        // Verify course exists and get its academic structure
        $course = $courseModel->find($courseId);
        if (!$course) {
            session()->setFlashdata('error', 'Course does not exist.');
            return redirect()->to('/admin/teacher-assignments');
        }

        if (empty($course['school_year_id']) || empty($course['semester']) || empty($course['term'])) {
            session()->setFlashdata('error', 'Course must have School Year, Semester, and Term set.');
            return redirect()->to('/admin/teacher-assignments');
        }

        // Check if already assigned
        if ($teacherAssignmentModel->isAssigned($teacherId, $courseId, $course['school_year_id'], $course['semester'], $course['term'])) {
            session()->setFlashdata('error', 'Teacher is already assigned to this course for this academic period.');
            return redirect()->to('/admin/teacher-assignments');
        }

        $data = [
            'teacher_id' => $teacherId,
            'course_id' => $courseId,
            'school_year_id' => $course['school_year_id'],
            'semester' => $course['semester'],
            'term' => $course['term'],
        ];

        if ($teacherAssignmentModel->insert($data)) {
            // Update the course's instructor_id
            $courseModel->update($courseId, ['instructor_id' => $teacherId]);
            session()->setFlashdata('success', 'Teacher assigned successfully!');
        } else {
            session()->setFlashdata('error', 'Failed to assign teacher.');
        }

        return redirect()->to('/admin/teacher-assignments');
    }

    public function deleteTeacherAssignment()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/admin/teacher-assignments');
        }

        $teacherAssignmentModel = new TeacherAssignmentModel();
        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');

        // Get the assignment to find the course_id
        $assignment = $teacherAssignmentModel->find($id);
        
        if ($teacherAssignmentModel->delete($id)) {
            // Set course instructor_id to null if assignment was deleted
            if ($assignment && !empty($assignment['course_id'])) {
                $courseModel->update($assignment['course_id'], ['instructor_id' => null]);
            }
            session()->setFlashdata('success', 'Teacher assignment deleted successfully!');
        } else {
            session()->setFlashdata('error', 'Failed to delete teacher assignment.');
        }

        return redirect()->to('/admin/teacher-assignments');
    }
}


