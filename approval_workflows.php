<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();

ensure_approval_workflows_table();

$requesterRoles = workflow_requester_roles();
$approverRoles = workflow_approver_roles();
$error = '';

if (is_post()) {
    verify_csrf();
    $step1 = $_POST['step1'] ?? [];
    $step2 = $_POST['step2'] ?? [];

    if (!is_array($step1) || !is_array($step2)) {
        $error = 'รูปแบบข้อมูลไม่ถูกต้อง';
    } else {
        $newRows = [];
        foreach ($requesterRoles as $requesterRole) {
            $steps = [
                (string)($step1[$requesterRole] ?? ''),
                (string)($step2[$requesterRole] ?? ''),
            ];
            $steps = array_values(array_filter($steps, static fn(string $role): bool => $role !== ''));

            if (count($steps) !== count(array_unique($steps))) {
                $error = 'ไม่ควรกำหนดผู้อนุมัติ role เดิมซ้ำใน workflow เดียวกัน';
                break;
            }
            foreach ($steps as $approverRole) {
                if (!in_array($approverRole, $approverRoles, true)) {
                    $error = 'พบ role ผู้อนุมัติที่ไม่รองรับ';
                    break 2;
                }
            }
            foreach ($steps as $index => $approverRole) {
                $newRows[] = [$requesterRole, $index + 1, $approverRole, workflow_status_for_role($approverRole)];
            }
        }

        if ($error === '') {
            db()->beginTransaction();
            try {
                execute_stmt("DELETE FROM approval_workflows WHERE request_type = 'all'");
                foreach ($newRows as $row) {
                    execute_stmt(
                        'INSERT INTO approval_workflows (request_type, requester_role, step_order, approver_role, status_code, is_active) VALUES (?, ?, ?, ?, ?, 1)',
                        ['all', $row[0], $row[1], $row[2], $row[3]]
                    );
                }
                db()->commit();
                set_flash('success', 'บันทึกลำดับการอนุมัติเรียบร้อย');
                redirect_to('approval_workflows.php');
            } catch (Throwable $e) {
                db()->rollBack();
                $error = 'บันทึกไม่สำเร็จ: ' . $e->getMessage();
            }
        }
    }
}

$workflowRows = fetch_all("SELECT * FROM approval_workflows WHERE request_type = 'all' AND is_active = 1 ORDER BY requester_role, step_order");
$workflow = [];
foreach ($workflowRows as $row) {
    $workflow[$row['requester_role']][(int)$row['step_order']] = $row['approver_role'];
}

$pageTitle = 'ลำดับการอนุมัติ';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4 rounded bg-white p-4 text-sm text-slate-600 shadow-sm">
    กำหนดลำดับผู้อนุมัติตาม role ของผู้ยื่นคำขอ ใช้ร่วมกันทั้งใบลาและใบรับรองเวลา หากไม่เลือกผู้อนุมัติ ระบบจะอนุมัติทันทีเมื่อส่งคำขอ
</div>

<?php if ($error): ?><div class="mb-4 rounded bg-rose-50 p-3 text-sm text-rose-700"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="rounded bg-white shadow-sm">
    <?= csrf_field() ?>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Role ผู้ยื่น</th>
                    <th class="px-4 py-3">ขั้นที่ 1</th>
                    <th class="px-4 py-3">ขั้นที่ 2</th>
                    <th class="px-4 py-3">ผลลัพธ์</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($requesterRoles as $requesterRole): ?>
                <?php
                    $first = $workflow[$requesterRole][1] ?? '';
                    $second = $workflow[$requesterRole][2] ?? '';
                    $summary = array_filter([$first ? role_label($first) : '', $second ? role_label($second) : '']);
                ?>
                <tr>
                    <td class="px-4 py-3 font-medium"><?= e(role_label($requesterRole)) ?></td>
                    <td class="px-4 py-3">
                        <select class="form-input min-w-56" name="step1[<?= e($requesterRole) ?>]">
                            <option value="">อนุมัติทันที</option>
                            <?php foreach ($approverRoles as $role): ?>
                            <option value="<?= e($role) ?>" <?= $first === $role ? 'selected' : '' ?>><?= e(role_label($role)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="px-4 py-3">
                        <select class="form-input min-w-56" name="step2[<?= e($requesterRole) ?>]">
                            <option value="">ไม่มีขั้นต่อไป</option>
                            <?php foreach ($approverRoles as $role): ?>
                            <option value="<?= e($role) ?>" <?= $second === $role ? 'selected' : '' ?>><?= e(role_label($role)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="px-4 py-3 text-slate-600"><?= $summary ? e(implode(' -> ', $summary) . ' -> อนุมัติ') : 'อนุมัติทันที' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-200 p-4">
        <button class="btn btn-primary" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึก</button>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
