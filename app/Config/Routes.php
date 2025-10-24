<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/about', 'Home::about');
$routes->get('/contact', 'Home::contact');
$routes->get('/home', 'Home::index');
// Auth routes
$routes->get('/register', 'Auth::register');
$routes->post('/register', 'Auth::register');

$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::login');

$routes->get('/logout', 'Auth::logout');
$routes->get('/dashboard', 'Auth::dashboard');
$routes->get('/seed-defaults', 'Auth::seedDefaults');

// Course routes
$routes->post('/course/enroll', 'Course::enroll');

// Instructor routes
$routes->get('/instructor/my-classes', 'Instructor::myClasses');
$routes->get('/instructor/submissions', 'Instructor::submissions');
$routes->get('/instructor/attendance', 'Instructor::attendance');

// Student routes - Protected by RoleAuth filter
$routes->group('student', ['filter' => 'roleauth'], static function ($routes) {
    $routes->get('courses', 'Student::courses');
    $routes->get('assignments', 'Student::assignments');
    $routes->get('grades', 'Student::grades');
});

// Admin routes - Temporarily remove filter for testing
$routes->get('/admin/dashboard', 'Admin::dashboard');
$routes->get('/admin/users', 'Admin::users');
$routes->get('/admin/reports', 'Admin::reports');
$routes->get('/admin/settings', 'Admin::settings');

// Teacher routes - Temporarily remove filter for testing
$routes->get('/teacher/dashboard', 'Teacher::dashboard');

// Announcement routes
$routes->get('/announcements', 'Announcement::index');
