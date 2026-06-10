<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = fetch_one('SELECT * FROM leave_requests WHERE id = ?', [$id]);
if (!$row || (int)$row['user_id'] !== (int)$_SESSION['user']['id'] || !in_array($row['status'], ['draft','pending_head','pending_dean'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

execute_stmt('UPDATE leave_requests SET status = "cancelled", cancelled_at = NOW(), current_approver_role = NULL WHERE id = ?', [$id]);
execute_stmt('INSERT INTO leave_approval_logs (leave_request_id, approver_id, approver_role, action, comment) VALUES (?, ?, ?, ?, ?)', [$id, $_SESSION['user']['id'], $_SESSION['user']['role'], 'cancelled', 'ผู้ใช้ยกเลิกใบลา']);
set_flash('success', 'ยกเลิกใบลาเรียบร้อย');
redirect_to('leave_view.php?id=' . $id);

