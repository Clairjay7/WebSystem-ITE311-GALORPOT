<?php

namespace App\Controllers;

use App\Models\CourseModel;

class Course extends BaseController
{
    protected $courseModel;

    public function __construct()
    {
        $this->courseModel = new CourseModel();
    }

    /**
     * Search courses by title or description
     * Returns JSON for AJAX requests or renders view for regular requests
     */
    public function search()
    {
        $searchTerm = $this->request->getGet('search_term') ?? $this->request->getPost('search_term') ?? '';

        // Build query
        if (!empty($searchTerm)) {
            $this->courseModel->like('title', $searchTerm);
            $this->courseModel->orLike('description', $searchTerm);
        }

        // Get courses with instructor and school year info
        $courses = $this->courseModel
            ->select('courses.*, school_years.school_year, users.name as instructor_name')
            ->join('school_years', 'school_years.id = courses.school_year_id', 'left')
            ->join('users', 'users.id = courses.instructor_id', 'left')
            ->orderBy('courses.created_at', 'DESC')
            ->findAll();

        // Return JSON for AJAX requests
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($courses);
        }

        // Render view for regular requests
        return view('courses/search_results', [
            'courses' => $courses,
            'searchTerm' => $searchTerm
        ]);
    }
}

