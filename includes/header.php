<?php
require_once __DIR__ . '/auth.php';
$flash = get_flash();
$pageTitle = $pageTitle ?? APP_TITLE;
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="app-body bg-slate-100 text-slate-900">
<?php if (!empty($_SESSION['user'])): ?>
<div class="app-shell min-h-screen lg:flex">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="app-content min-w-0 flex-1">
        <?php require __DIR__ . '/topbar.php'; ?>
        <main class="app-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
<?php else: ?>
<main class="min-h-screen">
<?php endif; ?>
<?php if ($flash): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: <?= json_encode($flash['type']) ?>,
        text: <?= json_encode($flash['message']) ?>,
        confirmButtonColor: '#2563eb'
    });
});
</script>
<?php endif; ?>
