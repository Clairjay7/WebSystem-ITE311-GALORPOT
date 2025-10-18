<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$name  = session('name')  ?? ($user['name']  ?? 'User');
$email = session('email') ?? ($user['email'] ?? '');
// Force student view regardless of logged-in role
$role  = 'student';
$roleLabel = 'Student';
$logo = base_url('https://pnghq.com/wp-content/uploads/student-icon-png-free-highres-download-94160.png');
?>

 

<div class="container py-4">
	<?php if (session()->getFlashdata('welcome')): ?>
		<div class="alert alert-success mb-3"><?= session()->getFlashdata('welcome') ?></div>
	<?php endif; ?>

	<div class="card">
		<div class="card-body p-4">
			<div class="d-flex align-items-center gap-3 mb-3">
				<img src="<?= $logo ?>" alt="Role" style="width:64px;height:64px;border-radius:12px;background:#fff;padding:10px;">
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

	<!-- Quick Access Sections -->
	<div class="row g-3 mt-4">
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Profile</h5>
					<p class="text-muted mb-3">Personal info, update password/details</p>
					<a href="#" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Courses / Subjects</h5>
					<p class="text-muted mb-3">Enrolled classes, schedules, materials</p>
					<a href="<?= site_url('/student/courses') ?>" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Assignments</h5>
					<p class="text-muted mb-3">Pending and submitted tasks</p>
					<a href="<?= site_url('/student/assignments') ?>" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Grades / Results</h5>
					<p class="text-muted mb-3">Exam scores and performance</p>
					<a href="<?= site_url('/student/grades') ?>" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Attendance</h5>
					<p class="text-muted mb-3">Track class attendance records</p>
					<a href="#" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Schedule / Timetable</h5>
					<p class="text-muted mb-3">Daily or weekly class timetable</p>
					<a href="#" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Exams</h5>
					<p class="text-muted mb-3">Upcoming exams, seat plans, results</p>
					<a href="#" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Announcements / Notices</h5>
					<p class="text-muted mb-3">School or teacher updates</p>
					<a href="<?= site_url('/announcements') ?>" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Messages / Communication</h5>
					<p class="text-muted mb-3">Chat or email with teachers/classmates</p>
					<a href="#" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Library / Resources</h5>
					<p class="text-muted mb-3">E‑books, study materials, references</p>
					<a href="#" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Payments / Fees</h5>
					<p class="text-muted mb-3">Tuition balance, receipts, online payment</p>
					<a href="#" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card h-100">
				<div class="card-body">
					<h5 class="card-title mb-2">Support / Help Desk</h5>
					<p class="text-muted mb-3">FAQs, contact admin or IT support</p>
					<a href="#" class="btn btn-sm btn-outline-primary">Open</a>
				</div>
			</div>
		</div>
	</div>
</div>

<?= $this->endSection() ?>