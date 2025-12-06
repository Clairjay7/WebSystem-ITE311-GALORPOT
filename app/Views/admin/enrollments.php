<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-enrollments<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Enrollment Management</h2>
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

    <!-- Create Enrollment Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-user-plus"></i> Enroll Student</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= site_url('/admin/enrollments/create') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="user_id" class="form-label">Student <span class="text-danger">*</span></label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>"><?= esc($student['name']) ?> (<?= esc($student['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= esc($course['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="school_year_id" class="form-label">School Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="school_year_id" name="school_year_id" required>
                            <option value="">Select School Year</option>
                            <?php foreach ($school_years as $sy): ?>
                                <option value="<?= $sy['id'] ?>"><?= esc($sy['school_year']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="term" class="form-label">Term <span class="text-danger">*</span></label>
                        <select class="form-select" id="term" name="term" required>
                            <option value="">Select Term</option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enroll Student
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Enrollments List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> All Enrollments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Term</th>
                            <th>Enrollment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($enrollments)): ?>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td><?= $enrollment['id'] ?></td>
                                    <td><?= esc($enrollment['student_name']) ?></td>
                                    <td><?= esc($enrollment['course_title']) ?></td>
                                    <td><?= esc($enrollment['school_year'] ?? 'N/A') ?></td>
                                    <td>Semester <?= $enrollment['semester'] ?? 'N/A' ?></td>
                                    <td>Term <?= $enrollment['term'] ?? 'N/A' ?></td>
                                    <td><?= $enrollment['enrollment_date'] ? date('M d, Y', strtotime($enrollment['enrollment_date'])) : 'N/A' ?></td>
                                    <td>
                                        <form method="post" action="<?= site_url('/admin/enrollments/delete') ?>" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $enrollment['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted">No enrollments found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

