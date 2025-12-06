<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-courses<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Course Management</h2>
        <a href="<?= site_url('/dashboard') ?>" class="btn btn-secondary">Back to Dashboard</a>
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

    <!-- Create Course Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-plus"></i> Create New Course</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= site_url('/admin/courses/create') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Course Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= function_exists('set_value') ? set_value('title') : (old('title') ?? '') ?>" required>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['title'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['title'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= function_exists('set_value') ? set_value('description') : (old('description') ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="school_year_id" class="form-label">School Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="school_year_id" name="school_year_id" required>
                            <option value="">Select School Year</option>
                            <?php foreach ($school_years as $sy): ?>
                                <option value="<?= $sy['id'] ?>" <?= (function_exists('set_value') ? set_value('school_year_id') : (old('school_year_id') ?? '')) == $sy['id'] ? 'selected' : '' ?>><?= esc($sy['school_year']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['school_year_id'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['school_year_id'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="1" <?= (function_exists('set_value') ? set_value('semester') : (old('semester') ?? '')) == '1' ? 'selected' : '' ?>>Semester 1</option>
                            <option value="2" <?= (function_exists('set_value') ? set_value('semester') : (old('semester') ?? '')) == '2' ? 'selected' : '' ?>>Semester 2</option>
                        </select>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['semester'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['semester'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="term" class="form-label">Term <span class="text-danger">*</span></label>
                        <select class="form-select" id="term" name="term" required>
                            <option value="">Select Term</option>
                            <option value="1" <?= (function_exists('set_value') ? set_value('term') : (old('term') ?? '')) == '1' ? 'selected' : '' ?>>Term 1</option>
                            <option value="2" <?= (function_exists('set_value') ? set_value('term') : (old('term') ?? '')) == '2' ? 'selected' : '' ?>>Term 2</option>
                        </select>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['term'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['term'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" id="createCourseBtn">
                            <i class="fas fa-save"></i> Create Course
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Courses List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> All Courses</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Term</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= $course['id'] ?></td>
                                    <td><?= esc($course['title']) ?></td>
                                    <td><?= esc($course['school_year'] ?? 'N/A') ?></td>
                                    <td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
                                    <td>Term <?= $course['term'] ?? 'N/A' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editCourse(<?= htmlspecialchars(json_encode($course)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="post" action="<?= site_url('/admin/courses/delete') ?>" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $course['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No courses found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= site_url('/admin/courses/update') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_course_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_school_year_id" class="form-label">School Year</label>
                        <select class="form-select" id="edit_school_year_id" name="school_year_id" required>
                            <?php foreach ($school_years as $sy): ?>
                                <option value="<?= $sy['id'] ?>"><?= esc($sy['school_year']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="edit_semester" class="form-label">Semester</label>
                            <select class="form-select" id="edit_semester" name="semester" required>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_term" class="form-label">Term</label>
                            <select class="form-select" id="edit_term" name="term" required>
                                <option value="1">Term 1</option>
                                <option value="2">Term 2</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCourse(course) {
    document.getElementById('edit_course_id').value = course.id;
    document.getElementById('edit_title').value = course.title || '';
    document.getElementById('edit_description').value = course.description || '';
    document.getElementById('edit_school_year_id').value = course.school_year_id || '';
    document.getElementById('edit_semester').value = course.semester || '';
    document.getElementById('edit_term').value = course.term || '';
    new bootstrap.Modal(document.getElementById('editCourseModal')).show();
}

// Prevent double submission on course creation form
document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.querySelector('form[action*="/admin/courses/create"]');
    const createBtn = document.getElementById('createCourseBtn');
    
    if (createForm && createBtn) {
        createForm.addEventListener('submit', function(e) {
            // Disable button and change text to prevent double clicks
            createBtn.disabled = true;
            createBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            // Let the form submit normally (don't prevent default)
        });
    }
});
</script>

<?= $this->endSection() ?>

