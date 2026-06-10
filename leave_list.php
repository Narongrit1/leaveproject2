<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

[$scopeSql, $scopeParams] = request_scope_sql('lr');
$status = trim((string)($_GET['status'] ?? ''));
$where = [$scopeSql];
$params = $scopeParams;
if ($status !== '') {
    $where[] = 'lr.status = ?';
    $params[] = $status;
}
$rows = fetch_all('SELECT lr.*, u.full_name, u.department_id, lt.name AS leave_type_name FROM leave_requests lr JOIN users u ON u.id = lr.user_id JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE ' . implode(' AND ', $where) . ' ORDER BY lr.created_at DESC', $params);

$pageTitle = 'รายการใบลา';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form method="get" class="flex items-center gap-2">
        <select class="form-input w-56" name="status">
            <option value="">ทุกสถานะ</option>
            <?php foreach (['draft','pending_head','pending_dean','approved','rejected','cancelled','hr_recorded'] as $s): ?>
            <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-secondary" type="submit"><i data-lucide="filter" class="h-4 w-4"></i>กรอง</button>
    </form>
    <div class="flex gap-2">
        <a class="btn btn-secondary" href="export_leave_csv.php"><i data-lucide="download" class="h-4 w-4"></i>CSV</a>
        <a class="btn btn-primary" href="leave_create.php"><i data-lucide="plus" class="h-4 w-4"></i>ยื่นใบลา</a>
    </div>
</div>
<div class="overflow-x-auto rounded bg-white shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr><th class="px-4 py-3">เลขที่</th><th class="px-4 py-3">ผู้ยื่น</th><th class="px-4 py-3">ประเภท</th><th class="px-4 py-3">ปีการศึกษา</th><th class="px-4 py-3">วันที่</th><th class="px-4 py-3">วัน</th><th class="px-4 py-3">สถานะ</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="px-4 py-3"><a class="font-medium text-blue-700" href="leave_view.php?id=<?= (int)$row['id'] ?>"><?= e($row['request_no']) ?></a></td>
                <td class="px-4 py-3"><?= e($row['full_name']) ?></td>
                <td class="px-4 py-3"><?= e($row['leave_type_name']) ?></td>
                <td class="px-4 py-3"><?= e((string)($row['academic_year_be'] ?? '-')) ?></td>
                <td class="px-4 py-3"><?= e(thai_date($row['start_date'])) ?> - <?= e(thai_date($row['end_date'])) ?></td>
                <td class="px-4 py-3"><?= e((string)$row['total_days']) ?></td>
                <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">ไม่พบข้อมูล</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
