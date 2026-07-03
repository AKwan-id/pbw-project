<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID user tidak valid.');
    header('Location: index.php');
    exit;
}

if ($id === current_user_id()) {
    set_flash('error', 'Akun yang sedang login tidak dapat dinonaktifkan dari Data User.');
    header('Location: index.php?status=aktif');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT id_user, nama, role, is_active, account_status FROM `user` WHERE id_user=? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    set_flash('error', 'Data user tidak ditemukan.');
    header('Location: index.php');
    exit;
}

if ($user['account_status'] === 'arsip') {
    set_flash('info', 'Akun sudah berada di arsip.');
    header('Location: index.php?status=arsip');
    exit;
}

if ($user['account_status'] === 'belum_aktif') {
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, 'DELETE FROM password_reset_token WHERE id_user = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!$stmt || !mysqli_stmt_execute($stmt)) throw new Exception('Gagal membatalkan token.');

        $stmt = mysqli_prepare($conn, 'DELETE FROM password_reset_log WHERE id_user = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!$stmt || !mysqli_stmt_execute($stmt)) throw new Exception('Gagal membatalkan log reset.');

        $stmt = mysqli_prepare($conn, 'DELETE FROM user_status_log WHERE id_user = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!$stmt || !mysqli_stmt_execute($stmt)) throw new Exception('Gagal membatalkan log status.');

        $stmt = mysqli_prepare($conn, 'UPDATE email_log SET id_user = NULL WHERE id_user = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!$stmt || !mysqli_stmt_execute($stmt)) throw new Exception('Gagal melepaskan log email.');

        $stmt = mysqli_prepare($conn, 'DELETE FROM `user` WHERE id_user = ? AND account_status = "belum_aktif"');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!$stmt || !mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) <= 0) {
            throw new Exception('Gagal membatalkan akun.');
        }

        mysqli_commit($conn);
        set_flash('success', 'Akun belum aktif berhasil dibatalkan. Username dan email dapat digunakan ulang.');
        header('Location: index.php?status=belum_aktif');
        exit;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        set_flash('error', 'Akun belum aktif gagal dibatalkan.');
        header('Location: detail.php?id=' . $id);
        exit;
    }
}

if ($user['account_status'] === 'aktif') {
    if ($user['role'] === 'admin' && active_admin_count($conn) <= 1) {
        set_flash('error', 'Admin terakhir tidak dapat dinonaktifkan karena sistem harus memiliki minimal satu admin aktif.');
        header('Location: index.php?status=aktif');
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, 'UPDATE `user` SET is_active = 0, account_status = "arsip", deactivated_at = NOW() WHERE id_user = ? AND account_status = "aktif"');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!$stmt || !mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) <= 0) {
            throw new Exception('Gagal menonaktifkan akun.');
        }

        $note = $user['role'] === 'admin' ? 'Admin dinonaktifkan oleh admin aktif.' : 'Petugas dinonaktifkan oleh admin aktif.';
        log_user_status($conn, $id, 'nonaktif_admin', current_user_id(), $note);
        mysqli_commit($conn);
        set_flash('success', 'Akun berhasil dinonaktifkan dan masuk arsip. Akun tidak bisa login atau memakai lupa password.');
        header('Location: index.php?status=arsip');
        exit;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        set_flash('error', 'Akun gagal dinonaktifkan.');
        header('Location: detail.php?id=' . $id);
        exit;
    }
}

set_flash('error', 'Status akun tidak valid.');
header('Location: index.php');
exit;
?>
