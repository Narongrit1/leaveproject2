<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = fetch_one('SELECT ar.*, u.id AS owner_id, u.department_id, u.role AS owner_role FROM attendance_requests ar JOIN users u ON u.id = ar.user_id WHERE ar.id = ?', [$id]);
if (!$row || !can_approve_status($row['status'], ['id' => $row['owner_id'], 'department_id' => $row['department_id'], 'role' => $row['owner_role']], 'attendance', $row)) {
    http_response_code(403);
    exit('Forbidden');
}
if (is_post()) {
    verify_csrf();
    $comment = trim((string)($_POST['comment'] ?? ''));
    $next = next_status_after_approval($row['status'], ['role' => $row['owner_role']], 'attendance');
    $nextApproverRole = $next === 'approved' ? null : approver_role_for_status($next, ['role' => $row['owner_role']], 'attendance');
    execute_stmt("UPDATE attendance_requests SET status = ?, current_approver_role = ?, approved_at = IF(? = 'approved', NOW(), approved_at) WHERE id = ?", [$next, $nextApproverRole, $next, $id]);
    execute_stmt('INSERT INTO attendance_approval_logs (attendance_request_id, approver_id, approver_role, action, comment) VALUES (?, ?, ?, ?, ?)', [$id, $_SESSION['user']['id'], $_SESSION['user']['role'], 'approved', $comment]);
    set_flash('success', 'อนุมัติใบรับรองเวลาเรียบร้อย');
    redirect_to('attendance_view.php?id=' . $id);
}
$pageTitle = 'อนุมัติใบรับรองเวลา';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="mx-auto max-w-xl rounded bg-white p-6 shadow-sm">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <h2 class="text-xl font-semibold">ยืนยันอนุมัติใบรับรองเวลาปฏิบัติงาน</h2>
    <label class="form-label mt-5" for="comment">ความเห็น</label>
    <textarea class="form-input" id="comment" name="comment" rows="4"></textarea>
    <div class="mt-5 flex gap-2"><button class="btn btn-primary" type="submit"><i data-lucide="check" class="h-4 w-4"></i>อนุมัติ</button><a class="btn btn-secondary" href="attendance_view.php?id=<?= $id ?>">ยกเลิก</a></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
