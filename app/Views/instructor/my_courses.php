<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-instructor-courses<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book"></i> My Courses</h2>
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
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Courses Assigned</h5>
                <p class="text-muted">You don't have any courses assigned for this academic period.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> My Assigned Courses</h5>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form id="searchForm" class="d-flex">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="Search by course title, CN, or description..." name="search_term">
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
                                <th>Time</th>
                                <th>Description</th>
                                <th>School Year</th>
                                <th>Semester</th>
                                <th>Term</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="coursesContainer">
                            <?php foreach ($courses as $course): ?>
                                <?php
                                $schoolYearModel = new \App\Models\SchoolYearModel();
                                $sy = $schoolYearModel->find($course['school_year_id']);
                                $schoolYear = $sy ? $sy['school_year'] : 'N/A';
                                ?>
                                <tr class="course-row" data-title="<?= strtolower(esc($course['title'])) ?>" data-control-number="<?= strtolower(esc($course['control_number'] ?? '')) ?>" data-description="<?= strtolower(esc($course['description'] ?? '')) ?>">
                                    <td><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></td>
                                    <td><strong><?= esc($course['title']) ?></strong></td>
                                    <td><span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span></td>
                                    <td><?= esc($course['time'] ?? 'N/A') ?></td>
                                    <td><?= esc($course['description'] ?? 'N/A') ?></td>
                                    <td><?= esc($schoolYear) ?></td>
                                    <td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
                                    <td>Term <?= $course['term'] ?? 'N/A' ?></td>
                                    <td><?= date('M d, Y', strtotime($course['created_at'])) ?></td>
                                    <td>
                                        <a href="<?= site_url('/materials/upload/' . $course['id']) ?>" class="btn btn-sm btn-info" title="Manage Materials">
                                            <i class="fas fa-file-alt"></i> Materials
                                        </a>
                                        <a href="<?= site_url('/instructor/course/' . $course['id']) ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-cog"></i> Manage Course
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
// My Assigned Courses search functionality
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
            const description = $(this).data('description') || '';
            const text = (title + ' ' + controlNumber + ' ' + description).toLowerCase();
            
            if (text.includes(searchValue)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        if (visibleCount === 0) {
            if ($('#noResultsRow').length === 0) {
                $('#coursesContainer').append('<tr id="noResultsRow"><td colspan="10" class="text-center text-muted">No courses found matching your search.</td></tr>');
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
        
        $('#coursesContainer').html('<tr><td colspan="10" class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</td></tr>');
        
        $.ajax({
            url: '<?= site_url('/courses/search') ?>',
            method: 'GET',
            data: { 
                search_term: searchTerm,
                context: 'instructor'
            },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .done(function(response) {
            $('#coursesContainer').empty();
            const courses = response.courses || response || [];
            
            if (courses.length > 0) {
                $.each(courses, function(index, course) {
                    const schoolYear = course.school_year || 'N/A';
                    const row = $('<tr>')
                        .addClass('course-row')
                        .attr('data-title', (course.title || '').toLowerCase())
                        .attr('data-control-number', (course.control_number || '').toLowerCase())
                        .attr('data-description', (course.description || '').toLowerCase())
                        .html(
                            '<td><strong>' + (course.control_number || 'N/A') + '</strong></td>' +
                            '<td><strong>' + (course.title || '') + '</strong></td>' +
                            '<td><span class="badge bg-info">' + (course.units || '0') + ' units</span></td>' +
                            '<td>' + (course.time || 'N/A') + '</td>' +
                            '<td>' + (course.description || 'N/A') + '</td>' +
                            '<td>' + schoolYear + '</td>' +
                            '<td>Semester ' + (course.semester || 'N/A') + '</td>' +
                            '<td>Term ' + (course.term || 'N/A') + '</td>' +
                            '<td>' + (course.created_at ? new Date(course.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A') + '</td>' +
                            '<td>' +
                            '<a href="<?= site_url('/materials/upload/') ?>' + course.id + '" class="btn btn-sm btn-info" title="Manage Materials">' +
                            '<i class="fas fa-file-alt"></i> Materials</a> ' +
                            '<a href="<?= site_url('/instructor/course/') ?>' + course.id + '" class="btn btn-sm btn-primary">' +
                            '<i class="fas fa-cog"></i> Manage Course</a>' +
                            '</td>'
                        );
                    $('#coursesContainer').append(row);
                });
            } else {
                $('#coursesContainer').html('<tr><td colspan="10" class="text-center text-muted">No courses found matching your search.</td></tr>');
            }
        })
        .fail(function() {
            $('#coursesContainer').html('<tr><td colspan="10" class="text-center text-danger">Error loading search results. Please try again.</td></tr>');
        });
    });
});
</script>

<?= $this->endSection() ?>

