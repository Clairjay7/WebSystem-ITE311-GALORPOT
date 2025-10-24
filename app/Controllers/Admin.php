<?php

namespace App\Controllers;

class Admin extends BaseController
{
    private function ensureAdmin()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        $role = strtolower((string) session('role'));
        if ($role !== 'admin') {
            session()->setFlashdata('error', 'Unauthorized. Admin access only.');
            return redirect()->to('/announcements');
        }
        return null;
    }

    public function users()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }
        return view('admin/users');
    }

    public function reports()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }
        return view('admin/reports');
    }

    public function settings()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }
        return view('admin/settings');
    }

    public function dashboard()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }
        
        $data = [
            'title' => 'Admin Dashboard'
        ];
        
        return view('admin_dashboard', $data);
    }
}


