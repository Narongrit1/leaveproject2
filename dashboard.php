<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$user = current_user();
$currentYearBe = current_academic_year_be();
$academicYearBe = normalize_academic_year_be($_GET['academic_year_be'] ?? $currentYearBe);
$years = fetch_all('SELECT year_be, title FROM academic_years WHERE is_active = 1 ORDER BY year_be DESC');
if (!$years) {
    $years = [['year_be' => $currentYearBe, 'title' => 'ปีการศึกษา ' . $currentYearBe]];
}

$role = $user['role'] ?? '';
$scopeParams = [];
if (can_manage_all() || in_array($role, ['dean', 'vice_dean', 'assistant_dean'], true)) {
    $scopeSql = '1=1';
    $scopeLabel = 'ข้อมูลทั้งหมด';
} elseif ($role === 'head_department') {
    $scopeSql = '(r.user_id = ? OR (u.department_id = ? AND u.role IN ("lecturer", "head_department")))';
    $scopeParams = [(int)$user['id'], (int)($user['department_id'] ?? 0)];
    $scopeLabel = 'ข้อมูลของฉันและอาจารย์ในสาขา';
} else {
    $scopeSql = 'r.user_id = ?';
    $scopeParams = [(int)$user['id']];
    $scopeLabel = 'ข้อมูลของฉัน';
}

function dashboard_scope_sql(string $requestAlias, string $baseScopeSql): string
{
    return str_replace('r.', $requestAlias . '.', $baseScopeSql);
}

$leaveScopeSql = dashboard_scope_sql('lr', $scopeSql);
$attendanceScopeSql = dashboard_scope_sql('ar', $scopeSql);

$leaveStats = fetch_all(
    'SELECT lr.status, COUNT(*) AS total
     FROM leave_requests lr
     JOIN users u ON u.id = lr.user_id
     WHERE lr.academic_year_be = ? AND ' . $leaveScopeSql . '
     GROUP BY lr.status',
    array_merge([$academicYearBe], $scopeParams)
);
$attendanceStats = fetch_all(
    'SELECT ar.status, COUNT(*) AS total
     FROM attendance_requests ar
     JOIN users u ON u.id = ar.user_id
     WHERE ar.academic_year_be = ? AND ' . $attendanceScopeSql . '
     GROUP BY ar.status',
    array_merge([$academicYearBe], $scopeParams)
);

$leaveCounts = [];
$totalLeaves = 0;
foreach ($leaveStats as $row) {
    $leaveCounts[(string)$row['status']] = (int)$row['total'];
    $totalLeaves += (int)$row['total'];
}

$attendanceCounts = [];
$totalAttendance = 0;
foreach ($attendanceStats as $row) {
    $attendanceCounts[(string)$row['status']] = (int)$row['total'];
    $totalAttendance += (int)$row['total'];
}

$latestLeaves = fetch_all(
    'SELECT lr.*, u.full_name, lt.name AS leave_type_name
     FROM leave_requests lr
     JOIN users u ON u.id = lr.user_id
     JOIN leave_types lt ON lt.id = lr.leave_type_id
     WHERE lr.academic_year_be = ? AND ' . $leaveScopeSql . '
     ORDER BY lr.created_at DESC
     LIMIT 8',
    array_merge([$academicYearBe], $scopeParams)
);
$latestAttendance = fetch_all(
    'SELECT ar.*, u.full_name
     FROM attendance_requests ar
     JOIN users u ON u.id = ar.user_id
     WHERE ar.academic_year_be = ? AND ' . $attendanceScopeSql . '
     ORDER BY ar.created_at DESC
     LIMIT 8',
    array_merge([$academicYearBe], $scopeParams)
);

