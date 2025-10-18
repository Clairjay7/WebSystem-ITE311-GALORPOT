<?php

namespace App\Controllers;

class Teacher extends BaseController
{
    public function dashboard()
    {
        // Check if user is logged in and has teacher role
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        
        if (session()->get('role') !== 'instructor') {
            return redirect()->to('/login');
        }
        
        $data = [
            'title' => 'Teacher Dashboard'
        ];
        
        return view('teacher_dashboard', $data);
    }
}
