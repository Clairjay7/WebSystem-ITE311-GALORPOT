<?php

namespace App\Controllers;

use App\Models\SchoolYearModel;
use App\Models\SemesterModel;
use App\Models\TermModel;

class AcademicController extends BaseController
{
    protected $schoolYearModel;
    protected $semesterModel;
    protected $termModel;

    public function __construct()
    {
        $this->schoolYearModel = new SchoolYearModel();
        $this->semesterModel = new SemesterModel();
        $this->termModel = new TermModel();
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
     * Display school years management page
     */
    public function index()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        helper(['form']);

        $data = [
            'school_years' => [],
            'migration_needed' => false,
        ];

        // Check if tables exist first
        try {
            $db = \Config\Database::connect();
            $db->query("SELECT 1 FROM school_years LIMIT 1");
        } catch (\Exception $e) {
            // Tables don't exist yet
            $data['migration_needed'] = true;
            $data['error_message'] = 'Database migrations need to be run first. Please run: <code>php spark migrate</code>';
            return view('academic/index', $data);
        }

        // If tables exist, try to load data
        try {
            $data['school_years'] = $this->schoolYearModel->orderBy('school_year', 'DESC')->findAll();

            // Check if there's an active academic period
            $data['has_active_academic_period'] = false;
            $data['active_academic_period_info'] = null;
            
            // Check for active school year
            $activeSchoolYear = $this->schoolYearModel->getActiveSchoolYear();
            $data['active_school_year'] = $activeSchoolYear ? $activeSchoolYear['school_year'] : null;
            
            if ($activeSchoolYear) {
                $data['has_active_academic_period'] = true;
                $data['active_academic_period_info'] = [
                    'type' => 'active_school_year',
                    'school_year' => $activeSchoolYear['school_year'],
                    'message' => 'There is an active school year (' . $activeSchoolYear['school_year'] . '). Please deactivate it first before creating a new academic structure.'
                ];
            } else {
                // Check for active term (current academic period based on dates)
                $activeTerm = $this->termModel->getActiveTerm();
                if ($activeTerm) {
                    $semester = $this->semesterModel->find($activeTerm['semester_id']);
                    if ($semester) {
                        $activeTermSchoolYear = $this->schoolYearModel->find($semester['school_year_id']);
                        if ($activeTermSchoolYear) {
                            $data['has_active_academic_period'] = true;
                            $data['active_academic_period_info'] = [
                                'type' => 'active_term',
                                'school_year' => $activeTermSchoolYear['school_year'],
                                'message' => 'There is currently an active academic period (School Year: ' . $activeTermSchoolYear['school_year'] . '). Please wait until the current term ends before creating a new academic structure.'
                            ];
                        }
                    }
                }
            }

            // Get full details for each school year
            if (!empty($data['school_years'])) {
                foreach ($data['school_years'] as &$sy) {
                    try {
                        $sy['semesters'] = $this->semesterModel->getBySchoolYear($sy['id']);
                        if (!empty($sy['semesters'])) {
                            foreach ($sy['semesters'] as &$sem) {
                                try {
                                    $sem['terms'] = $this->termModel->where('semester_id', $sem['id'])
                                        ->orderBy('term_number', 'ASC')
                                        ->findAll();
                                } catch (\Exception $e) {
                                    $sem['terms'] = [];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $sy['semesters'] = [];
                    }
                }
            }
            
            // Check if current year's school year exists
            $currentYear = (int) date('Y');
            $currentYearSchoolYear = $currentYear . '-' . ($currentYear + 1);
            $currentYearExists = false;
            foreach ($data['school_years'] as $sy) {
                if ($sy['school_year'] === $currentYearSchoolYear) {
                    $currentYearExists = true;
                    break;
                }
            }
            $data['current_year_exists'] = $currentYearExists;
            $data['current_year_school_year'] = $currentYearSchoolYear;
        } catch (\Exception $e) {
            // Error loading data, but tables exist
            log_message('error', 'Error loading academic data: ' . $e->getMessage());
            $data['school_years'] = [];
            $currentYear = (int) date('Y');
            $data['current_year_exists'] = false;
            $data['current_year_school_year'] = $currentYear . '-' . ($currentYear + 1);
        }

        return view('academic/index', $data);
    }

    /**
     * Create a new school year
     */
    public function createSchoolYear()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        // Log that form was submitted
        log_message('info', '=== CREATE SCHOOL YEAR METHOD CALLED ===');
        log_message('info', 'Request Method: ' . $this->request->getMethod());
        log_message('info', 'Is POST: ' . ($this->request->getMethod() === 'post' ? 'YES' : 'NO'));
        log_message('info', 'POST Data: ' . json_encode($this->request->getPost()));
        log_message('info', 'All POST keys: ' . implode(', ', array_keys($this->request->getPost() ?? [])));
        log_message('info', 'Raw Input: ' . $this->request->getBody());
        
        $requestMethod = $this->request->getMethod();
        log_message('info', 'Request method received: ' . $requestMethod);
        
        if (strtolower($requestMethod) !== 'post') {
            log_message('error', 'Method is not POST, redirecting... Method was: ' . $requestMethod);
            log_message('error', 'Is POST check: ' . ($this->request->is('post') ? 'YES' : 'NO'));
            session()->setFlashdata('error', 'Form submission error. Please ensure the form is submitted correctly.');
            return redirect()->to('/academic');
        }
        
        log_message('info', 'POST request confirmed, proceeding with creation...');

        // Check if tables exist
        try {
            $db = \Config\Database::connect();
            $db->query("SELECT 1 FROM school_years LIMIT 1");
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Database tables do not exist. Please run: php spark migrate');
            return redirect()->to('/academic');
        }

        helper(['form']);

        $rules = [
            'school_year' => 'required|regex_match[/^\d{4}-\d{4}$/]|is_unique[school_years.school_year]',
            'sem1_term1_start' => 'required|valid_date',
            'sem1_term1_end' => 'required|valid_date',
            'sem1_term2_start' => 'required|valid_date',
            'sem1_term2_end' => 'required|valid_date',
            'sem2_term1_start' => 'required|valid_date',
            'sem2_term1_end' => 'required|valid_date',
            'sem2_term2_start' => 'required|valid_date',
            'sem2_term2_end' => 'required|valid_date',
        ];

        $messages = [
            'school_year' => [
                'required' => 'School Year is required.',
                'regex_match' => 'Invalid School Year format. Use YYYY-YYYY and numbers only.',
                'is_unique' => 'This School Year already exists.',
            ],
            'sem1_term1_start' => ['required' => 'Semester 1 Term 1 Start Date is required.'],
            'sem1_term1_end' => ['required' => 'Semester 1 Term 1 End Date is required.'],
            'sem1_term2_start' => ['required' => 'Semester 1 Term 2 Start Date is required.'],
            'sem1_term2_end' => ['required' => 'Semester 1 Term 2 End Date is required.'],
            'sem2_term1_start' => ['required' => 'Semester 2 Term 1 Start Date is required.'],
            'sem2_term1_end' => ['required' => 'Semester 2 Term 1 End Date is required.'],
            'sem2_term2_start' => ['required' => 'Semester 2 Term 2 Start Date is required.'],
            'sem2_term2_end' => ['required' => 'Semester 2 Term 2 End Date is required.'],
        ];

        if (!$this->validate($rules, $messages)) {
            $errors = $this->validator->getErrors();
            $errorMsg = 'Validation failed: ' . implode(' | ', $errors);
            log_message('error', 'School Year Creation Validation Failed: ' . $errorMsg);
            log_message('error', 'POST Data: ' . json_encode($this->request->getPost()));
            log_message('error', 'Validation Errors: ' . json_encode($errors));
            
            // Show first error message more clearly
            $firstError = reset($errors);
            session()->setFlashdata('error', $firstError ?: $errorMsg);
            return redirect()->to('/academic');
        }

        log_message('info', 'Validation passed, proceeding with creation...');

        $schoolYear = trim($this->request->getPost('school_year'));

        // Validate format manually as well
        if (!preg_match('/^\d{4}-\d{4}$/', $schoolYear)) {
            session()->setFlashdata('error', 'Invalid School Year format. Use YYYY-YYYY and numbers only.');
            return redirect()->to('/academic');
        }

        // Get all required term dates
        $sem1_term1_start = $this->request->getPost('sem1_term1_start');
        $sem1_term1_end = $this->request->getPost('sem1_term1_end');
        $sem1_term2_start = $this->request->getPost('sem1_term2_start');
        $sem1_term2_end = $this->request->getPost('sem1_term2_end');
        $sem2_term1_start = $this->request->getPost('sem2_term1_start');
        $sem2_term1_end = $this->request->getPost('sem2_term1_end');
        $sem2_term2_start = $this->request->getPost('sem2_term2_start');
        $sem2_term2_end = $this->request->getPost('sem2_term2_end');

        // Validate all dates are provided
        $requiredDates = [
            'Semester 1 Term 1 Start' => $sem1_term1_start,
            'Semester 1 Term 1 End' => $sem1_term1_end,
            'Semester 1 Term 2 Start' => $sem1_term2_start,
            'Semester 1 Term 2 End' => $sem1_term2_end,
            'Semester 2 Term 1 Start' => $sem2_term1_start,
            'Semester 2 Term 1 End' => $sem2_term1_end,
            'Semester 2 Term 2 Start' => $sem2_term2_start,
            'Semester 2 Term 2 End' => $sem2_term2_end,
        ];

        foreach ($requiredDates as $label => $date) {
            if (empty($date)) {
                session()->setFlashdata('error', "All term dates are required. Missing: {$label}");
                return redirect()->to('/academic');
            }
        }

        // Validate that start dates are before end dates for each term
        $termDates = [
            ['Semester 1 Term 1', $sem1_term1_start, $sem1_term1_end],
            ['Semester 1 Term 2', $sem1_term2_start, $sem1_term2_end],
            ['Semester 2 Term 1', $sem2_term1_start, $sem2_term1_end],
            ['Semester 2 Term 2', $sem2_term2_start, $sem2_term2_end],
        ];

        foreach ($termDates as $term) {
            if (!$this->termModel->validateDates($term[1], $term[2])) {
                session()->setFlashdata('error', "{$term[0]}: Term start date must be earlier than end date.");
                return redirect()->to('/academic');
            }
        }

        // Validate that terms don't overlap - end date must be at least one day before next term's start date
        // Semester 1: Term 1 end < Term 2 start
        if (strtotime($sem1_term1_end) >= strtotime($sem1_term2_start)) {
            session()->setFlashdata('error', 'ERROR: Semester 1 Term 1 end date (' . date('M d, Y', strtotime($sem1_term1_end)) . ') must be at least one day before Semester 1 Term 2 start date (' . date('M d, Y', strtotime($sem1_term2_start)) . '). Dates cannot be the same or overlap.');
            return redirect()->to('/academic');
        }

        // Semester 1: Term 2 end < Semester 2 Term 1 start
        if (strtotime($sem1_term2_end) >= strtotime($sem2_term1_start)) {
            session()->setFlashdata('error', 'ERROR: Semester 1 Term 2 end date (' . date('M d, Y', strtotime($sem1_term2_end)) . ') must be at least one day before Semester 2 Term 1 start date (' . date('M d, Y', strtotime($sem2_term1_start)) . '). Dates cannot be the same or overlap.');
            return redirect()->to('/academic');
        }

        // Semester 2: Term 1 end < Term 2 start
        if (strtotime($sem2_term1_end) >= strtotime($sem2_term2_start)) {
            session()->setFlashdata('error', 'ERROR: Semester 2 Term 1 end date (' . date('M d, Y', strtotime($sem2_term1_end)) . ') must be at least one day before Semester 2 Term 2 start date (' . date('M d, Y', strtotime($sem2_term2_start)) . '). Dates cannot be the same or overlap.');
            return redirect()->to('/academic');
        }

        // Check if there's an active academic period (active school year)
        $activeSchoolYear = $this->schoolYearModel->getActiveSchoolYear();
        if ($activeSchoolYear) {
            session()->setFlashdata('error', 'ERROR: Cannot create a new Academic Structure. There is already an Active Academic Period (School Year: ' . $activeSchoolYear['school_year'] . '). Please deactivate the current active school year first before creating a new one.');
            return redirect()->to('/academic');
        }

        // Also check if there's an active term (current academic period based on dates)
        $activeTerm = $this->termModel->getActiveTerm();
        if ($activeTerm) {
            // Get the school year for this active term
            $semester = $this->semesterModel->find($activeTerm['semester_id']);
            if ($semester) {
                $activeTermSchoolYear = $this->schoolYearModel->find($semester['school_year_id']);
                if ($activeTermSchoolYear) {
                    session()->setFlashdata('error', 'ERROR: Cannot create a new Academic Structure. There is currently an Active Academic Period (School Year: ' . $activeTermSchoolYear['school_year'] . '). Please wait until the current term ends before creating a new academic structure.');
                    return redirect()->to('/academic');
                }
            }
        }

        // Validate that current year's school year exists before creating future years
        $startYear = (int) explode('-', $schoolYear)[0];
        $currentYear = (int) date('Y');
        
        // If trying to create a future year, check if current year exists
        if ($startYear > $currentYear) {
            $currentYearSchoolYear = $currentYear . '-' . ($currentYear + 1);
            $currentYearExists = $this->schoolYearModel->where('school_year', $currentYearSchoolYear)->first();
            
            if (!$currentYearExists) {
                session()->setFlashdata('error', 'ERROR: Cannot create School Year ' . $schoolYear . '. You must first create the current year\'s school year (' . $currentYearSchoolYear . ') before creating future school years.');
                return redirect()->to('/academic');
            }
        }

        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Extract start year from school year (e.g., "2025-2026" -> 2025)
            $startYear = (int) explode('-', $schoolYear)[0];
            $currentYear = (int) date('Y');
            
            // Set as active only if start year matches current year
            $isActive = ($startYear === $currentYear) ? 1 : 0;
            
            log_message('info', 'Creating school year: ' . $schoolYear . ' | Start Year: ' . $startYear . ' | Current Year: ' . $currentYear . ' | Will be Active: ' . ($isActive ? 'YES' : 'NO'));
            
            // Create school year
            $schoolYearId = $this->schoolYearModel->insert([
                'school_year' => $schoolYear,
                'is_active' => $isActive,
            ]);

            if (!$schoolYearId) {
                $errors = $this->schoolYearModel->errors();
                log_message('error', 'Failed to create school year. Errors: ' . json_encode($errors));
                throw new \Exception('Failed to create school year. ' . json_encode($errors));
            }
            
            log_message('info', 'School year created with ID: ' . $schoolYearId);

            // Create Semester 1
            $semester1Id = $this->semesterModel->insert([
                'school_year_id' => $schoolYearId,
                'semester_number' => 1,
            ]);

            if (!$semester1Id) {
                throw new \Exception('Failed to create Semester 1.');
            }

            // Create Semester 1 Terms
            $this->termModel->insert([
                'semester_id' => $semester1Id,
                'term_number' => 1,
                'start_date' => $sem1_term1_start,
                'end_date' => $sem1_term1_end,
            ]);

            $this->termModel->insert([
                'semester_id' => $semester1Id,
                'term_number' => 2,
                'start_date' => $sem1_term2_start,
                'end_date' => $sem1_term2_end,
            ]);

            // Create Semester 2
            $semester2Id = $this->semesterModel->insert([
                'school_year_id' => $schoolYearId,
                'semester_number' => 2,
            ]);

            if (!$semester2Id) {
                throw new \Exception('Failed to create Semester 2.');
            }

            // Create Semester 2 Terms
            $term3Result = $this->termModel->insert([
                'semester_id' => $semester2Id,
                'term_number' => 1,
                'start_date' => $sem2_term1_start,
                'end_date' => $sem2_term1_end,
            ]);

            if (!$term3Result) {
                throw new \Exception('Failed to create Semester 2 Term 1. Errors: ' . json_encode($this->termModel->errors()));
            }

            $term4Result = $this->termModel->insert([
                'semester_id' => $semester2Id,
                'term_number' => 2,
                'start_date' => $sem2_term2_start,
                'end_date' => $sem2_term2_end,
            ]);

            if (!$term4Result) {
                throw new \Exception('Failed to create Semester 2 Term 2. Errors: ' . json_encode($this->termModel->errors()));
            }

            // Set the newly created school year as active only if start year matches current year
            if ($isActive) {
                // Deactivate all other school years first
                $this->schoolYearModel->where('id !=', $schoolYearId)->set('is_active', 0)->update();
                
                // Then activate this one
                if (!$this->schoolYearModel->update($schoolYearId, ['is_active' => 1])) {
                    throw new \Exception('Failed to set school year as active.');
                }
                log_message('info', 'School year set as active because start year (' . $startYear . ') matches current year (' . $currentYear . ')');
            } else {
                log_message('info', 'School year NOT set as active because start year (' . $startYear . ') does not match current year (' . $currentYear . ')');
            }

            $db->transComplete();
            
            log_message('info', 'Transaction completed. Status: ' . ($db->transStatus() ? 'SUCCESS' : 'FAILED'));

            if ($db->transStatus() === false) {
                log_message('error', 'Transaction failed. Rolling back...');
                throw new \Exception('Transaction failed. Please check the logs for details.');
            }

            if ($isActive) {
                $successMsg = "School Year {$schoolYear} created successfully with Semester 1 (Term 1 & 2) and Semester 2 (Term 1 & 2). It has been automatically set as the Active Academic Period because the start year matches the current year.";
            } else {
                $successMsg = "School Year {$schoolYear} created successfully with Semester 1 (Term 1 & 2) and Semester 2 (Term 1 & 2). It will become active when the year reaches {$startYear}.";
            }
            session()->setFlashdata('success', $successMsg);
            log_message('info', "School Year {$schoolYear} created successfully with ID: {$schoolYearId}");
            log_message('info', 'Success message set in flashdata');
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->transRollback();
            }
            $errorMsg = 'Error creating school year: ' . $e->getMessage();
            log_message('error', $errorMsg);
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            log_message('error', 'POST Data: ' . json_encode($this->request->getPost()));
            session()->setFlashdata('error', $errorMsg);
        }

        return redirect()->to('/academic');
    }

