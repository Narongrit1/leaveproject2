<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$defaultAcademicYearBe = current_academic_year_be();
$reasonOptions = [
    'forgot_check_in' => 'ลืมบันทึกเวลามาเริ่มปฏิบัติงาน',
    'forgot_check_out' => 'ลืมบันทึกเวลาเลิกปฏิบัติงาน',
    'scanner_error' => 'ระบบสแกนลงเวลาขัดข้อง',
    'other' => 'อื่น ๆ พร้อมระบุ',
];
$error = '';
$form = [
    'academic_year_be' => (string)$defaultAcademicYearBe,
    'request_type' => 'time_record',
    'work_date' => '',
    'absent_date' => '',
    'makeup_date' => '',
    'reason_type' => 'forgot_check_in',
    'other_reason' => '',
    'swap_reason' => '',
];

if (is_post()) {
    verify_csrf();
    $academicYearBe = normalize_academic_year_be($_POST['academic_year_be'] ?? $defaultAcademicYearBe);
    $requestType = (string)($_POST['request_type'] ?? 'time_record');
    $action = (string)($_POST['action'] ?? 'draft');
    $workDate = null;
    $absentDate = null;
    $makeupDate = null;
    $reasonType = null;
    $otherReason = null;
    $reason = '';
    $form = [
        'academic_year_be' => (string)$academicYearBe,
        'request_type' => $requestType,
        'work_date' => (string)($_POST['work_date'] ?? ''),
        'absent_date' => (string)($_POST['absent_date'] ?? ''),
        'makeup_date' => (string)($_POST['makeup_date'] ?? ''),
        'reason_type' => (string)($_POST['reason_type'] ?? 'forgot_check_in'),
        'other_reason' => trim((string)($_POST['other_reason'] ?? '')),
        'swap_reason' => trim((string)($_POST['swap_reason'] ?? '')),
    ];

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

    $checkDates = $requestType === 'time_record' ? [$workDate] : [$absentDate, $makeupDate];
    if ($error === '' && ($attendanceOverlap = overlapping_attendance_request((int)$_SESSION['user']['id'], $checkDates))) {
        $error = 'มีใบรับรองเวลาในวันที่เลือกแล้ว: ' . $attendanceOverlap['request_no'];
    } elseif ($error === '' && ($leaveOverlap = overlapping_leave_on_dates((int)$_SESSION['user']['id'], $checkDates))) {
        $error = 'วันที่เลือกตรงกับใบลาแล้ว: ' . $leaveOverlap['request_no'] . ' (' . $leaveOverlap['leave_type_name'] . ') วันที่ ' . thai_date($leaveOverlap['start_date']) . ' - ' . thai_date($leaveOverlap['end_date']);
    }

    if ($error === '') {
        $status = $action === 'submit' ? next_pending_status(current_user(), 'attendance') : 'draft';
        $currentApproverRole = $status === 'draft' || $status === 'approved' ? null : approver_role_for_status($status, current_user(), 'attendance');
        $requestNo = generate_request_no('A');
        execute_stmt(
            'INSERT INTO attendance_requests (request_no, user_id, academic_year_be, request_type, work_date, absent_date, makeup_date, reason_type, other_reason, start_time, end_time, reason, status, current_approver_role, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?)',
            [$requestNo, $_SESSION['user']['id'], $academicYearBe, $requestType, $workDate ?: null, $absentDate ?: null, $makeupDate ?: null, $reasonType ?: null, $otherReason ?: null, $reason, $status, $currentApproverRole, $status === 'draft' ? null : date('Y-m-d H:i:s')]
        );
        $attendanceId = (int)db()->lastInsertId();
        execute_stmt('INSERT INTO attendance_approval_logs (attendance_request_id, approver_id, approver_role, action, comment) VALUES (?, ?, ?, ?, ?)', [$attendanceId, $_SESSION['user']['id'], $_SESSION['user']['role'], 'submitted', 'สร้างคำขอ']);
        set_flash('success', $status === 'draft' ? 'บันทึกแบบร่างแล้ว' : 'ส่งคำขอเรียบร้อย');
        redirect_to('attendance_view.php?id=' . $attendanceId);
    }
}

