<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$pageTitle = $pageTitle ?? 'Inventory Management System';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/style.css"
    >

</head>

<body>

<div class="page-wrapper">