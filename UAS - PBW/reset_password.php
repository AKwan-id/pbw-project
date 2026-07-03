<?php
require_once 'config/koneksi.php';
require_once 'includes/functions.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$user = null;
$token_row = null;

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'Link tidak valid.';
} else {
    $hash = hash('sha256', $token);
    $stmt = mysqli_prepare($conn, 'SELECT t.id_token, t.id_reset, t.id_user, t.purpose, t.expires_at, u.nama, u.username, u.email, u.role, u.account_status FROM password_reset_token t JOIN `user` u ON t.id_user = u.id_user WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW() AND u.account_status <> "arsip" LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $hash);
    mysqli_stmt_execute($stmt);
    $token_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$token_row) {
        $error = 'Link tidak valid, sudah digunakan, atau sudah kedaluwarsa.';
    } else {
        $user = $token_row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $user) {
    $password = $_POST['password_baru'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($password === '' || $konfirmasi === '') {
        $error = 'Password baru dan konfirmasi wajib diisi.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif (strcasecmp($password, $user['username']) === 0) {
        $error = 'Password tidak boleh sama dengan username.';
    } elseif ($user['purpose'] === 'aktivasi' && $user['role'] === 'admin' && active_admin_count($conn) >= MAX_ACTIVE_ADMINS) {
        $error = 'Aktivasi admin tidak dapat dilanjutkan karena jumlah admin aktif sudah mencapai batas maksimal ' . MAX_ACTIVE_ADMINS . ' orang.';
    } else {
        $hash_password = password_hash($password, PASSWORD_DEFAULT);
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, 'UPDATE `user` SET password = ?, is_active = 1, account_status = "aktif", must_change_password = 0, password_changed_at = NOW(), deactivated_at = NULL WHERE id_user = ?');
            mysqli_stmt_bind_param($stmt, 'si', $hash_password, $user['id_user']);
            if (!$stmt || !mysqli_stmt_execute($stmt)) {
                throw new Exception('Gagal memperbarui password.');
            }
            mark_token_used($conn, (int)$user['id_token'], (int)$user['id_reset']);
            if ($user['purpose'] === 'aktivasi') {
                log_user_status($conn, (int)$user['id_user'], 'aktivasi', null, 'Akun diaktifkan melalui link email.');
            }
            mysqli_commit($conn);
            header('Location: login.php?' . ($user['purpose'] === 'aktivasi' ? 'activated=1' : 'reset=1'));
            exit;
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = 'Gagal memperbarui password. Silakan coba lagi.';
        }
    }
}

$page_title = 'Atur Password - SIMPI';
$prefix = '';
include 'includes/header.php';
?>
<div class="login-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="card login-card shadow-lg">
          <div class="card-body p-4">
            <h3 class="fw-bold text-center mb-1">Atur Password</h3>
            <p class="text-muted text-center mb-4">Buat password pribadi untuk akun SIMPI.</p>

            <?php if ($error): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>

            <?php if (!$error || $user): ?>
            <form method="post" autocomplete="off">
              <input type="hidden" name="token" value="<?= e($token); ?>">
              <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="password_baru" class="form-control" autocomplete="new-password" required autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" class="form-control" autocomplete="new-password" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Simpan Password</button>
            </form>
            <?php else: ?>
              <a href="forgot_password.php" class="btn btn-primary w-100">Minta Link Baru</a>
            <?php endif; ?>
            <a href="login.php" class="btn btn-link w-100 mt-2">Kembali ke Login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
