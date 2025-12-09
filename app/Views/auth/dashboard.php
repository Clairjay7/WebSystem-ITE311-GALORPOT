<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$name = session('name') ?? 'User';
$email = session('email') ?? '';
$role = strtolower((string) (session('role') ?? 'student'));

$roleToLabel = [
	'student'    => 'Student',
	'instructor' => 'Instructor',
	'admin'      => 'Admin',
];

$roleLabel = $roleToLabel[$role] ?? ucfirst($role);

$roleToLogo = [
	'student'    => 'https://pnghq.com/wp-content/uploads/student-icon-png-free-highres-download-94160.png',
	'instructor' => 'https://static.vecteezy.com/system/resources/previews/016/305/941/original/instructor-icon-design-free-vector.jpg',
	'admin'      => 'https://static.vecteezy.com/system/resources/previews/000/290/610/original/administration-vector-icon.jpg',
];

$logo = $roleToLogo[$role] ?? $roleToLogo['student'];
?>

<div class="container py-4">
	<!-- DEBUG INFO -->
	<div class="alert alert-info small">
		<strong>Debug:</strong> 
		Logged In: <?= session()->get('isLoggedIn') ? 'YES' : 'NO' ?> | 
		Role: <?= session()->get('role') ?? 'NONE' ?> | 
		Name: <?= session()->get('name') ?? 'NONE' ?>
	</div>

	<?php if (session()->getFlashdata('welcome')): ?>
		<div class="alert alert-success mb-3"><?= session()->getFlashdata('welcome') ?></div>
	<?php endif; ?>
	<?php if (session()->getFlashdata('success')): ?>
		<div class="alert alert-success mb-3"><?= session()->getFlashdata('success') ?></div>
	<?php endif; ?>
	<?php if (session()->getFlashdata('error')): ?>
		<div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
	<?php endif; ?>

	<?php if (isset($migration_warning)): ?>
		<div class="alert alert-warning mb-3">
			<strong>Setup Required:</strong> <?= esc($migration_warning) ?>
			<br><small>After running migrations, go to <a href="<?= site_url('/academic') ?>">Academic Management</a> to create a school year.</small>
		</div>
	<?php endif; ?>

	<!-- Current Academic Period / Active School Year -->
	<?php if (isset($current_period) && $current_period): ?>
		<div class="card border-success mb-3">
			<div class="card-header bg-success text-white">
				<h5 class="mb-0"><i class="fas fa-calendar-check"></i> Active Academic Period</h5>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-4">
						<strong>School Year:</strong><br>
						<h4 class="text-primary"><?= esc($current_period['school_year']['school_year']) ?></h4>
					</div>
					<div class="col-md-4">
						<strong>Semester:</strong><br>
						<h4 class="text-info">Semester <?= $current_period['semester']['semester_number'] ?></h4>
					</div>
					<div class="col-md-4">
						<strong>Term:</strong><br>
						<h4 class="text-warning">Term <?= $current_period['term']['term_number'] ?></h4>
					</div>
				</div>
				<hr>
				<div class="row">
					<div class="col-md-6">
						<strong>Term Start:</strong> <?= date('F d, Y', strtotime($current_period['term']['start_date'])) ?>
					</div>
					<div class="col-md-6">
						<strong>Term End:</strong> <?= date('F d, Y', strtotime($current_period['term']['end_date'])) ?>
					</div>
				</div>
				<?php if ($role === 'student'): ?>
					<hr>
					<div class="text-center">
						<a href="<?= site_url('/student/enroll') ?>" class="btn btn-success btn-lg">
							<i class="fas fa-user-plus"></i> Enroll in Courses
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php elseif (isset($active_school_year) && $active_school_year): ?>
		<div class="card border-primary mb-3">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Active School Year</h5>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-12 mb-3">
						<strong>School Year:</strong>
						<h3 class="text-primary mb-0"><?= esc($active_school_year['school_year']) ?></h3>
						<?php if (isset($active_school_year_period) && $active_school_year_period): ?>
							<div class="alert alert-info mt-2 mb-0">
								<strong><i class="fas fa-calendar-range"></i> School Year Period:</strong><br>
								<strong>Start:</strong> <?= date('F d, Y', strtotime($active_school_year_period['start_date'])) ?> (Semester 1 Term 1)<br>
								<strong>End:</strong> <?= date('F d, Y', strtotime($active_school_year_period['end_date'])) ?> (Semester 2 Term 2)
							</div>
						<?php else: ?>
							<small class="text-muted">This school year is currently active. Term dates will be set by the administrator.</small>
						<?php endif; ?>
					</div>
				</div>
				<?php if ($role === 'student'): ?>
					<hr>
					<div class="text-center">
						<a href="<?= site_url('/student/enroll') ?>" class="btn btn-primary btn-lg">
							<i class="fas fa-user-plus"></i> Enroll in Courses for This School Year
						</a>
					</div>
				<?php endif; ?>
				<?php if (isset($active_school_year_semesters) && !empty($active_school_year_semesters)): ?>
					<hr>
					<h6><strong>Available Semesters and Terms:</strong></h6>
					<div class="row">
						<?php foreach ($active_school_year_semesters as $sem): ?>
							<div class="col-md-6 mb-3">
								<div class="card border-info">
									<div class="card-header bg-info text-white py-2">
										<strong>Semester <?= $sem['semester_number'] ?></strong>
									</div>
									<div class="card-body p-2">
										<?php 
										$termModel = new \App\Models\TermModel();
										$terms = $termModel->where('semester_id', $sem['id'])->orderBy('term_number', 'ASC')->findAll();
										?>
										<?php if (!empty($terms)): ?>
											<?php foreach ($terms as $term): ?>
												<div class="mb-2">
													<strong>Term <?= $term['term_number'] ?>:</strong>
													<?php if ($term['start_date'] && $term['end_date']): ?>
														<br><small>
															<?= date('M d, Y', strtotime($term['start_date'])) ?> - 
															<?= date('M d, Y', strtotime($term['end_date'])) ?>
														</small>
													<?php else: ?>
														<br><small class="text-muted">Dates to be set</small>
													<?php endif; ?>
												</div>
											<?php endforeach; ?>
										<?php else: ?>
											<small class="text-muted">Terms to be created</small>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php else: ?>
		<div class="alert alert-warning mb-3">
			<strong><i class="fas fa-exclamation-triangle"></i> No Active School Year</strong><br>
			<small>Please contact the administrator to set up the academic structure.</small>
		</div>
	<?php endif; ?>

    <div class="card">
		<div class="card-body p-4">
			<div class="d-flex align-items-center gap-3 mb-3">
				<img src="<?= esc($logo) ?>" alt="Role" class="rounded bg-white p-2" width="64" height="64">
				<div>
					<h3 class="mb-1">Welcome, <?= esc($name) ?>!</h3>
					<span class="badge bg-primary text-uppercase"><?= esc($roleLabel) ?></span>
				</div>
			</div>
			<div class="row g-3">
				<div class="col-md-6">
					<div class="border rounded p-2 bg-light"><strong>Email:</strong> <?= esc($email) ?></div>
				</div>
				<div class="col-md-6">
					<div class="border rounded p-2 bg-light"><strong>Role:</strong> <?= esc($roleLabel) ?></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Conditional content per role -->
	<?php if ($role === 'student'): ?>
		<?php if (isset($active_school_year) && $active_school_year): ?>
			<div class="alert alert-success mt-3">
				<i class="fas fa-check-circle"></i> <strong>School Year <?= esc($active_school_year['school_year']) ?> is now open!</strong>
				<br><small>You can now enroll in courses for this academic period.</small>
			</div>
		<?php endif; ?>
		
		<!-- Pending Enrollment Requests -->
		<?php if (isset($pending_enrollments) && !empty($pending_enrollments)): ?>
			<div class="card mt-4 border-warning">
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
									<th>Description</th>
									<th>Request Date</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($pending_enrollments as $pending): ?>
									<tr>
										<td><strong><?= esc($pending['control_number'] ?? 'N/A') ?></strong></td>
										<td><strong><?= esc($pending['course_title']) ?></strong></td>
										<td><span class="badge bg-info"><?= esc($pending['units'] ?? '0') ?> units</span></td>
										<td><?= esc($pending['instructor_name'] ?? 'N/A') ?></td>
										<td><?= esc($pending['description'] ?? 'N/A') ?></td>
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
			<div class="card mt-4 border-danger">
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
									<th>Description</th>
									<th>Request Date</th>
									<th>Rejection Reason</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($rejected_enrollments as $rejected): ?>
									<tr>
										<td><strong><?= esc($rejected['course_title']) ?></strong></td>
										<td><?= esc($rejected['description'] ?? 'N/A') ?></td>
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

		<!-- Approved Enrolled Courses -->
		<?php if (isset($enrollments) && !empty($enrollments)): ?>
			<div class="card mt-4">
				<div class="card-header bg-success text-white">
					<h5 class="mb-0"><i class="fas fa-check-circle"></i> My Enrolled Courses (Approved)</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>Course</th>
									<th>Description</th>
									<th>Time</th>
									<th>Enrollment Date</th>
									<th>Course End Date</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($enrollments as $enrollment): ?>
									<tr>
										<td><?= esc($enrollment['course_title']) ?></td>
										<td><?= esc($enrollment['description'] ?? 'N/A') ?></td>
										<td><?= esc($enrollment['time'] ?? 'N/A') ?></td>
										<td><?= date('M d, Y', strtotime($enrollment['enrollment_date'])) ?></td>
										<td>
											<?php if (isset($enrollment['term_end_date'])): ?>
												<i class="fas fa-calendar-times text-warning"></i> <?= date('M d, Y', strtotime($enrollment['term_end_date'])) ?>
											<?php else: ?>
												<span class="text-muted">N/A</span>
											<?php endif; ?>
										</td>
										<td>
											<a href="<?= site_url('/student/course/' . $enrollment['course_id']) ?>" class="btn btn-sm btn-primary">
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
		
		<?php 
		$hasNoEnrollments = (empty($enrollments) || !isset($enrollments));
		$hasNoPending = (empty($pending_enrollments) || !isset($pending_enrollments));
		$hasNoRejected = (empty($rejected_enrollments) || !isset($rejected_enrollments));
		if (isset($student_message) && $hasNoEnrollments && $hasNoPending && $hasNoRejected): 
		?>
			<div class="alert alert-info mt-4"><?= esc($student_message) ?></div>
		<?php endif; ?>
		<div class="row g-3 mt-4">
			<div class="col-md-4">
				<div class="card h-100"><div class="card-body"><h5 class="card-title mb-2">Courses</h5><p class="text-muted mb-3">Your enrolled subjects and materials</p><a href="<?= site_url('/student/courses') ?>" class="btn btn-sm btn-outline-primary">Open</a></div></div>
			</div>
			<div class="col-md-4">
				<div class="card h-100"><div class="card-body"><h5 class="card-title mb-2">Announcements</h5><p class="text-muted mb-3">Course announcements and updates</p><a href="<?= site_url('/student/announcements') ?>" class="btn btn-sm btn-outline-primary">Open</a></div></div>
			</div>
			<div class="col-md-4">
				<div class="card h-100"><div class="card-body"><h5 class="card-title mb-2">Grades</h5><p class="text-muted mb-3">Exam scores and performance</p><a href="<?= site_url('/student/grades') ?>" class="btn btn-sm btn-outline-primary">Open</a></div></div>
			</div>
		</div>
	<?php elseif ($role === 'instructor'): ?>
		<?php if (isset($active_school_year) && $active_school_year): ?>
			<div class="alert alert-success mt-3">
				<i class="fas fa-check-circle"></i> <strong>School Year <?= esc($active_school_year['school_year']) ?> is now open!</strong>
				<br><small>You can now be assigned to courses for this academic period.</small>
			</div>
		<?php endif; ?>
		
		<?php if (isset($instructor_message)): ?>
			<div class="alert alert-info mt-4"><?= esc($instructor_message) ?></div>
		<?php elseif (isset($assigned_courses) && !empty($assigned_courses)): ?>
			<div class="card mt-4">
				<div class="card-header">
					<h5 class="mb-0">My Assigned Courses</h5>
				</div>
				<div class="card-body">
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
							<tbody>
								<?php foreach ($assigned_courses as $course): ?>
									<tr>
										<td><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></td>
										<td><strong><?= esc($course['title']) ?></strong></td>
										<td><span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span></td>
										<td><?= esc($course['time'] ?? 'N/A') ?></td>
										<td><?= esc($course['description'] ?? 'N/A') ?></td>
										<td>
											<?php
											$schoolYearModel = new \App\Models\SchoolYearModel();
											$sy = $schoolYearModel->find($course['school_year_id'] ?? null);
											echo $sy ? esc($sy['school_year']) : 'N/A';
											?>
										</td>
										<td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
										<td>Term <?= $course['term'] ?? 'N/A' ?></td>
										<td><?= isset($course['created_at']) ? date('M d, Y', strtotime($course['created_at'])) : 'N/A' ?></td>
										<td>
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
		
		<!-- Completed Courses -->
		<?php if (isset($completed_courses) && !empty($completed_courses)): ?>
			<div class="card mt-4 border-secondary">
				<div class="card-header bg-secondary text-white">
					<h5 class="mb-0"><i class="fas fa-check-circle"></i> Completed Courses</h5>
				</div>
				<div class="card-body">
					<div class="alert alert-info mb-3">
						<i class="fas fa-info-circle"></i> These courses have been completed. You can still view them for reference.
					</div>
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>CN</th>
									<th>Course</th>
									<th>Units</th>
									<th>School Year</th>
									<th>Semester</th>
									<th>Term</th>
									<th>End Date</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($completed_courses as $course): ?>
									<tr class="table-secondary">
										<td><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></td>
										<td><?= esc($course['title']) ?></td>
										<td><span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span></td>
										<td><?= esc($course['school_year'] ?? 'N/A') ?></td>
										<td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
										<td>Term <?= $course['term'] ?? 'N/A' ?></td>
										<td>
											<?php if (isset($course['term_end_date'])): ?>
												<i class="fas fa-calendar-times text-warning"></i> <?= date('M d, Y', strtotime($course['term_end_date'])) ?>
											<?php else: ?>
												<span class="text-muted">N/A</span>
											<?php endif; ?>
										</td>
										<td><span class="badge bg-secondary">COMPLETED</span></td>
										<td>
											<a href="<?= site_url('/instructor/course/' . $course['id']) ?>" class="btn btn-sm btn-outline-primary">
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
		<div class="row g-3 mt-4">
			<div class="col-md-4">
				<div class="card h-100"><div class="card-body"><h5 class="card-title mb-2">My Classes</h5><p class="text-muted mb-3">Manage classes and materials</p><a href="<?= site_url('/instructor/my-courses') ?>" class="btn btn-sm btn-outline-primary">Open</a></div></div>
			</div>
			<div class="col-md-4">
				<div class="card h-100"><div class="card-body"><h5 class="card-title mb-2">Completed Courses</h5><p class="text-muted mb-3">View completed courses and history</p><a href="<?= site_url('/instructor/completed-courses') ?>" class="btn btn-sm btn-outline-secondary">Open</a></div></div>
			</div>
			<div class="col-md-4">
				<div class="card h-100"><div class="card-body"><h5 class="card-title mb-2">Submissions</h5><p class="text-muted mb-3">Review and grade student work</p><a href="<?= site_url('/instructor/submissions') ?>" class="btn btn-sm btn-outline-primary">Open</a></div></div>
			</div>
			<div class="col-md-4">
				<div class="card h-100"><div class="card-body"><h5 class="card-title mb-2">Attendance</h5><p class="text-muted mb-3">Record and track attendance</p><a href="<?= site_url('/instructor/attendance') ?>" class="btn btn-sm btn-outline-primary">Open</a></div></div>
			</div>
		</div>
	<?php elseif ($role === 'admin'): ?>
		<!-- Admin Cards -->
		<div class="row g-3 mt-4">
			<div class="col-md-3">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title mb-2">Academic Structure</h5>
						<p class="text-muted mb-3">Manage school years, semesters, and terms</p>
						<a href="<?= site_url('/academic') ?>" class="btn btn-sm btn-outline-primary">
							Open
						</a>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title mb-2">Course Management</h5>
						<p class="text-muted mb-3">Create and manage courses</p>
						<a href="<?= site_url('/admin/courses') ?>" class="btn btn-sm btn-outline-primary">
							Open
						</a>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title mb-2">Teacher Assignments</h5>
						<p class="text-muted mb-3">Assign teachers to courses</p>
						<a href="<?= site_url('/admin/teacher-assignments') ?>" class="btn btn-sm btn-outline-primary">
							Open
						</a>
					</div>
				</div>
			</div>
		</div>
		<div class="row g-3 mt-2">
			<div class="col-md-4">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title mb-2">User Management</h5>
						<p class="text-muted mb-3">Create, update, and assign roles</p>
						<button class="btn btn-sm btn-outline-primary" onclick="toggleSection('userManagementSection')">
							Open
						</button>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title mb-2">Completed Courses</h5>
						<p class="text-muted mb-3">View completed courses and history</p>
						<a href="<?= site_url('/admin/completed-courses') ?>" class="btn btn-sm btn-outline-secondary">
							Open
						</a>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title mb-2">Reports</h5>
						<p class="text-muted mb-3">System and academic reports</p>
						<button class="btn btn-sm btn-outline-primary" disabled>
							Coming Soon
						</button>
					</div>
				</div>
			</div>
		</div>
		<div class="row g-3 mt-2">
			<div class="col-md-4">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title mb-2">Settings</h5>
						<p class="text-muted mb-3">Configure site preferences</p>
						<button class="btn btn-sm btn-outline-primary" disabled>
							Coming Soon
						</button>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Completed Courses -->
		<?php if (isset($completed_courses) && !empty($completed_courses)): ?>
			<div class="card mt-4 border-secondary">
				<div class="card-header bg-secondary text-white">
					<h5 class="mb-0"><i class="fas fa-check-circle"></i> Completed Courses</h5>
				</div>
				<div class="card-body">
					<div class="alert alert-info mb-3">
						<i class="fas fa-info-circle"></i> These courses have been completed. You can still view them for reference.
					</div>
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>CN</th>
									<th>Course</th>
									<th>Units</th>
									<th>Instructor</th>
									<th>School Year</th>
									<th>Semester</th>
									<th>Term</th>
									<th>End Date</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($completed_courses as $course): ?>
									<tr class="table-secondary">
										<td><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></td>
										<td><strong><?= esc($course['title']) ?></strong></td>
										<td><span class="badge bg-info"><?= esc($course['units'] ?? '0') ?> units</span></td>
										<td><?= esc($course['instructor_name'] ?? 'N/A') ?></td>
										<td><?= esc($course['school_year'] ?? 'N/A') ?></td>
										<td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
										<td>Term <?= $course['term'] ?? 'N/A' ?></td>
										<td>
											<?php if (isset($course['term_end_date'])): ?>
												<i class="fas fa-calendar-times text-warning"></i> <?= date('M d, Y', strtotime($course['term_end_date'])) ?>
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

		<!-- Admin User Management (Hidden by default) -->
		<div class="mt-4 d-none" id="userManagementSection">
			<!-- Add User Form (Simple, no modal) -->
			<div class="card mb-3">
				<div class="card-header">
					<h5 class="mb-0"><i class="fas fa-plus"></i> Add New User</h5>
				</div>
				<div class="card-body">
					<form method="post" action="<?= site_url('/admin/users/create') ?>" id="simpleCreateForm">
						<?= csrf_field() ?>
						<div class="row g-3">
							<div class="col-md-6">
								<label for="simple_name" class="form-label">Name *</label>
								<input type="text" class="form-control" id="simple_name" name="name" required minlength="3" pattern="^[a-zA-Z0-9\s]+$" placeholder="Enter name" title="Only letters, numbers, and spaces are allowed">
								<small class="form-text text-muted">Minimum 3 characters. Only letters, numbers, and spaces allowed (no special characters).</small>
								<?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['name'])): ?>
									<div class="text-danger small mt-1"><?= session()->getFlashdata('validation')['name'] ?></div>
								<?php endif; ?>
							</div>
							<div class="col-md-6">
								<label for="simple_email" class="form-label">Email *</label>
								<input type="email" class="form-control" id="simple_email" name="email" required>
							</div>
							<div class="col-md-6">
								<label for="simple_password" class="form-label">Password *</label>
								<input type="password" class="form-control" id="simple_password" name="password" required minlength="6">
							</div>
							<div class="col-md-6">
								<label for="simple_role" class="form-label">Role *</label>
								<select class="form-select" id="simple_role" name="role" required>
									<option value="">Select Role</option>
									<option value="student">Student</option>
									<option value="instructor">Instructor</option>
									<option value="admin">Admin</option>
								</select>
							</div>
							<div class="col-12">
								<button type="submit" class="btn btn-primary">
									<i class="fas fa-save"></i> Create User
								</button>
								<button type="reset" class="btn btn-secondary">Clear</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<!-- Users Table -->
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h5 class="mb-0">All Users</h5>
					<button class="btn btn-secondary btn-sm" onclick="toggleSection('userManagementSection')">
						<i class="fas fa-times"></i> Close
					</button>
				</div>
				<div class="card-body">
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

					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Email</th>
									<th>Role</th>
									<th>Created</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($users)): ?>
									<?php foreach ($users as $user): ?>
										<tr>
											<td><?= $user['id'] ?></td>
											<td><?= esc($user['name']) ?></td>
											<td><?= esc($user['email']) ?></td>
											<td>
												<span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'instructor' ? 'warning' : 'info') ?>">
													<?= ucfirst($user['role']) ?>
												</span>
											</td>
											<td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
											<td>
												<?php if ($user['id'] != session()->get('id')): ?>
													<button class="btn btn-sm btn-warning" onclick='editUser(<?= json_encode($user) ?>)'>
														<i class="fas fa-edit"></i>
													</button>
													<button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= esc($user['name']) ?>')">
														<i class="fas fa-trash"></i>
													</button>
												<?php else: ?>
													<span class="text-muted small">
														<i class="fas fa-info-circle"></i> Current User
													</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr><td colspan="6" class="text-center text-muted">No users found</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			
			<!-- Deleted Users Section -->
			<?php if (!empty($deleted_users)): ?>
			<div class="card mt-4">
				<div class="card-header bg-secondary text-white">
					<h5 class="mb-0"><i class="fas fa-trash"></i> Deleted Users</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-striped table-hover">
							<thead>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Email</th>
									<th>Role</th>
									<th>Deleted At</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($deleted_users as $user): ?>
									<tr class="table-secondary">
										<td><?= $user['id'] ?></td>
										<td><?= esc($user['name']) ?></td>
										<td><?= esc($user['email']) ?></td>
										<td>
											<span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'instructor' ? 'warning' : 'info') ?>">
												<?= ucfirst($user['role']) ?>
											</span>
										</td>
										<td><?= $user['deleted_at'] ? date('M d, Y H:i', strtotime($user['deleted_at'])) : 'N/A' ?></td>
										<td>
											<button class="btn btn-sm btn-success" onclick="restoreUser(<?= $user['id'] ?>, '<?= esc($user['name']) ?>')">
												<i class="fas fa-undo"></i> Restore
											</button>
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

		<!-- Create User Modal -->
		<div class="modal fade" id="createUserModal" tabindex="-1">
			<div class="modal-dialog">
				<div class="modal-content">
					<form method="post" action="<?= site_url('/admin/users/create') ?>" id="createUserForm">
						<?= csrf_field() ?>
						<div class="modal-header">
							<h5 class="modal-title">Add New User</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
						</div>
						<div class="modal-body">
							<div class="mb-3">
								<label for="create_user_name" class="form-label">Name</label>
								<input type="text" class="form-control" id="create_user_name" name="name" autocomplete="name" required minlength="3" pattern="^[a-zA-Z0-9\s]+$" placeholder="Enter name" title="Only letters, numbers, and spaces are allowed">
								<small class="form-text text-muted">Minimum 3 characters. Only letters, numbers, and spaces allowed (no special characters).</small>
								<?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['name'])): ?>
									<div class="text-danger small mt-1"><?= session()->getFlashdata('validation')['name'] ?></div>
								<?php endif; ?>
							</div>
							<div class="mb-3">
								<label for="create_user_email" class="form-label">Email</label>
								<input type="email" class="form-control" id="create_user_email" name="email" autocomplete="email" required>
							</div>
							<div class="mb-3">
								<label for="create_user_password" class="form-label">Password</label>
								<input type="password" class="form-control" id="create_user_password" name="password" autocomplete="new-password" required minlength="6">
							</div>
							<div class="mb-3">
								<label for="create_user_role" class="form-label">Role</label>
								<select class="form-select" id="create_user_role" name="role" autocomplete="off" required>
									<option value="student">Student</option>
									<option value="instructor">Instructor</option>
									<option value="admin">Admin</option>
								</select>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-primary" id="createUserBtn">Create User</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Edit User Modal -->
		<div class="modal fade" id="editUserModal" tabindex="-1">
			<div class="modal-dialog">
				<div class="modal-content">
					<form method="post" action="<?= site_url('/admin/users/update') ?>">
						<?= csrf_field() ?>
						<input type="hidden" name="id" id="edit_user_id">
						<div class="modal-header">
							<h5 class="modal-title">Edit User</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
						</div>
						<div class="modal-body">
							<div class="mb-3">
								<label for="edit_user_name" class="form-label">Name</label>
								<input type="text" class="form-control" name="name" id="edit_user_name" autocomplete="name" required minlength="3" pattern="^[a-zA-Z0-9\s]+$" placeholder="Enter name" title="Only letters, numbers, and spaces are allowed">
								<small class="form-text text-muted">Minimum 3 characters. Only letters, numbers, and spaces allowed (no special characters).</small>
							</div>
							<div class="mb-3">
								<label for="edit_user_email" class="form-label">Email</label>
								<input type="email" class="form-control" name="email" id="edit_user_email" autocomplete="email" required>
							</div>
							<div class="mb-3">
								<label for="edit_user_password" class="form-label">Password (leave blank to keep current)</label>
								<input type="password" class="form-control" id="edit_user_password" name="password" autocomplete="new-password" minlength="6">
							</div>
							<div class="mb-3">
								<label for="edit_user_role" class="form-label">Role</label>
								<select class="form-select" name="role" id="edit_user_role" autocomplete="off" required>
									<option value="student">Student</option>
									<option value="instructor">Instructor</option>
									<option value="admin">Admin</option>
								</select>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-warning">Update User</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Delete User Form (hidden) -->
		<form method="post" action="<?= site_url('/admin/users/delete') ?>" id="deleteUserForm">
			<?= csrf_field() ?>
			<input type="hidden" name="id" id="delete_user_id">
		</form>
		
		<!-- Restore User Form (hidden) -->
		<form method="post" action="<?= site_url('/admin/users/restore') ?>" id="restoreUserForm">
			<?= csrf_field() ?>
			<input type="hidden" name="id" id="restore_user_id">
		</form>

		<script>
		// Auto-show User Management if there's a success/error message
		document.addEventListener('DOMContentLoaded', function() {
			<?php if (session()->getFlashdata('success') || session()->getFlashdata('error')): ?>
				var section = document.getElementById('userManagementSection');
				if (section) {
					section.classList.remove('d-none');
					section.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			<?php endif; ?>

			// Debug form submission
			var createForm = document.getElementById('createUserForm');
			if (createForm) {
				console.log('Create form found:', createForm);
				
				createForm.addEventListener('submit', function(e) {
					console.log('=== FORM SUBMIT EVENT FIRED ===');
					console.log('Action:', this.action);
					console.log('Method:', this.method);
					
					var formData = new FormData(this);
					console.log('Form data:');
					for (var pair of formData.entries()) {
						console.log(pair[0] + ': ' + pair[1]);
					}
					
					// Check if all required fields are filled
					var name = document.getElementById('create_user_name').value;
					var email = document.getElementById('create_user_email').value;
					var password = document.getElementById('create_user_password').value;
					var role = document.getElementById('create_user_role').value;
					
					console.log('Validation check:');
					console.log('Name:', name, '(length:', name.length, ')');
					console.log('Email:', email);
					console.log('Password:', password, '(length:', password.length, ')');
					console.log('Role:', role);
					
					if (!name || !email || !password || !role) {
						alert('ERROR: Some fields are empty!');
						e.preventDefault();
						return false;
					}
					
					alert('Form will submit to: ' + this.action);
					console.log('Form submitting now...');
					// Let it submit normally
				});
			} else {
				console.error('Create form NOT found!');
			}
		});

		function toggleSection(sectionId) {
			var section = document.getElementById(sectionId);
			if (section) {
				if (section.classList.contains('d-none')) {
					section.classList.remove('d-none');
					section.scrollIntoView({ behavior: 'smooth', block: 'start' });
				} else {
					section.classList.add('d-none');
					window.scrollTo({ top: 0, behavior: 'smooth' });
				}
			}
		}

		function editUser(user) {
			document.getElementById('edit_user_id').value = user.id;
			document.getElementById('edit_user_name').value = user.name;
			document.getElementById('edit_user_email').value = user.email;
			document.getElementById('edit_user_role').value = user.role;
			new bootstrap.Modal(document.getElementById('editUserModal')).show();
		}

		function deleteUser(id, name) {
			if (confirm('Are you sure you want to mark user as deleted: ' + name + '?')) {
				document.getElementById('delete_user_id').value = id;
				document.getElementById('deleteUserForm').submit();
			}
		}
		
		function restoreUser(id, name) {
			if (confirm('Are you sure you want to restore user: ' + name + '?')) {
				document.getElementById('restore_user_id').value = id;
				document.getElementById('restoreUserForm').submit();
			}
		}
		</script>

	<?php else: ?>
		<div class="alert alert-warning mt-4">Your role (<?= esc($roleLabel) ?>) has no custom dashboard yet.</div>
	<?php endif; ?>

	<div class="mt-4">
		<a href="<?= site_url('/logout') ?>" class="btn btn-outline-danger btn-sm">Logout</a>
	</div>
</div>

<?= $this->endSection() ?>


