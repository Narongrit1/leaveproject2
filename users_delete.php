<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
if ($id && $id !== (int)$_SESSION['user']['id']) {
    execute_stmt('UPDATE users SET is_active = 0 WHERE id = ?', [$id]);
}
set_flash('success', 'ปิดการใช้งานผู้ใช้แล้ว');
redirect_to('users_list.php');

