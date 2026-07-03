<?php
$prefix = '';
require_once 'config/koneksi.php';
require_once 'includes/auth.php';
require_login($prefix);

$error = '';
$id_user = current_user_id();

$stmt = mysqli_prepare($conn, 'SELECT id_user, nama, username, email, role, account_status, last_login_at, password_changed_at FROM `user` WHERE id_user = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    destroy_app_session();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_sekarang = $_POST['password_sekarang'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    $stmt = mysqli_prepare($conn, 'SELECT password FROM `user` WHERE id_user = ? AND account_status = "aktif" LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $password_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$password_row || !password_verify($password_sekarang, $password_row['password'])) {
        $error = 'Password saat ini tidak sesuai.';
    } elseif ($password_baru === '' || $konfirmasi === '') {
        $error = 'Password baru dan konfirmasi wajib diisi.';
    } elseif (strlen($password_baru) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password_baru !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif (strcasecmp($password_baru, $user['username']) === 0) {
        $error = 'Password tidak boleh sama dengan username.';
    } else {
        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, 'UPDATE `user` SET password = ?, password_changed_at = NOW() WHERE id_user = ? AND account_status="aktif"');
        mysqli_stmt_bind_param($stmt, 'si', $hash, $id_user);

        if ($stmt && mysqli_stmt_execute($stmt)) {
            set_flash('success', 'Password berhasil diperbarui.');
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Gagal memperbarui password. Silakan coba lagi.';
    }
}

$page_title = 'Akun - SIMPI';
$active = 'password';
include 'includes/header.php';
include 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="row g-4 justify-content-center">
    <div class="col-lg-6">
      <div class="card content-card shadow-sm h-100">
        <div class="card-body p-4">
          <h3 class="fw-bold mb-2">Ganti Password</h3>
          <p class="text-muted mb-4">Gunakan password pribadi. Admin tidak dapat melihat atau mengganti password pengguna.</p>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
          <?php endif; ?>

          <form method="post" autocomplete="off">
            <div class="mb-3">
              <label class="form-label">Password Saat Ini</label>
              <input type="password" name="password_sekarang" class="form-control" autocomplete="current-password" required autofocus>
            </div>
            <div class="mb-3">
              <label class="form-label">Password Baru</label>
              <input type="password" name="password_baru" class="form-control" autocomplete="new-password" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Konfirmasi Password</label>
              <input type="password" name="konfirmasi_password" class="form-control" autocomplete="new-password" required>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">Simpan Password</button>
              <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card content-card shadow-sm h-100">
        <div class="card-body p-4">
          <h3 class="fw-bold mb-2">Identitas Akun</h3>
          <p class="text-muted mb-4">Identitas akun dikunci untuk menjaga riwayat akses dan laporan.</p>
          <div class="row g-3">
            <div class="col-md-6"><div class="security-box"><span>Nama</span><b><?= e($user['nama']); ?></b></div></div>
            <div class="col-md-6"><div class="security-box"><span>Username</span><b><?= e($user['username']); ?></b></div></div>
            <div class="col-md-6"><div class="security-box"><span>Email</span><b><?= e($user['email']); ?></b></div></div>
            <div class="col-md-6"><div class="security-box"><span>Role</span><b><?= e(ucfirst($user['role'])); ?></b></div></div>
            <div class="col-md-6"><div class="security-box"><span>Login Terakhir</span><b><?= e(tanggal_waktu_id($user['last_login_at'])); ?></b></div></div>
            <div class="col-md-6"><div class="security-box"><span>Password Diubah</span><b><?= e(tanggal_waktu_id($user['password_changed_at'])); ?></b></div></div>
          </div>
          <div class="security-note mt-3">Jika pengguna sudah tidak bertugas, penonaktifan akun dilakukan oleh admin aktif melalui menu Data User.</div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
