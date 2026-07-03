<?php
require_once 'config/koneksi.php';
require_once 'includes/functions.php';

if (user_count($conn) === 0) {
    header('Location: aktivasi.php');
    exit;
}

$message = '';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !valid_email($email)) {
        $error = 'Masukkan email yang valid.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id_user, nama, email, role, is_active, account_status FROM `user` WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user && $user['account_status'] !== 'arsip') {
            $purpose = ((int)$user['is_active'] === 1 && $user['account_status'] === 'aktif') ? 'reset' : 'aktivasi';
            $boleh_kirim = !($purpose === 'aktivasi' && $user['role'] === 'admin' && active_admin_count($conn) >= MAX_ACTIVE_ADMINS);
            if ($boleh_kirim) {
                $token = create_password_token($conn, (int)$user['id_user'], $user['email'], $purpose);
                if ($token) {
                    send_password_link($conn, $user, $token['token'], $purpose);
                }
            }
        }
        $message = 'Jika email terdaftar, link pemulihan password akan dikirim. Periksa kotak masuk atau folder spam.';
        $email = '';
    }
}

$page_title = 'Lupa Password - SIMPI';
$prefix = '';
include 'includes/header.php';
?>
<div class="login-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="card login-card shadow-lg">
          <div class="card-body p-4">
            <h3 class="fw-bold text-center mb-1">Pemulihan Password</h3>
            <p class="text-muted text-center mb-4">Masukkan email akun untuk menerima link pengaturan password.</p>

            <?php if ($message): ?><div class="alert alert-info"><?= e($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>

            <form method="post" autocomplete="off">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($email); ?>" autocomplete="off" required autofocus>
              </div>
              <button type="submit" class="btn btn-primary w-100">Kirim Link</button>
              <a href="login.php" class="btn btn-link w-100 mt-2">Kembali ke Login</a>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
