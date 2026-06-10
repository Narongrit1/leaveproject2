<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = fetch_one('SELECT lr.*, u.id AS owner_id, u.department_id FROM leave_requests lr JOIN users u ON u.id = lr.user_id WHERE lr.id = ?', [$id]);
if (!$row || !can_approve_status($row['status'], ['id' => $row['owner_id'], 'department_id' => $row['department_id']])) {
    http_response_code(403);
    exit('Forbidden');
}

if (is_post()) {
    verify_csrf();
    $comment = trim((string)($_POST['comment'] ?? ''));
    execute_stmt('UPDATE leave_requests SET status = "rejected", rejected_at = NOW(), current_approver_role = NULL WHERE id = ?', [$id]);
    execute_stmt('INSERT INTO leave_approval_logs (leave_request_id, approver_id, approver_role, action, comment) VALUES (?, ?, ?, ?, ?)', [$id, $_SESSION['user']['id'], $_SESSION['user']['role'], 'rejected', $comment]);
    set_flash('success', 'บันทึกผลไม่อนุมัติแล้ว');
    redirect_to('leave_view.php?id=' . $id);
}

$pageTitle = 'ไม่อนุมัติใบลา';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="mx-auto max-w-xl rounded bg-white p-6 shadow-sm">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <h2 class="text-xl font-semibold">ยืนยันไม่อนุมัติใบลา</h2>
    <label class="form-label mt-5" for="comment">เหตุผล</label>
    <textarea class="form-input" id="comment" name="comment" rows="4" required></textarea>
    <div class="mt-5 flex gap-2">
        <button class="btn btn-danger" type="submit"><i data-lucide="x" class="h-4 w-4"></i>ไม่อนุมัติ</button>
        <a class="btn btn-secondary" href="leave_view.php?id=<?= $id ?>">ยกเลิก</a>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>

