<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

function report_leave_category(array $row): ?string
{
    $code = strtolower((string)($row['leave_type_code'] ?? ''));
    $name = (string)($row['leave_type_name'] ?? '');

    if ($code === 'sick' || str_contains($name, 'ป่วย')) {
        return 'sick';
    }
    if ($code === 'personal' || str_contains($name, 'กิจ')) {
        return 'personal';
    }
    if ($code === 'vacation' || str_contains($name, 'พักร้อน')) {
        return 'vacation';
    }
    if (in_array($code, ['training', 'seminar'], true) || str_contains($name, 'อบรม') || str_contains($name, 'สัมมนา')) {
        return 'training';
    }
    if (in_array($code, ['offsite', 'meeting'], true) || str_contains($name, 'ปฏิบัติงานนอกสถานที่') || str_contains($name, 'ประชุม')) {
        return 'offsite';
    }

    return null;
}

function report_days(float $days): string
{
    return $days > 0 ? rtrim(rtrim(number_format($days, 2, '.', ''), '0'), '.') : '';
}

function report_thai_month(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $months = [1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $dt = new DateTimeImmutable($date);
    return $months[(int)$dt->format('n')] . ' ' . ((int)$dt->format('Y') + 543);
}

[$scopeSql, $scopeParams] = request_scope_sql('lr');
$academicYearBe = normalize_academic_year_be($_GET['academic_year_be'] ?? current_academic_year_be());
$selectedTeacherId = (int)($_GET['teacher_id'] ?? 0);

$where = [
    $scopeSql,
    'lr.academic_year_be = ?',
    'lr.status IN ("approved", "hr_recorded")',
    'u.role IN ("lecturer", "head_department", "dean", "vice_dean", "assistant_dean")',
];
$params = array_merge($scopeParams, [$academicYearBe]);

if ($selectedTeacherId > 0) {
    $where[] = 'lr.user_id = ?';
    $params[] = $selectedTeacherId;
}

$rows = fetch_all(
    'SELECT lr.*, u.full_name, u.personnel_code, u.position_name, u.department_id, d.name AS department_name, lt.name AS leave_type_name, lt.code AS leave_type_code
     FROM leave_requests lr
     JOIN users u ON u.id = lr.user_id
     LEFT JOIN departments d ON d.id = u.department_id
     JOIN leave_types lt ON lt.id = lr.leave_type_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY u.full_name, lr.start_date, lr.created_at',
    $params
);

$teacherRows = fetch_all(
    'SELECT DISTINCT u.id, u.full_name
     FROM users u
     JOIN leave_requests lr ON lr.user_id = u.id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY u.full_name',
    $params
);

$requestIds = array_map(static fn($row) => (int)$row['id'], $rows);
$logsByRequest = [];
if ($requestIds) {
    $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
    $logs = fetch_all(
        'SELECT lal.leave_request_id, lal.approver_role, lal.action, lal.created_at, approver.full_name
         FROM leave_approval_logs lal
         JOIN users approver ON approver.id = lal.approver_id
         WHERE lal.leave_request_id IN (' . $placeholders . ')
           AND lal.action IN ("approved", "hr_recorded")
         ORDER BY lal.created_at ASC, lal.id ASC',
        $requestIds
    );
    foreach ($logs as $log) {
        $requestId = (int)$log['leave_request_id'];
        $logsByRequest[$requestId][] = $log;
    }
}

$categories = [
    'sick' => 'ลาป่วย',
    'personal' => 'ลากิจ',
    'vacation' => 'พักร้อน',
    'training' => 'อบรม/สัมมนา',
    'offsite' => 'ปฏิบัติงานนอกสถานที่/ประชุม',
];
$runningTotals = [];
$tableRows = [];
$firstDate = $rows ? min(array_column($rows, 'start_date')) : null;
$lastDate = $rows ? max(array_column($rows, 'end_date')) : null;
$reportUser = null;
if ($selectedTeacherId > 0) {
    $reportUser = fetch_one('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.id = ?', [$selectedTeacherId]);
} elseif ((current_user()['role'] ?? '') === 'lecturer') {
    $reportUser = fetch_one('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.id = ?', [(int)current_user()['id']]);
} elseif ($rows) {
    $reportUser = $rows[0];
}
$entitlements = ['personal' => 0.0, 'vacation' => 0.0];
$entitlementRows = fetch_all(
    'SELECT lt.code, e.entitled_days
     FROM leave_type_entitlements e
     JOIN leave_types lt ON lt.id = e.leave_type_id
     WHERE e.academic_year_be = ? AND lt.code IN ("personal", "vacation")',
    [$academicYearBe]
);
foreach ($entitlementRows as $row) {
    $entitlements[(string)$row['code']] = (float)$row['entitled_days'];
}

foreach ($rows as $row) {
    $userId = (int)$row['user_id'];
    if (!isset($runningTotals[$userId])) {
        $runningTotals[$userId] = array_fill_keys(array_keys($categories), 0.0);
    }

    $category = report_leave_category($row);
    $currentDays = array_fill_keys(array_keys($categories), 0.0);
    if ($category !== null) {
        $currentDays[$category] = (float)$row['total_days'];
        $runningTotals[$userId][$category] += (float)$row['total_days'];
    }

    $supervisor = '';
    $finalApprove = '';
    $hr = '';
    foreach ($logsByRequest[(int)$row['id']] ?? [] as $log) {
        if ($log['action'] === 'hr_recorded') {
            $hr = (string)$log['full_name'];
            continue;
        }
        if ($log['approver_role'] === 'head_department') {
            $supervisor = (string)$log['full_name'];
        }
        $finalApprove = (string)$log['full_name'];
    }

    $tableRows[] = [
        'row' => $row,
        'current_days' => $currentDays,
        'running_days' => $runningTotals[$userId],
        'supervisor' => $supervisor,
        'final_approve' => $finalApprove,
        'hr' => $hr,
    ];
}

$pageTitle = 'ตารางสรุปการลาของอาจารย์';
require __DIR__ . '/includes/header.php';
?>
<section class="mb-4 rounded bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="section-kicker">Leave Report</div>
            <h2 class="text-xl font-semibold text-slate-900">ตารางสรุปการลาของอาจารย์</h2>
            <p class="mt-1 text-sm text-slate-500">แสดงจำนวนวันลาต่อใบลาและยอดสะสมตามประเภทการลา ปีการศึกษา <?= e((string)$academicYearBe) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn btn-secondary" href="export_leave_teacher_table_pdf.php?academic_year_be=<?= (int)$academicYearBe ?>&teacher_id=<?= (int)$selectedTeacherId ?>"><i data-lucide="file-down" class="h-4 w-4"></i>Export PDF</a>
            <a class="btn btn-secondary" href="report_leave_summary.php">กลับรายงานสรุป</a>
        </div>
    </div>
    <form method="get" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="form-label" for="academic_year_be">ปีการศึกษา</label>
            <input class="form-input w-36" id="academic_year_be" name="academic_year_be" type="number" min="2400" max="2700" value="<?= e((string)$academicYearBe) ?>">
        </div>
        <div>
            <label class="form-label" for="teacher_id">อาจารย์</label>
            <select class="form-input w-72" id="teacher_id" name="teacher_id">
                <option value="0">ทั้งหมดตามสิทธิ์</option>
                <?php foreach ($teacherRows as $teacher): ?>
                    <option value="<?= (int)$teacher['id'] ?>" <?= (int)$teacher['id'] === $selectedTeacherId ? 'selected' : '' ?>><?= e($teacher['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit"><i data-lucide="filter" class="h-4 w-4"></i>กรอง</button>
    </form>
</section>

<section class="overflow-x-auto rounded bg-white shadow-sm">
    <div class="leave-report-sheet min-w-[1180px] p-5">
        <div class="relative mb-3 text-center">
            <img class="absolute left-2 top-0 h-16 w-16 object-contain" src="assets/img/siam-seal.png" alt="Siam University">
            <div class="text-2xl font-semibold leading-tight">มหาวิทยาลัยสยาม&nbsp;&nbsp; Siam University</div>
            <div class="text-xl font-semibold leading-tight">ใบลา ประจำปีการศึกษา <?= e((string)$academicYearBe) ?> ตั้งแต่เดือน <?= e(report_thai_month($firstDate)) ?> - <?= e(report_thai_month($lastDate)) ?></div>
            <div class="text-base leading-tight">(Leave Request Form for the Academic Year <?= e((string)($academicYearBe - 543)) ?>)</div>
        </div>
        <div class="mb-3 grid grid-cols-[1.2fr_1fr_1fr] gap-x-6 gap-y-2 text-sm">
            <div>ชื่อ - นามสกุล (Name)&nbsp;&nbsp; <span class="inline-block min-w-56 border-b border-slate-700"><?= e($reportUser['full_name'] ?? 'ทั้งหมดตามสิทธิ์') ?></span></div>
            <div>ตำแหน่ง (Position)&nbsp;&nbsp; <span class="inline-block min-w-48 border-b border-slate-700"><?= e($reportUser['position_name'] ?? '-') ?></span></div>
            <div>สังกัด (Department)&nbsp;&nbsp; <span class="inline-block min-w-56 border-b border-slate-700"><?= e($reportUser['department_name'] ?? '-') ?></span></div>
            <div>รหัสบุคลากร (Employee ID)&nbsp;&nbsp; <span class="inline-block min-w-40 border-b border-slate-700"><?= e($reportUser['personnel_code'] ?? '-') ?></span></div>
            <div>วันที่เริ่มงาน (Start Date)&nbsp;&nbsp; <span class="inline-block min-w-36 border-b border-slate-700">-</span></div>
            <div>สิทธิการลา (Leave Entitlement)&nbsp;&nbsp; ลากิจ <?= e(report_days($entitlements['personal'])) ?> วัน&nbsp;&nbsp; ลาพักร้อน <?= e(report_days($entitlements['vacation'])) ?> วัน</div>
        </div>
    <table class="leave-report-table min-w-full text-xs">
        <thead class="text-center text-slate-800">
            <tr>
                <th class="px-2 py-2" rowspan="2">วันที่ยื่นใบลา<br><span>Leave Application Date</span></th>
                <th class="px-2 py-2" rowspan="2">วันที่ลา<br><span>Date</span></th>
                <th class="px-2 py-2" colspan="5">ระบุจำนวนวันลา (Number of Leave Days)</th>
                <th class="px-2 py-2" rowspan="2">เหตุผลการลางาน<br><span>Reason for leave</span></th>
                <th class="px-2 py-2" rowspan="2">ผู้ลางาน<br><span>Signature</span></th>
                <th class="px-2 py-2" rowspan="2">ผู้บังคับบัญชา<br><span>Superior's comments</span></th>
                <th class="px-2 py-2" rowspan="2">Final<br>Approval</th>
                <th class="px-2 py-2" colspan="5">Leave Summary (For HR only)</th>
                <th class="px-2 py-2" rowspan="2">HR</th>
            </tr>
            <tr>
                <?php foreach ($categories as $label): ?>
                    <th class="px-2 py-2"><?= e($label) ?></th>
                <?php endforeach; ?>
                <?php foreach ($categories as $label): ?>
                    <th class="px-2 py-2"><?= e($label) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableRows as $item): ?>
                <?php $row = $item['row']; ?>
                <tr>
                    <td class="px-2 py-2 whitespace-nowrap"><?= e(thai_date(substr((string)($row['submitted_at'] ?: $row['created_at']), 0, 10))) ?></td>
                    <td class="leave-date-cell px-2 py-2 text-center whitespace-nowrap"><?= e(thai_date($row['start_date'])) ?> - <?= e(thai_date($row['end_date'])) ?></td>
                    <?php foreach (array_keys($categories) as $key): ?>
                        <td class="px-2 py-2 text-center"><?= e(report_days((float)$item['current_days'][$key])) ?></td>
                    <?php endforeach; ?>
                    <td class="leave-reason-cell px-2 py-2"><?= e($row['reason']) ?></td>
                    <td class="px-2 py-2 whitespace-nowrap"></td>
                    <td class="px-2 py-2 whitespace-nowrap"></td>
                    <td class="px-2 py-2 whitespace-nowrap"></td>
                    <?php foreach (array_keys($categories) as $key): ?>
                        <td class="px-2 py-2 text-center"><?= e(report_days((float)$item['running_days'][$key])) ?></td>
                    <?php endforeach; ?>
                    <td class="px-2 py-2 whitespace-nowrap"></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$tableRows): ?>
                <tr><td colspan="17" class="px-3 py-8 text-center text-slate-500">ไม่พบข้อมูล</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
