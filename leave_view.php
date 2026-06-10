<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$id = (int)($_GET['id'] ?? 0);
$row = fetch_one('SELECT lr.*, u.full_name, u.position_name, u.department_id, u.role AS owner_role, d.name AS department_name, lt.name AS leave_type_name FROM leave_requests lr JOIN users u ON u.id = lr.user_id LEFT JOIN departments d ON d.id = u.department_id JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE lr.id = ?', [$id]);
if (!$row || !can_view_user_record(['id' => $row['user_id'], 'department_id' => $row['department_id']])) {
    http_response_code(404);
    exit('Not found');
}
$attachments = fetch_all('SELECT * FROM leave_attachments WHERE leave_request_id = ?', [$id]);
$logs = fetch_all('SELECT l.*, u.full_name FROM leave_approval_logs l JOIN users u ON u.id = l.approver_id WHERE l.leave_request_id = ? ORDER BY l.created_at', [$id]);
$canApprove = can_approve_status($row['status'], ['id' => $row['user_id'], 'department_id' => $row['department_id'], 'role' => $row['owner_role']], 'leave', $row);
$canCancel = (int)$row['user_id'] === (int)$_SESSION['user']['id'] && in_array($row['status'], ['draft','pending_head','pending_dean'], true);

$pageTitle = 'รายละเอียดใบลา';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4 flex flex-wrap gap-2">
    <a class="btn btn-secondary" href="leave_list.php"><i data-lucide="arrow-left" class="h-4 w-4"></i>กลับ</a>
    <a class="btn btn-secondary" href="leave_print.php?id=<?= $id ?>"><i data-lucide="printer" class="h-4 w-4"></i>พิมพ์</a>
    <?php if ($canApprove && in_array($row['status'], ['pending_head','pending_dean'], true)): ?>
        <a class="btn btn-primary" href="leave_approve.php?id=<?= $id ?>"><i data-lucide="check" class="h-4 w-4"></i>อนุมัติ</a>
        <a class="btn btn-danger" href="leave_reject.php?id=<?= $id ?>"><i data-lucide="x" class="h-4 w-4"></i>ไม่อนุมัติ</a>
    <?php endif; ?>
    <?php if (can_manage_all() && $row['status'] === 'approved'): ?><a class="btn btn-primary" href="leave_hr_record.php?id=<?= $id ?>"><i data-lucide="clipboard-check" class="h-4 w-4"></i>HR บันทึก</a><?php endif; ?>
    <?php if ($canCancel): ?><a class="btn btn-secondary" href="leave_cancel.php?id=<?= $id ?>"><i data-lucide="ban" class="h-4 w-4"></i>ยกเลิก</a><?php endif; ?>
</div>
<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <section class="rounded bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
            <div>
                <div class="text-sm text-slate-500">เลขที่ <?= e($row['request_no']) ?></div>
                <h2 class="mt-1 text-xl font-semibold"><?= e($row['leave_type_name']) ?></h2>
            </div>
            <span class="rounded px-3 py-1 text-sm <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span>
        </div>
        <dl class="mt-5 grid gap-4 md:grid-cols-2">
            <div><dt class="text-sm text-slate-500">ผู้ยื่น</dt><dd class="font-medium"><?= e($row['full_name']) ?></dd></div>
            <div><dt class="text-sm text-slate-500">ตำแหน่ง</dt><dd><?= e($row['position_name']) ?></dd></div>
            <div><dt class="text-sm text-slate-500">สังกัด</dt><dd><?= e($row['department_name']) ?></dd></div>
            <div><dt class="text-sm text-slate-500">ปีการศึกษา</dt><dd><?= e((string)($row['academic_year_be'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm text-slate-500">จำนวนวัน</dt><dd><?= e((string)$row['total_days']) ?> วัน</dd></div>
            <div><dt class="text-sm text-slate-500">วันที่ลา</dt><dd><?= e(thai_date($row['start_date'])) ?> - <?= e(thai_date($row['end_date'])) ?></dd></div>
            <div><dt class="text-sm text-slate-500">ติดต่อระหว่างลา</dt><dd><?= e($row['contact_during_leave'] ?: '-') ?></dd></div>
            <div class="md:col-span-2"><dt class="text-sm text-slate-500">เหตุผล</dt><dd class="whitespace-pre-wrap"><?= e($row['reason']) ?></dd></div>
        </dl>
    </section>
    <aside class="space-y-6">
        <section class="rounded bg-white p-5 shadow-sm">
            <h3 class="font-semibold">ไฟล์แนบ</h3>
            <div class="mt-3 space-y-2 text-sm">
                <?php foreach ($attachments as $file): ?><div class="rounded border border-slate-200 p-3"><?= e($file['original_name']) ?></div><?php endforeach; ?>
                <?php if (!$attachments): ?><div class="text-slate-500">ไม่มีไฟล์แนบ</div><?php endif; ?>
            </div>
        </section>
        <section class="rounded bg-white p-5 shadow-sm">
            <h3 class="font-semibold">ประวัติ</h3>
            <div class="mt-3 space-y-3 text-sm">
                <?php foreach ($logs as $log): ?>
                <div class="border-l-2 border-blue-200 pl-3">
                    <div class="font-medium"><?= e($log['full_name']) ?>: <?= e($log['action']) ?></div>
                    <div class="text-slate-500"><?= e($log['created_at']) ?></div>
                    <?php if ($log['comment']): ?><div><?= e($log['comment']) ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
