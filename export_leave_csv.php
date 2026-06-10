<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

[$scopeSql, $scopeParams] = request_scope_sql('lr');
$rows = fetch_all('SELECT lr.*, u.full_name, d.name AS department_name, lt.name AS leave_type_name FROM leave_requests lr JOIN users u ON u.id = lr.user_id LEFT JOIN departments d ON d.id = u.department_id JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE ' . $scopeSql . ' ORDER BY lr.created_at DESC', $scopeParams);
csv_download_headers('leave_report.csv');
$out = fopen('php://output', 'w');
fputcsv($out, ['เลขที่', 'ผู้ยื่น', 'สังกัด', 'ประเภท', 'ปีการศึกษา', 'เริ่ม', 'สิ้นสุด', 'จำนวนวัน', 'สถานะ', 'เหตุผล']);
foreach ($rows as $row) {
    fputcsv($out, [$row['request_no'], $row['full_name'], $row['department_name'], $row['leave_type_name'], $row['academic_year_be'] ?? '', $row['start_date'], $row['end_date'], $row['total_days'], status_label($row['status']), $row['reason']]);
}
fclose($out);
