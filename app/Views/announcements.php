<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-announcements<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-bullhorn me-2 text-primary"></i>
            Announcements
        </h2>
        <?php if (session()->get('isLoggedIn')): ?>
            <a href="<?= site_url('/logout') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($announcements)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Announcements Yet</h4>
                <p class="text-muted">There are currently no announcements to display. Please check back later.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($announcements as $announcement): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <?= esc($announcement['title']) ?>
                                </h5>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?= date('M j, Y', strtotime($announcement['created_at'])) ?>
                                </small>
                            </div>
                            <div class="card-text">
                                <?= nl2br(esc($announcement['content'])) ?>
                            </div>
                            <hr class="my-2">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Posted on <?= date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
