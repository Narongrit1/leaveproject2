<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? 0);
$row = fetch_one(
    'SELECT ar.*, u.full_name, u.personnel_code, u.position_name, u.department_id, d.name AS department_name
     FROM attendance_requests ar
     JOIN users u ON u.id = ar.user_id
     LEFT JOIN departments d ON d.id = u.department_id
     WHERE ar.id = ?',
    [$id]
);
if (!$row || !can_view_user_record(['id' => $row['user_id'], 'department_id' => $row['department_id']])) {
    http_response_code(404);
    exit('Not found');
}

$requestType = $row['request_type'] ?? 'time_record';
$payload = [
    'request_no' => (string)$row['request_no'],
    'request_type' => $requestType,
    'personnel_code' => $row['personnel_code'] ?: '-',
    'submitted_date' => thai_date(substr((string)($row['submitted_at'] ?: $row['created_at']), 0, 10)),
    'full_name' => (string)$row['full_name'],
    'position_name' => (string)($row['position_name'] ?: '-'),
    'department_name' => (string)($row['department_name'] ?: '-'),
    'work_date' => thai_date($row['work_date'] ?? null),
    'absent_date' => thai_date($row['absent_date'] ?? null),
    'makeup_date' => thai_date($row['makeup_date'] ?? null),
    'reason_type' => (string)($row['reason_type'] ?? ''),
    'reason_label' => attendance_reason_label($row['reason_type'] ?? null, $row['other_reason'] ?? null),
    'other_reason' => (string)($row['other_reason'] ?? ''),
    'reason' => (string)$row['reason'],
];

$templatePath = __DIR__ . '/assets/templates/attendance_certificate_template.pdf';
$scriptPath = __DIR__ . '/scripts/render_attendance_certificate_pdf.py';
if (!is_file($templatePath) || !is_file($scriptPath)) {
    http_response_code(500);
    exit('PDF template is not available.');
}

$tempBase = tempnam(sys_get_temp_dir(), 'attendance_print_');
$jsonPath = $tempBase . '.json';
$pdfPath = $tempBase . '.pdf';
file_put_contents($jsonPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$command = 'python ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($jsonPath) . ' ' . escapeshellarg($templatePath) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
$output = shell_exec($command);
if (!is_file($pdfPath) || filesize($pdfPath) === 0) {
    @unlink($jsonPath);
    @unlink($pdfPath);
    @unlink($tempBase);
    http_response_code(500);
    exit('Cannot generate PDF: ' . e((string)$output));
}

$pdf = file_get_contents($pdfPath);
@unlink($jsonPath);
@unlink($pdfPath);
@unlink($tempBase);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="attendance-certificate-' . $id . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
