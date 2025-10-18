<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-teacher-dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>

<h1>Welcome, Teacher!</h1>
<p>This is your teacher dashboard. More features will be added soon to help you manage your classes and students.</p>

<button>My Classes</button>
<button>Submissions</button>
<button>Attendance</button>
<button>Grades</button>

<a href="<?= site_url('/logout') ?>">Logout</a>

<?= $this->endSection() ?>
