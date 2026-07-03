<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID proyek tidak valid.');
    header('Location: index.php');
    exit;
}

$cek = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM progres WHERE id_proyek=?');
mysqli_stmt_bind_param($cek, 'i', $id);
mysqli_stmt_execute($cek);
$jumlah = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
if ($jumlah && (int)$jumlah['total'] > 0) {
    set_flash('error', 'Proyek tidak dapat dihapus karena masih memiliki laporan progres.');
    header('Location: index.php');
    exit;
}

$cek_revisi = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM anggaran_revisi WHERE id_proyek=?');
mysqli_stmt_bind_param($cek_revisi, 'i', $id);
mysqli_stmt_execute($cek_revisi);
$jumlah_revisi = mysqli_fetch_assoc(mysqli_stmt_get_result($cek_revisi));
if ($jumlah_revisi && (int)$jumlah_revisi['total'] > 0) {
    set_flash('error', 'Proyek tidak dapat dihapus karena sudah memiliki riwayat revisi anggaran. Data dipertahankan untuk audit.');
    header('Location: index.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'DELETE FROM proyek WHERE id_proyek=?');
mysqli_stmt_bind_param($stmt, 'i', $id);
if ($stmt && mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    set_flash('success', 'Data proyek berhasil dihapus.');
} else {
    set_flash('error', 'Data proyek tidak ditemukan atau gagal dihapus.');
}
header('Location: index.php');
exit;
?>
