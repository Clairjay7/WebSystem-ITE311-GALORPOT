<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-instructor-course-view<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book"></i> <?= esc($course['title']) ?></h2>
        <a href="<?= site_url('/instructor/my-courses') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to My Courses
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

                        <dt class="col-sm-3">Description:</dt>
                        <dd class="col-sm-9"><?= esc($course['description'] ?? 'No description provided') ?></dd>

                        <dt class="col-sm-3">School Year:</dt>
                        <dd class="col-sm-9">
                            <?php
                            $schoolYearModel = new \App\Models\SchoolYearModel();
                            $sy = $schoolYearModel->find($course['school_year_id']);
                            echo $sy ? esc($sy['school_year']) : 'N/A';
                            ?>
                        </dd>

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

                        <dt class="col-sm-3">Created:</dt>
                        <dd class="col-sm-9"><?= date('F d, Y', strtotime($course['created_at'])) ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Pending Enrollment Requests -->
            <?php if (isset($pending_enrollments) && !empty($pending_enrollments)): ?>
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-clock"></i> Pending Enrollment Requests (<?= count($pending_enrollments) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Email</th>
                                        <th>Request Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_enrollments as $pending): ?>
                                        <tr>
                                            <td><?= esc($pending['student_name']) ?></td>
                                            <td><?= esc($pending['student_email']) ?></td>
                                            <td><?= date('M d, Y', strtotime($pending['enrollment_date'])) ?></td>
                                            <td>
                                                <form method="post" action="<?= site_url('/instructor/approve-enrollment') ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="enrollment_id" value="<?= $pending['id'] ?>">
                                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $pending['id'] ?>">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                                
                                                <!-- Reject Modal -->
                                                <div class="modal fade" id="rejectModal<?= $pending['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="post" action="<?= site_url('/instructor/reject-enrollment') ?>">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="enrollment_id" value="<?= $pending['id'] ?>">
                                                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Reject Enrollment Request</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to reject this enrollment request?</p>
                                                                    <div class="mb-3">
                                                                        <label for="rejection_reason_<?= $pending['id'] ?>" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                                        <textarea class="form-control" id="rejection_reason_<?= $pending['id'] ?>" name="rejection_reason" rows="3" required placeholder="Please provide a reason for rejecting this enrollment request..."></textarea>
                                                                        <small class="form-text text-muted">This reason will be visible to the student.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Reject Enrollment</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Enroll Students Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Enroll Students</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= site_url('/instructor/enroll-student') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="student_id" class="form-label">Select Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Choose a student...</option>
                                    <?php foreach ($students as $student): ?>
                                        <?php
                                        // Check if student is already enrolled (approved or pending)
                                        $isEnrolled = false;
                                        foreach ($enrollments as $enrollment) {
                                            if ($enrollment['user_id'] == $student['id']) {
                                                $isEnrolled = true;
                                                break;
                                            }
                                        }
                                        // Also check pending enrollments
                                        if (!$isEnrolled && isset($pending_enrollments)) {
                                            foreach ($pending_enrollments as $pending) {
                                                if ($pending['user_id'] == $student['id']) {
                                                    $isEnrolled = true;
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        <?php if (!$isEnrolled): ?>
                                            <option value="<?= $student['id'] ?>"><?= esc($student['name']) ?> (<?= esc($student['email']) ?>)</option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus"></i> Enroll Student
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Enrolled Students List -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Enrolled Students (<?= count($enrollments) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($enrollments)): ?>
                        <p class="text-muted text-center py-3">No students enrolled yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Email</th>
                                        <th>Enrollment Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?= esc($enrollment['student_name']) ?></td>
                                            <td><?= esc($enrollment['student_email']) ?></td>
                                            <td><?= date('M d, Y', strtotime($enrollment['enrollment_date'])) ?></td>
                                            <td>
                                                <form method="post" action="<?= site_url('/instructor/unenroll-student') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to unenroll this student?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>">
                                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-user-minus"></i> Unenroll
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Course Management Sections -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Course Management</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100 border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                                    <h5>Course Materials</h5>
                                    <p class="text-muted">Upload and manage course materials</p>
                                    <a href="#" class="btn btn-primary">Manage Materials</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-bullhorn fa-3x text-info mb-3"></i>
                                    <h5>Announcements</h5>
                                    <p class="text-muted">Post announcements to students</p>
                                    <a href="#" class="btn btn-info">Manage Announcements</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-clipboard-check fa-3x text-success mb-3"></i>
                                    <h5>Assignments</h5>
                                    <p class="text-muted">Create and manage assignments</p>
                                    <a href="#" class="btn btn-success">Manage Assignments</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-graduation-cap fa-3x text-warning mb-3"></i>
                                    <h5>Grades</h5>
                                    <p class="text-muted">View and manage student grades</p>
                                    <a href="#" class="btn btn-warning">Manage Grades</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Enrolled Students</small>
                        <h4><?= count($enrollments) ?></h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Active Assignments</small>
                        <h4>0</h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Announcements</small>
                        <h4>0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

