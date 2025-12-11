<?php $uri = service('uri'); ?>
<?php
$isLoggedIn = (bool) session()->get('isLoggedIn');
$role = strtolower((string) (session('role') ?? ''));
$roleLabel = $role ? strtoupper($role) : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('/') ?>">
      <!-- brand image removed as requested -->
    </a>
    <?php /* role badge removed as requested */ ?>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <?php if ($isLoggedIn): ?>
            <?php
            // Role-based dashboard links
            $dashboardUrl = '/dashboard'; // default fallback
            $isActive = false;
            
            switch ($role) {
                case 'admin':
                    $dashboardUrl = '/admin/dashboard';
                    $isActive = $uri->getPath() === 'admin/dashboard';
                    break;
                case 'instructor':
                    $dashboardUrl = '/dashboard';
                    $isActive = $uri->getPath() === 'dashboard';
                    break;
                case 'student':
                default:
                    $dashboardUrl = '/dashboard';
                    $isActive = $uri->getPath() === 'dashboard';
                    break;
            }
            ?>
            <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= site_url($dashboardUrl) ?>">Dashboard</a>
          <?php else: ?>
            <a class="nav-link<?= in_array($uri->getPath(), ['', 'home'], true) ? ' active' : '' ?>" href="<?= site_url('/home') ?>">Home</a>
          <?php endif; ?>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $uri->getPath() === 'about' ? ' active' : '' ?>" href="<?= site_url('/about') ?>">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $uri->getPath() === 'contact' ? ' active' : '' ?>" href="<?= site_url('/contact') ?>">Contact</a>
        </li>

        <?php if ($isLoggedIn): ?>
          <!-- Notifications Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-bell"></i>
              <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none; font-size: 0.7rem; padding: 0.2em 0.4em;">
                0
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="min-width: 350px; max-height: 400px; overflow-y: auto;">
              <li><h6 class="dropdown-header">Notifications</h6></li>
              <li><hr class="dropdown-divider"></li>
              <li id="notificationList">
                <div class="px-3 py-2 text-muted text-center">
                  <small>No notifications</small>
                </div>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= site_url('/logout') ?>">
              <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
          </li>
        <?php else: ?>
          <?php if ($uri->getPath() !== 'login'): ?>
            <li class="nav-item">
              <a class="nav-link<?= $uri->getPath() === 'login' ? ' active' : '' ?>" href="<?= site_url('/login') ?>">Login</a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link<?= $uri->getPath() === 'register' ? ' active' : '' ?>" href="<?= site_url('/register') ?>">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>


