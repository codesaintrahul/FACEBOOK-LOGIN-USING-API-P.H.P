<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Facebook Login Demo';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Facebook Login Demo using PHP, MySQL and Meta OAuth."
    >

    <title>
        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >
</head>

<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') ?>">

<main class="site-shell">