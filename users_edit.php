<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();

$id = (int)($_GET['id'] ?? 0);
$editUser = fetch_one('SELECT * FROM users WHERE id = ?', [$id]);
if (!$editUser) { http_response_code(404); exit('Not found'); }
$departments = fetch_all('SELECT * FROM departments ORDER BY name');
$roles = ['admin','dean','vice_dean','assistant_dean','head_department','lecturer','staff','hr'];
$error = '';
if (is_post()) {
    verify_csrf();
    execute_stmt('UPDATE users SET personnel_code=?, full_name=?, position_name=?, phone=?, email=?, department_id=?, role=?, is_active=? WHERE id=?', [
        trim((string)$_POST['personnel_code']), trim((string)$_POST['full_name']), trim((string)$_POST['position_name']), trim((string)$_POST['phone']), trim((string)$_POST['email']), ($_POST['department_id'] ?: null), (string)$_POST['role'], isset($_POST['is_active']) ? 1 : 0, $id
    ]);
    if (!empty($_POST['reset_password'])) {
        execute_stmt('UPDATE users SET password_hash=?, must_change_password=1 WHERE id=?', [password_hash('123456', PASSWORD_DEFAULT), $id]);
    }
    set_flash('success', 'บันทึกผู้ใช้แล้ว');
    redirect_to('users_list.php');
}
$pageTitle = 'แก้ไขผู้ใช้';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/user_form.php';
require __DIR__ . '/includes/footer.php';

