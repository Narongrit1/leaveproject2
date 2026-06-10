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

$ownUsedRows = fetch_all(
    'SELECT lr.leave_type_id, COALESCE(SUM(lr.total_days), 0) AS used_days
     FROM leave_requests lr
     WHERE lr.user_id = ?
       AND lr.academic_year_be = ?
       AND lr.status IN ("approved", "hr_recorded")
     GROUP BY lr.leave_type_id',
    [(int)$user['id'], $academicYearBe]
);
$ownUsedByType = [];
foreach ($ownUsedRows as $row) {
    $ownUsedByType[(int)$row['leave_type_id']] = (float)$row['used_days'];
}

$ownLeaveRows = fetch_all(
    'SELECT lt.id, lt.name, lt.code, lt.color, COALESCE(e.entitled_days, 0) AS entitled_days
     FROM leave_types lt
     LEFT JOIN leave_type_entitlements e ON e.leave_type_id = lt.id AND e.academic_year_be = ?
     WHERE lt.is_active = 1
     ORDER BY lt.id',
    [$academicYearBe]
);
$ownLeaveSummary = [];
foreach ($ownLeaveRows as $row) {
    $used = $ownUsedByType[(int)$row['id']] ?? 0.0;
    $entitled = (float)$row['entitled_days'];
    $ownLeaveSummary[] = [
        'name' => (string)$row['name'],
        'code' => (string)($row['code'] ?? ''),
        'color' => trim((string)($row['color'] ?? '')) ?: '#2563eb',
        'used' => $used,
        'entitled' => $entitled,
        'remaining' => max(0, $entitled - $used),
    ];
}

$statusList = ['draft', 'pending_head', 'pending_dean', 'approved', 'rejected', 'cancelled', 'hr_recorded'];
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
    <?php foreach (['draft', 'pending_head', 'pending_dean', 'approved'] as $status): ?>
        <div class="dashboard-stat-card rounded bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="text-sm text-slate-500"><?= e(status_label($status)) ?></div>
                <span class="stat-icon"><i data-lucide="file-text" class="h-5 w-5 text-blue-600"></i></span>
            </div>
            <div class="mt-3 text-3xl font-semibold"><?= (int)($leaveCounts[$status] ?? 0) ?></div>
            <div class="mt-1 text-sm text-slate-500">รายการใบลา</div>
        </div>
    <?php endforeach; ?>
</div>

<section class="data-panel mt-6 rounded bg-white shadow-sm">
    <div class="panel-header border-b border-slate-200 px-5 py-4">
        <h2 class="font-semibold">Dashboard การลาของฉัน</h2>
        <p class="mt-1 text-sm text-slate-500">สรุปสิทธิ์และวันที่ใช้จริงในปีการศึกษา <?= e((string)$academicYearBe) ?></p>
    </div>
    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($ownLeaveSummary as $row): ?>
            <div class="rounded border border-slate-200 p-4">
                <div class="flex items-center gap-2">
                    <span class="inline-block h-3 w-3 rounded-full" style="background: <?= e($row['color']) ?>"></span>
                    <div class="font-medium"><?= e($row['name']) ?></div>
                </div>
                <div class="mt-3 text-2xl font-semibold"><?= e((string)round($row['used'], 2)) ?> วัน</div>
                <div class="mt-1 text-sm text-slate-500">สิทธิ์ <?= e((string)round($row['entitled'], 2)) ?> วัน · คงเหลือ <?= e((string)round($row['remaining'], 2)) ?> วัน</div>
            </div>
        <?php endforeach; ?>
        <?php if (!$ownLeaveSummary): ?>
            <div class="py-8 text-center text-slate-500 md:col-span-2 xl:col-span-4">ยังไม่มีข้อมูลสิทธิ์การลา</div>
        <?php endif; ?>
    </div>
</section>

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
        <?php foreach (['pending_head', 'pending_dean', 'approved', 'hr_recorded'] as $status): ?>
            <div class="rounded border border-slate-200 p-4">
                <div class="text-sm text-slate-500"><?= e(status_label($status)) ?></div>
                <div class="mt-2 text-2xl font-semibold"><?= (int)($attendanceCounts[$status] ?? 0) ?></div>
                <div class="mt-1 text-sm text-slate-500">รายการใบรับรองเวลา</div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
