<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

function pdf_report_category(array $row): ?string
{
    $code = strtolower((string)($row['leave_type_code'] ?? ''));
    $name = (string)($row['leave_type_name'] ?? '');
    if ($code === 'sick' || str_contains($name, 'ป่วย')) { return 'sick'; }
    if ($code === 'personal' || str_contains($name, 'กิจ')) { return 'personal'; }
    if ($code === 'vacation' || str_contains($name, 'พักร้อน')) { return 'vacation'; }
    if (in_array($code, ['training', 'seminar'], true) || str_contains($name, 'อบรม') || str_contains($name, 'สัมมนา')) { return 'training'; }
    if (in_array($code, ['offsite', 'meeting'], true) || str_contains($name, 'ปฏิบัติงานนอกสถานที่') || str_contains($name, 'ประชุม')) { return 'offsite'; }
    return null;
}

function pdf_report_days(float $days): string
{
    return $days > 0 ? rtrim(rtrim(number_format($days, 2, '.', ''), '0'), '.') : '';
}

function pdf_short_date(?string $date): string
{
    return $date ? thai_date($date) : '-';
}

[$scopeSql, $scopeParams] = request_scope_sql('lr');
$academicYearBe = normalize_academic_year_be($_GET['academic_year_be'] ?? current_academic_year_be());
$selectedTeacherId = (int)($_GET['teacher_id'] ?? 0);

$where = [$scopeSql, 'lr.academic_year_be = ?', 'lr.status IN ("approved", "hr_recorded")'];
$params = array_merge($scopeParams, [$academicYearBe]);
if ($selectedTeacherId > 0) {
    $where[] = 'lr.user_id = ?';
    $params[] = $selectedTeacherId;
}

$rows = fetch_all(
    'SELECT lr.*, u.full_name, u.personnel_code, u.position_name, d.name AS department_name, lt.name AS leave_type_name, lt.code AS leave_type_code
     FROM leave_requests lr
     JOIN users u ON u.id = lr.user_id
     LEFT JOIN departments d ON d.id = u.department_id
     JOIN leave_types lt ON lt.id = lr.leave_type_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY u.full_name, lr.start_date, lr.created_at',
    $params
);

$teacher = null;
if ($selectedTeacherId > 0) {
    $teacher = fetch_one('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.id = ?', [$selectedTeacherId]);
} elseif ((current_user()['role'] ?? '') === 'lecturer') {
    $teacher = fetch_one('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.id = ?', [(int)current_user()['id']]);
} elseif ($rows) {
    $teacher = $rows[0];
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

$requestIds = array_map(static fn($row) => (int)$row['id'], $rows);
$logsByRequest = [];
if ($requestIds) {
    $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
    $logs = fetch_all(
        'SELECT lal.leave_request_id, lal.approver_role, lal.action, approver.full_name
         FROM leave_approval_logs lal
         JOIN users approver ON approver.id = lal.approver_id
         WHERE lal.leave_request_id IN (' . $placeholders . ')
           AND lal.action IN ("approved", "hr_recorded")
         ORDER BY lal.created_at ASC, lal.id ASC',
        $requestIds
    );
    foreach ($logs as $log) {
        $logsByRequest[(int)$log['leave_request_id']][] = $log;
    }
}

$running = [];
$exportRows = [];
foreach ($rows as $row) {
    $userId = (int)$row['user_id'];
    $running[$userId] ??= ['sick' => 0.0, 'personal' => 0.0, 'vacation' => 0.0, 'training' => 0.0, 'offsite' => 0.0];
    $current = ['sick' => 0.0, 'personal' => 0.0, 'vacation' => 0.0, 'training' => 0.0, 'offsite' => 0.0];
    $category = pdf_report_category($row);
    if ($category !== null) {
        $current[$category] = (float)$row['total_days'];
        $running[$userId][$category] += (float)$row['total_days'];
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

    $exportRows[] = [
        'submitted_date' => pdf_short_date(substr((string)($row['submitted_at'] ?: $row['created_at']), 0, 10)),
        'leave_date' => pdf_short_date($row['start_date']) . '-' . pdf_short_date($row['end_date']),
        'sick' => pdf_report_days($current['sick']),
        'personal' => pdf_report_days($current['personal']),
        'vacation' => pdf_report_days($current['vacation']),
        'training' => pdf_report_days($current['training']),
        'offsite' => pdf_report_days($current['offsite']),
        'reason' => (string)$row['reason'],
        'requester' => '',
        'supervisor' => '',
        'final_approve' => '',
        'sum_sick' => pdf_report_days($running[$userId]['sick']),
        'sum_personal' => pdf_report_days($running[$userId]['personal']),
        'sum_vacation' => pdf_report_days($running[$userId]['vacation']),
        'sum_training' => pdf_report_days($running[$userId]['training']),
        'sum_offsite' => pdf_report_days($running[$userId]['offsite']),
        'hr' => '',
    ];
}

$payload = [
    'academic_year_be' => $academicYearBe,
    'full_name' => $teacher['full_name'] ?? 'ทั้งหมดตามสิทธิ์',
    'position_name' => $teacher['position_name'] ?? '-',
    'department_name' => $teacher['department_name'] ?? '-',
    'personnel_code' => $teacher['personnel_code'] ?? '-',
    'start_work_date' => '-',
    'personal_entitlement' => pdf_report_days($entitlements['personal']),
    'vacation_entitlement' => pdf_report_days($entitlements['vacation']),
    'rows' => $exportRows,
];

$templatePath = __DIR__ . '/assets/templates/leave_report_template.pdf';
$scriptPath = __DIR__ . '/scripts/render_leave_report_pdf.py';
if (!is_file($templatePath) || !is_file($scriptPath)) {
    http_response_code(500);
    exit('PDF template is not available.');
}

$tempBase = tempnam(sys_get_temp_dir(), 'leave_report_');
$jsonPath = $tempBase . '.json';
$pdfPath = $tempBase . '.pdf';
file_put_contents($jsonPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$command = 'python ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($jsonPath) . ' ' . escapeshellarg($templatePath) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
$output = shell_exec($command);
if (!is_file($pdfPath) || filesize($pdfPath) === 0) {
    @unlink($jsonPath);
    @unlink($pdfPath);
    http_response_code(500);
    exit('Cannot generate PDF: ' . e((string)$output));
}

$pdf = file_get_contents($pdfPath);
@unlink($jsonPath);
@unlink($pdfPath);
@unlink($tempBase);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="leave-teacher-report-' . $academicYearBe . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
