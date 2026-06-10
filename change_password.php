<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$error = '';

if (is_post()) {
    verify_csrf();
    $current = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $user = fetch_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user']['id']]);

    if (!$user || !password_verify($current, $user['password_hash'])) {
        $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    } elseif (strlen($newPassword) < 6) {
        $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($newPassword !== $confirm) {
        $error = 'ยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        execute_stmt('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user']['id']]);
        refresh_current_user();
        set_flash('success', 'เปลี่ยนรหัสผ่านเรียบร้อย');
        redirect_to('dashboard.php');
    }
}

$pageTitle = 'เปลี่ยนรหัสผ่าน';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-lg rounded bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold">เปลี่ยนรหัสผ่าน</h2>
    <?php if ($error): ?><div class="mt-4 rounded bg-rose-50 p-3 text-sm text-rose-700"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="form-label" for="current_password">รหัสผ่านปัจจุบัน</label>
            <input class="form-input" id="current_password" name="current_password" type="password" required>
        </div>
        <div>
            <label class="form-label" for="new_password">รหัสผ่านใหม่</label>
            <input class="form-input" id="new_password" name="new_password" type="password" required minlength="6">
        </div>
        <div>
            <label class="form-label" for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
            <input class="form-input" id="confirm_password" name="confirm_password" type="password" required minlength="6">
        </div>
        <button class="btn btn-primary" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึก</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

