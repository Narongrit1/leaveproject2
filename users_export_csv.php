<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();
$rows = fetch_all('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id ORDER BY u.full_name');
csv_download_headers('users.csv');
$out = fopen('php://output', 'w');
fputcsv($out, ['รหัส', 'ชื่อ', 'ตำแหน่ง', 'โทร', 'Email', 'สังกัด', 'Role']);
foreach ($rows as $row) { fputcsv($out, [$row['personnel_code'], $row['full_name'], $row['position_name'], $row['phone'], $row['email'], $row['department_name'], $row['role']]); }
fclose($out);

