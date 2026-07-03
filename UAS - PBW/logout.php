<?php
require_once 'includes/functions.php';
destroy_app_session();
header('Location: login.php');
exit;
?>
