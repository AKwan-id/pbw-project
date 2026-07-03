<?php if (!isset($prefix)) { $prefix = ''; } ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= e($prefix); ?>dashboard.php">SIMPI</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link <?= $active == 'dashboard' ? 'active' : ''; ?>" href="<?= e($prefix); ?>dashboard.php">Dashboard</a></li>
        <?php if (is_admin()): ?>
          <li class="nav-item"><a class="nav-link <?= $active == 'user' ? 'active' : ''; ?>" href="<?= e($prefix); ?>user/index.php">Data User</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link <?= $active == 'proyek' ? 'active' : ''; ?>" href="<?= e($prefix); ?>proyek/index.php">Data Proyek</a></li>
        <li class="nav-item"><a class="nav-link <?= $active == 'progres' ? 'active' : ''; ?>" href="<?= e($prefix); ?>progres/index.php">Data Progres</a></li>
        <li class="nav-item"><a class="nav-link <?= $active == 'password' ? 'active' : ''; ?>" href="<?= e($prefix); ?>ganti_password.php">Akun</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($prefix); ?>logout.php" onclick="return confirmLogout()">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
