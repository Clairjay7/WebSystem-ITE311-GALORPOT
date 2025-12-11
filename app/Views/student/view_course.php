<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-student-course-view<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book"></i> <?= esc($course['title']) ?></h2>
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

    <?php if (isset($is_enrolled) && !$is_enrolled): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-info-circle"></i> <strong>Course Preview</strong> - You are viewing this course as a preview. 
            <a href="<?= site_url('/student/enroll') ?>" class="alert-link">Enroll now</a> to access course materials and full content.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Course Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Title:</dt>
                        <dd class="col-sm-9"><?= esc($course['title']) ?></dd>

                        <dt class="col-sm-3">Control Number (CN):</dt>
                        <dd class="col-sm-9"><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></dd>

                        <dt class="col-sm-3">Units:</dt>
                        <dd class="col-sm-9"><span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span></dd>

                        <dt class="col-sm-3">Time:</dt>
                        <dd class="col-sm-9"><?= esc($course['time'] ?? 'N/A') ?></dd>

                        <dt class="col-sm-3">Description:</dt>
                        <dd class="col-sm-9"><?= esc($course['description'] ?? 'No description provided') ?></dd>

                        <dt class="col-sm-3">Instructor:</dt>
                        <dd class="col-sm-9"><?= esc($instructor['name'] ?? 'N/A') ?></dd>

                        <dt class="col-sm-3">School Year:</dt>
                        <dd class="col-sm-9"><?= esc($school_year['school_year'] ?? 'N/A') ?></dd>

                        <dt class="col-sm-3">Semester:</dt>
                        <dd class="col-sm-9">Semester <?= $course['semester'] ?? 'N/A' ?></dd>

                        <dt class="col-sm-3">Term:</dt>
                        <dd class="col-sm-9">Term <?= $course['term'] ?? 'N/A' ?></dd>

                        <?php if (isset($term_start_date) && $term_start_date): ?>
                            <dt class="col-sm-3">Sem <?= $course['semester'] ?? 'N/A' ?> - Term <?= $course['term'] ?? 'N/A' ?> Start:</dt>
                            <dd class="col-sm-9">
                                <i class="fas fa-calendar-check text-success"></i> 
                                <strong><?= date('F d, Y', strtotime($term_start_date)) ?></strong>
                            </dd>
                        <?php endif; ?>

                        <?php if (isset($term_end_date) && $term_end_date): ?>
                            <dt class="col-sm-3">Sem <?= $course['semester'] ?? 'N/A' ?> - Term <?= $course['term'] ?? 'N/A' ?> End:</dt>
                            <dd class="col-sm-9">
                                <i class="fas fa-calendar-times text-warning"></i> 
                                <strong><?= date('F d, Y', strtotime($term_end_date)) ?></strong>
                            </dd>
                        <?php endif; ?>

                        <?php if (isset($is_enrolled) && $is_enrolled && isset($enrollment['enrollment_date'])): ?>
                            <dt class="col-sm-3">Enrollment Date:</dt>
                            <dd class="col-sm-9"><?= date('F d, Y', strtotime($enrollment['enrollment_date'])) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Course Materials Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> Course Materials</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($is_enrolled) && !$is_enrolled): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-lock"></i> <strong>Enrollment Required</strong><br>
                            You must be enrolled in this course to access materials. 
                            <a href="<?= site_url('/student/enroll') ?>" class="alert-link">Enroll now</a> to download course materials.
                        </div>
                    <?php elseif (empty($materials)): ?>
                        <p class="text-muted">No materials available yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Uploaded Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $material): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file"></i> <?= esc($material['file_name']) ?>
                                            </td>
                                            <td>
                                                <?= date('M d, Y H:i', strtotime($material['created_at'])) ?>
                                            </td>
                                            <td>
                                                <a href="<?= site_url('/materials/download/' . $material['id']) ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Course Content Sections -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Course Content</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100 border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                                    <h5>Course Materials</h5>
                                    <p class="text-muted">View course materials and resources</p>
                                    <a href="#materials" class="btn btn-primary">View Materials</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-bullhorn fa-3x text-info mb-3"></i>
                                    <h5>Announcements</h5>
                                    <p class="text-muted">View course announcements</p>
                                    <a href="#" class="btn btn-info">View Announcements</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-clipboard-check fa-3x text-success mb-3"></i>
                                    <h5>Assignments</h5>
                                    <p class="text-muted">View and submit assignments</p>
                                    <a href="#" class="btn btn-success">View Assignments</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-graduation-cap fa-3x text-warning mb-3"></i>
                                    <h5>Grades</h5>
                                    <p class="text-muted">View your grades</p>
                                    <a href="#" class="btn btn-warning">View Grades</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Course Stats</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($is_enrolled) && $is_enrolled && isset($enrollment['enrollment_date'])): ?>
                        <div class="mb-3">
                            <small class="text-muted">Enrollment Date</small>
                            <h6><?= date('M d, Y', strtotime($enrollment['enrollment_date'])) ?></h6>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Status</small>
                            <h6><span class="badge bg-success">Enrolled</span></h6>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <small class="text-muted">Status</small>
                            <h6><span class="badge bg-warning">Not Enrolled</span></h6>
                        </div>
                        <div class="mb-3">
                            <a href="<?= site_url('/student/enroll') ?>" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-user-plus"></i> Enroll Now
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($term_end_date) && $term_end_date): ?>
                        <div class="mb-3">
                            <small class="text-muted">Course End Date</small>
                            <h6>
                                <i class="fas fa-calendar-times text-warning"></i> 
                                <?= date('M d, Y', strtotime($term_end_date)) ?>
                            </h6>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Instructor</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong><?= esc($instructor['name'] ?? 'N/A') ?></strong></p>
                    <p class="text-muted small mb-0"><?= esc($instructor['email'] ?? 'N/A') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

