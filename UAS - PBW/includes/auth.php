<?php
require_once __DIR__ . '/functions.php';

function refresh_current_user_session() {
    global $conn;
    if (empty($_SESSION['id_user']) || empty($conn)) {
        return;
    }

    $id_user = (int) $_SESSION['id_user'];
    $stmt = mysqli_prepare($conn, 'SELECT id_user, nama, username, email, role, is_active, account_status, must_change_password FROM `user` WHERE id_user = ? LIMIT 1');
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    if (!mysqli_stmt_execute($stmt)) {
        return;
    }

    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) {
        destroy_app_session();
        return;
    }

    $_SESSION['nama'] = $row['nama'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['must_change_password'] = (int) $row['must_change_password'];
    if ((int)$row['is_active'] !== 1 || $row['account_status'] !== 'aktif') {
        destroy_app_session();
        return;
    }
}

function require_login($prefix = '') {
    if (empty($_SESSION['id_user'])) {
        header('Location: ' . $prefix . 'login.php');
        exit;
    }

    if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
        destroy_app_session();
        header('Location: ' . $prefix . 'login.php?timeout=1');
        exit;
    }

    refresh_current_user_session();

    if (empty($_SESSION['id_user'])) {
        header('Location: ' . $prefix . 'login.php');
        exit;
    }

    $_SESSION['last_activity'] = time();

    $current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!empty($_SESSION['must_change_password']) && !in_array($current_page, ['ganti_password.php', 'logout.php'], true)) {
        header('Location: ' . $prefix . 'ganti_password.php');
        exit;
    }
}

function is_admin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function current_user_id() {
    return (int)($_SESSION['id_user'] ?? 0);
}

function require_admin($prefix = '') {
    require_login($prefix);
    if (!is_admin()) {
        set_flash('error', 'Akses ditolak. Halaman ini hanya untuk admin.');
        header('Location: ' . $prefix . 'dashboard.php');
        exit;
    }
}
?>
