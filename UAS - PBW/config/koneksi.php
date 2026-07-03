<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'simpi';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die('Koneksi database gagal.');
}

mysqli_set_charset($conn, 'utf8mb4');
?>
