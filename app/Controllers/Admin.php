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

    /**
     * Check for time conflict for a teacher
     * Returns true if there's a conflict (same teacher, same time, same semester, same term)
     * @param int $teacherId The teacher ID to check
     * @param string $time The time slot to check
     * @param int $schoolYearId The school year ID
     * @param int $semester The semester (1 or 2)
     * @param int $term The term (1 or 2)
     * @param int|null $excludeCourseId Course ID to exclude from check (for updates)
     * @return bool True if conflict exists, false otherwise
     */
    private function checkTimeConflict($teacherId, $time, $schoolYearId, $semester, $term, $excludeCourseId = null)
    {
        $courseModel = new CourseModel();
        $teacherAssignmentModel = new TeacherAssignmentModel();
        
        // Check courses where instructor_id matches
        $builder = $courseModel
            ->where('instructor_id', $teacherId)
            ->where('time', $time)
            ->where('school_year_id', $schoolYearId)
            ->where('semester', $semester)
            ->where('term', $term);
        
        if ($excludeCourseId !== null) {
            $builder->where('id !=', $excludeCourseId);
        }
        
        $conflict = $builder->first();
        if ($conflict) {
            return true;
        }
        
        // Check courses assigned via teacher_assignments
        $assignments = $teacherAssignmentModel
            ->where('teacher_id', $teacherId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester', $semester)
            ->where('term', $term)
            ->findAll();
        
        foreach ($assignments as $assignment) {
            $course = $courseModel->find($assignment['course_id']);
            if ($course && $course['time'] === $time) {
                if ($excludeCourseId === null || $course['id'] != $excludeCourseId) {
                    return true;
                }
            }
        }
        
        return false;
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
            'name' => [
                'label' => 'Name',
                'rules' => 'required|min_length[3]|alpha_numeric_space',
                'errors' => [
                    'required' => 'Name is required',
                    'min_length' => 'Name must be at least 3 characters long',
                    'alpha_numeric_space' => 'Name can only contain letters, numbers, and spaces. Special characters are not allowed.',
                ],
            ],
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'role' => 'required|in_list[student,instructor,admin]'
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            log_message('error', 'Validation failed: ' . json_encode($errors));
            file_put_contents(WRITEPATH . 'createuser_debug.txt', "VALIDATION FAILED: " . json_encode($errors) . "\n\n", FILE_APPEND);
            
            // Store validation errors for display
            session()->setFlashdata('validation', $errors);
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
        
        // Prevent admin from editing themselves
        if ($id == session()->get('id')) {
            session()->setFlashdata('error', 'You cannot edit your own account!');
            log_message('error', 'Update user: Admin attempted to edit their own account. ID: ' . $id);
            return redirect()->to('/dashboard');
        }
        
        $rules = [
            'name' => [
                'label' => 'Name',
                'rules' => 'required|min_length[3]|alpha_numeric_space',
                'errors' => [
                    'required' => 'Name is required',
                    'min_length' => 'Name must be at least 3 characters long',
                    'alpha_numeric_space' => 'Name can only contain letters, numbers, and spaces. Special characters are not allowed.',
                ],
            ],
            'email' => 'required|valid_email',
            'role' => 'required|in_list[student,instructor,admin]'
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            session()->setFlashdata('validation', $errors);
            session()->setFlashdata('error', 'Validation failed: ' . implode(', ', $errors));
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
        if (!$this->request->is('post')) {
            log_message('error', 'Delete user: Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/dashboard');
        }

        $id = $this->request->getPost('id');
        
        if (empty($id)) {
            session()->setFlashdata('error', 'User ID is required.');
            log_message('error', 'Delete user: No ID provided');
            return redirect()->to('/dashboard');
        }

        // Prevent deleting yourself
        if ($id == session()->get('id')) {
            session()->setFlashdata('error', 'You cannot delete your own account!');
            return redirect()->to('/dashboard');
        }

        log_message('info', 'Attempting to delete user ID: ' . $id);
        $result = $this->userModel->delete($id);
        log_message('info', 'Delete user result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            session()->setFlashdata('success', 'User marked as deleted successfully!');
        } else {
            $errors = $this->userModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            log_message('error', 'Failed to delete user. Errors: ' . json_encode($errors));
            session()->setFlashdata('error', 'Failed to mark user as deleted: ' . $errorMsg);
        }

        return redirect()->to('/dashboard');
    }

    public function restoreUser()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if (!$this->request->is('post')) {
            log_message('error', 'Restore user: Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/dashboard');
        }

        $id = $this->request->getPost('id');
        
        if (empty($id)) {
            session()->setFlashdata('error', 'User ID is required.');
            log_message('error', 'Restore user: No ID provided');
            return redirect()->to('/dashboard');
        }

        log_message('info', 'Attempting to restore user ID: ' . $id);
        $user = $this->userModel->withDeleted()->find($id);

        if (!$user) {
            session()->setFlashdata('error', 'User not found.');
            log_message('error', 'Restore user: User not found. ID: ' . $id);
            return redirect()->to('/dashboard');
        }

        if (empty($user['deleted_at'])) {
            session()->setFlashdata('error', 'User is not deleted.');
            log_message('error', 'Restore user: User is not deleted. ID: ' . $id);
            return redirect()->to('/dashboard');
        }

        $result = $this->userModel->update($id, ['deleted_at' => null]);
        log_message('info', 'Restore user result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            session()->setFlashdata('success', 'User restored successfully!');
        } else {
            $errors = $this->userModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            log_message('error', 'Failed to restore user. Errors: ' . json_encode($errors));
            session()->setFlashdata('error', 'Failed to restore user: ' . $errorMsg);
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
            'courses' => $courseModel->select('courses.*, school_years.school_year, users.name as instructor_name')
                ->join('school_years', 'school_years.id = courses.school_year_id', 'left')
                ->join('users', 'users.id = courses.instructor_id', 'left')
                ->orderBy('courses.created_at', 'DESC')
                ->findAll(),
            'deleted_courses' => $courseModel->select('courses.*, school_years.school_year')
                ->join('school_years', 'school_years.id = courses.school_year_id', 'left')
                ->withDeleted()
                ->onlyDeleted()
                ->orderBy('courses.deleted_at', 'DESC')
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
            'title' => [
                'label' => 'Course Title',
                'rules' => 'required|min_length[3]|max_length[30]|alpha_numeric_space',
                'errors' => [
                    'required' => 'Course Title is required',
                    'min_length' => 'Course Title must be at least 3 characters long',
                    'max_length' => 'Course Title must not exceed 30 characters',
                    'alpha_numeric_space' => 'Course Title can only contain letters, numbers, and spaces. Special characters are not allowed.',
                ],
            ],
            'control_number' => [
                'label' => 'Control Number',
                'rules' => 'required|regex_match[/^CN-\d{4}$/]|is_unique[courses.control_number]',
                'errors' => [
                    'regex_match' => 'Control Number must be in format CN-YYYY (e.g., CN-2024)',
                ],
            ],
            'units' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[5]',
            'description' => 'permit_empty|max_length[5000]',
            'school_year_id' => 'required|integer',
            'semester' => 'required|in_list[1,2]',
            'term' => 'required|in_list[1,2]',
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            log_message('error', 'CREATE COURSE - Validation failed: ' . json_encode($errors));
            session()->setFlashdata('error', 'Please fix the validation errors below.');
            session()->setFlashdata('validation', $errors);
            return redirect()->to('/admin/courses')->withInput();
        }

        // Get control number - if control_number_year is provided, prepend CN-
        $controlNumberYear = $this->request->getPost('control_number_year');
        $controlNumber = $this->request->getPost('control_number');
        if (!empty($controlNumberYear) && empty($controlNumber)) {
            $controlNumber = 'CN-' . $controlNumberYear;
        }
        
        // Validate that academic structure is provided
        $data = [
            'title' => $this->request->getPost('title'),
            'control_number' => $controlNumber,
            'units' => (int) $this->request->getPost('units'),
            'description' => $this->request->getPost('description'),
            'time' => $this->request->getPost('time'),
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

        // Check for time conflict if instructor is assigned
        if (!empty($data['time']) && !empty($data['instructor_id'])) {
            $conflict = $this->checkTimeConflict($data['instructor_id'], $data['time'], $data['school_year_id'], $data['semester'], $data['term'], null);
            if ($conflict) {
                session()->setFlashdata('error', 'Time conflict: The selected teacher already has a course scheduled at ' . $data['time'] . ' for the same semester and term.');
                return redirect()->to('/admin/courses')->withInput();
            }
        }

        // Validate that the selected school year is active
        $schoolYearModel = new SchoolYearModel();
        $selectedSchoolYear = $schoolYearModel->find($data['school_year_id']);
        
        if (!$selectedSchoolYear) {
            log_message('error', 'CREATE COURSE - School year not found: ' . $data['school_year_id']);
            session()->setFlashdata('error', 'Selected school year does not exist.');
            return redirect()->to('/admin/courses')->withInput();
        }
        
        if (!$selectedSchoolYear['is_active']) {
            log_message('error', 'CREATE COURSE - School year is not active: ' . $selectedSchoolYear['school_year']);
            session()->setFlashdata('error', 'ERROR: Cannot create course for School Year ' . $selectedSchoolYear['school_year'] . '. Only active school years can be used for course creation. Please select an active school year.');
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
        log_message('info', 'UPDATE COURSE METHOD CALLED - Method: ' . $this->request->getMethod());
        log_message('info', 'UPDATE COURSE - POST data: ' . json_encode($this->request->getPost()));
        
        if ($redirect = $this->ensureAdmin()) {
            log_message('info', 'UPDATE COURSE - Not admin, redirecting');
            return $redirect;
        }

        if (!$this->request->is('post')) {
            log_message('error', 'UPDATE COURSE - Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/courses');
        }

        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');
        log_message('info', 'UPDATE COURSE - Course ID: ' . $id);
        $existingCourse = $courseModel->find($id);
        
        // Check if control_number is being changed
        $controlNumber = $this->request->getPost('control_number');
        $isControlNumberChanged = ($existingCourse && $existingCourse['control_number'] !== $controlNumber);
        
        $rules = [
            'title' => [
                'label' => 'Course Title',
                'rules' => 'required|min_length[3]|max_length[30]|alpha_numeric_space',
                'errors' => [
                    'required' => 'Course Title is required',
                    'min_length' => 'Course Title must be at least 3 characters long',
                    'max_length' => 'Course Title must not exceed 30 characters',
                    'alpha_numeric_space' => 'Course Title can only contain letters, numbers, and spaces. Special characters are not allowed.',
                ],
            ],
            'control_number' => [
                'label' => 'Control Number',
                'rules' => $isControlNumberChanged 
                    ? 'required|regex_match[/^CN-\d{4}$/]|is_unique[courses.control_number]'
                    : 'required|regex_match[/^CN-\d{4}$/]',
                'errors' => [
                    'regex_match' => 'Control Number must be in format CN-YYYY (e.g., CN-2024)',
                ],
            ],
            'units' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[5]',
            'description' => 'permit_empty|max_length[5000]',
            'school_year_id' => 'required|integer',
            'semester' => 'required|in_list[1,2]',
            'term' => 'required|in_list[1,2]',
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('error', 'Validation failed: ' . implode(', ', $this->validator->getErrors()));
            return redirect()->to('/admin/courses');
        }

        // Get control number - if control_number_year is provided, prepend CN-
        $controlNumberYear = $this->request->getPost('control_number_year');
        $controlNumber = $this->request->getPost('control_number');
        if (!empty($controlNumberYear) && empty($controlNumber)) {
            $controlNumber = 'CN-' . $controlNumberYear;
        }
        
        $time = $this->request->getPost('time');
        // Convert empty string to null for database
        $timeValue = ($time === '' || $time === null) ? null : $time;
        
        $data = [
            'title' => $this->request->getPost('title'),
            'control_number' => $controlNumber,
            'units' => (int) $this->request->getPost('units'),
            'description' => $this->request->getPost('description'),
            'time' => $timeValue,
            'instructor_id' => $existingCourse['instructor_id'], // Keep existing instructor_id
            'school_year_id' => $this->request->getPost('school_year_id'),
            'semester' => $this->request->getPost('semester'),
            'term' => $this->request->getPost('term'),
        ];
        
        log_message('info', 'UPDATE COURSE - ID: ' . $id);
        log_message('info', 'UPDATE COURSE - Time POST value: ' . var_export($time, true));
        log_message('info', 'UPDATE COURSE - Time value to save: ' . var_export($timeValue, true));
        log_message('info', 'UPDATE COURSE - Full data: ' . json_encode($data));

        // Check for time conflict if instructor is assigned
        if (!empty($data['time']) && !empty($data['instructor_id'])) {
            $conflict = $this->checkTimeConflict($data['instructor_id'], $data['time'], $data['school_year_id'], $data['semester'], $data['term'], $id);
            if ($conflict) {
                session()->setFlashdata('error', 'Time conflict: The assigned teacher already has a course scheduled at ' . $data['time'] . ' for the same semester and term.');
                return redirect()->to('/admin/courses');
            }
        }

        // Validate that the selected school year is active
        $schoolYearModel = new SchoolYearModel();
        $selectedSchoolYear = $schoolYearModel->find($data['school_year_id']);
        
        if (!$selectedSchoolYear) {
            session()->setFlashdata('error', 'Selected school year does not exist.');
            return redirect()->to('/admin/courses');
        }
        
        if (!$selectedSchoolYear['is_active']) {
            session()->setFlashdata('error', 'ERROR: Cannot update course to School Year ' . $selectedSchoolYear['school_year'] . '. Only active school years can be used. Please select an active school year.');
            return redirect()->to('/admin/courses');
        }

        $updateResult = $courseModel->update($id, $data);
        log_message('info', 'UPDATE COURSE - Update result: ' . ($updateResult ? 'SUCCESS' : 'FAILED'));
        if (!$updateResult) {
            $errors = $courseModel->errors();
            log_message('error', 'UPDATE COURSE - Errors: ' . json_encode($errors));
        }
        
        if ($updateResult) {
            session()->setFlashdata('success', 'Course updated successfully!');
        } else {
            $errors = $courseModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            session()->setFlashdata('error', 'Failed to update course: ' . $errorMsg);
        }

        return redirect()->to('/admin/courses');
    }

    public function checkControlNumber()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $controlNumber = $this->request->getGet('control_number');
        $excludeId = $this->request->getGet('exclude_id'); // For edit mode, exclude current course ID

        if (empty($controlNumber)) {
            return $this->response->setJSON(['exists' => false]);
        }

        // Validate format
        if (!preg_match('/^CN-\d{4}$/', $controlNumber)) {
            return $this->response->setJSON(['exists' => false, 'invalid' => true]);
        }

        $courseModel = new CourseModel();
        $query = $courseModel->where('control_number', $controlNumber);
        
        // If editing, exclude the current course
        if (!empty($excludeId)) {
            $query->where('id !=', $excludeId);
        }

        $existing = $query->first();

        return $this->response->setJSON([
            'exists' => $existing !== null,
            'course_title' => $existing ? $existing['title'] : null
        ]);
    }

    public function deleteCourse()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if (!$this->request->is('post')) {
            log_message('error', 'Delete course: Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/courses');
        }

        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');
        
        if (empty($id)) {
            session()->setFlashdata('error', 'Course ID is required.');
            log_message('error', 'Delete course: No ID provided');
            return redirect()->to('/admin/courses');
        }

        log_message('info', 'Attempting to delete course ID: ' . $id);
        $result = $courseModel->delete($id);
        log_message('info', 'Delete course result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            session()->setFlashdata('success', 'Course marked as deleted successfully!');
        } else {
            $errors = $courseModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            log_message('error', 'Failed to delete course. Errors: ' . json_encode($errors));
            session()->setFlashdata('error', 'Failed to mark course as deleted: ' . $errorMsg);
        }

        return redirect()->to('/admin/courses');
    }

    public function restoreCourse()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if (!$this->request->is('post')) {
            log_message('error', 'Restore course: Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/courses');
        }

        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');
        
        if (empty($id)) {
            session()->setFlashdata('error', 'Course ID is required.');
            log_message('error', 'Restore course: No ID provided');
            return redirect()->to('/admin/courses');
        }

        log_message('info', 'Attempting to restore course ID: ' . $id);
        $course = $courseModel->withDeleted()->find($id);

        if (!$course) {
            session()->setFlashdata('error', 'Course not found.');
            log_message('error', 'Restore course: Course not found. ID: ' . $id);
            return redirect()->to('/admin/courses');
        }

        if (empty($course['deleted_at'])) {
            session()->setFlashdata('error', 'Course is not deleted.');
            log_message('error', 'Restore course: Course is not deleted. ID: ' . $id);
            return redirect()->to('/admin/courses');
        }

        $result = $courseModel->update($id, ['deleted_at' => null]);
        log_message('info', 'Restore course result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            session()->setFlashdata('success', 'Course restored successfully!');
        } else {
            $errors = $courseModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            log_message('error', 'Failed to restore course. Errors: ' . json_encode($errors));
            session()->setFlashdata('error', 'Failed to restore course: ' . $errorMsg);
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
            'deleted_enrollments' => $enrollmentModel->select('enrollments.*, users.name as student_name, courses.title as course_title, school_years.school_year')
                ->join('users', 'users.id = enrollments.user_id')
                ->join('courses', 'courses.id = enrollments.course_id')
                ->join('school_years', 'school_years.id = enrollments.school_year_id', 'left')
                ->withDeleted()
                ->onlyDeleted()
                ->orderBy('enrollments.deleted_at', 'DESC')
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

        if (!$this->request->is('post')) {
            log_message('error', 'Delete enrollment: Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/enrollments');
        }

        $enrollmentModel = new EnrollmentModel();
        $id = $this->request->getPost('id');
        
        if (empty($id)) {
            session()->setFlashdata('error', 'Enrollment ID is required.');
            log_message('error', 'Delete enrollment: No ID provided');
            return redirect()->to('/admin/enrollments');
        }

        log_message('info', 'Attempting to delete enrollment ID: ' . $id);
        $result = $enrollmentModel->delete($id);
        log_message('info', 'Delete enrollment result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            session()->setFlashdata('success', 'Enrollment marked as deleted successfully!');
        } else {
            $errors = $enrollmentModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            log_message('error', 'Failed to delete enrollment. Errors: ' . json_encode($errors));
            session()->setFlashdata('error', 'Failed to mark enrollment as deleted: ' . $errorMsg);
        }

        return redirect()->to('/admin/enrollments');
    }

    public function restoreEnrollment()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if (!$this->request->is('post')) {
            log_message('error', 'Restore enrollment: Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/enrollments');
        }

        $enrollmentModel = new EnrollmentModel();
        $id = $this->request->getPost('id');
        
        if (empty($id)) {
            session()->setFlashdata('error', 'Enrollment ID is required.');
            log_message('error', 'Restore enrollment: No ID provided');
            return redirect()->to('/admin/enrollments');
        }

        log_message('info', 'Attempting to restore enrollment ID: ' . $id);
        $enrollment = $enrollmentModel->withDeleted()->find($id);

        if (!$enrollment) {
            session()->setFlashdata('error', 'Enrollment not found.');
            log_message('error', 'Restore enrollment: Enrollment not found. ID: ' . $id);
            return redirect()->to('/admin/enrollments');
        }

        if (empty($enrollment['deleted_at'])) {
            session()->setFlashdata('error', 'Enrollment is not deleted.');
            log_message('error', 'Restore enrollment: Enrollment is not deleted. ID: ' . $id);
            return redirect()->to('/admin/enrollments');
        }

        $result = $enrollmentModel->update($id, ['deleted_at' => null]);
        log_message('info', 'Restore enrollment result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            session()->setFlashdata('success', 'Enrollment restored successfully!');
        } else {
            $errors = $enrollmentModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            log_message('error', 'Failed to restore enrollment. Errors: ' . json_encode($errors));
            session()->setFlashdata('error', 'Failed to restore enrollment: ' . $errorMsg);
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
            'assignments' => $teacherAssignmentModel->select('teacher_assignments.*, users.name as teacher_name, courses.title as course_title, courses.control_number, courses.units, courses.time, school_years.school_year')
                ->join('users', 'users.id = teacher_assignments.teacher_id')
                ->join('courses', 'courses.id = teacher_assignments.course_id')
                ->join('school_years', 'school_years.id = teacher_assignments.school_year_id')
                ->orderBy('teacher_assignments.created_at', 'DESC')
                ->findAll(),
            'deleted_assignments' => $teacherAssignmentModel->select('teacher_assignments.*, users.name as teacher_name, courses.title as course_title, courses.control_number, courses.units, courses.time, school_years.school_year')
                ->join('users', 'users.id = teacher_assignments.teacher_id')
                ->join('courses', 'courses.id = teacher_assignments.course_id')
                ->join('school_years', 'school_years.id = teacher_assignments.school_year_id')
                ->withDeleted()
                ->onlyDeleted()
                ->orderBy('teacher_assignments.deleted_at', 'DESC')
                ->findAll(),
            'teachers' => $userModel->where('role', 'instructor')->findAll(),
            'courses' => $courseModel->select('courses.*, courses.control_number, courses.units, courses.time, school_years.school_year')
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

        // Check for time conflict if course has a time set
        if (!empty($course['time'])) {
            $conflict = $this->checkTimeConflict($teacherId, $course['time'], $course['school_year_id'], $course['semester'], $course['term'], null);
            if ($conflict) {
                session()->setFlashdata('error', 'Time conflict: The teacher already has a course scheduled at ' . $course['time'] . ' for the same semester and term.');
                return redirect()->to('/admin/teacher-assignments');
            }
        }

        $data = [
            'teacher_id' => $teacherId,
            'course_id' => $courseId,
            'school_year_id' => $course['school_year_id'],
            'semester' => $course['semester'],
            'term' => $course['term'],
        ];

        try {
            $assignmentId = $teacherAssignmentModel->insert($data);
            if ($assignmentId) {
                // Update the course's instructor_id
                $courseModel->update($courseId, ['instructor_id' => $teacherId]);
                session()->setFlashdata('success', 'Teacher assigned successfully!');
                log_message('info', 'Teacher assignment created successfully. Assignment ID: ' . $assignmentId . ', Teacher ID: ' . $teacherId . ', Course ID: ' . $courseId);
            } else {
                $errors = $teacherAssignmentModel->errors();
                log_message('error', 'Failed to create teacher assignment. Errors: ' . json_encode($errors));
                session()->setFlashdata('error', 'Failed to assign teacher. ' . (isset($errors) ? implode(', ', $errors) : ''));
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception creating teacher assignment: ' . $e->getMessage());
            session()->setFlashdata('error', 'Failed to assign teacher: ' . $e->getMessage());
        }

        return redirect()->to('/admin/teacher-assignments');
    }

    public function updateTeacherAssignment()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        log_message('info', 'UPDATE TEACHER ASSIGNMENT - Method: ' . $this->request->getMethod());
        log_message('info', 'UPDATE TEACHER ASSIGNMENT - POST data: ' . json_encode($this->request->getPost()));

        // Check if request is POST (case-insensitive)
        if (!$this->request->is('post')) {
            log_message('error', 'UPDATE TEACHER ASSIGNMENT - Method is not POST, redirecting... Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/teacher-assignments');
        }

        $teacherAssignmentModel = new TeacherAssignmentModel();
        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');
        
        log_message('info', 'UPDATE TEACHER ASSIGNMENT - ID received: ' . $id);

        // Validate that ID is provided
        if (empty($id)) {
            session()->setFlashdata('error', 'Assignment ID is required.');
            log_message('error', 'Update teacher assignment failed: No ID provided');
            return redirect()->to('/admin/teacher-assignments');
        }

        $rules = [
            'id' => 'required|integer',
            'teacher_id' => 'required|integer',
            'course_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            $errorMsg = 'Validation failed: ' . implode(', ', $errors);
            session()->setFlashdata('error', $errorMsg);
            log_message('error', 'Update teacher assignment validation failed: ' . json_encode($errors));
            log_message('error', 'POST data: ' . json_encode($this->request->getPost()));
            return redirect()->to('/admin/teacher-assignments');
        }

        $teacherId = $this->request->getPost('teacher_id');
        $courseId = $this->request->getPost('course_id');

        // Get existing assignment
        $existingAssignment = $teacherAssignmentModel->find($id);
        if (!$existingAssignment) {
            session()->setFlashdata('error', 'Assignment not found.');
            return redirect()->to('/admin/teacher-assignments');
        }

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

        // Check if already assigned (excluding current assignment)
        if ($teacherAssignmentModel->isAssigned($teacherId, $courseId, $course['school_year_id'], $course['semester'], $course['term'], $id)) {
            session()->setFlashdata('error', 'Teacher is already assigned to this course for this academic period.');
            return redirect()->to('/admin/teacher-assignments');
        }

        // Check for time conflict if course has a time set
        if (!empty($course['time'])) {
            $conflict = $this->checkTimeConflict($teacherId, $course['time'], $course['school_year_id'], $course['semester'], $course['term'], $courseId);
            if ($conflict) {
                session()->setFlashdata('error', 'Time conflict: The teacher already has a course scheduled at ' . $course['time'] . ' for the same semester and term.');
                return redirect()->to('/admin/teacher-assignments');
            }
        }

        $data = [
            'teacher_id' => $teacherId,
            'course_id' => $courseId,
            'school_year_id' => $course['school_year_id'],
            'semester' => $course['semester'],
            'term' => $course['term'],
        ];

        $oldTeacherId = $existingAssignment['teacher_id'];
        $oldCourseId = $existingAssignment['course_id'];
        
        // Validation: If course is changed, teacher must also be changed (cannot be the same teacher)
        if ($oldCourseId != $courseId && $oldTeacherId == $teacherId) {
            session()->setFlashdata('error', 'When changing the course, you must also assign a different teacher. The same teacher cannot be assigned to a different course in this assignment.');
            log_message('error', 'Update teacher assignment failed: Course changed but teacher is the same. Old Course: ' . $oldCourseId . ', New Course: ' . $courseId . ', Teacher: ' . $teacherId);
            return redirect()->to('/admin/teacher-assignments');
        }
        
        // Check BEFORE updating if old teacher has other assignments for the old course
        $oldTeacherOtherAssignments = null;
        if ($oldTeacherId != $teacherId || $oldCourseId != $courseId) {
            $oldTeacherOtherAssignments = $teacherAssignmentModel
                ->where('teacher_id', $oldTeacherId)
                ->where('course_id', $oldCourseId)
                ->where('id !=', $id)
                ->first();
        }
        
        try {
            log_message('info', 'UPDATE TEACHER ASSIGNMENT - Attempting to update. ID: ' . $id);
            log_message('info', 'UPDATE TEACHER ASSIGNMENT - Old values - Teacher: ' . $oldTeacherId . ', Course: ' . $oldCourseId);
            log_message('info', 'UPDATE TEACHER ASSIGNMENT - New values - Teacher: ' . $teacherId . ', Course: ' . $courseId);
            log_message('info', 'UPDATE TEACHER ASSIGNMENT - Data to update: ' . json_encode($data));
            
            // Check if anything actually changed
            $hasChanges = false;
            if ($oldTeacherId != $teacherId || 
                $oldCourseId != $courseId || 
                $existingAssignment['school_year_id'] != $data['school_year_id'] ||
                $existingAssignment['semester'] != $data['semester'] ||
                $existingAssignment['term'] != $data['term']) {
                $hasChanges = true;
            }
            
            if (!$hasChanges) {
                session()->setFlashdata('info', 'No changes detected. Assignment remains the same.');
                return redirect()->to('/admin/teacher-assignments');
            }
            
            // Update the teacher assignment record
            $updateResult = $teacherAssignmentModel->update($id, $data);
            
            log_message('info', 'UPDATE TEACHER ASSIGNMENT - Update result: ' . ($updateResult ? 'SUCCESS' : 'FAILED'));
            
            if ($updateResult === false) {
                $errors = $teacherAssignmentModel->errors();
                $db = \Config\Database::connect();
                $dbError = $db->error();
                log_message('error', 'UPDATE TEACHER ASSIGNMENT - Model errors: ' . json_encode($errors));
                log_message('error', 'UPDATE TEACHER ASSIGNMENT - DB error: ' . json_encode($dbError));
                
                $errorMsg = 'Failed to update teacher assignment. ';
                if (!empty($errors)) {
                    $errorMsg .= implode(', ', $errors);
                } elseif (!empty($dbError) && isset($dbError['message'])) {
                    $errorMsg .= $dbError['message'];
                } else {
                    $errorMsg .= 'Database update failed. The teacher may already be assigned to this course for this academic period.';
                }
                
                session()->setFlashdata('error', $errorMsg);
                return redirect()->to('/admin/teacher-assignments');
            }
            
            if ($updateResult) {
                // Update the course's instructor_id to the new teacher
                $courseModel->update($courseId, ['instructor_id' => $teacherId]);
                
                // If teacher changed, handle the old teacher
                if ($oldTeacherId != $teacherId) {
                    // If old teacher has no other assignments for the old course, 
                    // and it's the same course, the instructor_id is already updated to new teacher
                    // If it's a different course, handle the old course
                    if ($oldCourseId != $courseId) {
                        // Different course - check if old course still has any assignments
                        $oldCourseAssignments = $teacherAssignmentModel
                            ->where('course_id', $oldCourseId)
                            ->first();
                        
                        if ($oldCourseAssignments) {
                            // Set instructor_id to the teacher who has the assignment
                            $courseModel->update($oldCourseId, ['instructor_id' => $oldCourseAssignments['teacher_id']]);
                        } else {
                            // No assignments for old course, set instructor_id to null
                            $courseModel->update($oldCourseId, ['instructor_id' => null]);
                        }
                    }
                } elseif ($oldCourseId != $courseId) {
                    // Same teacher, different course
                    // Check if old course still has any assignments
                    $oldCourseAssignments = $teacherAssignmentModel
                        ->where('course_id', $oldCourseId)
                        ->first();
                    
                    if ($oldCourseAssignments) {
                        // Set instructor_id to the teacher who has the assignment
                        $courseModel->update($oldCourseId, ['instructor_id' => $oldCourseAssignments['teacher_id']]);
                    } else {
                        // No assignments for old course, set instructor_id to null
                        $courseModel->update($oldCourseId, ['instructor_id' => null]);
                    }
                }
                
                session()->setFlashdata('success', 'Teacher assignment updated successfully! The old teacher will no longer see this course.');
                log_message('info', 'Teacher assignment updated. ID: ' . $id . ', Old Teacher: ' . $oldTeacherId . ', New Teacher: ' . $teacherId . ', Course: ' . $courseId);
            } else {
                $errors = $teacherAssignmentModel->errors();
                $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
                log_message('error', 'Failed to update teacher assignment. ID: ' . $id . ', Errors: ' . json_encode($errors));
                session()->setFlashdata('error', 'Failed to update teacher assignment: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception updating teacher assignment: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            session()->setFlashdata('error', 'Failed to update teacher assignment: ' . $e->getMessage());
        }

        return redirect()->to('/admin/teacher-assignments');
    }

    public function deleteTeacherAssignment()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if (!$this->request->is('post')) {
            log_message('error', 'Delete teacher assignment: Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/teacher-assignments');
        }

        $teacherAssignmentModel = new TeacherAssignmentModel();
        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');
        
        if (empty($id)) {
            session()->setFlashdata('error', 'Assignment ID is required.');
            log_message('error', 'Delete teacher assignment: No ID provided');
            return redirect()->to('/admin/teacher-assignments');
        }

        // Get the assignment to find the course_id
        $assignment = $teacherAssignmentModel->find($id);
        
        if (!$assignment) {
            session()->setFlashdata('error', 'Teacher assignment not found.');
            log_message('error', 'Delete teacher assignment: Assignment not found. ID: ' . $id);
            return redirect()->to('/admin/teacher-assignments');
        }
        
        log_message('info', 'Attempting to delete teacher assignment ID: ' . $id);
        $result = $teacherAssignmentModel->delete($id);
        log_message('info', 'Delete teacher assignment result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            // Set course instructor_id to null if assignment was deleted
            if ($assignment && !empty($assignment['course_id'])) {
                $courseModel->update($assignment['course_id'], ['instructor_id' => null]);
            }
            session()->setFlashdata('success', 'Teacher assignment marked as deleted successfully!');
        } else {
            $errors = $teacherAssignmentModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            log_message('error', 'Failed to delete teacher assignment. Errors: ' . json_encode($errors));
            session()->setFlashdata('error', 'Failed to mark teacher assignment as deleted: ' . $errorMsg);
        }

        return redirect()->to('/admin/teacher-assignments');
    }

    public function restoreTeacherAssignment()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if (!$this->request->is('post')) {
            log_message('error', 'Restore teacher assignment: Method is not POST. Method was: ' . $this->request->getMethod());
            return redirect()->to('/admin/teacher-assignments');
        }

        $teacherAssignmentModel = new TeacherAssignmentModel();
        $courseModel = new CourseModel();
        $id = $this->request->getPost('id');
        
        if (empty($id)) {
            session()->setFlashdata('error', 'Assignment ID is required.');
            log_message('error', 'Restore teacher assignment: No ID provided');
            return redirect()->to('/admin/teacher-assignments');
        }

        log_message('info', 'Attempting to restore teacher assignment ID: ' . $id);
        $assignment = $teacherAssignmentModel->withDeleted()->find($id);

        if (!$assignment) {
            session()->setFlashdata('error', 'Teacher assignment not found.');
            log_message('error', 'Restore teacher assignment: Assignment not found. ID: ' . $id);
            return redirect()->to('/admin/teacher-assignments');
        }

        if (empty($assignment['deleted_at'])) {
            session()->setFlashdata('error', 'Teacher assignment is not deleted.');
            log_message('error', 'Restore teacher assignment: Assignment is not deleted. ID: ' . $id);
            return redirect()->to('/admin/teacher-assignments');
        }

        $result = $teacherAssignmentModel->update($id, ['deleted_at' => null]);
        log_message('info', 'Restore teacher assignment result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            // Restore instructor_id if needed
            if ($assignment && !empty($assignment['course_id']) && !empty($assignment['teacher_id'])) {
                $courseModel->update($assignment['course_id'], ['instructor_id' => $assignment['teacher_id']]);
            }
            session()->setFlashdata('success', 'Teacher assignment restored successfully!');
        } else {
            $errors = $teacherAssignmentModel->errors();
            $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
            log_message('error', 'Failed to restore teacher assignment. Errors: ' . json_encode($errors));
            session()->setFlashdata('error', 'Failed to restore teacher assignment: ' . $errorMsg);
        }

        return redirect()->to('/admin/teacher-assignments');
    }

    /**
     * View all completed courses
     */
    public function completedCourses()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $courseModel = new CourseModel();
        $termModel = new TermModel();
        $semesterModel = new SemesterModel();
        $schoolYearModel = new SchoolYearModel();
        $userModel = new UserModel();

        $data = [
            'courses' => [],
            'current_period' => null,
            'active_school_year' => null,
        ];

        try {
            $currentPeriod = $termModel->getCurrentAcademicPeriod();
            $data['current_period'] = $currentPeriod;
            $activeSchoolYear = $schoolYearModel->getActiveSchoolYear();
            $data['active_school_year'] = $activeSchoolYear;

            $today = date('Y-m-d');
            $completedCourses = [];

            // Get all courses
            $allCourses = $courseModel->orderBy('created_at', 'DESC')->findAll();

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
                        $course['term_start_date'] = $term['start_date'];
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

            $data['courses'] = $completedCourses;
        } catch (\Exception $e) {
            log_message('error', 'Error loading admin completed courses: ' . $e->getMessage());
            $data['error'] = 'Error loading completed courses. Please try again later.';
        }

        return view('admin/completed_courses', $data);
    }
}


