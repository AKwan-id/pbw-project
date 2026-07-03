<?php
require_once 'config/koneksi.php';
require_once 'includes/auth.php';
require_login('');

$page_title = 'Dashboard - SIMPI';
$prefix = '';
$active = 'dashboard';
include 'includes/header.php';
include 'includes/navbar.php';

$total_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM `user` WHERE account_status='aktif'"))['total'];
$total_proyek = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM proyek'))['total'];
$total_progres = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM progres'))['total'];
$rata_progres = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COALESCE(ROUND(AVG(persentase)), 0) AS rata FROM progres'))['rata'];

$jumlah_berjalan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM proyek WHERE status='Berjalan'"))['total'];
$jumlah_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM proyek WHERE status='Selesai'"))['total'];
$jumlah_tertunda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM proyek WHERE status='Tertunda'"))['total'];

$latest = mysqli_query($conn, "SELECT progres.*, proyek.nama_proyek 
                              FROM progres 
                              JOIN proyek ON progres.id_proyek = proyek.id_proyek 
                              ORDER BY progres.tanggal_laporan DESC, progres.id_progres DESC 
                              LIMIT 5");
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="p-4 mb-4 content-card hero-card">
    <h2 class="fw-bold mb-1">Dashboard Monitoring Proyek</h2>
    <p class="text-muted mb-0">Kelola data proyek infrastruktur, pantau status pekerjaan, dan tinjau progres lapangan dalam satu dashboard terintegrasi.</p>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card card-stat"><div class="card-body"><div class="text-muted">User Aktif</div><div class="stat-value"><?= e($total_user); ?></div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card card-stat"><div class="card-body"><div class="text-muted">Total Proyek</div><div class="stat-value"><?= e($total_proyek); ?></div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card card-stat"><div class="card-body"><div class="text-muted">Laporan Progres</div><div class="stat-value"><?= e($total_progres); ?></div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card card-stat"><div class="card-body"><div class="text-muted">Rata-rata Progres</div><div class="stat-value"><?= e($rata_progres); ?>%</div></div></div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card content-card"><div class="card-body"><span class="badge bg-primary badge-status">Berjalan</span><h4 class="mt-2 mb-0"><?= e($jumlah_berjalan); ?> Proyek</h4></div></div></div>
    <div class="col-md-4"><div class="card content-card"><div class="card-body"><span class="badge bg-success badge-status">Selesai</span><h4 class="mt-2 mb-0"><?= e($jumlah_selesai); ?> Proyek</h4></div></div></div>
    <div class="col-md-4"><div class="card content-card"><div class="card-body"><span class="badge bg-warning text-dark badge-status">Tertunda</span><h4 class="mt-2 mb-0"><?= e($jumlah_tertunda); ?> Proyek</h4></div></div></div>
  </div>

  <div class="card content-card">
    <div class="card-header bg-white fw-bold">Laporan Progres Terbaru</div>
    <div class="card-body table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Nama Proyek</th>
            <th>Progres</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($latest) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted">Belum ada laporan progres.</td></tr>
          <?php endif; ?>
          <?php while ($row = mysqli_fetch_assoc($latest)): ?>
          <tr>
            <td><?= e(tanggal_id($row['tanggal_laporan'])); ?></td>
            <td><?= e($row['nama_proyek']); ?></td>
            <td style="min-width: 170px;">
              <div class="progress">
                <div class="progress-bar <?= e(progress_bar_class($row['persentase'])); ?>" style="width: <?= e($row['persentase']); ?>%;"><?= e($row['persentase']); ?>%</div>
              </div>
            </td>
            <td><?= e($row['keterangan']); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
