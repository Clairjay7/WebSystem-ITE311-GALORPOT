<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        // Get user role and current URI
        $role = strtolower((string) session('role'));
        $uri = $request->getUri()->getPath();

        // Remove leading slash for consistent comparison
        $uri = ltrim($uri, '/');

        // Allow access to common routes for all logged-in users
        $commonRoutes = ['', 'home', 'about', 'contact', 'logout', 'announcements'];
        if (in_array($uri, $commonRoutes)) {
            return null; // Allow access to common routes
        }

        // Role-based access control for protected routes
        switch ($role) {
            case 'admin':
                // Admins can access any route starting with /admin
                if (strpos($uri, 'admin') === 0) {
                    error_log("ROLEAUTH DEBUG: Admin access GRANTED to URI: {$uri}");
                    return null; // Allow access
                }
                error_log("ROLEAUTH DEBUG: Admin access DENIED to URI: {$uri}");
                break;

            case 'instructor':
                // Teachers can only access routes starting with /teacher
                if (strpos($uri, 'teacher') === 0) {
                    error_log("ROLEAUTH DEBUG: Teacher access GRANTED to URI: {$uri}");
                    return null; // Allow access
                }
                error_log("ROLEAUTH DEBUG: Teacher access DENIED to URI: {$uri}");
                break;

            case 'student':
                // Students can access routes starting with /student and /announcements
                if (strpos($uri, 'student') === 0) {
                    error_log("ROLEAUTH DEBUG: Student access GRANTED to URI: {$uri}");
                    return null; // Allow access
                }
                error_log("ROLEAUTH DEBUG: Student access DENIED to URI: {$uri}");
                break;
        }

        // If we reach here, access is denied
        error_log("ROLEAUTH DEBUG: FINAL DENIAL - Role: {$role}, URI: {$uri}");
        session()->setFlashdata('error', 'Access Denied: Insufficient Permissions');
        return redirect()->to('/announcements');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No after processing needed
        return null;
    }
}
