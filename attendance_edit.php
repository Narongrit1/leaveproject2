<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row = fetch_one('SELECT * FROM attendance_requests WHERE id = ?', [$id]);
if (!$row || (int)$row['user_id'] !== (int)$_SESSION['user']['id'] || $row['status'] !== 'draft') {
    http_response_code(403);
    exit('แก้ไขได้เฉพาะคำขอของตนเองที่เป็นแบบร่าง');
}

$reasonOptions = [
    'forgot_check_in' => 'ลืมบันทึกเวลามาเริ่มปฏิบัติงาน',
    'forgot_check_out' => 'ลืมบันทึกเวลาเลิกปฏิบัติงาน',
    'scanner_error' => 'ระบบสแกนลงเวลาขัดข้อง',
    'other' => 'อื่น ๆ พร้อมระบุ',
];
$error = '';

if (is_post()) {
    verify_csrf();
    $academicYearBe = normalize_academic_year_be($_POST['academic_year_be'] ?? ($row['academic_year_be'] ?? current_academic_year_be()));
    $requestType = (string)($_POST['request_type'] ?? ($row['request_type'] ?? 'time_record'));
    $workDate = null;
    $absentDate = null;
    $makeupDate = null;
    $reasonType = null;
    $otherReason = null;
    $reason = '';

    if (!in_array($requestType, ['time_record', 'workday_swap'], true)) {
        $requestType = 'time_record';
    }

    if ($requestType === 'time_record') {
        $workDate = (string)($_POST['work_date'] ?? '');
        $reasonType = (string)($_POST['reason_type'] ?? '');
        $otherReason = trim((string)($_POST['other_reason'] ?? ''));
        if (!$workDate || !array_key_exists($reasonType, $reasonOptions) || ($reasonType === 'other' && $otherReason === '')) {
            $error = 'กรุณากรอกวันที่ปฏิบัติการและสาเหตุให้ครบถ้วน';
        }
        $reason = attendance_reason_label($reasonType, $otherReason);
    } else {
        $absentDate = (string)($_POST['absent_date'] ?? '');
        $makeupDate = (string)($_POST['makeup_date'] ?? '');
        $reason = trim((string)($_POST['swap_reason'] ?? ''));
        if (!$absentDate || !$makeupDate || $reason === '') {
            $error = 'กรุณากรอกวันที่ไม่มาปฏิบัติงาน วันที่มาปฏิบัติงาน และเหตุผลให้ครบถ้วน';
        }
    }

    if ($error === '') {
        execute_stmt(
            'UPDATE attendance_requests SET academic_year_be=?, request_type=?, work_date=?, absent_date=?, makeup_date=?, reason_type=?, other_reason=?, start_time=NULL, end_time=NULL, reason=? WHERE id=?',
            [$academicYearBe, $requestType, $workDate ?: null, $absentDate ?: null, $makeupDate ?: null, $reasonType ?: null, $otherReason ?: null, $reason, $id]
        );
        set_flash('success', 'แก้ไขแบบร่างแล้ว');
        redirect_to('attendance_view.php?id=' . $id);
    }
}

$requestType = $row['request_type'] ?? 'time_record';
$reasonType = $row['reason_type'] ?? 'forgot_check_in';
$pageTitle = 'แก้ไขใบรับรองเวลา';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="rounded bg-white p-6 shadow-sm attendance-form">
    <?= csrf_field() ?>
    <div class="grid gap-5 md:grid-cols-2">
        <div class="md:col-span-2"><label class="form-label">ปีการศึกษา</label><input class="form-input" name="academic_year_be" type="number" min="2400" max="2700" value="<?= e((string)($row['academic_year_be'] ?? current_academic_year_be())) ?>" required></div>

        <section class="request-section md:col-span-2" data-section="time_record">
            <div class="request-section-header">
                <label class="request-group-radio">
                    <input type="radio" name="request_type" value="time_record" <?= $requestType === 'time_record' ? 'checked' : '' ?>>
                    <span>
                        <strong>ขออนุมัติบันทึกเวลาการมาปฏิบัติงาน</strong>
                        <small>กรอกวันที่ปฏิบัติการและเลือกสาเหตุการขอบันทึกเวลา</small>
                    </span>
                </label>
            </div>
            <div class="request-group-controls grid gap-5 md:grid-cols-2">
                <div><label class="form-label">วันที่ปฏิบัติการ</label><input class="form-input" name="work_date" type="date" value="<?= e($row['work_date'] ?? '') ?>"></div>
                <fieldset>
                    <legend class="form-label">สาเหตุ</legend>
                    <div class="reason-radio-list">
                        <?php foreach ($reasonOptions as $value => $label): ?>
                            <div class="reason-radio-item">
                                <label class="radio-row"><input type="radio" name="reason_type" value="<?= e($value) ?>" <?= $reasonType === $value ? 'checked' : '' ?>><?= e($label) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="md:col-span-2" data-other-reason>
                    <label class="form-label">ระบุสาเหตุอื่น</label>
                    <input class="form-input" name="other_reason" value="<?= e($row['other_reason'] ?? '') ?>">
                </div>
            </div>
        </section>

        <section class="request-section md:col-span-2" data-section="workday_swap">
            <div class="request-section-header">
                <label class="request-group-radio">
                    <input type="radio" name="request_type" value="workday_swap" <?= $requestType === 'workday_swap' ? 'checked' : '' ?>>
                    <span>
                        <strong>สลับวันทำงาน</strong>
                        <small>กรอกวันที่ไม่มาปฏิบัติงาน วันที่มาปฏิบัติงานทดแทน และเหตุผล</small>
                    </span>
                </label>
            </div>
            <div class="request-group-controls grid gap-5 md:grid-cols-2">
                <div><label class="form-label">วันที่ไม่มาปฏิบัติงาน</label><input class="form-input" name="absent_date" type="date" value="<?= e($row['absent_date'] ?? '') ?>"></div>
                <div><label class="form-label">วันที่มาปฏิบัติงาน</label><input class="form-input" name="makeup_date" type="date" value="<?= e($row['makeup_date'] ?? '') ?>"></div>
                <div class="md:col-span-2"><label class="form-label">เหตุผล</label><textarea class="form-input" name="swap_reason" rows="4"><?= e($requestType === 'workday_swap' ? $row['reason'] : '') ?></textarea></div>
            </div>
        </section>
    </div>
    <?php if ($error): ?><div class="mt-5 rounded bg-rose-50 p-3 text-sm text-rose-700"><?= e($error) ?></div><?php endif; ?>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึก</button><a class="btn btn-secondary" href="attendance_view.php?id=<?= $id ?>">กลับ</a></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
