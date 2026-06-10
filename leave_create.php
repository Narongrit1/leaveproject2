<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$leaveTypes = fetch_all('SELECT * FROM leave_types WHERE is_active = 1 ORDER BY id');
$defaultAcademicYearBe = current_academic_year_be();
$error = '';

if (is_post()) {
    verify_csrf();
    $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);
    $academicYearBe = normalize_academic_year_be($_POST['academic_year_be'] ?? $defaultAcademicYearBe);
    $startDate = (string)($_POST['start_date'] ?? '');
    $endDate = (string)($_POST['end_date'] ?? '');
    $reason = trim((string)($_POST['reason'] ?? ''));
    $contact = trim((string)($_POST['contact_during_leave'] ?? ''));
    $action = (string)($_POST['action'] ?? 'draft');
    $leaveType = fetch_one('SELECT * FROM leave_types WHERE id = ?', [$leaveTypeId]);
    $totalDays = ($startDate && $endDate) ? calculate_days($startDate, $endDate) : 0;

    if (!$leaveType || !$startDate || !$endDate || $totalDays <= 0 || $reason === '') {
        $error = 'กรุณากรอกข้อมูลใบลาให้ครบถ้วนและตรวจสอบวันที่';
    } elseif (($uploadError = validate_upload($_FILES['attachment'] ?? [])) !== null) {
        $error = $uploadError;
    } elseif ($leaveType['requires_attachment_days'] !== null && $totalDays >= (float)$leaveType['requires_attachment_days'] && empty($_FILES['attachment']['name'])) {
        $error = 'ลาป่วยตั้งแต่ 3 วันขึ้นไปต้องแนบใบรับรองแพทย์';
    } else {
        $today = new DateTimeImmutable('today');
        $start = new DateTimeImmutable($startDate);
        $advance = working_days_between($today, $start->modify('-1 day'));
        if ((int)$leaveType['advance_working_days'] > 0 && $advance < (int)$leaveType['advance_working_days']) {
            $error = $leaveType['name'] . ' ต้องยื่นล่วงหน้าอย่างน้อย ' . $leaveType['advance_working_days'] . ' วันทำการ';
        }
    }

    if ($error === '') {
        $status = $action === 'submit' ? next_pending_status(current_user(), 'leave') : 'draft';
        $currentApproverRole = $status === 'draft' || $status === 'approved' ? null : approver_role_for_status($status, current_user(), 'leave');
        $requestNo = generate_request_no('L');
        execute_stmt(
            'INSERT INTO leave_requests (request_no, user_id, leave_type_id, academic_year_be, start_date, end_date, total_days, reason, contact_during_leave, status, current_approver_role, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$requestNo, $_SESSION['user']['id'], $leaveTypeId, $academicYearBe, $startDate, $endDate, $totalDays, $reason, $contact, $status, $currentApproverRole, $status === 'draft' ? null : date('Y-m-d H:i:s')]
        );
        $leaveId = (int)db()->lastInsertId();
        execute_stmt('INSERT INTO leave_approval_logs (leave_request_id, approver_id, approver_role, action, comment) VALUES (?, ?, ?, ?, ?)', [$leaveId, $_SESSION['user']['id'], $_SESSION['user']['role'], $status === 'draft' ? 'submitted' : 'submitted', 'สร้างใบลา']);
        $file = save_upload($_FILES['attachment'] ?? [], LEAVE_UPLOAD_DIR);
        if ($file) {
            execute_stmt('INSERT INTO leave_attachments (leave_request_id, original_name, stored_name, file_path, mime_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)', [$leaveId, $file['original_name'], $file['stored_name'], $file['file_path'], $file['mime_type'], $file['file_size'], $_SESSION['user']['id']]);
        }
        set_flash('success', $status === 'draft' ? 'บันทึกแบบร่างแล้ว' : 'ส่งใบลาเรียบร้อย');
        redirect_to('leave_view.php?id=' . $leaveId);
    }
}

$pageTitle = 'ยื่นใบลา';
require __DIR__ . '/includes/header.php';
?>
<form method="post" enctype="multipart/form-data" class="rounded bg-white p-6 shadow-sm">
    <?= csrf_field() ?>
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="form-label" for="leave_type_id">ประเภทการลา</label>
            <select class="form-input" id="leave_type_id" name="leave_type_id" required>
                <option value="">เลือกประเภทการลา</option>
                <?php foreach ($leaveTypes as $type): ?><option value="<?= (int)$type['id'] ?>"><?= e($type['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="attachment">ไฟล์แนบ</label>
            <input class="form-input" id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <div>
            <label class="form-label" for="academic_year_be">ปีการศึกษา</label>
            <input class="form-input" id="academic_year_be" name="academic_year_be" type="number" min="2400" max="2700" value="<?= e((string)$defaultAcademicYearBe) ?>" required>
        </div>
        <div>
            <label class="form-label" for="start_date">วันที่เริ่มลา</label>
            <input class="form-input" id="start_date" name="start_date" type="date" required>
        </div>
        <div>
            <label class="form-label" for="end_date">วันที่สิ้นสุด</label>
            <input class="form-input" id="end_date" name="end_date" type="date" required>
        </div>
        <div class="md:col-span-2">
            <label class="form-label" for="reason">เหตุผลการลา</label>
            <textarea class="form-input" id="reason" name="reason" rows="4" required></textarea>
        </div>
        <div class="md:col-span-2">
            <label class="form-label" for="contact_during_leave">ที่อยู่/เบอร์ติดต่อระหว่างลา</label>
            <input class="form-input" id="contact_during_leave" name="contact_during_leave">
        </div>
    </div>
    <?php if ($error): ?><div class="mt-5 rounded bg-rose-50 p-3 text-sm text-rose-700"><?= e($error) ?></div><?php endif; ?>
    <div class="mt-6 flex flex-wrap gap-3">
        <button class="btn btn-secondary" name="action" value="draft" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึกแบบร่าง</button>
        <button class="btn btn-primary" name="action" value="submit" type="submit"><i data-lucide="send" class="h-4 w-4"></i>ส่งอนุมัติ</button>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
