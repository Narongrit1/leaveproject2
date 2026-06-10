<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? 0);
$row = fetch_one('SELECT ar.*, u.full_name, u.position_name, u.department_id, u.role AS owner_role, d.name AS department_name FROM attendance_requests ar JOIN users u ON u.id = ar.user_id LEFT JOIN departments d ON d.id = u.department_id WHERE ar.id = ?', [$id]);
if (!$row || !can_view_user_record(['id' => $row['user_id'], 'department_id' => $row['department_id']])) {
    http_response_code(404);
    exit('Not found');
}
$attachments = fetch_all('SELECT * FROM attendance_attachments WHERE attendance_request_id = ?', [$id]);
$logs = fetch_all('SELECT l.*, u.full_name FROM attendance_approval_logs l JOIN users u ON u.id = l.approver_id WHERE l.attendance_request_id = ? ORDER BY l.created_at', [$id]);
$canApprove = can_approve_status($row['status'], ['id' => $row['user_id'], 'department_id' => $row['department_id'], 'role' => $row['owner_role']], 'attendance', $row);
$canCancel = (int)$row['user_id'] === (int)$_SESSION['user']['id'] && in_array($row['status'], ['draft','pending_head','pending_dean'], true);
$requestType = $row['request_type'] ?? 'time_record';
$pageTitle = 'รายละเอียดใบรับรองเวลา';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4 flex flex-wrap gap-2">
    <a class="btn btn-secondary" href="attendance_list.php"><i data-lucide="arrow-left" class="h-4 w-4"></i>กลับ</a>
    <a class="btn btn-secondary" href="attendance_print.php?id=<?= $id ?>"><i data-lucide="printer" class="h-4 w-4"></i>พิมพ์</a>
    <?php if ($canApprove && in_array($row['status'], ['pending_head','pending_dean'], true)): ?>
        <a class="btn btn-primary" href="attendance_approve.php?id=<?= $id ?>"><i data-lucide="check" class="h-4 w-4"></i>อนุมัติ</a>
        <a class="btn btn-danger" href="attendance_reject.php?id=<?= $id ?>"><i data-lucide="x" class="h-4 w-4"></i>ไม่อนุมัติ</a>
    <?php endif; ?>
    <?php if (can_manage_all() && $row['status'] === 'approved'): ?><a class="btn btn-primary" href="attendance_hr_record.php?id=<?= $id ?>"><i data-lucide="clipboard-check" class="h-4 w-4"></i>HR บันทึก</a><?php endif; ?>
    <?php if ($canCancel): ?><a class="btn btn-secondary" href="attendance_cancel.php?id=<?= $id ?>"><i data-lucide="ban" class="h-4 w-4"></i>ยกเลิก</a><?php endif; ?>
</div>
<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <section class="rounded bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
            <div><div class="text-sm text-slate-500">เลขที่ <?= e($row['request_no']) ?></div><h2 class="mt-1 text-xl font-semibold">ใบรับรองเวลาปฏิบัติงาน</h2></div>
            <span class="rounded px-3 py-1 text-sm <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span>
        </div>
        <dl class="mt-5 grid gap-4 md:grid-cols-2">
            <div><dt class="text-sm text-slate-500">ผู้ยื่น</dt><dd class="font-medium"><?= e($row['full_name']) ?></dd></div>
            <div><dt class="text-sm text-slate-500">ตำแหน่ง</dt><dd><?= e($row['position_name']) ?></dd></div>
            <div><dt class="text-sm text-slate-500">สังกัด</dt><dd><?= e($row['department_name']) ?></dd></div>
            <div><dt class="text-sm text-slate-500">ปีการศึกษา</dt><dd><?= e((string)($row['academic_year_be'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm text-slate-500">ประเภทคำขอ</dt><dd><?= e(attendance_request_type_label($requestType)) ?></dd></div>
            <?php if ($requestType === 'workday_swap'): ?>
                <div><dt class="text-sm text-slate-500">วันที่ไม่มาปฏิบัติงาน</dt><dd><?= e(thai_date($row['absent_date'])) ?></dd></div>
                <div><dt class="text-sm text-slate-500">วันที่มาปฏิบัติงาน</dt><dd><?= e(thai_date($row['makeup_date'])) ?></dd></div>
            <?php else: ?>
                <div><dt class="text-sm text-slate-500">วันที่ปฏิบัติการ</dt><dd><?= e(thai_date($row['work_date'])) ?></dd></div>
                <div><dt class="text-sm text-slate-500">สาเหตุ</dt><dd><?= e(attendance_reason_label($row['reason_type'] ?? null, $row['other_reason'] ?? null)) ?></dd></div>
            <?php endif; ?>
            <div class="md:col-span-2"><dt class="text-sm text-slate-500">รายละเอียด</dt><dd class="whitespace-pre-wrap"><?= e($row['reason']) ?></dd></div>
        </dl>
    </section>
    <aside class="space-y-6">
        <section class="rounded bg-white p-5 shadow-sm"><h3 class="font-semibold">ไฟล์แนบ</h3><div class="mt-3 space-y-2 text-sm"><?php foreach ($attachments as $file): ?><div class="rounded border border-slate-200 p-3"><?= e($file['original_name']) ?></div><?php endforeach; ?><?php if (!$attachments): ?><div class="text-slate-500">ไม่มีไฟล์แนบ</div><?php endif; ?></div></section>
        <section class="rounded bg-white p-5 shadow-sm"><h3 class="font-semibold">ประวัติ</h3><div class="mt-3 space-y-3 text-sm"><?php foreach ($logs as $log): ?><div class="border-l-2 border-blue-200 pl-3"><div class="font-medium"><?= e($log['full_name']) ?>: <?= e($log['action']) ?></div><div class="text-slate-500"><?= e($log['created_at']) ?></div><?php if ($log['comment']): ?><div><?= e($log['comment']) ?></div><?php endif; ?></div><?php endforeach; ?></div></section>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
