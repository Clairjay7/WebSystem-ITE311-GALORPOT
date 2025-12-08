<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-courses<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book"></i> My Courses</h2>
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
                <h5 class="text-muted">No Enrolled Courses</h5>
                <p class="text-muted">You don't have any enrolled courses yet.</p>
                <a href="<?= site_url('/student/enroll') ?>" class="btn btn-primary mt-3">
                    <i class="fas fa-user-plus"></i> Enroll in Courses
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> All My Enrolled Courses</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>CN</th>
                                <th>Course</th>
                                <th>Units</th>
                                <th>Instructor</th>
                                <th>Description</th>
                                <th>School Year</th>
                                <th>Semester</th>
                                <th>Term</th>
                                <th>Enrollment Date</th>
                                <th>Course End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr class="<?= isset($course['is_expired']) && $course['is_expired'] ? 'table-secondary' : '' ?>">
                                    <td><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></td>
                                    <td><strong><?= esc($course['title']) ?></strong></td>
                                    <td><span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span></td>
                                    <td><?= esc($course['instructor_name'] ?? 'N/A') ?></td>
                                    <td><?= esc($course['description'] ?? 'N/A') ?></td>
                                    <td><?= esc($course['school_year'] ?? 'N/A') ?></td>
                                    <td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
                                    <td>Term <?= $course['term'] ?? 'N/A' ?></td>
                                    <td><?= date('M d, Y', strtotime($course['enrollment_date'])) ?></td>
                                    <td>
                                        <?php if (isset($course['term_end_date'])): ?>
                                            <i class="fas fa-calendar-times text-warning"></i> <?= date('M d, Y', strtotime($course['term_end_date'])) ?>
                                            <?php if (isset($course['is_expired']) && $course['is_expired']): ?>
                                                <br><small class="text-danger">(Expired)</small>
                                            <?php endif; ?>
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
</div>

<?= $this->endSection() ?>

