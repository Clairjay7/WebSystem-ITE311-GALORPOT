<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-teacher-assignments<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Teacher Assignment Management</h2>
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

    <!-- Create Assignment Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> Assign Teacher to Course</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= site_url('/admin/teacher-assignments/create') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= esc($teacher['name']) ?> (<?= esc($teacher['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>" 
                                    data-school-year="<?= $course['school_year_id'] ?>"
                                    data-semester="<?= $course['semester'] ?>"
                                    data-term="<?= $course['term'] ?>">
                                    <?= esc($course['title']) ?> (<?= esc($course['school_year'] ?? 'N/A') ?> - Semester <?= $course['semester'] ?>, Term <?= $course['term'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Course includes School Year, Semester, and Term information</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" id="assignTeacherBtn">
                            <i class="fas fa-save"></i> Assign Teacher
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> All Teacher Assignments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teacher</th>
                            <th>Course</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Term</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assignments)): ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?= $assignment['id'] ?></td>
                                    <td><?= esc($assignment['teacher_name']) ?></td>
                                    <td><?= esc($assignment['course_title']) ?></td>
                                    <td><?= esc($assignment['school_year']) ?></td>
                                    <td>Semester <?= $assignment['semester'] ?></td>
                                    <td>Term <?= $assignment['term'] ?></td>
                                    <td>
                                        <form method="post" action="<?= site_url('/admin/teacher-assignments/delete') ?>" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $assignment['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted">No assignments found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Prevent double submission on teacher assignment form
document.addEventListener('DOMContentLoaded', function() {
    const assignForm = document.querySelector('form[action*="/admin/teacher-assignments/create"]');
    const assignBtn = document.getElementById('assignTeacherBtn');
    
    if (assignForm && assignBtn) {
        assignForm.addEventListener('submit', function(e) {
            // Disable button and change text to prevent double clicks
            assignBtn.disabled = true;
            assignBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            // Let the form submit normally (don't prevent default)
        });
    }
});
</script>

<?= $this->endSection() ?>

