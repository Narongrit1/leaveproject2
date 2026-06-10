<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

[$scopeSql, $scopeParams] = request_scope_sql('ar');
$rows = fetch_all('SELECT ar.*, u.full_name, d.name AS department_name FROM attendance_requests ar JOIN users u ON u.id = ar.user_id LEFT JOIN departments d ON d.id = u.department_id WHERE ' . $scopeSql . ' ORDER BY ar.created_at DESC', $scopeParams);
csv_download_headers('attendance_report.csv');
$out = fopen('php://output', 'w');
fputcsv($out, ['เลขที่', 'ผู้ยื่น', 'สังกัด', 'ปีการศึกษา', 'ประเภทคำขอ', 'วันที่ปฏิบัติการ', 'วันที่ไม่มาปฏิบัติงาน', 'วันที่มาปฏิบัติงาน', 'สาเหตุ', 'สถานะ', 'รายละเอียด']);
foreach ($rows as $row) {
    fputcsv($out, [
        $row['request_no'],
        $row['full_name'],
        $row['department_name'],
        $row['academic_year_be'] ?? '',
        attendance_request_type_label($row['request_type'] ?? 'time_record'),
        $row['work_date'] ?? '',
        $row['absent_date'] ?? '',
        $row['makeup_date'] ?? '',
        ($row['request_type'] ?? 'time_record') === 'workday_swap' ? '' : attendance_reason_label($row['reason_type'] ?? null, $row['other_reason'] ?? null),
        status_label($row['status']),
        $row['reason'],
    ]);
}
fclose($out);
