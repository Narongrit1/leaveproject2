<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();

if (is_post()) {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $code = trim((string)($_POST['code'] ?? ''));
    if ($name !== '' && $code !== '') {
        execute_stmt('INSERT INTO leave_types (name, code, requires_attachment_days, advance_working_days, color, is_active) VALUES (?, ?, ?, ?, ?, 1)', [
            $name, $code, $_POST['requires_attachment_days'] === '' ? null : (float)$_POST['requires_attachment_days'], (int)($_POST['advance_working_days'] ?? 0), trim((string)($_POST['color'] ?? '#2563eb'))
        ]);
        set_flash('success', 'เพิ่มประเภทการลาแล้ว');
    }
    redirect_to('leave_types_list.php');
}
$rows = fetch_all('SELECT * FROM leave_types ORDER BY id');
$pageTitle = 'จัดการประเภทการลา';
require __DIR__ . '/includes/header.php';
?>
<div class="grid gap-6 lg:grid-cols-[380px_1fr]">
    <form method="post" class="rounded bg-white p-5 shadow-sm">
        <?= csrf_field() ?>
        <h2 class="font-semibold">เพิ่มประเภทการลา</h2>
        <div class="mt-4 space-y-3">
            <div><label class="form-label">ชื่อ</label><input class="form-input" name="name" required></div>
            <div><label class="form-label">Code</label><input class="form-input" name="code" required></div>
            <div><label class="form-label">ต้องแนบไฟล์เมื่อวันลา >=</label><input class="form-input" name="requires_attachment_days" type="number" step="0.5"></div>
            <div><label class="form-label">ยื่นล่วงหน้า วันทำการ</label><input class="form-input" name="advance_working_days" type="number" value="0"></div>
            <div><label class="form-label">สีปฏิทิน</label><input class="form-input" name="color" type="color" value="#2563eb"></div>
        </div>
        <button class="btn btn-primary mt-4" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึก</button>
    </form>
    <div class="overflow-x-auto rounded bg-white shadow-sm">
        <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">ชื่อ</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">แนบไฟล์</th><th class="px-4 py-3">ล่วงหน้า</th><th class="px-4 py-3">สี</th></tr></thead><tbody class="divide-y divide-slate-100"><?php foreach ($rows as $row): ?><tr><td class="px-4 py-3 font-medium"><?= e($row['name']) ?></td><td class="px-4 py-3"><?= e($row['code']) ?></td><td class="px-4 py-3"><?= e((string)($row['requires_attachment_days'] ?? '-')) ?></td><td class="px-4 py-3"><?= (int)$row['advance_working_days'] ?></td><td class="px-4 py-3"><span class="inline-block h-5 w-8 rounded" style="background:<?= e($row['color']) ?>"></span></td></tr><?php endforeach; ?></tbody></table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

