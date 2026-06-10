<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();
[$scopeSql, $scopeParams] = request_scope_sql('lr');
$academicYearBe = normalize_academic_year_be($_GET['academic_year_be'] ?? current_academic_year_be());
$rows = fetch_all('SELECT u.full_name, d.name AS department_name, COUNT(lr.id) total, COALESCE(SUM(lr.total_days),0) days FROM users u LEFT JOIN departments d ON d.id = u.department_id LEFT JOIN leave_requests lr ON lr.user_id = u.id AND lr.academic_year_be = ? WHERE ' . str_replace('lr.user_id', 'u.id', $scopeSql) . ' GROUP BY u.id, u.full_name, d.name ORDER BY u.full_name', array_merge([$academicYearBe], $scopeParams));
$pageTitle = 'รายงานรายบุคคล';
require __DIR__ . '/includes/header.php';
?>
<form method="get" class="mb-4 flex flex-wrap items-center gap-2">
    <label class="form-label mb-0" for="academic_year_be">ปีการศึกษา</label>
    <input class="form-input w-32" id="academic_year_be" name="academic_year_be" type="number" min="2400" max="2700" value="<?= e((string)$academicYearBe) ?>">
    <button class="btn btn-secondary" type="submit"><i data-lucide="filter" class="h-4 w-4"></i>กรอง</button>
</form>
<div class="overflow-x-auto rounded bg-white shadow-sm"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">ชื่อ</th><th class="px-4 py-3">สังกัด</th><th class="px-4 py-3">จำนวนรายการ</th><th class="px-4 py-3">จำนวนวัน</th></tr></thead><tbody class="divide-y divide-slate-100"><?php foreach ($rows as $row): ?><tr><td class="px-4 py-3 font-medium"><?= e($row['full_name']) ?></td><td class="px-4 py-3"><?= e($row['department_name']) ?></td><td class="px-4 py-3"><?= (int)$row['total'] ?></td><td class="px-4 py-3"><?= e((string)$row['days']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
