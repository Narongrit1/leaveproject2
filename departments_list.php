<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();

if (is_post()) {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $id = (int)($_POST['id'] ?? 0);
    if ($name !== '') {
        if ($id > 0) {
            execute_stmt('UPDATE departments SET name = ? WHERE id = ?', [$name, $id]);
        } else {
            execute_stmt('INSERT INTO departments (name) VALUES (?)', [$name]);
        }
        set_flash('success', 'บันทึกภาควิชาแล้ว');
    }
    redirect_to('departments_list.php');
}
$rows = fetch_all('SELECT d.*, COUNT(u.id) AS user_count FROM departments d LEFT JOIN users u ON u.department_id = d.id GROUP BY d.id ORDER BY d.name');
$pageTitle = 'จัดการภาควิชา';
require __DIR__ . '/includes/header.php';
?>
<div class="grid gap-6 lg:grid-cols-[360px_1fr]">
    <form method="post" class="rounded bg-white p-5 shadow-sm">
        <?= csrf_field() ?>
        <h2 class="font-semibold">เพิ่มภาควิชา</h2>
        <label class="form-label mt-4" for="name">ชื่อภาควิชา/หน่วยงาน</label>
        <input class="form-input" id="name" name="name" required>
        <button class="btn btn-primary mt-4" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึก</button>
    </form>
    <div class="overflow-x-auto rounded bg-white shadow-sm">
        <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">ชื่อ</th><th class="px-4 py-3">จำนวนผู้ใช้</th></tr></thead><tbody class="divide-y divide-slate-100"><?php foreach ($rows as $row): ?><tr><td class="px-4 py-3 font-medium"><?= e($row['name']) ?></td><td class="px-4 py-3"><?= (int)$row['user_count'] ?></td></tr><?php endforeach; ?></tbody></table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

