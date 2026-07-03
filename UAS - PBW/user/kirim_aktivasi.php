<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, 'SELECT id_user, nama, email, role, is_active, account_status FROM `user` WHERE id_user = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    set_flash('error', 'Data user tidak ditemukan.');
} elseif ($user['account_status'] === 'arsip') {
    set_flash('error', 'Akun arsip tidak menerima link aktivasi.');
} elseif ((int)$user['is_active'] === 1 || $user['account_status'] !== 'belum_aktif') {
    set_flash('error', 'Akun sudah aktif. Pemulihan password dilakukan oleh pemilik akun melalui halaman lupa password.');
} elseif ($user['role'] === 'admin' && active_admin_count($conn) >= MAX_ACTIVE_ADMINS) {
    set_flash('error', 'Link aktivasi admin tidak dikirim karena jumlah admin aktif sudah mencapai batas maksimal ' . MAX_ACTIVE_ADMINS . ' orang.');
} else {
    $token = create_password_token($conn, (int)$user['id_user'], $user['email'], 'aktivasi');
    if ($token) {
        $send = send_password_link($conn, $user, $token['token'], 'aktivasi');
        if ($send['success']) {
            set_flash('success', 'Link aktivasi berhasil dikirim ulang ke email pengguna.');
        } else {
            set_flash('error', 'Email aktivasi gagal dikirim. Periksa konfigurasi email.');
        }
    } else {
        set_flash('error', 'Gagal membuat link aktivasi.');
    }
}
header('Location: index.php?status=belum_aktif');
exit;
?>
