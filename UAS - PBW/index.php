<?php
require_once 'includes/functions.php';
if (!empty($_SESSION['id_user'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>
