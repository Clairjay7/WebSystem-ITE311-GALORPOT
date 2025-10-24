<?php

namespace App\Controllers;
use App\Models\UserModel;

class Auth extends BaseController
{
    private function generateUniqueStudentId(): string
    {
        $model = new UserModel();
        // Format: S-YYYYMMDD-XXXX (random 4 digits)
        $prefix = 'S-' . date('Ymd') . '-';
        do {
            $candidate = $prefix . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $model->where('student_id', $candidate)->first();
        } while ($exists);
        return $candidate;
    }
    public function register()
    {
        helper(['form', 'url']);
        $data = [];

        if ($this->request->getMethod() == 'POST') {
            $rules = [
                'first_name'     => 'required|min_length[2]|max_length[100]',
                'last_name'      => 'required|min_length[2]|max_length[100]',
                'email'          => 'required|valid_email|max_length[100]|is_unique[users.email]',
                'password'       => 'required|min_length[6]',
                'student_id'     => 'permit_empty|max_length[50]',
                'middle_name'    => 'permit_empty|max_length[100]',
                'date_of_birth'  => 'permit_empty|valid_date',
                'gender'         => 'permit_empty|in_list[Male,Female,Other]',
                'contact_number' => 'permit_empty|max_length[30]',
            ];

            if (!$this->validate($rules)) {
                $data['validation'] = $this->validator;
            } else {
                $model = new UserModel();

                $first = trim($this->request->getPost('first_name'));
                $middle = trim($this->request->getPost('middle_name'));
                $last = trim($this->request->getPost('last_name'));
                $fullName = trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last);

                // Always auto-generate a unique student_id
                $studentId = $this->generateUniqueStudentId();

                $newData = [
                    'name'           => $fullName,
                    'first_name'     => $first,
                    'middle_name'    => $middle,
                    'last_name'      => $last,
                    'student_id'     => $studentId,
                    'date_of_birth'  => $this->request->getPost('date_of_birth'),
                    'gender'         => $this->request->getPost('gender'),
                    'contact_number' => trim($this->request->getPost('contact_number')),
                    'email'          => trim($this->request->getPost('email')),
                    'password'       => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                    'role'           => 'student'
                ];

                try {
                    $result = $model->insert($newData);
                    
                    if ($result) {
                        session()->setFlashdata('success', 'Registration successful!');
                        return redirect()->to('/login');
                    } else {
                        $errors = $model->errors();
                        session()->setFlashdata('error', 'Registration failed: ' . implode(', ', $errors));
                    }
                } catch (\Exception $e) {
                    session()->setFlashdata('error', 'Database error: ' . $e->getMessage());
                }
            }
        }

