<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();

$rows = fetch_all('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id ORDER BY u.role, u.full_name');
$pageTitle = 'จัดการผู้ใช้';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4 flex justify-end gap-2">
    <a class="btn btn-secondary" href="users_export_csv.php"><i data-lucide="download" class="h-4 w-4"></i>CSV</a>
    <a class="btn btn-secondary" href="users_import.php"><i data-lucide="upload" class="h-4 w-4"></i>Import</a>
    <a class="btn btn-primary" href="users_create.php"><i data-lucide="user-plus" class="h-4 w-4"></i>เพิ่มผู้ใช้</a>
</div>
<div class="overflow-x-auto rounded bg-white shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">ชื่อ</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">ตำแหน่ง</th><th class="px-4 py-3">สังกัด</th><th class="px-4 py-3">Role</th><th class="px-4 py-3"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="px-4 py-3 font-medium"><?= e($row['full_name']) ?></td><td class="px-4 py-3"><?= e($row['email']) ?></td><td class="px-4 py-3"><?= e($row['position_name']) ?></td><td class="px-4 py-3"><?= e($row['department_name']) ?></td><td class="px-4 py-3"><?= e(role_label($row['role'])) ?></td>
                <td class="px-4 py-3 text-right"><a class="text-blue-700" href="users_edit.php?id=<?= (int)$row['id'] ?>">แก้ไข</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

