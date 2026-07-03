<?php
if (session_status() === PHP_SESSION_NONE) {
    $secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure_cookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
}

define('SESSION_TIMEOUT_SECONDS', 600);
define('MAX_ACTIVE_ADMINS', 8);

function destroy_app_session() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function set_flash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function show_flash() {
    foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $key => $class) {
        if (!empty($_SESSION['flash_' . $key])) {
            echo '<div class="alert alert-' . $class . ' alert-dismissible fade show" role="alert">';
            echo e($_SESSION['flash_' . $key]);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['flash_' . $key]);
        }
    }
}

function status_options() {
    return ['Perencanaan', 'Berjalan', 'Selesai', 'Tertunda'];
}

function role_options() {
    return ['admin', 'petugas'];
}

function valid_date($date) {
    if ($date === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function valid_percent($value) {
    $value = trim((string) $value);
    if ($value === '') return false;
    return preg_match('/^(100|[1-9]?[0-9])$/', $value) === 1;
}

function status_badge_class($status) {
    switch ($status) {
        case 'Perencanaan': return 'bg-secondary';
        case 'Berjalan': return 'bg-primary';
        case 'Selesai': return 'bg-success';
        case 'Tertunda': return 'bg-warning text-dark';
        default: return 'bg-light text-dark';
    }
}

function progress_bar_class($persentase) {
    $persentase = (int) $persentase;
    if ($persentase >= 100) return 'bg-success';
    if ($persentase >= 60) return 'bg-primary';
    if ($persentase >= 30) return 'bg-info text-dark';
    return 'bg-warning text-dark';
}

function tanggal_id($tanggal) {
    if (!$tanggal || $tanggal === '0000-00-00') return '-';
    return date('d-m-Y', strtotime($tanggal));
}

function tanggal_singkat($tanggal) {
    if (!$tanggal || $tanggal === '0000-00-00') return '-';
    return date('d/m/y', strtotime($tanggal));
}

function tanggal_waktu_id($tanggal) {
    if (!$tanggal || $tanggal === '0000-00-00 00:00:00') return '-';
    return date('d-m-Y H:i', strtotime($tanggal));
}

function format_rupiah($nilai) {
    $angka = (float)($nilai ?? 0);
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function normalisasi_anggaran($nilai) {
    $nilai = trim((string)$nilai);
    if ($nilai === '') return 0;
    if (strpos($nilai, '-') !== false) return -1;
    $nilai = preg_replace('/[^0-9]/', '', $nilai);
    if ($nilai === '') return 0;
    return (float)$nilai;
}

function project_exists($conn, $id_proyek) {
    $stmt = mysqli_prepare($conn, 'SELECT id_proyek FROM proyek WHERE id_proyek = ? LIMIT 1');
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'i', $id_proyek);
    if (!mysqli_stmt_execute($stmt)) return false;
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

function valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function app_url($path = '') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = preg_replace('#/(user|proyek|progres)$#', '', $dir);
    $base = rtrim($dir, '/');
    if ($base === '/' || $base === '.') {
        $base = '';
    }
    return $scheme . '://' . $host . $base . '/' . ltrim($path, '/');
}

function user_count($conn) {
    $result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM `user`');
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}

function active_admin_count($conn) {
    $result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM `user` WHERE role = "admin" AND is_active = 1 AND account_status = "aktif"');
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}

function account_status_options() {
    return ['aktif', 'belum_aktif', 'arsip'];
}

function account_status_label($status, $is_active = null) {
    if ($status === 'arsip') return 'Arsip';
    if ($status === 'aktif' && (int)$is_active === 1) return 'Aktif';
    return 'Belum aktif';
}

function account_status_badge_class($status, $is_active = null) {
    if ($status === 'arsip') return 'bg-secondary';
    if ($status === 'aktif' && (int)$is_active === 1) return 'bg-success';
    return 'bg-warning text-dark';
}

function log_user_status($conn, $id_user, $action, $actor_user_id = null, $note = '') {
    $id_user = (int)$id_user;
    if ($id_user <= 0 || !in_array($action, ['dibuat', 'aktivasi', 'arsip_admin', 'nonaktif_admin', 'nonaktif_sendiri'], true)) return;
    $actor_user_id = $actor_user_id ? (int)$actor_user_id : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = mysqli_prepare($conn, 'INSERT INTO user_status_log (id_user, actor_user_id, action, note, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iissss', $id_user, $actor_user_id, $action, $note, $ip, $agent);
        mysqli_stmt_execute($stmt);
    }
}

function lock_badge_class($is_locked) {
    return (int)$is_locked === 1 ? 'lock-closed' : 'lock-open';
}

function lock_label($is_locked) {
    return (int)$is_locked === 1 ? 'Terkunci' : 'Belum dikunci';
}

function record_login($conn, $id_user) {
    $id_user = (int)$id_user;
    if ($id_user <= 0) return;

    $stmt = mysqli_prepare($conn, 'UPDATE `user` SET last_login_at = NOW() WHERE id_user = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_user);
        mysqli_stmt_execute($stmt);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $log = mysqli_prepare($conn, 'INSERT INTO login_log (id_user, ip_address, user_agent) VALUES (?, ?, ?)');
    if ($log) {
        mysqli_stmt_bind_param($log, 'iss', $id_user, $ip, $agent);
        mysqli_stmt_execute($log);
    }
}

function create_password_token($conn, $id_user, $email, $purpose = 'reset') {
    $id_user = (int)$id_user;
    if ($id_user <= 0 || !in_array($purpose, ['aktivasi', 'reset'], true)) {
        return false;
    }

    $cek_user = mysqli_prepare($conn, 'SELECT account_status, is_active FROM `user` WHERE id_user = ? LIMIT 1');
    if (!$cek_user) return false;
    mysqli_stmt_bind_param($cek_user, 'i', $id_user);
    if (!mysqli_stmt_execute($cek_user)) return false;
    $row_user = mysqli_fetch_assoc(mysqli_stmt_get_result($cek_user));
    if (!$row_user || $row_user['account_status'] === 'arsip') return false;
    if ($purpose === 'aktivasi' && $row_user['account_status'] !== 'belum_aktif') return false;
    if ($purpose === 'reset' && ((int)$row_user['is_active'] !== 1 || $row_user['account_status'] !== 'aktif')) return false;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $log = mysqli_prepare($conn, 'INSERT INTO password_reset_log (id_user, email, purpose, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
    if (!$log) return false;
    mysqli_stmt_bind_param($log, 'issss', $id_user, $email, $purpose, $ip, $agent);
    if (!mysqli_stmt_execute($log)) return false;
    $id_reset = mysqli_insert_id($conn);

    $raw_token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $raw_token);
    $stmt = mysqli_prepare($conn, 'INSERT INTO password_reset_token (id_user, id_reset, token_hash, purpose, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'iiss', $id_user, $id_reset, $token_hash, $purpose);
    if (!mysqli_stmt_execute($stmt)) return false;

    return ['token' => $raw_token, 'id_reset' => $id_reset];
}

function mark_token_used($conn, $id_token, $id_reset = null) {
    $id_token = (int)$id_token;
    $stmt = mysqli_prepare($conn, 'UPDATE password_reset_token SET used_at = NOW() WHERE id_token = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_token);
        mysqli_stmt_execute($stmt);
    }
    if ($id_reset) {
        $id_reset = (int)$id_reset;
        $log = mysqli_prepare($conn, 'UPDATE password_reset_log SET completed_at = NOW() WHERE id_reset = ?');
        if ($log) {
            mysqli_stmt_bind_param($log, 'i', $id_reset);
            mysqli_stmt_execute($log);
        }
    }
}

function log_email_delivery($conn, $id_user, $email, $subject, $success, $error = '') {
    $status = $success ? 'terkirim' : 'gagal';
    $id_user = $id_user ? (int)$id_user : null;
    $stmt = mysqli_prepare($conn, 'INSERT INTO email_log (id_user, email, subject, status, error_message) VALUES (?, ?, ?, ?, ?)');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'issss', $id_user, $email, $subject, $status, $error);
        mysqli_stmt_execute($stmt);
    }
}

function send_password_link($conn, array $user, $token, $purpose = 'reset') {
    require_once __DIR__ . '/mailer.php';
    $link = app_url('reset_password.php?token=' . urlencode($token));
    $subject = $purpose === 'aktivasi' ? 'Aktivasi Akun SIMPI' : 'Reset Password SIMPI';
    $nama = $user['nama'] ?? 'Pengguna';
    $html = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#1f2937">';
    $html .= '<h2 style="margin-bottom:8px">' . e($subject) . '</h2>';
    $html .= '<p>Halo ' . e($nama) . ',</p>';
    if ($purpose === 'aktivasi') {
        $html .= '<p>Akun SIMPI telah dibuat untuk email ini. Klik tombol di bawah untuk membuat password dan mengaktifkan akun.</p>';
    } else {
        $html .= '<p>Kami menerima permintaan pemulihan password untuk akun SIMPI. Klik tombol di bawah untuk membuat password baru.</p>';
    }
    $html .= '<p><a href="' . e($link) . '" style="display:inline-block;padding:10px 16px;background:#0f3d5e;color:#ffffff;text-decoration:none;border-radius:8px">Atur Password</a></p>';
    $html .= '<p>Link berlaku 30 menit dan hanya dapat digunakan satu kali.</p>';
    $html .= '<p>Jika tidak merasa meminta perubahan ini, abaikan email ini.</p>';
    $html .= '</div>';
    $text = "Halo $nama,\n\nBuka link berikut untuk mengatur password SIMPI:\n$link\n\nLink berlaku 30 menit dan hanya dapat digunakan satu kali.";
    $result = send_app_email($user['email'], $subject, $html, $text);
    log_email_delivery($conn, $user['id_user'] ?? null, $user['email'], $subject, $result['success'], $result['error']);
    return $result;
}

function latest_password_reset($conn, $id_user) {
    $id_user = (int)$id_user;
    $stmt = mysqli_prepare($conn, 'SELECT requested_at AS reset_at, purpose FROM password_reset_log WHERE id_user = ? ORDER BY requested_at DESC, id_reset DESC LIMIT 1');
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    if (!mysqli_stmt_execute($stmt)) return null;
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row ?: null;
}


function ringkas_teks($value, $limit = 90) {
    $text = trim(preg_replace('/\s+/', ' ', (string)$value));
    if ($text === '') return '-';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 3) . '...' : $text;
    }
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

function revisi_required_reason($reason) {
    $reason = trim((string)$reason);
    return strlen($reason) >= 8;
}

function audit_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function audit_user_agent() {
    return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
}

?>
