<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

[$scopeSql, $scopeParams] = request_scope_sql('lr');
$academicYearBe = normalize_academic_year_be($_GET['academic_year_be'] ?? current_academic_year_be());
$reportWhere = $scopeSql . ' AND lr.academic_year_be = ?';
$reportParams = array_merge($scopeParams, [$academicYearBe]);
$byStatus = fetch_all('SELECT lr.status, COUNT(*) total, SUM(lr.total_days) days FROM leave_requests lr JOIN users u ON u.id = lr.user_id WHERE ' . $reportWhere . ' GROUP BY lr.status', $reportParams);
$byType = fetch_all('SELECT lt.name, COUNT(*) total, SUM(lr.total_days) days FROM leave_requests lr JOIN users u ON u.id = lr.user_id JOIN leave_types lt ON lt.id = lr.leave_type_id WHERE ' . $reportWhere . ' GROUP BY lt.id, lt.name ORDER BY lt.id', $reportParams);
$role = current_user()['role'] ?? '';
$pageTitle = 'รายงานสรุปการลา';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4 flex gap-2">
    <form method="get" class="flex items-center gap-2">
        <label class="form-label mb-0" for="academic_year_be">ปีการศึกษา</label>
        <input class="form-input w-32" id="academic_year_be" name="academic_year_be" type="number" min="2400" max="2700" value="<?= e((string)$academicYearBe) ?>">
        <button class="btn btn-secondary" type="submit"><i data-lucide="filter" class="h-4 w-4"></i>กรอง</button>
    </form>
    <a class="btn btn-secondary" href="report_leave_individual.php">รายบุคคล</a>
    <a class="btn btn-secondary" href="report_leave_teacher_table.php">ตารางสรุปอาจารย์</a>
    <?php if (in_array($role, ['admin', 'hr', 'dean', 'vice_dean', 'assistant_dean'], true)): ?>
        <a class="btn btn-secondary" href="report_hr_summary.php">HR Summary</a>
    <?php endif; ?>
    <a class="btn btn-secondary" href="export_leave_csv.php"><i data-lucide="download" class="h-4 w-4"></i>CSV</a>
</div>
<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded bg-white p-5 shadow-sm"><h2 class="font-semibold">ตามสถานะ</h2><table class="mt-4 min-w-full text-sm"><tbody><?php foreach ($byStatus as $row): ?><tr class="border-t"><td class="py-3"><?= e(status_label($row['status'])) ?></td><td class="py-3 text-right"><?= (int)$row['total'] ?> รายการ</td><td class="py-3 text-right"><?= e((string)$row['days']) ?> วัน</td></tr><?php endforeach; ?></tbody></table></section>
    <section class="rounded bg-white p-5 shadow-sm"><h2 class="font-semibold">ตามประเภทการลา</h2><table class="mt-4 min-w-full text-sm"><tbody><?php foreach ($byType as $row): ?><tr class="border-t"><td class="py-3"><?= e($row['name']) ?></td><td class="py-3 text-right"><?= (int)$row['total'] ?> รายการ</td><td class="py-3 text-right"><?= e((string)$row['days']) ?> วัน</td></tr><?php endforeach; ?></tbody></table></section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
