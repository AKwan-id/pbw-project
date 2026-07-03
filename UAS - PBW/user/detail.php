<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID user tidak valid.');
    header('Location: index.php');
    exit;
}

$sql = 'SELECT u.id_user, u.nama, u.username, u.email, u.role, u.is_active, u.account_status,
               u.last_login_at, u.password_changed_at, u.deactivated_at, u.created_at,
               (SELECT COUNT(*) FROM progres pr WHERE pr.created_by = u.id_user) AS jumlah_laporan,
               (SELECT au.nama FROM user_status_log l LEFT JOIN `user` au ON au.id_user = l.actor_user_id
                 WHERE l.id_user = u.id_user AND l.action = "dibuat" ORDER BY l.id_log ASC LIMIT 1) AS dibuat_oleh,
               (SELECT l.created_at FROM user_status_log l
                 WHERE l.id_user = u.id_user AND l.action = "aktivasi" ORDER BY l.id_log DESC LIMIT 1) AS tanggal_aktivasi,
               (SELECT au.nama FROM user_status_log l LEFT JOIN `user` au ON au.id_user = l.actor_user_id
                 WHERE l.id_user = u.id_user AND l.action IN ("arsip_admin", "nonaktif_admin", "nonaktif_sendiri") ORDER BY l.id_log DESC LIMIT 1) AS dinonaktifkan_oleh,
               (SELECT l.note FROM user_status_log l
                 WHERE l.id_user = u.id_user AND l.action IN ("arsip_admin", "nonaktif_admin", "nonaktif_sendiri") ORDER BY l.id_log DESC LIMIT 1) AS catatan_nonaktif
        FROM `user` u WHERE u.id_user = ? LIMIT 1';
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    set_flash('error', 'Data user tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$status_label = account_status_label($data['account_status'], $data['is_active']);
$status_badge = account_status_badge_class($data['account_status'], $data['is_active']);
$status_akses = 'Tidak bisa login';
if ($data['account_status'] === 'aktif' && (int)$data['is_active'] === 1) {
    $status_akses = 'Bisa login';
} elseif ($data['account_status'] === 'belum_aktif') {
    $status_akses = 'Menunggu aktivasi email';
}

$back_status = $data['account_status'] === 'arsip' ? 'arsip' : ($data['account_status'] === 'belum_aktif' ? 'belum_aktif' : 'aktif');

$page_title = ($data['account_status'] === 'arsip' ? 'Detail Arsip' : 'Detail Akun') . ' - SIMPI';
$active = 'user';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h2 class="fw-bold mb-1"><?= $data['account_status'] === 'arsip' ? 'Detail Arsip' : 'Detail Akun'; ?></h2>
      <p class="text-muted mb-0">Informasi akun bersifat baca-saja untuk menjaga riwayat akses dan laporan.</p>
    </div>
    <a href="index.php?status=<?= e($back_status); ?>" class="btn btn-outline-secondary">Kembali</a>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="security-box"><span>Status Akun</span><b><?= e($status_label); ?></b></div></div>
    <div class="col-md-3"><div class="security-box"><span>Status Akses</span><b><?= e($status_akses); ?></b></div></div>
    <div class="col-md-3"><div class="security-box"><span>Role</span><b><?= e(ucfirst($data['role'])); ?></b></div></div>
    <div class="col-md-3"><div class="security-box"><span>Laporan Progres</span><b><?= e((int)$data['jumlah_laporan']); ?> laporan</b></div></div>
  </div>

  <div class="card content-card mb-3">
    <div class="card-header bg-white fw-bold">Identitas Akun</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><div class="detail-label">Nama lengkap</div><div class="detail-value"><?= e($data['nama']); ?></div></div>
        <div class="col-md-6"><div class="detail-label">Username</div><div class="detail-value"><?= e($data['username']); ?></div></div>
        <div class="col-md-6"><div class="detail-label">Email</div><div class="detail-value"><?= e($data['email']); ?></div></div>
        <div class="col-md-6"><div class="detail-label">Status</div><span class="badge <?= e($status_badge); ?> badge-status"><?= e($status_label); ?></span></div>
      </div>
    </div>
  </div>

  <div class="card content-card mb-3">
    <div class="card-header bg-white fw-bold">Riwayat Akses</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4"><div class="detail-label">Tanggal dibuat</div><div class="detail-value"><?= e(tanggal_waktu_id($data['created_at'])); ?></div></div>
        <div class="col-md-4"><div class="detail-label">Dibuat oleh</div><div class="detail-value"><?= e($data['dibuat_oleh'] ?: 'Sistem'); ?></div></div>
        <div class="col-md-4"><div class="detail-label">Tanggal aktivasi</div><div class="detail-value"><?= e(tanggal_waktu_id($data['tanggal_aktivasi'])); ?></div></div>
        <div class="col-md-4"><div class="detail-label">Login terakhir</div><div class="detail-value"><?= e(tanggal_waktu_id($data['last_login_at'])); ?></div></div>
        <div class="col-md-4"><div class="detail-label">Password diubah</div><div class="detail-value"><?= e(tanggal_waktu_id($data['password_changed_at'])); ?></div></div>
        <div class="col-md-4"><div class="detail-label">Tanggal nonaktif</div><div class="detail-value"><?= e(tanggal_waktu_id($data['deactivated_at'])); ?></div></div>
        <?php if ($data['account_status'] === 'arsip'): ?>
          <div class="col-md-4"><div class="detail-label">Dinonaktifkan oleh</div><div class="detail-value"><?= e($data['dinonaktifkan_oleh'] ?: '-'); ?></div></div>
          <div class="col-md-8"><div class="detail-label">Catatan</div><div class="detail-value"><?= e($data['catatan_nonaktif'] ?: '-'); ?></div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="security-note mb-3">
    Admin hanya mengatur akses akun. Password, email, username, role, dan isi laporan tidak diubah melalui halaman ini.
  </div>

  <div class="d-flex flex-wrap gap-2">
    <?php if ($data['account_status'] === 'belum_aktif'): ?>
      <a href="kirim_aktivasi.php?id=<?= e($data['id_user']); ?>" class="btn btn-primary" onclick="return konfirmasiKirimAktivasi()">Kirim Aktivasi</a>
      <a href="hapus.php?id=<?= e($data['id_user']); ?>" class="btn btn-outline-danger" onclick="return konfirmasiBatalkanUser()">Batalkan Akun</a>
    <?php elseif ($data['account_status'] === 'aktif' && (int)$data['id_user'] !== current_user_id()): ?>
      <?php if ($data['role'] !== 'admin' || active_admin_count($conn) > 1): ?>
        <a href="hapus.php?id=<?= e($data['id_user']); ?>" class="btn btn-outline-danger" onclick="return konfirmasiNonaktifUser()">Nonaktifkan Akun</a>
      <?php else: ?>
        <span class="btn btn-outline-secondary disabled">Dilindungi</span>
      <?php endif; ?>
    <?php elseif ((int)$data['id_user'] === current_user_id()): ?>
      <span class="btn btn-outline-secondary disabled">Akun Aktif</span>
    <?php endif; ?>
    <a href="index.php?status=<?= e($back_status); ?>" class="btn btn-secondary">Kembali</a>
  </div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
