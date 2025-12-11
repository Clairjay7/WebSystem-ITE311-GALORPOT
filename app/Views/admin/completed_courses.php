<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-completed-courses<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-check-circle"></i> Completed Courses</h2>
        <a href="<?= site_url('/dashboard') ?>" class="btn btn-secondary">
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

    <?php if (isset($active_school_year) && $active_school_year): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>School Year:</strong> <?= esc($active_school_year['school_year']) ?>
            <?php if (isset($current_period) && $current_period): ?>
                <br><small>Current: Semester <?= $current_period['semester']['semester_number'] ?> - Term <?= $current_period['term']['term_number'] ?></small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= esc($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($courses)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Completed Courses</h5>
                <p class="text-muted">There are no completed courses yet.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> These courses have been completed. You can still view them for reference.
        </div>
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Completed Courses (<?= count($courses) ?>)</h5>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form id="searchForm" class="d-flex">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="Search by course title, CN, instructor, or school year..." name="search_term">
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
                                <th>CN</th>
                                <th>Course Title</th>
                                <th>Units</th>
                                <th>Instructor</th>
                                <th>School Year</th>
                                <th>Semester</th>
                                <th>Term</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="coursesContainer">
                            <?php foreach ($courses as $course): ?>
                                <tr class="table-secondary course-row" data-title="<?= strtolower(esc($course['title'])) ?>" data-control-number="<?= strtolower(esc($course['control_number'] ?? '')) ?>" data-instructor="<?= strtolower(esc($course['instructor_name'] ?? '')) ?>" data-school-year="<?= strtolower(esc($course['school_year'] ?? '')) ?>">
                                    <td><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></td>
                                    <td><strong><?= esc($course['title']) ?></strong></td>
                                    <td><span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span></td>
                                    <td><?= esc($course['instructor_name'] ?? 'N/A') ?></td>
                                    <td><strong><?= esc($course['school_year'] ?? 'N/A') ?></strong></td>
                                    <td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
                                    <td>Term <?= $course['term'] ?? 'N/A' ?></td>
                                    <td>
                                        <?php if (isset($course['term_start_date'])): ?>
                                            <i class="fas fa-calendar-check text-success"></i> <?= date('M d, Y', strtotime($course['term_start_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($course['term_end_date'])): ?>
                                            <i class="fas fa-calendar-times text-warning"></i> <strong><?= date('M d, Y', strtotime($course['term_end_date'])) ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary">COMPLETED</span></td>
                                    <td>
                                        <a href="<?= site_url('/admin/courses') ?>" class="btn btn-sm btn-outline-primary">
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
</div>

<script>
// Completed courses search functionality
$(document).ready(function() {
    const originalCourses = $('#coursesContainer').html();
    
    // Instant client-side filtering
    $('#searchInput').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase().trim();
        
        if (searchValue === '') {
            $('#coursesContainer').html(originalCourses);
            return;
        }
        
        let visibleCount = 0;
        $('.course-row').each(function() {
            const title = $(this).data('title') || '';
            const controlNumber = $(this).data('control-number') || '';
            const instructor = $(this).data('instructor') || '';
            const schoolYear = $(this).data('school-year') || '';
            const text = (title + ' ' + controlNumber + ' ' + instructor + ' ' + schoolYear).toLowerCase();
            
            if (text.includes(searchValue)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        if (visibleCount === 0) {
            if ($('#noResultsRow').length === 0) {
                $('#coursesContainer').append('<tr id="noResultsRow" class="table-secondary"><td colspan="11" class="text-center text-muted">No courses found matching your search.</td></tr>');
            }
        } else {
            $('#noResultsRow').remove();
        }
    });
    
    // Server-side search with AJAX
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        const searchTerm = $('#searchInput').val().trim();
        
        if (!searchTerm) {
            $('#coursesContainer').html(originalCourses);
            return;
        }
        
        $('#coursesContainer').html('<tr class="table-secondary"><td colspan="11" class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</td></tr>');
        
        $.ajax({
            url: '<?= site_url('/courses/search') ?>',
            method: 'GET',
            data: { 
                search_term: searchTerm,
                context: 'admin'
            },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .done(function(response) {
            $('#coursesContainer').empty();
            const courses = response.courses || response || [];
            
            // Filter to only show completed courses (where term end date has passed)
            const today = new Date().toISOString().split('T')[0];
            const completedCourses = courses.filter(function(course) {
                // This is a simplified check - in real scenario, you'd check term end date
                return true; // For now, show all search results
            });
            
            if (completedCourses.length > 0) {
                $.each(completedCourses, function(index, course) {
                    const startDate = course.term_start_date ? new Date(course.term_start_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A';
                    const endDate = course.term_end_date ? new Date(course.term_end_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A';
                    
                    const row = $('<tr>')
                        .addClass('table-secondary course-row')
                        .attr('data-title', (course.title || '').toLowerCase())
                        .attr('data-control-number', (course.control_number || '').toLowerCase())
                        .attr('data-instructor', (course.instructor_name || '').toLowerCase())
                        .attr('data-school-year', (course.school_year || '').toLowerCase())
                        .html(
                            '<td><strong>' + (course.control_number || 'N/A') + '</strong></td>' +
                            '<td><strong>' + (course.title || '') + '</strong></td>' +
                            '<td><span class="badge bg-info">' + (course.units || '0') + ' units</span></td>' +
                            '<td>' + (course.instructor_name || 'N/A') + '</td>' +
                            '<td><strong>' + (course.school_year || 'N/A') + '</strong></td>' +
                            '<td>Semester ' + (course.semester || 'N/A') + '</td>' +
                            '<td>Term ' + (course.term || 'N/A') + '</td>' +
                            '<td><i class="fas fa-calendar-check text-success"></i> ' + startDate + '</td>' +
                            '<td><i class="fas fa-calendar-times text-warning"></i> <strong>' + endDate + '</strong></td>' +
                            '<td><span class="badge bg-secondary">COMPLETED</span></td>' +
                            '<td><a href="<?= site_url('/admin/courses') ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View Course</a></td>'
                        );
                    $('#coursesContainer').append(row);
                });
            } else {
                $('#coursesContainer').html('<tr class="table-secondary"><td colspan="11" class="text-center text-muted">No completed courses found matching your search.</td></tr>');
            }
        })
        .fail(function() {
            $('#coursesContainer').html('<tr class="table-secondary"><td colspan="11" class="text-center text-danger">Error loading search results. Please try again.</td></tr>');
        });
    });
});
</script>

<?= $this->endSection() ?>