$pageTitle = 'ยื่นใบรับรองเวลาปฏิบัติงาน';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="rounded bg-white p-6 shadow-sm attendance-form">
    <?= csrf_field() ?>
    <div class="grid gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="form-label" for="academic_year_be">ปีการศึกษา</label>
            <input class="form-input" id="academic_year_be" name="academic_year_be" type="number" min="2400" max="2700" value="<?= e($form['academic_year_be']) ?>" required>
        </div>

        <section class="request-section md:col-span-2" data-section="time_record">
            <div class="request-section-header">
                <label class="request-group-radio">
                    <input type="radio" name="request_type" value="time_record" <?= $form['request_type'] === 'time_record' ? 'checked' : '' ?>>
                    <span>
                        <strong>ขออนุมัติบันทึกเวลาการมาปฏิบัติงาน</strong>
                        <small>กรอกวันที่ปฏิบัติการและเลือกสาเหตุการขอบันทึกเวลา</small>
                    </span>
                </label>
            </div>
            <div class="request-group-controls grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="work_date">วันที่ปฏิบัติการ</label>
                    <input class="form-input" id="work_date" name="work_date" type="date" value="<?= e($form['work_date']) ?>">
                </div>
                <fieldset>
                    <legend class="form-label">สาเหตุ</legend>
                    <div class="reason-radio-list">
                        <?php foreach ($reasonOptions as $value => $label): ?>
                            <div class="reason-radio-item">
                                <label class="radio-row"><input type="radio" name="reason_type" value="<?= e($value) ?>" <?= $form['reason_type'] === $value ? 'checked' : '' ?>><?= e($label) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="md:col-span-2" data-other-reason>
                    <label class="form-label" for="other_reason">ระบุสาเหตุอื่น</label>
                    <input class="form-input" id="other_reason" name="other_reason" value="<?= e($form['other_reason']) ?>">
                </div>
            </div>
        </section>

        <section class="request-section md:col-span-2" data-section="workday_swap">
            <div class="request-section-header">
                <label class="request-group-radio">
                    <input type="radio" name="request_type" value="workday_swap" <?= $form['request_type'] === 'workday_swap' ? 'checked' : '' ?>>
                    <span>
                        <strong>สลับวันทำงาน</strong>
                        <small>กรอกวันที่ไม่มาปฏิบัติงาน วันที่มาปฏิบัติงานทดแทน และเหตุผล</small>
                    </span>
                </label>
            </div>
            <div class="request-group-controls grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="absent_date">วันที่ไม่มาปฏิบัติงาน</label>
                    <input class="form-input" id="absent_date" name="absent_date" type="date" value="<?= e($form['absent_date']) ?>">
                </div>
                <div>
                    <label class="form-label" for="makeup_date">วันที่มาปฏิบัติงาน</label>
                    <input class="form-input" id="makeup_date" name="makeup_date" type="date" value="<?= e($form['makeup_date']) ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="swap_reason">เหตุผล</label>
                    <textarea class="form-input" id="swap_reason" name="swap_reason" rows="4"><?= e($form['swap_reason']) ?></textarea>
                </div>
            </div>
        </section>
    </div>
    <?php if ($error): ?><div class="mt-5 rounded bg-rose-50 p-3 text-sm text-rose-700"><?= e($error) ?></div><?php endif; ?>
    <div class="mt-6 flex flex-wrap gap-3">
        <button class="btn btn-secondary" name="action" value="draft" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึกแบบร่าง</button>
        <button class="btn btn-primary" name="action" value="submit" type="submit"><i data-lucide="send" class="h-4 w-4"></i>ส่งอนุมัติ</button>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