    /**
     * Update term dates
     */
    public function updateTermDates()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/academic');
        }

        $termId = $this->request->getPost('term_id');
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        if (empty($termId) || empty($startDate) || empty($endDate)) {
            session()->setFlashdata('error', 'All fields are required.');
            return redirect()->to('/academic');
        }

        // Validate dates
        if (!$this->termModel->validateDates($startDate, $endDate)) {
            session()->setFlashdata('error', 'Term start date must be earlier than end date.');
            return redirect()->to('/academic');
        }

        // Get the term to find its semester and term number
        $term = $this->termModel->find($termId);
        if (!$term) {
            session()->setFlashdata('error', 'Term not found.');
            return redirect()->to('/academic');
        }

        // Get semester to find other terms in the same semester and school year
        $semester = $this->semesterModel->find($term['semester_id']);
        if (!$semester) {
            session()->setFlashdata('error', 'Semester not found.');
            return redirect()->to('/academic');
        }

        // Get all terms in the same semester
        $allTermsInSemester = $this->termModel->where('semester_id', $semester['id'])->findAll();
        
        // Get all semesters in the same school year
        $allSemesters = $this->semesterModel->where('school_year_id', $semester['school_year_id'])->findAll();
        $allTermIds = [];
        foreach ($allSemesters as $sem) {
            $terms = $this->termModel->where('semester_id', $sem['id'])->findAll();
            foreach ($terms as $t) {
                if ($t['id'] != $termId) { // Exclude the term being updated
                    $allTermIds[] = $t;
                }
            }
        }

        // Check for overlaps with other terms
        foreach ($allTermIds as $otherTerm) {
            // Check if this term's end date overlaps with next term's start date
            if ($term['term_number'] == 1 && $otherTerm['term_number'] == 2 && $otherTerm['semester_id'] == $term['semester_id']) {
                // Same semester, Term 1 -> Term 2
                if (strtotime($endDate) >= strtotime($otherTerm['start_date'])) {
                    session()->setFlashdata('error', 'ERROR: Term end date (' . date('M d, Y', strtotime($endDate)) . ') must be at least one day before the next term\'s start date (' . date('M d, Y', strtotime($otherTerm['start_date'])) . '). Dates cannot be the same or overlap.');
                    return redirect()->to('/academic');
                }
            } elseif ($term['term_number'] == 2 && $otherTerm['term_number'] == 1 && $otherTerm['semester_id'] != $term['semester_id']) {
                // Different semester, Term 2 of Sem 1 -> Term 1 of Sem 2
                $otherSemester = $this->semesterModel->find($otherTerm['semester_id']);
                if ($otherSemester && $otherSemester['semester_number'] == 2 && $semester['semester_number'] == 1) {
                    if (strtotime($endDate) >= strtotime($otherTerm['start_date'])) {
                        session()->setFlashdata('error', 'ERROR: Term end date (' . date('M d, Y', strtotime($endDate)) . ') must be at least one day before the next term\'s start date (' . date('M d, Y', strtotime($otherTerm['start_date'])) . '). Dates cannot be the same or overlap.');
                        return redirect()->to('/academic');
                    }
                }
            } elseif ($term['term_number'] == 2 && $otherTerm['term_number'] == 1 && $otherTerm['semester_id'] == $term['semester_id']) {
                // Same semester, Term 2 -> Term 1 (shouldn't happen, but check anyway)
                if (strtotime($otherTerm['end_date']) >= strtotime($startDate)) {
                    session()->setFlashdata('error', 'ERROR: Term start date (' . date('M d, Y', strtotime($startDate)) . ') must be at least one day after the previous term\'s end date (' . date('M d, Y', strtotime($otherTerm['end_date'])) . '). Dates cannot be the same or overlap.');
                    return redirect()->to('/academic');
                }
            }
        }

        if ($this->termModel->update($termId, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ])) {
            session()->setFlashdata('success', 'Term dates updated successfully.');
        } else {
            session()->setFlashdata('error', 'Failed to update term dates.');
        }

        return redirect()->to('/academic');
    }

    /**
     * Set active school year
     */
    public function setActiveSchoolYear()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/academic');
        }

        $id = $this->request->getPost('id');

        if ($this->schoolYearModel->setActive($id)) {
            session()->setFlashdata('success', 'Active school year updated.');
        } else {
            session()->setFlashdata('error', 'Failed to update active school year.');
        }

        return redirect()->to('/academic');
    }

    /**
     * Get current academic period (API endpoint)
     */
    public function getCurrentPeriod()
    {
        $currentPeriod = $this->termModel->getCurrentAcademicPeriod();

        if (!$currentPeriod) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No active term for the current date.',
            ]);
        }

        // Get school year period (start = Sem 1 Term 1 Start, end = Sem 2 Term 2 End)
        $schoolYearPeriod = $this->schoolYearModel->getSchoolYearPeriod($currentPeriod['school_year']['id']);

        $data = [
            'school_year' => $currentPeriod['school_year']['school_year'],
            'semester' => $currentPeriod['semester']['semester_number'],
            'term' => $currentPeriod['term']['term_number'],
            'term_start' => $currentPeriod['term']['start_date'],
            'term_end' => $currentPeriod['term']['end_date'],
        ];

        // Add school year period if available
        if ($schoolYearPeriod) {
            $data['school_year_start'] = $schoolYearPeriod['start_date'];
            $data['school_year_end'] = $schoolYearPeriod['end_date'];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}

