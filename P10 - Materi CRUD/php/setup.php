<?php
$server   = "localhost";
$username = "root";
$password = "";


$conn = new mysqli($server, $username, $password);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$queries = [
    "CREATE DATABASE IF NOT EXISTS `alumni` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

    "USE `alumni`",

    "CREATE TABLE IF NOT EXISTS `users` (
        `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `email_verified_at` timestamp NULL DEFAULT NULL,
        `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`) USING BTREE,
        UNIQUE INDEX `users_email_unique` (`email` ASC) USING BTREE
    ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic",
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === true) {
        echo "OK: " . substr($sql, 0, 60) . "...<br>\n";
    } else {
        echo "ERROR: " . $conn->error . "<br>\n";
    }
}

echo "Setup selesai.<br>\n";
echo "Kembali ke <a href='index.php'>index.php</a> untuk mulai menggunakan aplikasi.<br>\n";
$conn->close();
