<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID progres tidak valid.');
    header('Location: index.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT id_progres, created_by, is_locked FROM progres WHERE id_progres=? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    set_flash('error', 'Data progres tidak ditemukan.');
    header('Location: index.php');
    exit;
}

if (is_admin()) {
    set_flash('error', 'Admin tidak membuka atau mengunci laporan progres. Admin hanya mengelola akun user.');
    header('Location: index.php');
    exit;
}

if ((int)$data['created_by'] !== current_user_id()) {
    set_flash('error', 'Akses ditolak. Kunci laporan hanya dapat diubah oleh pemilik laporan.');
    header('Location: index.php');
    exit;
}

$new_status = (int)$data['is_locked'] === 1 ? 0 : 1;
$current_id = current_user_id();
$stmt = mysqli_prepare($conn, 'UPDATE progres SET is_locked=? WHERE id_progres=? AND created_by=?');
mysqli_stmt_bind_param($stmt, 'iii', $new_status, $id, $current_id);

if ($stmt && mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0) {
    if ($new_status === 1) {
        set_flash('success', 'Laporan progres berhasil dikunci. User lain tidak dapat mengubah atau menghapus laporan ini.');
    } else {
        set_flash('success', 'Kunci laporan progres berhasil dibuka. Laporan dapat diedit atau dihapus oleh pemilik laporan.');
    }
} else {
    set_flash('error', 'Gagal mengubah status kunci laporan.');
}

header('Location: index.php');
exit;
?>
