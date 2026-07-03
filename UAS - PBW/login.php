<?php
require_once 'config/koneksi.php';
require_once 'includes/functions.php';

if (user_count($conn) === 0) {
    header('Location: aktivasi.php');
    exit;
}

if (!empty($_SESSION['id_user'])) {
    if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
        destroy_app_session();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
    header('Location: dashboard.php');
    exit;
}

$error = '';
$info = '';
if (isset($_GET['timeout'])) {
    $info = 'Sesi login berakhir karena tidak aktif. Silakan login kembali.';
}
if (isset($_GET['reset'])) {
    $info = 'Password berhasil diperbarui. Silakan login.';
}
if (isset($_GET['activated'])) {
    $info = 'Akun berhasil diaktifkan. Silakan login.';
}
if (isset($_GET['nonaktif'])) {
    $info = 'Akun berhasil dinonaktifkan dan telah keluar dari sistem.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id_user, nama, username, email, password, role, is_active, account_status FROM `user` WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row && (int)$row['is_active'] === 1 && $row['account_status'] === 'aktif' && !empty($row['password']) && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['must_change_password'] = 0;
            $_SESSION['last_activity'] = time();
            record_login($conn, (int)$row['id_user']);
            header('Location: dashboard.php');
            exit;
        }

        if ($row && $row['account_status'] === 'arsip') {
            $error = 'Akun ini sudah masuk arsip dan tidak dapat digunakan untuk login.';
        } elseif ($row && ((int)$row['is_active'] !== 1 || $row['account_status'] !== 'aktif')) {
            $error = 'Akun belum aktif. Periksa email aktivasi atau hubungi administrator.';
        } else {
            $error = 'Username atau password tidak sesuai.';
        }
    }
}

$page_title = 'Login - SIMPI';
$prefix = '';
include 'includes/header.php';
?>
<div class="login-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="card login-card shadow-lg">
          <div class="card-body p-4">
            <h3 class="fw-bold text-center mb-1">SIMPI</h3>
            <p class="text-muted text-center mb-4">Sistem Informasi Monitoring Proyek Infrastruktur</p>

            <?php if ($info): ?>
              <div class="alert alert-info"><?= e($info); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
              <div class="alert alert-danger"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" autocomplete="off" autocapitalize="none" spellcheck="false" required autofocus>
              </div>
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <label class="form-label mb-1">Password</label>
                  <a href="forgot_password.php" class="forgot-link">Lupa password?</a>
                </div>
                <input type="password" name="password" class="form-control" autocomplete="off" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
