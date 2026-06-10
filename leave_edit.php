<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = fetch_one('SELECT * FROM leave_requests WHERE id = ?', [$id]);
if (!$row || (int)$row['user_id'] !== (int)$_SESSION['user']['id'] || $row['status'] !== 'draft') {
    http_response_code(403);
    exit('แก้ไขได้เฉพาะใบลาของตนเองที่เป็นแบบร่าง');
}
$leaveTypes = fetch_all('SELECT * FROM leave_types WHERE is_active = 1 ORDER BY id');
$error = '';
if (is_post()) {
    verify_csrf();
    $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);
    $academicYearBe = normalize_academic_year_be($_POST['academic_year_be'] ?? ($row['academic_year_be'] ?? current_academic_year_be()));
    $startDate = (string)($_POST['start_date'] ?? '');
    $endDate = (string)($_POST['end_date'] ?? '');
    $reason = trim((string)($_POST['reason'] ?? ''));
    $contact = trim((string)($_POST['contact_during_leave'] ?? ''));
    $totalDays = ($startDate && $endDate) ? calculate_days($startDate, $endDate) : 0;
    if ($leaveTypeId <= 0 || $totalDays <= 0 || $reason === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        execute_stmt('UPDATE leave_requests SET leave_type_id=?, academic_year_be=?, start_date=?, end_date=?, total_days=?, reason=?, contact_during_leave=? WHERE id=?', [$leaveTypeId, $academicYearBe, $startDate, $endDate, $totalDays, $reason, $contact, $id]);
        set_flash('success', 'แก้ไขแบบร่างแล้ว');
        redirect_to('leave_view.php?id=' . $id);
    }
}
$pageTitle = 'แก้ไขใบลา';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="rounded bg-white p-6 shadow-sm">
    <?= csrf_field() ?>
    <div class="grid gap-5 md:grid-cols-2">
        <div><label class="form-label">ประเภทการลา</label><select class="form-input" name="leave_type_id"><?php foreach ($leaveTypes as $type): ?><option value="<?= (int)$type['id'] ?>" <?= (int)$row['leave_type_id'] === (int)$type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label">ปีการศึกษา</label><input class="form-input" name="academic_year_be" type="number" min="2400" max="2700" value="<?= e((string)($row['academic_year_be'] ?? current_academic_year_be())) ?>" required></div>
        <div><label class="form-label">วันที่เริ่มลา</label><input class="form-input" name="start_date" type="date" value="<?= e($row['start_date']) ?>" required></div>
        <div><label class="form-label">วันที่สิ้นสุด</label><input class="form-input" name="end_date" type="date" value="<?= e($row['end_date']) ?>" required></div>
        <div class="md:col-span-2"><label class="form-label">เหตุผล</label><textarea class="form-input" name="reason" rows="4" required><?= e($row['reason']) ?></textarea></div>
        <div class="md:col-span-2"><label class="form-label">ติดต่อระหว่างลา</label><input class="form-input" name="contact_during_leave" value="<?= e($row['contact_during_leave']) ?>"></div>
    </div>
    <?php if ($error): ?><div class="mt-5 rounded bg-rose-50 p-3 text-sm text-rose-700"><?= e($error) ?></div><?php endif; ?>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึก</button><a class="btn btn-secondary" href="leave_view.php?id=<?= $id ?>">กลับ</a></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
