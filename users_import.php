<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();

$message = '';
if (is_post()) {
    verify_csrf();
    $message = 'หน้านี้เตรียมไว้สำหรับ import CSV ในรอบถัดไป ขณะนี้ seed data อยู่ใน database/seed.sql แล้ว';
}
$pageTitle = 'Import รายชื่อผู้ใช้';
require __DIR__ . '/includes/header.php';
?>
<form method="post" enctype="multipart/form-data" class="rounded bg-white p-6 shadow-sm">
    <?= csrf_field() ?>
    <label class="form-label" for="csv">CSV รายชื่อบุคลากร</label>
    <input class="form-input" id="csv" type="file" name="csv" accept=".csv">
    <?php if ($message): ?><div class="mt-4 rounded bg-blue-50 p-3 text-sm text-blue-700"><?= e($message) ?></div><?php endif; ?>
    <div class="mt-5 flex gap-2"><button class="btn btn-primary" type="submit"><i data-lucide="upload" class="h-4 w-4"></i>Import</button><a class="btn btn-secondary" href="users_list.php">กลับ</a></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
