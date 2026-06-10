<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();

$departments = fetch_all('SELECT * FROM departments ORDER BY name');
$roles = ['admin','dean','vice_dean','assistant_dean','head_department','lecturer','staff','hr'];
$error = '';
if (is_post()) {
    verify_csrf();
    $email = trim((string)$_POST['email']);
    if ($email === '' || trim((string)$_POST['full_name']) === '') {
        $error = 'กรุณากรอกชื่อและ email';
    } else {
        execute_stmt('INSERT INTO users (personnel_code, full_name, position_name, phone, email, password_hash, department_id, role, must_change_password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)', [
            trim((string)$_POST['personnel_code']), trim((string)$_POST['full_name']), trim((string)$_POST['position_name']), trim((string)$_POST['phone']), $email, password_hash('123456', PASSWORD_DEFAULT), ($_POST['department_id'] ?: null), (string)$_POST['role'], isset($_POST['is_active']) ? 1 : 0
        ]);
        set_flash('success', 'เพิ่มผู้ใช้แล้ว รหัสเริ่มต้นคือ 123456');
        redirect_to('users_list.php');
    }
}
$pageTitle = 'เพิ่มผู้ใช้';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/user_form.php';
require __DIR__ . '/includes/footer.php';

