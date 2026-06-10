<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

[$scopeSql, $scopeParams] = request_scope_sql('lr');
$rows = fetch_all("SELECT lr.*, u.full_name, lt.name AS leave_type_name, lt.color FROM leave_requests lr JOIN users u ON u.id = lr.user_id JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE lr.status IN ('approved','hr_recorded') AND $scopeSql", $scopeParams);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(array_map(function ($row) {
    $end = (new DateTimeImmutable($row['end_date']))->modify('+1 day')->format('Y-m-d');
    return [
        'id' => (string)$row['id'],
        'title' => $row['full_name'] . ' - ' . $row['leave_type_name'],
        'start' => $row['start_date'],
        'end' => $end,
        'backgroundColor' => $row['color'],
        'borderColor' => $row['color'],
    ];
}, $rows), JSON_UNESCAPED_UNICODE);
