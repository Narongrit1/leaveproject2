<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? 0);
$row = fetch_one('SELECT lr.*, u.full_name, u.position_name, u.department_id, d.name AS department_name, lt.name AS leave_type_name FROM leave_requests lr JOIN users u ON u.id = lr.user_id LEFT JOIN departments d ON d.id = u.department_id JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE lr.id = ?', [$id]);
if (!$row || !can_view_user_record(['id' => $row['user_id'], 'department_id' => $row['department_id']])) {
    http_response_code(404);
    exit('Not found');
}
$pageTitle = 'พิมพ์ใบลา';
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        body{background:#e2e8f0;padding:24px;font-family:Tahoma,sans-serif}
        .sheet{width:210mm;min-height:297mm;margin:auto;background:white;padding:22mm 20mm;color:#111;box-shadow:0 8px 24px rgba(15,23,42,.12)}
        .line{border-bottom:1px dotted #111;display:inline-block;min-width:120px;padding:0 6px}
        @media print{body{padding:0;background:white}.sheet{box-shadow:none;margin:0}}
    </style>
</head>
<body>
<div class="no-print" style="max-width:210mm;margin:0 auto 12px;text-align:right"><button onclick="window.print()" class="btn btn-primary">พิมพ์</button></div>
<main class="sheet print-sheet">
    <div style="text-align:center;font-weight:bold;font-size:20px">ใบลา</div>
    <div style="text-align:center;margin-top:4px">มหาวิทยาลัยสยาม</div>
    <div style="text-align:center;margin-top:4px">ปีการศึกษา <?= e((string)($row['academic_year_be'] ?? '-')) ?></div>
    <div style="text-align:right;margin-top:28px">วันที่ <span class="line"><?= e(thai_date(date('Y-m-d'))) ?></span></div>
    <p style="margin-top:28px">เรื่อง ขออนุญาต<?= e($row['leave_type_name']) ?></p>
    <p>เรียน คณบดีคณะเทคโนโลยีสารสนเทศ</p>
    <p style="line-height:2">
        ข้าพเจ้า <span class="line" style="min-width:260px"><?= e($row['full_name']) ?></span>
        ตำแหน่ง <span class="line" style="min-width:220px"><?= e($row['position_name']) ?></span>
        สังกัด <span class="line" style="min-width:220px"><?= e($row['department_name']) ?></span>
    </p>
    <p style="line-height:2">
        มีความประสงค์ขอ<?= e($row['leave_type_name']) ?>
        ตั้งแต่วันที่ <span class="line"><?= e(thai_date($row['start_date'])) ?></span>
        ถึงวันที่ <span class="line"><?= e(thai_date($row['end_date'])) ?></span>
        รวม <span class="line"><?= e((string)$row['total_days']) ?></span> วัน
    </p>
    <p style="line-height:2">เนื่องจาก <span class="line" style="min-width:500px"><?= e($row['reason']) ?></span></p>
    <p style="line-height:2">ระหว่างลาติดต่อได้ที่ <span class="line" style="min-width:500px"><?= e($row['contact_during_leave'] ?: '-') ?></span></p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:48px">
        <div></div>
        <div style="text-align:center">
            <div>ลงชื่อ ................................................ ผู้ยื่นใบลา</div>
            <div style="margin-top:8px">( <?= e($row['full_name']) ?> )</div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:60px">
        <div style="border:1px solid #111;padding:16px;min-height:130px">
            <div style="font-weight:bold">ความเห็นผู้บังคับบัญชา</div>
            <p>□ อนุมัติ &nbsp;&nbsp; □ ไม่อนุมัติ</p>
            <p>ลงชื่อ ................................................</p>
        </div>
        <div style="border:1px solid #111;padding:16px;min-height:130px">
            <div style="font-weight:bold">คำสั่งคณบดี</div>
            <p>□ อนุมัติ &nbsp;&nbsp; □ ไม่อนุมัติ</p>
            <p>ลงชื่อ ................................................</p>
        </div>
    </div>
</main>
</body>
</html>
