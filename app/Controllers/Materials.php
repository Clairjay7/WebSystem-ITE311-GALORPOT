<?php

namespace App\Controllers;

use App\Models\MaterialModel;
use App\Models\CourseModel;
use App\Models\EnrollmentModel;

class Materials extends BaseController
{
    protected $materialModel;
    protected $courseModel;
    protected $enrollmentModel;

    public function __construct()
    {
        $this->materialModel = new MaterialModel();
        $this->courseModel = new CourseModel();
        $this->enrollmentModel = new EnrollmentModel();
    }

    /**
     * Check if user is logged in
     */
    private function ensureLoggedIn()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        return null;
    }

    /**
     * Check if user is enrolled in a course (for students)
     * @param int $userId The user ID
     * @param int $courseId The course ID
     * @return array Returns array with 'enrolled' (bool) and 'message' (string)
     */
    private function isEnrolledInCourse($userId, $courseId)
    {
        $role = strtolower((string) session()->get('role'));
        
        // Admins and instructors can always access
        if ($role === 'admin' || $role === 'instructor') {
            return ['enrolled' => true, 'message' => ''];
        }
        
        // For students, check enrollment
        if ($role === 'student') {
            // Get course details to check academic structure
            $course = $this->courseModel->find($courseId);
            
            if (!$course) {
                return ['enrolled' => false, 'message' => 'Course not found.'];
            }
            
            // Check if enrollment exists with approved status
            $enrollmentBuilder = $this->enrollmentModel
                ->where('user_id', $userId)
                ->where('course_id', $courseId);
            
            // Check if status column exists
            try {
                $db = \Config\Database::connect();
                $columns = $db->getFieldNames('enrollments');
                if (in_array('status', $columns)) {
                    $enrollmentBuilder->where('status', 'approved');
                }
            } catch (\Exception $e) {
                // Status column might not exist, continue without it
            }
            
            // Also check academic structure if course has it
            if (!empty($course['school_year_id']) && !empty($course['semester']) && !empty($course['term'])) {
                $enrollmentBuilder->where('school_year_id', $course['school_year_id'])
                    ->where('semester', $course['semester'])
                    ->where('term', $course['term']);
            }
            
            $enrollment = $enrollmentBuilder->first();
            
            if ($enrollment === null) {
                return ['enrolled' => false, 'message' => 'You are not enrolled in this course. Please enroll first to access course materials.'];
            }
            
            // Check if enrollment is pending
            if (isset($enrollment['status']) && $enrollment['status'] === 'pending') {
                return ['enrolled' => false, 'message' => 'Your enrollment in this course is still pending approval. You cannot download materials until your enrollment is approved.'];
            }
            
            // Check if enrollment is rejected
            if (isset($enrollment['status']) && $enrollment['status'] === 'rejected') {
                return ['enrolled' => false, 'message' => 'Your enrollment in this course was rejected. You cannot access course materials.'];
            }
            
            return ['enrolled' => true, 'message' => ''];
        }
        
        return ['enrolled' => false, 'message' => 'Unauthorized access.'];
    }

    /**
     * Display the file upload form and handle file upload
     * @param int $course_id - The course ID
     */
    public function upload($course_id)
    {
        if ($redirect = $this->ensureLoggedIn()) {
            return $redirect;
        }

        $role = strtolower((string) session()->get('role'));
        
        // Only admin and instructor can upload materials
        if ($role !== 'admin' && $role !== 'instructor') {
            session()->setFlashdata('error', 'Unauthorized. Only admins and instructors can upload materials.');
            return redirect()->to('/dashboard');
        }

        $course = $this->courseModel->find($course_id);
        
        if (!$course) {
            session()->setFlashdata('error', 'Course not found.');
            return redirect()->to('/dashboard');
        }

        // Handle file upload
        if ($this->request->is('post')) {
            log_message('debug', 'Material upload POST received for course: ' . $course_id);
            
            $validation = \Config\Services::validation();
            
            $validation->setRules([
                'material_file' => [
                    'label' => 'File',
                    'rules' => 'uploaded[material_file]|max_size[material_file,10240]|ext_in[material_file,pdf,ppt,pptx]',
                    'errors' => [
                        'uploaded' => 'Please select a file to upload.',
                        'max_size' => 'File size must not exceed 10MB.',
                        'ext_in' => 'Invalid file type. Only PDF and PPT files are allowed.'
                    ]
                ]
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                log_message('error', 'Material upload validation failed: ' . json_encode($errors));
                
                // Set flashdata for validation errors
                if (!empty($errors)) {
                    session()->setFlashdata('error', implode('<br>', $errors));
                }
                
                $data = [
                    'course' => $course,
                    'validation' => $validation,
                    'materials' => $this->materialModel->getMaterialsByCourse($course_id)
                ];
                return view('materials/upload', $data);
            }

            $file = $this->request->getFile('material_file');
            
            // Check if file was uploaded
            if ($file === null || !$file->isValid()) {
                $errorMsg = 'No file was uploaded or file upload failed.';
                if ($file !== null) {
                    $errorCode = $file->getError();
                    $errorMsg = 'File upload error (Code: ' . $errorCode . ').';
                }
                log_message('error', 'Material upload - File invalid: ' . $errorMsg);
                session()->setFlashdata('error', $errorMsg);
                return redirect()->to('/materials/upload/' . $course_id);
            }
            
            if (!$file->hasMoved()) {
                // Additional validation: Check file extension manually
                $allowedExtensions = ['pdf', 'ppt', 'pptx'];
                $fileExtension = strtolower($file->getClientExtension());
                
                if (!in_array($fileExtension, $allowedExtensions)) {
                    session()->setFlashdata('error', 'Invalid file type. Only PDF and PPT files are allowed.');
                    return redirect()->to('/materials/upload/' . $course_id);
                }
                
                // Check if file name already exists for this course
                $fileName = $file->getClientName();
                if ($this->materialModel->fileNameExists($course_id, $fileName)) {
                    session()->setFlashdata('error', 'A file with the name "' . esc($fileName) . '" already exists in this course. Please use a different file name or rename your file.');
                    return redirect()->to('/materials/upload/' . $course_id);
                }
                
                // Create upload directory if it doesn't exist
                $uploadPath = WRITEPATH . 'uploads/materials/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // Generate unique filename
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);

                if ($file->hasMoved()) {
                    // Save to database
                    $materialData = [
                        'course_id' => $course_id,
                        'file_name' => $file->getClientName(),
                        'file_path' => 'uploads/materials/' . $newName
                    ];

                    if ($this->materialModel->insertMaterial($materialData)) {
                        session()->setFlashdata('success', 'Material uploaded successfully.');
                    } else {
                        // Delete uploaded file if database insert fails
                        @unlink($uploadPath . $newName);
                        session()->setFlashdata('error', 'Failed to save material record.');
                    }
                } else {
                    $errorMsg = 'Failed to move uploaded file.';
                    log_message('error', 'Material upload - File move failed. Error: ' . $file->getErrorString());
                    session()->setFlashdata('error', $errorMsg . ' ' . ($file->getErrorString() ?? ''));
                }
            } else {
                session()->setFlashdata('error', 'File has already been moved.');
            }

            return redirect()->to('/materials/upload/' . $course_id);
        }

        // Display upload form
        $data = [
            'course' => $course,
            'materials' => $this->materialModel->getMaterialsByCourse($course_id),
            'deleted_materials' => $this->materialModel->getDeletedMaterialsByCourse($course_id)
        ];

        return view('materials/upload', $data);
    }

    /**
     * Handle the deletion of a material record and the associated file
     * @param int $material_id - The material ID
     */
    public function delete($material_id)
    {
        if ($redirect = $this->ensureLoggedIn()) {
            return $redirect;
        }

        $role = strtolower((string) session()->get('role'));
        
        // Only admin and instructor can delete materials
        if ($role !== 'admin' && $role !== 'instructor') {
            session()->setFlashdata('error', 'Unauthorized. Only admins and instructors can delete materials.');
            return redirect()->to('/dashboard');
        }

        $material = $this->materialModel->getMaterialById($material_id);
        
        if (!$material) {
            session()->setFlashdata('error', 'Material not found.');
            return redirect()->to('/dashboard');
        }

        // Soft delete from database (file remains on filesystem for restore)
        if ($this->materialModel->delete($material_id)) {
            session()->setFlashdata('success', 'Material deleted successfully. You can restore it from the deleted materials section.');
        } else {
            session()->setFlashdata('error', 'Failed to delete material.');
        }

        return redirect()->back();
    }

    /**
     * Restore a deleted material
     * @param int $material_id - The material ID
     */
    public function restore($material_id)
    {
        if ($redirect = $this->ensureLoggedIn()) {
            return $redirect;
        }

        $role = strtolower((string) session()->get('role'));
        
        // Only admin and instructor can restore materials
        if ($role !== 'admin' && $role !== 'instructor') {
            session()->setFlashdata('error', 'Unauthorized. Only admins and instructors can restore materials.');
            return redirect()->to('/dashboard');
        }

        // Check if material exists in deleted records
        $deletedMaterial = $this->materialModel->onlyDeleted()->find($material_id);
        
        if (!$deletedMaterial) {
            session()->setFlashdata('error', 'Deleted material not found.');
            return redirect()->back();
        }

        // Restore the material
        if ($this->materialModel->restoreMaterial($material_id)) {
            session()->setFlashdata('success', 'Material restored successfully.');
        } else {
            session()->setFlashdata('error', 'Failed to restore material.');
        }

        return redirect()->back();
    }

    /**
     * Handle the file download for enrolled students
     * @param int $material_id - The material ID
     */
    public function download($material_id)
    {
        if ($redirect = $this->ensureLoggedIn()) {
            return $redirect;
        }

        $material = $this->materialModel->getMaterialById($material_id);
        
        if (!$material) {
            session()->setFlashdata('error', 'Material not found.');
            return redirect()->to('/dashboard');
        }

        $userId = (int) session()->get('id');
        $courseId = $material['course_id'];

        // Check if user is enrolled in the course
        $enrollmentCheck = $this->isEnrolledInCourse($userId, $courseId);
        if (!$enrollmentCheck['enrolled']) {
            $errorMessage = !empty($enrollmentCheck['message']) 
                ? $enrollmentCheck['message'] 
                : 'You are not enrolled in this course. Please enroll first to access course materials.';
            session()->setFlashdata('error', $errorMessage);
            
            // Redirect based on user role
            $role = strtolower((string) session()->get('role'));
            if ($role === 'student') {
                return redirect()->to('/student/enroll');
            }
            return redirect()->to('/dashboard');
        }

        // Get file path
        $filePath = WRITEPATH . $material['file_path'];
        
        if (!file_exists($filePath)) {
            session()->setFlashdata('error', 'File not found.');
            return redirect()->to('/dashboard');
        }

        // Force download
        return $this->response->download($filePath, null)->setFileName($material['file_name']);
    }
}

