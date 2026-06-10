<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin', 'hr', 'dean', 'vice_dean', 'assistant_dean']);
enforce_password_change();
$academicYearBe = normalize_academic_year_be($_GET['academic_year_be'] ?? current_academic_year_be());
$leaveCount = fetch_one('SELECT COUNT(*) total FROM leave_requests WHERE status = "hr_recorded" AND academic_year_be = ?', [$academicYearBe]);
$attendanceCount = fetch_one('SELECT COUNT(*) total FROM attendance_requests WHERE status = "hr_recorded" AND academic_year_be = ?', [$academicYearBe]);
$pageTitle = 'รายงาน HR';
require __DIR__ . '/includes/header.php';
?>
<form method="get" class="mb-4 flex flex-wrap items-center gap-2">
    <label class="form-label mb-0" for="academic_year_be">ปีการศึกษา</label>
    <input class="form-input w-32" id="academic_year_be" name="academic_year_be" type="number" min="2400" max="2700" value="<?= e((string)$academicYearBe) ?>">
    <button class="btn btn-secondary" type="submit"><i data-lucide="filter" class="h-4 w-4"></i>กรอง</button>
</form>
<div class="grid gap-4 md:grid-cols-2">
    <div class="rounded bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">ใบลา HR Recorded</div><div class="mt-2 text-3xl font-semibold"><?= (int)$leaveCount['total'] ?></div></div>
    <div class="rounded bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">ใบรับรองเวลา HR Recorded</div><div class="mt-2 text-3xl font-semibold"><?= (int)$attendanceCount['total'] ?></div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
