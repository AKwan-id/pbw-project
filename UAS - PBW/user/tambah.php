<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);

$nama = '';
$username = '';
$email = '';
$role = 'petugas';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'petugas';

    if ($nama === '' || $username === '' || $email === '') {
        set_flash('error', 'Nama, username, dan email wajib diisi.');
    } elseif (strlen($nama) < 3) {
        set_flash('error', 'Nama minimal 3 karakter.');
    } elseif (strlen($username) < 4) {
        set_flash('error', 'Username minimal 4 karakter.');
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        set_flash('error', 'Username hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda minus.');
    } elseif (!valid_email($email)) {
        set_flash('error', 'Email tidak valid.');
    } elseif (!in_array($role, role_options(), true)) {
        set_flash('error', 'Role tidak valid.');
    } elseif ($role === 'admin' && active_admin_count($conn) >= MAX_ACTIVE_ADMINS) {
        set_flash('error', 'Jumlah admin aktif sudah mencapai batas maksimal ' . MAX_ACTIVE_ADMINS . ' orang. Nonaktifkan admin lama sebelum menambah admin baru.');
    } else {
        $cek = mysqli_prepare($conn, 'SELECT id_user FROM `user` WHERE username = ? OR email = ? LIMIT 1');
        mysqli_stmt_bind_param($cek, 'ss', $username, $email);
        mysqli_stmt_execute($cek);
        $hasil_cek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil_cek) > 0) {
            set_flash('error', 'Username atau email sudah terdaftar. Gunakan akun personal yang berbeda untuk setiap pengguna.');
        } else {
            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare($conn, 'INSERT INTO `user` (nama, username, email, password, role, is_active, account_status) VALUES (?, ?, ?, NULL, ?, 0, "belum_aktif")');
                mysqli_stmt_bind_param($stmt, 'ssss', $nama, $username, $email, $role);
                if (!$stmt || !mysqli_stmt_execute($stmt)) {
                    throw new Exception('Gagal menambah user.');
                }

                $new_id = mysqli_insert_id($conn);
                log_user_status($conn, $new_id, 'dibuat', current_user_id(), 'User dibuat oleh admin.');

                $user = ['id_user' => $new_id, 'nama' => $nama, 'email' => $email];
                $token = create_password_token($conn, $new_id, $email, 'aktivasi');
                if (!$token) {
                    throw new Exception('Gagal membuat token aktivasi.');
                }

                $send = send_password_link($conn, $user, $token['token'], 'aktivasi');
                mysqli_commit($conn);

                if ($send['success']) {
                    set_flash('success', 'User berhasil ditambahkan. Link aktivasi telah dikirim ke email pengguna.');
                } else {
                    set_flash('info', 'User berhasil ditambahkan, tetapi email aktivasi gagal dikirim. Periksa konfigurasi email lalu kirim ulang aktivasi.');
                }
                header('Location: index.php?status=belum_aktif');
                exit;
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                set_flash('error', 'Gagal menambah user. Pastikan data belum terdaftar.');
            }
        }
    }
}

$page_title = 'Tambah User - SIMPI';
$active = 'user';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="card content-card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h2 class="fw-bold mb-1">Tambah User</h2>
          <p class="text-muted mb-0">Setiap akun dibuat untuk satu pengguna dengan email kantor personal.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
      </div>

      <form method="post" autocomplete="off">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" autocomplete="off" value="<?= e($nama); ?>" required>
            <div class="form-text">Nama dikunci setelah akun dibuat untuk menjaga konsistensi audit.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" autocomplete="off" autocapitalize="none" spellcheck="false" value="<?= e($username); ?>" required>
            <div class="form-text">Username dikunci setelah akun dibuat.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email Kantor Personal</label>
            <input type="email" name="email" class="form-control" autocomplete="off" value="<?= e($email); ?>" required>
            <div class="form-text">Link aktivasi dan pemulihan password dikirim ke email ini. Email tidak dipakai bersama.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
              <option value="petugas" <?= $role === 'petugas' ? 'selected' : ''; ?>>Petugas</option>
            </select>
            <div class="form-text">Admin aktif dibatasi maksimal <?= e(MAX_ACTIVE_ADMINS); ?> orang.</div>
          </div>
        </div>

        <div class="security-note mt-4">
          <strong>Catatan akses:</strong> Password dibuat sendiri oleh pengguna melalui email aktivasi. Admin tidak melihat password dan tidak mengambil alih akun pengguna.
        </div>

        <div class="mt-4">
          <button class="btn btn-primary">Simpan dan Kirim Aktivasi</button>
          <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
