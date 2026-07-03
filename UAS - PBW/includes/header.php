<?php
require_once __DIR__ . '/functions.php';
if (!isset($page_title)) { $page_title = 'SIMPI'; }
if (!isset($prefix)) { $prefix = ''; }
if (!isset($active)) { $active = ''; }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e($prefix); ?>assets/css/style.css">
</head>
<body>
