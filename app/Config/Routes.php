<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Redirect root to dashboard if logged in, otherwise to login
$routes->get('/', function() {
    if (session()->get('isLoggedIn')) {
        return redirect()->to('/dashboard');
    }
    return redirect()->to('/login');
});

$routes->get('/about', 'Home::about');
$routes->get('/contact', 'Home::contact');
$routes->get('/home', function() {
    if (session()->get('isLoggedIn')) {
        return redirect()->to('/dashboard');
    }
    return redirect()->to('/login');
});
// Auth routes
$routes->get('/register', 'Auth::register');
$routes->post('/register', 'Auth::register');

$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::login');

$routes->get('/logout', 'Auth::logout');
// Dashboard route
$routes->get('/dashboard', 'Dashboard::index');
$routes->get('/seed-defaults', 'Auth::seedDefaults');

// Admin routes
$routes->get('/admin/dashboard', 'Admin::dashboard');
$routes->get('/admin/users', 'Admin::users');
$routes->post('/admin/users/create', 'Admin::createUser');
$routes->get('/admin/users/test', 'Admin::testCreateUser'); // TEST ROUTE
$routes->post('/admin/users/update', 'Admin::updateUser');
$routes->post('/admin/users/delete', 'Admin::deleteUser');
$routes->post('/admin/users/restore', 'Admin::restoreUser');
$routes->get('/admin/reports', 'Admin::reports');
$routes->get('/admin/settings', 'Admin::settings');
$routes->get('/admin/completed-courses', 'Admin::completedCourses');

// Academic structure routes (Admin only)
$routes->get('/academic', 'AcademicController::index');
$routes->post('/academic/school-year/create', 'AcademicController::createSchoolYear');
$routes->post('/academic/term/update-dates', 'AcademicController::updateTermDates');
$routes->post('/academic/school-year/set-active', 'AcademicController::setActiveSchoolYear');
$routes->get('/academic/current-period', 'AcademicController::getCurrentPeriod');

// Course management routes
$routes->get('/admin/courses', 'Admin::courses');
$routes->get('/admin/courses/check-cn', 'Admin::checkControlNumber');
$routes->post('/admin/courses/create', 'Admin::createCourse');
$routes->post('/admin/courses/update', 'Admin::updateCourse');
$routes->post('/admin/courses/delete', 'Admin::deleteCourse');
$routes->post('/admin/courses/restore', 'Admin::restoreCourse');

// Enrollment routes
$routes->get('/admin/enrollments', 'Admin::enrollments');
$routes->post('/admin/enrollments/create', 'Admin::createEnrollment');
$routes->post('/admin/enrollments/delete', 'Admin::deleteEnrollment');
$routes->post('/admin/enrollments/restore', 'Admin::restoreEnrollment');

// Teacher assignment routes
$routes->get('/admin/teacher-assignments', 'Admin::teacherAssignments');
$routes->post('/admin/teacher-assignments/create', 'Admin::createTeacherAssignment');
$routes->post('/admin/teacher-assignments/update', 'Admin::updateTeacherAssignment');
$routes->post('/admin/teacher-assignments/delete', 'Admin::deleteTeacherAssignment');
$routes->post('/admin/teacher-assignments/restore', 'Admin::restoreTeacherAssignment');

// Student routes
$routes->get('/student/enroll', 'Student::enroll');
$routes->get('/student/courses', 'Student::courses');
$routes->post('/student/enroll/self-enroll', 'Student::selfEnroll');
$routes->get('/student/course/(:num)', 'Student::viewCourse/$1');

// Instructor routes
$routes->get('/instructor/my-courses', 'Instructor::myCourses');
$routes->get('/instructor/completed-courses', 'Instructor::completedCourses');
$routes->get('/instructor/course/(:num)', 'Instructor::viewCourse/$1');
$routes->post('/instructor/enroll-student', 'Instructor::enrollStudent');
$routes->post('/instructor/unenroll-student', 'Instructor::unenrollStudent');
$routes->post('/instructor/approve-enrollment', 'Instructor::approveEnrollment');
$routes->post('/instructor/reject-enrollment', 'Instructor::rejectEnrollment');