        return view('auth/register', $data);
    }

    public function login()
    {
        helper(['form', 'url']);
        $data = [];

        // Ensure default admin & instructor accounts exist when opening the login page
        if ($this->request->getMethod() !== 'POST') {
            $this->ensureDefaultUsers();
        }

        if ($this->request->getMethod() == 'POST') {
            $rules = [
                'email'    => 'required|valid_email',
                'password' => 'required|min_length[6]',
                'role'     => 'required|in_list[student,instructor,admin]'
            ];

            if (!$this->validate($rules)) {
                $data['validation'] = $this->validator;
            } else {
                $model = new UserModel();
                $user = $model->where('email', $this->request->getPost('email'))->first();

                if ($user) {
                    // Debug: Log user data
                    error_log("LOGIN DEBUG: Found user - Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}");
                    
                    if (password_verify($this->request->getPost('password'), $user['password'])) {
                        // Do not block login based on requested role; rely on stored role
                        // $requestedRole = $this->request->getPost('role');
                        $sessionData = [
                            'id'        => $user['id'],
                            'name'      => $user['name'],
                            'email'     => $user['email'],
                            'role'      => $user['role'] ?? 'student',
                            'isLoggedIn'=> true,
                        ];
                        session()->set($sessionData);

                        // Debug: Log session data and redirection
                        error_log("LOGIN DEBUG: Session set - Role: {$user['role']}, Redirecting based on role");

                        // Friendly welcome message
                        session()->setFlashdata('welcome', 'Welcome back, ' . $user['name'] . '!');

                        // Role-based redirection
                        switch ($user['role']) {
                            case 'admin':
                                error_log("LOGIN DEBUG: Redirecting admin to /admin/dashboard");
                                return redirect()->to('/admin/dashboard');
                            case 'instructor':
                                error_log("LOGIN DEBUG: Redirecting instructor to /teacher/dashboard");
                                return redirect()->to('/teacher/dashboard');
                            case 'student':
                            default:
                                error_log("LOGIN DEBUG: Redirecting student to /announcements");
                                return redirect()->to('/announcements');
                        }
                    } else {
                        session()->setFlashdata('error', 'Wrong password.');
                        return redirect()->to('/login');
                    }
                } else {
                    session()->setFlashdata('error', 'Email not found.');
                    return redirect()->to('/login');
                }
            }
        }

        return view('auth/login', $data);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }

    public function dashboard()
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        // Ensure a role exists in session for unified dashboard rendering
        if (!session()->has('role') || empty(session('role'))) {
            session()->set('role', 'student');
        }

        $role = strtolower((string) session('role'));
        $userId = (int) session('id');

        $model = new UserModel();
        $data = [];

        if ($role === 'admin') {
            // Example role-specific data for admin: user counts by role
            $studentsCount = (new UserModel())->where('role', 'student')->countAllResults();
            $instructorsCount = (new UserModel())->where('role', 'instructor')->countAllResults();
            $adminsCount = (new UserModel())->where('role', 'admin')->countAllResults();

            $data['metrics'] = [
                'students' => $studentsCount,
                'instructors' => $instructorsCount,
                'admins' => $adminsCount,
            ];
        } elseif ($role === 'instructor') {
            // Example role-specific data for instructor: latest students
            $recentStudents = (new UserModel())
                ->where('role', 'student')
                ->orderBy('id', 'DESC')
                ->findAll(5);
            $data['recentStudents'] = $recentStudents;
        } else {
            // Example role-specific data for student: own profile
            $profile = $model->find($userId);
            $data['profile'] = $profile;
        }

        return view('auth/dashboard', $data);
    }

    // Temporary seeding endpoint to create default admin and instructor accounts
    public function seedDefaults()
    {
        $model = new UserModel();
        $created = [];

        $defaults = [
            [
                'name' => 'Site Admin',
                'email' => 'admin@example.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
            ],
            [
                'name' => 'Lead Instructor',
                'email' => 'teacher@example.com',
                'password' => password_hash('teacher123', PASSWORD_DEFAULT),
                'role' => 'instructor',
            ],
        ];

        foreach ($defaults as $userData) {
            $existing = $model->where('email', $userData['email'])->first();
            if (!$existing) {
                $model->insert($userData);
                $created[] = $userData['email'];
            }
        }

        if (!empty($created)) {
            return $this->response->setJSON(['status' => 'ok', 'created' => $created]);
        }

        return $this->response->setJSON(['status' => 'ok', 'message' => 'Already seeded']);
    }

    // Internal utility: silently ensure default admin & instructor accounts exist
    private function ensureDefaultUsers(): void
    {
        try {
            $model = new UserModel();
            $defaults = [
                [
                    'name' => 'Site Admin',
                    'email' => 'admin@example.com',
                    'password' => password_hash('admin123', PASSWORD_DEFAULT),
                    'role' => 'admin',
                ],
                [
                    'name' => 'Lead Instructor',
                    'email' => 'teacher@example.com',
                    'password' => password_hash('teacher123', PASSWORD_DEFAULT),
                    'role' => 'instructor',
                ],
            ];

            foreach ($defaults as $userData) {
                $exists = $model->where('email', $userData['email'])->first();
                if (!$exists) {
                    $model->insert($userData);
                }
            }
        } catch (\Throwable $e) {
            // avoid disrupting login page if DB not ready
        }
    }
}