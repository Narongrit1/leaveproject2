<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

[$scopeSql, $scopeParams] = request_scope_sql('ar');
$rows = fetch_all('SELECT ar.*, u.full_name, u.department_id FROM attendance_requests ar JOIN users u ON u.id = ar.user_id WHERE ' . $scopeSql . ' ORDER BY ar.created_at DESC', $scopeParams);
$pageTitle = 'รายการใบรับรองเวลาปฏิบัติงาน';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4 flex justify-end gap-2">
    <a class="btn btn-secondary" href="export_attendance_csv.php"><i data-lucide="download" class="h-4 w-4"></i>CSV</a>
    <a class="btn btn-primary" href="attendance_create.php"><i data-lucide="plus" class="h-4 w-4"></i>ยื่นใบรับรอง</a>
</div>
<div class="overflow-x-auto rounded bg-white shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">เลขที่</th><th class="px-4 py-3">ผู้ยื่น</th><th class="px-4 py-3">ประเภท</th><th class="px-4 py-3">ปีการศึกษา</th><th class="px-4 py-3">วันที่เกี่ยวข้อง</th><th class="px-4 py-3">สถานะ</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="px-4 py-3"><a class="font-medium text-blue-700" href="attendance_view.php?id=<?= (int)$row['id'] ?>"><?= e($row['request_no']) ?></a></td>
                <td class="px-4 py-3"><?= e($row['full_name']) ?></td>
                <td class="px-4 py-3"><?= e(attendance_request_type_label($row['request_type'] ?? 'time_record')) ?></td>
                <td class="px-4 py-3"><?= e((string)($row['academic_year_be'] ?? '-')) ?></td>
                <td class="px-4 py-3">
                    <?php if (($row['request_type'] ?? 'time_record') === 'workday_swap'): ?>
                        <?= e(thai_date($row['absent_date'])) ?> → <?= e(thai_date($row['makeup_date'])) ?>
                    <?php else: ?>
                        <?= e(thai_date($row['work_date'])) ?>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">ไม่พบข้อมูล</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
