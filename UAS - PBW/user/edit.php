<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);
$id = (int)($_GET['id'] ?? 0);
header('Location: detail.php?id=' . $id);
exit;
?>
