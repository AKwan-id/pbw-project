<?php
require_once 'config/koneksi.php';
require_once 'includes/functions.php';

if (user_count($conn) > 0) {
    header('Location: login.php');
    exit;
}

$error = '';
$nama = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $username === '' || $email === '' || $password === '' || $konfirmasi === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (!valid_email($email)) {
        $error = 'Email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $must_change_password = 0;
        $stmt = mysqli_prepare($conn, 'INSERT INTO `user` (nama, username, email, password, role, is_active, account_status, must_change_password, password_changed_at) VALUES (?, ?, ?, ?, "admin", 1, "aktif", ?, NOW())');
        mysqli_stmt_bind_param($stmt, 'ssssi', $nama, $username, $email, $hash, $must_change_password);

        if ($stmt && mysqli_stmt_execute($stmt)) {
            $admin_id = mysqli_insert_id($conn);
            log_user_status($conn, $admin_id, 'aktivasi', null, 'Administrator pertama dibuat.');
            session_regenerate_id(true);
            set_flash('success', 'Administrator berhasil dibuat. Silakan login.');
            header('Location: login.php');
            exit;
        }

        $error = 'Gagal membuat administrator. Silakan coba lagi.';
    }
}

$page_title = 'Aktivasi Administrator - SIMPI';
$prefix = '';
include 'includes/header.php';
?>
<div class="login-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card login-card shadow-lg">
          <div class="card-body p-4">
            <h3 class="fw-bold text-center mb-1">Aktivasi Administrator</h3>
            <p class="text-muted text-center mb-4">Daftarkan administrator untuk mengaktifkan akses sistem.</p>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
              <div class="mb-3">
                <label class="form-label">Nama Admin</label>
                <input type="text" name="nama" class="form-control" value="<?= e($nama); ?>" autocomplete="off" required autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= e($username); ?>" autocomplete="off" autocapitalize="none" spellcheck="false" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($email); ?>" autocomplete="off" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" class="form-control" autocomplete="new-password" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Aktivasi Administrator</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
