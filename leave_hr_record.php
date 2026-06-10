<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin', 'hr']);
enforce_password_change();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = fetch_one('SELECT * FROM leave_requests WHERE id = ?', [$id]);
if (!$row || $row['status'] !== 'approved') {
    http_response_code(404);
    exit('Not found');
}

if (is_post()) {
    verify_csrf();
    $note = trim((string)($_POST['note'] ?? ''));
    execute_stmt('UPDATE leave_requests SET status = "hr_recorded", hr_recorded_at = NOW() WHERE id = ?', [$id]);
    execute_stmt('INSERT INTO hr_records (record_type, request_id, recorded_by, note) VALUES ("leave", ?, ?, ?)', [$id, $_SESSION['user']['id'], $note]);
    execute_stmt('INSERT INTO leave_approval_logs (leave_request_id, approver_id, approver_role, action, comment) VALUES (?, ?, ?, ?, ?)', [$id, $_SESSION['user']['id'], $_SESSION['user']['role'], 'hr_recorded', $note]);
    set_flash('success', 'HR บันทึกใบลาเรียบร้อย');
    redirect_to('leave_view.php?id=' . $id);
}

$pageTitle = 'HR บันทึกใบลา';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="mx-auto max-w-xl rounded bg-white p-6 shadow-sm">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <h2 class="text-xl font-semibold">HR บันทึกใบลา</h2>
    <label class="form-label mt-5" for="note">หมายเหตุ</label>
    <textarea class="form-input" id="note" name="note" rows="4"></textarea>
    <div class="mt-5 flex gap-2">
        <button class="btn btn-primary" type="submit"><i data-lucide="clipboard-check" class="h-4 w-4"></i>บันทึก</button>
        <a class="btn btn-secondary" href="leave_view.php?id=<?= $id ?>">ยกเลิก</a>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>

