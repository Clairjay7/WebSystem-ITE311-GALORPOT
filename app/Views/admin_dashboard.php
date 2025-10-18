<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-admin-dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>

<h1>Welcome, Admin!</h1>
<p>This is your admin dashboard. You have full control over the system and can manage all users and settings.</p>

<button>User Management</button>
<button>Reports</button>
<button>Settings</button>
<button>Announcements</button>

<a href="<?= site_url('/logout') ?>">Logout</a>

<?= $this->endSection() ?>