$dashboardStatusCards = [
    'draft' => ['icon' => 'file-pen-line', 'color' => '#64748b', 'bg' => '#f1f5f9', 'label' => 'รายการใบลา'],
    'pending_head' => ['icon' => 'clock-3', 'color' => '#d97706', 'bg' => '#fffbeb', 'label' => 'รายการใบลา'],
    'pending_dean' => ['icon' => 'hourglass', 'color' => '#ea580c', 'bg' => '#fff7ed', 'label' => 'รายการใบลา'],
    'approved' => ['icon' => 'circle-check', 'color' => '#059669', 'bg' => '#ecfdf5', 'label' => 'รายการใบลา'],
];
$attendanceStatusCards = [
    'pending_head' => ['icon' => 'clock-3', 'color' => '#ca8a04', 'bg' => '#fefce8'],
    'pending_dean' => ['icon' => 'user-check', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    'approved' => ['icon' => 'badge-check', 'color' => '#0284c7', 'bg' => '#f0f9ff'],
    'hr_recorded' => ['icon' => 'archive-check', 'color' => '#0f766e', 'bg' => '#f0fdfa'],
];
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-hero mb-6">
    <div>
        <div class="section-kicker">Faculty Operations</div>
        <h2>Dashboard ปีการศึกษา <?= e((string)$academicYearBe) ?></h2>
        <p><?= e($scopeLabel) ?> · ปีการศึกษาปัจจุบัน <?= e((string)$currentYearBe) ?></p>
    </div>
    <div class="dashboard-hero-metrics" aria-label="Summary">
        <div><span><?= $totalLeaves ?></span><small>รายการใบลา</small></div>
        <div><span><?= $totalAttendance ?></span><small>รายการใบรับรองเวลา</small></div>
    </div>
    <form method="get" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="form-label" for="academic_year_be">ปีการศึกษา</label>
            <select class="form-input w-48" id="academic_year_be" name="academic_year_be">
                <?php foreach ($years as $year): ?>
                    <option value="<?= (int)$year['year_be'] ?>" <?= (int)$year['year_be'] === $academicYearBe ? 'selected' : '' ?>>
                        <?= e($year['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit"><i data-lucide="filter" class="h-4 w-4"></i>แสดงผล</button>
    </form>
</section>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($dashboardStatusCards as $status => $card): ?>
        <div class="dashboard-stat-card rounded bg-white p-5 shadow-sm" style="--stat-color: <?= e($card['color']) ?>; --stat-bg: <?= e($card['bg']) ?>;">
            <div class="flex items-center justify-between">
                <div class="text-sm text-slate-500"><?= e(status_label($status)) ?></div>
                <span class="stat-icon"><i data-lucide="<?= e($card['icon']) ?>" class="h-5 w-5"></i></span>
            </div>
            <div class="mt-3 text-3xl font-semibold"><?= (int)($leaveCounts[$status] ?? 0) ?></div>
            <div class="mt-1 text-sm text-slate-500"><?= e($card['label']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="data-panel rounded bg-white shadow-sm">
        <div class="panel-header flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">รายการใบลา</h2>
            <a class="text-sm font-medium text-blue-700" href="leave_list.php">ดูทั้งหมด</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr><th class="px-5 py-3">เลขที่</th><th class="px-5 py-3">ผู้ยื่น</th><th class="px-5 py-3">ประเภท</th><th class="px-5 py-3">วันที่</th><th class="px-5 py-3">สถานะ</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($latestLeaves as $row): ?>
                    <tr>
                        <td class="px-5 py-3"><a class="font-medium text-blue-700" href="leave_view.php?id=<?= (int)$row['id'] ?>"><?= e($row['request_no']) ?></a></td>
                        <td class="px-5 py-3"><?= e($row['full_name']) ?></td>
                        <td class="px-5 py-3"><?= e($row['leave_type_name']) ?></td>
                        <td class="px-5 py-3"><?= e(thai_date($row['start_date'])) ?> - <?= e(thai_date($row['end_date'])) ?></td>
                        <td class="px-5 py-3"><span class="rounded px-2 py-1 text-xs <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$latestLeaves): ?><tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">ยังไม่มีรายการ</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="data-panel rounded bg-white shadow-sm">
        <div class="panel-header flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold">รายการใบรับรองเวลา</h2>
            <a class="text-sm font-medium text-blue-700" href="attendance_list.php">ดูทั้งหมด</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr><th class="px-5 py-3">เลขที่</th><th class="px-5 py-3">ผู้ยื่น</th><th class="px-5 py-3">ประเภท</th><th class="px-5 py-3">วันที่เกี่ยวข้อง</th><th class="px-5 py-3">สถานะ</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($latestAttendance as $row): ?>
                    <tr>
                        <td class="px-5 py-3"><a class="font-medium text-blue-700" href="attendance_view.php?id=<?= (int)$row['id'] ?>"><?= e($row['request_no']) ?></a></td>
                        <td class="px-5 py-3"><?= e($row['full_name']) ?></td>
                        <td class="px-5 py-3"><?= e(attendance_request_type_label($row['request_type'] ?? 'time_record')) ?></td>
                        <td class="px-5 py-3">
                            <?php if (($row['request_type'] ?? 'time_record') === 'workday_swap'): ?>
                                <?= e(thai_date($row['absent_date'])) ?> ถึง <?= e(thai_date($row['makeup_date'])) ?>
                            <?php else: ?>
                                <?= e(thai_date($row['work_date'])) ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3"><span class="rounded px-2 py-1 text-xs <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$latestAttendance): ?><tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">ยังไม่มีรายการ</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="data-panel mt-6 rounded bg-white shadow-sm">
    <div class="panel-header border-b border-slate-200 px-5 py-4">
        <h2 class="font-semibold">สรุปใบรับรองเวลา</h2>
    </div>
    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($attendanceStatusCards as $status => $card): ?>
            <div class="dashboard-stat-card rounded bg-white p-4 shadow-sm" style="--stat-color: <?= e($card['color']) ?>; --stat-bg: <?= e($card['bg']) ?>;">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-slate-500"><?= e(status_label($status)) ?></div>
                    <span class="stat-icon"><i data-lucide="<?= e($card['icon']) ?>" class="h-5 w-5"></i></span>
                </div>
                <div class="mt-2 text-2xl font-semibold"><?= (int)($attendanceCounts[$status] ?? 0) ?></div>
                <div class="mt-1 text-sm text-slate-500">รายการใบรับรองเวลา</div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
