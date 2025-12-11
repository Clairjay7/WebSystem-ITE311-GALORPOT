<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-enroll<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-plus"></i> Course Enrollment</h2>
        <a href="<?= site_url('/dashboard') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= esc($error) ?>
        </div>
    <?php endif; ?>

    <!-- Current Academic Period -->
    <?php if (isset($current_period) && $current_period): ?>
        <div class="card border-success mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Current Academic Period</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>School Year:</strong><br>
                        <h5 class="text-primary"><?= esc($current_period['school_year']['school_year']) ?></h5>
                    </div>
                    <div class="col-md-3">
                        <strong>Semester:</strong><br>
                        <h5 class="text-info">Semester <?= $current_period['semester']['semester_number'] ?></h5>
                    </div>
                    <div class="col-md-3">
                        <strong>Term:</strong><br>
                        <h5 class="text-warning">Term <?= $current_period['term']['term_number'] ?></h5>
                    </div>
                    <div class="col-md-3">
                        <strong>Term Period:</strong><br>
                        <small>
                            <?= date('M d, Y', strtotime($current_period['term']['start_date'])) ?> - 
                            <?= date('M d, Y', strtotime($current_period['term']['end_date'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (isset($active_school_year) && $active_school_year): ?>
        <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Active School Year</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <strong>School Year:</strong> <?= esc($active_school_year['school_year']) ?><br>
                    <small>No active term for the current date. Please wait for the administrator to set term dates.</small>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>No Active School Year</strong><br>
            Please contact the administrator to set up the academic structure.
        </div>
    <?php endif; ?>

    <!-- Enrolled Courses -->
    <?php if (isset($enrolled_courses) && !empty($enrolled_courses)): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle"></i> My Enrolled Courses (Approved)</h5>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form id="enrolledCoursesSearchForm" class="d-flex">
                            <div class="input-group">
                                <input type="text" id="enrolledCoursesSearchInput" class="form-control" placeholder="Search by course title, description, or instructor..." name="search_term">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Description</th>
                                <th>Units</th>
                                <th>Time</th>
                                <th>Instructor</th>
                                <th>Course End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="enrolledCoursesContainer">
                            <?php foreach ($enrolled_courses as $course): ?>
                                <tr class="enrolled-course-row" data-title="<?= strtolower(esc($course['title'])) ?>" data-description="<?= strtolower(esc($course['description'] ?? '')) ?>" data-instructor="<?= strtolower(esc($course['instructor_name'] ?? '')) ?>">
                                    <td><strong><?= esc($course['title']) ?></strong></td>
                                    <td><?= esc($course['description'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span></td>
                                    <td><?= esc($course['time'] ?? 'N/A') ?></td>
                                    <td><?= esc($course['instructor_name'] ?? 'Not assigned') ?></td>
                                    <td>
                                        <?php if (isset($course['term_end_date'])): ?>
                                            <i class="fas fa-calendar-times text-warning"></i> <?= date('M d, Y', strtotime($course['term_end_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('/student/course/' . $course['id']) ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View Course
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pending Enrollment Requests -->
    <?php if (isset($pending_enrollments) && !empty($pending_enrollments)): ?>
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Pending Enrollment Requests (<?= count($pending_enrollments) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> Your enrollment requests are waiting for instructor approval.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>CN</th>
                                <th>Course</th>
                                <th>Units</th>
                                <th>Teacher</th>
                                <th>Request Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_enrollments as $pending): ?>
                                <tr>
                                    <td><strong><?= esc($pending['control_number'] ?? 'N/A') ?></strong></td>
                                    <td><strong><?= esc($pending['course_title'] ?? 'N/A') ?></strong></td>
                                    <td><span class="badge bg-info"><?= esc($pending['units'] ?? '0') ?> units</span></td>
                                    <td><?= esc($pending['instructor_name'] ?? 'N/A') ?></td>
                                    <td><?= date('M d, Y', strtotime($pending['enrollment_date'])) ?></td>
                                    <td><span class="badge bg-warning">Pending Approval</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Rejected Enrollment Requests -->
    <?php if (isset($rejected_enrollments) && !empty($rejected_enrollments)): ?>
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-times-circle"></i> Rejected Enrollment Requests (<?= count($rejected_enrollments) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle"></i> Your enrollment requests have been rejected. You can re-enroll if the course is still available.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Request Date</th>
                                <th>Rejection Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rejected_enrollments as $rejected): ?>
                                <tr>
                                    <td><strong><?= esc($rejected['course_title']) ?></strong></td>
                                    <td><?= date('M d, Y', strtotime($rejected['enrollment_date'])) ?></td>
                                    <td>
                                        <?php if (!empty($rejected['rejection_reason'])): ?>
                                            <div class="text-danger">
                                                <i class="fas fa-comment-alt"></i> <?= esc($rejected['rejection_reason']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No reason provided</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-danger">Rejected</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Available Courses -->
    <?php if ((isset($current_period) && $current_period) || (isset($active_school_year) && $active_school_year && isset($available_courses) && !empty($available_courses))): ?>
        <?php if (empty($available_courses)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <h5>No Available Courses</h5>
                    <p class="text-muted">There are no courses available for enrollment in the current academic period.</p>
                    <p class="text-muted">Please contact the administrator to add courses.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Available Courses for Enrollment</h5>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form id="availableCoursesSearchForm" class="d-flex">
                                <div class="input-group">
                                    <input type="text" id="availableCoursesSearchInput" class="form-control" placeholder="Search by course title, description, or instructor..." name="search_term">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="availableCoursesContainer">
                        <div class="row g-3">
                            <?php foreach ($available_courses as $course): ?>
                                <div class="col-md-6 available-course-card" data-title="<?= strtolower(esc($course['title'])) ?>" data-description="<?= strtolower(esc($course['description'] ?? '')) ?>" data-instructor="<?= strtolower(esc($course['instructor_name'] ?? '')) ?>">
                                    <div class="card border-primary h-100">
                                        <div class="card-body">
                                            <h5 class="card-title text-primary"><?= esc($course['title']) ?></h5>
                                            <p class="card-text"><?= esc($course['description'] ?? 'No description available.') ?></p>
                                            <p class="card-text mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-chalkboard-teacher"></i> 
                                                    <strong>Instructor:</strong> <?= esc($course['instructor_name'] ?? 'Not assigned') ?>
                                                </small>
                                            </p>
                                            <p class="card-text mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> 
                                                    <strong>Time:</strong> <?= esc($course['time'] ?? 'N/A') ?>
                                                </small>
                                            </p>
                                            <p class="card-text mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-book"></i> 
                                                    <strong>Units:</strong> <span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span>
                                                </small>
                                            </p>
                                            <div class="d-flex gap-2">
                                                <a href="<?= site_url('/student/course/' . $course['id']) ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View Course
                                                </a>
                                                <form method="post" action="<?= site_url('/student/enroll/self-enroll') ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                    <input type="hidden" name="school_year_id" value="<?= isset($current_period) && isset($current_period['school_year']) ? $current_period['school_year']['id'] : (isset($active_school_year) ? $active_school_year['id'] : '') ?>">
                                                    <input type="hidden" name="semester" value="<?= isset($current_period) && isset($current_period['semester']) ? $current_period['semester']['semester_number'] : (isset($course['semester']) ? $course['semester'] : '') ?>">
                                                    <input type="hidden" name="term" value="<?= isset($current_period) && isset($current_period['term']) ? $current_period['term']['term_number'] : (isset($course['term']) ? $course['term'] : '') ?>">
                                                <button type="submit" class="btn btn-primary enroll-btn" data-course-id="<?= $course['id'] ?>">
                                                    <i class="fas fa-user-plus"></i> Enroll in Course
                                                </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Prevent double submission on enrollment forms
document.addEventListener('DOMContentLoaded', function() {
    const enrollForms = document.querySelectorAll('form[action*="/student/enroll/self-enroll"]');
    
    enrollForms.forEach(function(form) {
        const enrollBtn = form.querySelector('.enroll-btn');
        
        if (form && enrollBtn) {
            form.addEventListener('submit', function(e) {
                // Disable button and change text to prevent double clicks
                enrollBtn.disabled = true;
                enrollBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                // Let the form submit normally (don't prevent default)
            });
        }
    });

    // Enrolled Courses search functionality
    <?php if (isset($enrolled_courses) && !empty($enrolled_courses)): ?>
    const originalEnrolledCourses = $('#enrolledCoursesContainer').html();
    
    $('#enrolledCoursesSearchInput').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase().trim();
        
        if (searchValue === '') {
            $('#enrolledCoursesContainer').html(originalEnrolledCourses);
            return;
        }
        
        let visibleCount = 0;
        $('.enrolled-course-row').each(function() {
            const title = $(this).data('title') || '';
            const description = $(this).data('description') || '';
            const instructor = $(this).data('instructor') || '';
            const text = (title + ' ' + description + ' ' + instructor).toLowerCase();
            
            if (text.includes(searchValue)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        if (visibleCount === 0) {
            if ($('#noEnrolledResultsRow').length === 0) {
                $('#enrolledCoursesContainer').append('<tr id="noEnrolledResultsRow"><td colspan="7" class="text-center text-muted">No courses found matching your search.</td></tr>');
            }
        } else {
            $('#noEnrolledResultsRow').remove();
        }
    });
    
    $('#enrolledCoursesSearchForm').on('submit', function(e) {
        e.preventDefault();
        $('#enrolledCoursesSearchInput').trigger('keyup');
    });
    <?php endif; ?>

    // Available Courses search functionality
    <?php if (isset($available_courses) && !empty($available_courses)): ?>
    const originalAvailableCourses = $('#availableCoursesContainer').html();
    
    $('#availableCoursesSearchInput').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase().trim();
        
        if (searchValue === '') {
            $('#availableCoursesContainer').html(originalAvailableCourses);
            return;
        }
        
        let visibleCount = 0;
        $('.available-course-card').each(function() {
            const title = $(this).data('title') || '';
            const description = $(this).data('description') || '';
            const instructor = $(this).data('instructor') || '';
            const text = (title + ' ' + description + ' ' + instructor).toLowerCase();
            
            if (text.includes(searchValue)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        if (visibleCount === 0) {
            if ($('#noAvailableResults').length === 0) {
                $('#availableCoursesContainer').html('<div id="noAvailableResults" class="col-12 text-center py-5"><i class="fas fa-search fa-3x text-muted mb-3"></i><h5 class="text-muted">No courses found matching your search.</h5></div>');
            }
        } else {
            $('#noAvailableResults').remove();
        }
    });
    
    // Server-side search for available courses
    $('#availableCoursesSearchForm').on('submit', function(e) {
        e.preventDefault();
        const searchTerm = $('#availableCoursesSearchInput').val().trim();
        
        if (!searchTerm) {
            $('#availableCoursesContainer').html(originalAvailableCourses);
            return;
        }
        
        $('#availableCoursesContainer').html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-3">Searching...</p></div>');
        
        $.ajax({
            url: '<?= site_url('/courses/search') ?>',
            method: 'GET',
            data: { 
                search_term: searchTerm,
                context: 'student'
            },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .done(function(response) {
            $('#availableCoursesContainer').empty();
            const courses = response.courses || response || [];
            
            if (courses.length > 0) {
                const row = $('<div>').addClass('row g-3');
                $.each(courses, function(index, course) {
                    const courseCard = $('<div>')
                        .addClass('col-md-6 available-course-card')
                        .attr('data-title', (course.title || '').toLowerCase())
                        .attr('data-description', (course.description || '').toLowerCase())
                        .attr('data-instructor', (course.instructor_name || '').toLowerCase())
                        .html(
                            '<div class="card border-primary h-100">' +
                            '<div class="card-body">' +
                            '<h5 class="card-title text-primary">' + (course.title || '') + '</h5>' +
                            '<p class="card-text">' + (course.description || 'No description available.') + '</p>' +
                            '<p class="card-text mb-2"><small class="text-muted"><i class="fas fa-chalkboard-teacher"></i> <strong>Instructor:</strong> ' + (course.instructor_name || 'Not assigned') + '</small></p>' +
                            '<p class="card-text mb-2"><small class="text-muted"><i class="fas fa-clock"></i> <strong>Time:</strong> ' + (course.time || 'N/A') + '</small></p>' +
                            '<p class="card-text mb-2"><small class="text-muted"><i class="fas fa-book"></i> <strong>Units:</strong> <span class="badge bg-info">' + (course.units || '0') + ' units</span></small></p>' +
                            '<div class="d-flex gap-2">' +
                            '<a href="<?= site_url('/student/course/') ?>' + course.id + '" class="btn btn-outline-primary"><i class="fas fa-eye"></i> View Course</a>' +
                            '<form method="post" action="<?= site_url('/student/enroll/self-enroll') ?>" class="d-inline">' +
                            '<?= csrf_field() ?>' +
                            '<input type="hidden" name="course_id" value="' + course.id + '">' +
                            '<input type="hidden" name="school_year_id" value="<?= isset($current_period) && isset($current_period['school_year']) ? $current_period['school_year']['id'] : '' ?>">' +
                            '<input type="hidden" name="semester" value="<?= isset($current_period) && isset($current_period['semester']) ? $current_period['semester']['semester_number'] : '' ?>">' +
                            '<input type="hidden" name="term" value="<?= isset($current_period) && isset($current_period['term']) ? $current_period['term']['term_number'] : '' ?>">' +
                            '<button type="submit" class="btn btn-primary enroll-btn" data-course-id="' + course.id + '"><i class="fas fa-user-plus"></i> Enroll in Course</button>' +
                            '</form>' +
                            '</div>' +
                            '</div>' +
                            '</div>'
                        );
                    row.append(courseCard);
                });
                $('#availableCoursesContainer').html(row);
            } else {
                $('#availableCoursesContainer').html('<div class="col-12 text-center py-5"><i class="fas fa-search fa-3x text-muted mb-3"></i><h5 class="text-muted">No courses found matching your search.</h5></div>');
            }
        })
        .fail(function() {
            $('#availableCoursesContainer').html('<div class="col-12 text-center py-5"><div class="text-danger">Error loading search results. Please try again.</div></div>');
        });
    });
    <?php endif; ?>
});
</script>

<?= $this->endSection() ?>

