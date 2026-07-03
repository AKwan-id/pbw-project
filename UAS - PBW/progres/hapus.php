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
    set_flash('error', 'Admin tidak menghapus laporan progres. Admin hanya mengelola akun user.');
    header('Location: index.php');
    exit;
}

if ((int)$data['created_by'] !== current_user_id()) {
    set_flash('error', 'Akses ditolak. Laporan progres hanya dapat dihapus oleh pemilik laporan.');
    header('Location: index.php');
    exit;
}

if ((int)$data['is_locked'] === 1) {
    set_flash('error', 'Laporan progres masih terkunci. Buka kunci terlebih dahulu sebelum menghapus.');
    header('Location: index.php');
    exit;
}

$cek_revisi = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM progres_revisi WHERE id_progres=?');
mysqli_stmt_bind_param($cek_revisi, 'i', $id);
mysqli_stmt_execute($cek_revisi);
$jumlah_revisi = mysqli_fetch_assoc(mysqli_stmt_get_result($cek_revisi));
if ($jumlah_revisi && (int)$jumlah_revisi['total'] > 0) {
    set_flash('error', 'Laporan progres tidak dapat dihapus karena sudah memiliki riwayat revisi. Data dipertahankan untuk audit.');
    header('Location: index.php');
    exit;
}

$current_id = current_user_id();
$stmt = mysqli_prepare($conn, 'DELETE FROM progres WHERE id_progres=? AND created_by=? AND is_locked=0');
mysqli_stmt_bind_param($stmt, 'ii', $id, $current_id);
if ($stmt && mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    set_flash('success', 'Data progres berhasil dihapus.');
} else {
    set_flash('error', 'Data progres gagal dihapus. Pastikan laporan milik akun ini dan belum terkunci.');
}
header('Location: index.php');
exit;
?>
