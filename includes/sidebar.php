<?php
$user = current_user();
$role = $user['role'] ?? '';
$items = [
    ['href' => 'dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'roles' => []],
    ['href' => 'leave_dashboard.php', 'icon' => 'bar-chart-3', 'label' => 'Dashboard สรุปการลา', 'roles' => ['admin', 'hr', 'dean', 'vice_dean', 'assistant_dean', 'head_department']],
    ['href' => 'leave_create.php', 'icon' => 'file-plus-2', 'label' => 'ยื่นใบลา', 'roles' => []],
    ['href' => 'leave_list.php', 'icon' => 'files', 'label' => 'รายการใบลา', 'roles' => []],
    ['href' => 'attendance_create.php', 'icon' => 'clipboard-plus', 'label' => 'ยื่นใบรับรองเวลา', 'roles' => []],
    ['href' => 'attendance_list.php', 'icon' => 'clipboard-list', 'label' => 'รายการใบรับรองเวลา', 'roles' => []],
    ['href' => 'leave_calendar.php', 'icon' => 'calendar-days', 'label' => 'ปฏิทินการลา', 'roles' => ['admin', 'hr', 'dean', 'vice_dean', 'assistant_dean', 'head_department']],
    ['href' => 'report_leave_summary.php', 'icon' => 'bar-chart-3', 'label' => 'รายงาน', 'roles' => ['admin', 'hr', 'dean', 'vice_dean', 'assistant_dean', 'head_department']],
    ['href' => 'users_list.php', 'icon' => 'users', 'label' => 'จัดการผู้ใช้', 'roles' => ['admin']],
    ['href' => 'approval_workflows.php', 'icon' => 'git-branch', 'label' => 'ลำดับการอนุมัติ', 'roles' => ['admin']],
    ['href' => 'departments_list.php', 'icon' => 'building-2', 'label' => 'ภาควิชา', 'roles' => ['admin']],
    ['href' => 'academic_years.php', 'icon' => 'calendar-range', 'label' => 'ปีการศึกษา', 'roles' => ['admin']],
    ['href' => 'leave_types_list.php', 'icon' => 'settings-2', 'label' => 'ประเภทการลา', 'roles' => ['admin']],
];
$current = basename($_SERVER['PHP_SELF'] ?? '');
?>
<aside class="app-sidebar border-r border-slate-200 bg-white lg:w-72">
    <div class="sidebar-brand flex h-16 items-center gap-3 border-b border-slate-200 px-5">
        <div class="brand-mark grid h-10 w-10 place-items-center rounded bg-blue-700 text-white"><i data-lucide="building"></i></div>
        <div>
            <div class="text-sm font-semibold text-slate-900">มหาวิทยาลัยสยาม</div>
            <div class="text-xs text-slate-500">Faculty Leave System</div>
        </div>
    </div>
    <nav class="sidebar-nav space-y-1 p-3" aria-label="Main menu">
        <?php foreach ($items as $item): ?>
            <?php if (!empty($item['roles']) && !in_array($role, $item['roles'], true)) { continue; } ?>
            <?php $active = $current === $item['href']; ?>
            <a href="<?= e($item['href']) ?>" class="sidebar-link flex items-center gap-3 rounded px-3 py-2 text-sm font-medium <?= $active ? 'is-active bg-blue-700 text-white' : 'text-slate-700 hover:bg-slate-100' ?>">
                <i data-lucide="<?= e($item['icon']) ?>" class="h-4 w-4"></i>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
