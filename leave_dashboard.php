<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
enforce_password_change();

$academicYearBe = normalize_academic_year_be($_GET['academic_year_be'] ?? current_academic_year_be());
$years = fetch_all('SELECT year_be, title FROM academic_years WHERE is_active = 1 ORDER BY year_be DESC');
if (!$years) {
    $years = [['year_be' => $academicYearBe, 'title' => 'ปีการศึกษา ' . $academicYearBe]];
}

$academicRoles = ['lecturer', 'head_department', 'dean', 'vice_dean', 'assistant_dean'];
$currentUser = current_user();
$role = $currentUser['role'] ?? '';
$teacherWhere = 'u.is_active = 1 AND u.role IN ("lecturer","head_department","dean","vice_dean","assistant_dean")';
$teacherParams = [];
if ($role === 'head_department') {
    $teacherWhere .= ' AND u.department_id = ?';
    $teacherParams[] = (int)($currentUser['department_id'] ?? 0);
} elseif (!can_manage_all() && !in_array($role, ['dean', 'vice_dean', 'assistant_dean'], true)) {
    $teacherWhere .= ' AND u.id = ?';
    $teacherParams[] = (int)($currentUser['id'] ?? 0);
}

$teacherOptions = fetch_all('SELECT u.id, u.full_name FROM users u WHERE ' . $teacherWhere . ' ORDER BY u.full_name', $teacherParams);
$selectedTeacherId = (int)($_GET['teacher_id'] ?? 0);
if ($selectedTeacherId > 0) {
    $allowedTeacherIds = array_map(static function ($teacher) {
        return (int)$teacher['id'];
    }, $teacherOptions);
    if (in_array($selectedTeacherId, $allowedTeacherIds, true)) {
        $teacherWhere .= ' AND u.id = ?';
        $teacherParams[] = $selectedTeacherId;
    } else {
        $selectedTeacherId = 0;
    }
}

$teacherCountRow = fetch_one('SELECT COUNT(*) AS total FROM users u WHERE ' . $teacherWhere, $teacherParams);
$teacherCount = max(1, (int)($teacherCountRow['total'] ?? 0));

$usedRows = fetch_all(
    'SELECT lr.leave_type_id, COALESCE(SUM(lr.total_days), 0) AS used_days
     FROM leave_requests lr
     JOIN users u ON u.id = lr.user_id
     WHERE lr.academic_year_be = ?
       AND lr.status IN ("approved", "hr_recorded")
       AND ' . $teacherWhere . '
     GROUP BY lr.leave_type_id',
    array_merge([$academicYearBe], $teacherParams)
);
$usedByType = [];
foreach ($usedRows as $row) {
    $usedByType[(int)$row['leave_type_id']] = (float)$row['used_days'];
}

$rows = fetch_all(
    'SELECT lt.id, lt.name, lt.code, lt.color, COALESCE(e.entitled_days, 0) AS standard_days
     FROM leave_types lt
     LEFT JOIN leave_type_entitlements e ON e.leave_type_id = lt.id AND e.academic_year_be = ?
     WHERE lt.is_active = 1
     ORDER BY lt.id',
    [$academicYearBe]
);

$palette = ['#2563eb', '#ef4444', '#f59e0b', '#10b981', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'];
$labels = [];
$usedData = [];
$standardData = [];
$colors = [];
$standardColors = [];
$tableRows = [];
$summaryCards = [];
$totalUsed = 0.0;
$totalStandard = 0.0;

foreach ($rows as $index => $row) {
    $leaveTypeId = (int)$row['id'];
    $used = $usedByType[$leaveTypeId] ?? 0.0;
    $standard = (float)$row['standard_days'] * $teacherCount;
    $color = trim((string)($row['color'] ?? '')) ?: $palette[$index % count($palette)];
    $labels[] = (string)$row['name'];
    $usedData[] = round($used, 2);
    $standardData[] = round($standard, 2);
    $colors[] = $color;
    $standardColors[] = 'rgba(148, 163, 184, 0.24)';
    $totalUsed += $used;
    $totalStandard += $standard;
    if (in_array($row['code'] ?? '', ['sick', 'personal', 'vacation'], true)) {
        $summaryCards[(string)$row['code']] = [
            'name' => (string)$row['name'],
            'used' => $used,
            'standard' => $standard,
            'color' => $color,
        ];
    }
    $tableRows[] = [
        'name' => (string)$row['name'],
        'used' => $used,
        'standard' => $standard,
        'remaining' => max(0, $standard - $used),
        'percent' => $standard > 0 ? min(999, round(($used / $standard) * 100, 1)) : 0,
        'color' => $color,
    ];
}

$pageTitle = 'Dashboard สรุปการลา';
require __DIR__ . '/includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<section class="leave-analytics-hero mb-6">
    <div>
        <div class="section-kicker">Leave Analytics</div>
        <h2>Dashboard สรุปการลา</h2>
        <p>เปรียบเทียบวันลาที่ใช้จริงของอาจารย์กับค่ามาตรฐาน แยกตามประเภทการลา</p>
    </div>
    <form method="get" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="form-label" for="academic_year_be">ปีการศึกษา</label>
            <select class="form-input w-48" id="academic_year_be" name="academic_year_be">
                <?php foreach ($years as $year): ?>
                    <option value="<?= (int)$year['year_be'] ?>" <?= (int)$year['year_be'] === $academicYearBe ? 'selected' : '' ?>><?= e($year['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="teacher_id">อาจารย์</label>
            <select class="form-input w-64" id="teacher_id" name="teacher_id">
                <option value="0">อาจารย์ทั้งหมด</option>
                <?php foreach ($teacherOptions as $teacher): ?>
                    <option value="<?= (int)$teacher['id'] ?>" <?= (int)$teacher['id'] === $selectedTeacherId ? 'selected' : '' ?>><?= e($teacher['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit"><i data-lucide="filter" class="h-4 w-4"></i>แสดงผล</button>
    </form>
</section>

<div class="mb-6 grid gap-4 md:grid-cols-3">
    <?php $summaryLabels = ['sick' => 'จำนวนวันลาป่วย', 'personal' => 'จำนวนวันลากิจ', 'vacation' => 'จำนวนวันลาพักร้อน']; ?>
    <?php foreach (['sick', 'personal', 'vacation'] as $code): ?>
        <?php $card = $summaryCards[$code] ?? ['name' => $code, 'used' => 0, 'standard' => 0, 'color' => '#2563eb']; ?>
        <div class="leave-summary-card rounded p-5 text-white shadow-sm" style="--summary-color: <?= e($card['color']) ?>">
            <div class="text-sm font-medium opacity-90"><?= e($summaryLabels[$code]) ?></div>
            <div class="mt-2 text-3xl font-semibold"><?= e((string)round((float)$card['used'], 2)) ?> วัน</div>
            <div class="mt-2 text-sm opacity-85">มาตรฐาน <?= e((string)round((float)$card['standard'], 2)) ?> วัน</div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid gap-6 xl:grid-cols-[1.35fr_0.9fr]">
    <section class="chart-panel rounded bg-white p-5 shadow-sm">
        <div class="panel-header-lite">
            <h3>กราฟแท่งแนวนอน</h3>
            <p>สีสดแสดงวันลาที่ใช้จริง เทียบกับเส้นมาตรฐานของแต่ละประเภท</p>
        </div>
        <div class="chart-wrap chart-wrap-wide" style="height: <?= max(24, count($labels) * 3.2) ?>rem"><canvas id="leaveBarChart"></canvas></div>
    </section>
    <section class="chart-panel rounded bg-white p-5 shadow-sm">
        <div class="panel-header-lite">
            <h3>Radar เปรียบเทียบ</h3>
            <p>มองภาพรวมความต่างระหว่างใช้จริงและมาตรฐาน</p>
        </div>
        <div class="chart-wrap"><canvas id="leaveRadarChart"></canvas></div>
    </section>
</div>

<section class="mt-6 overflow-x-auto rounded bg-white shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr><th class="px-4 py-3">ประเภทการลา</th><th class="px-4 py-3">ใช้จริง</th><th class="px-4 py-3">มาตรฐาน</th><th class="px-4 py-3">คงเหลือ</th><th class="px-4 py-3">ใช้ไป</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($tableRows as $row): ?>
                <tr>
                    <td class="px-4 py-3 font-medium"><span class="mr-2 inline-block h-3 w-3 rounded-full" style="background:<?= e($row['color']) ?>"></span><?= e($row['name']) ?></td>
                    <td class="px-4 py-3"><?= e((string)round($row['used'], 2)) ?> วัน</td>
                    <td class="px-4 py-3"><?= e((string)round($row['standard'], 2)) ?> วัน</td>
                    <td class="px-4 py-3"><?= e((string)round($row['remaining'], 2)) ?> วัน</td>
                    <td class="px-4 py-3"><?= e((string)$row['percent']) ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
    const usedData = <?= json_encode($usedData) ?>;
    const standardData = <?= json_encode($standardData) ?>;
    const colors = <?= json_encode($colors) ?>;
    const standardColors = <?= json_encode($standardColors) ?>;
    const gridColor = 'rgba(148, 163, 184, 0.25)';

    new Chart(document.getElementById('leaveBarChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'มาตรฐาน', data: standardData, backgroundColor: standardColors, barThickness: 24, maxBarThickness: 24, borderRadius: 12, borderSkipped: false, order: 1 },
                { label: 'ใช้จริง', data: usedData, backgroundColor: colors, barThickness: 14, maxBarThickness: 14, borderRadius: 7, borderSkipped: false, order: 2 }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            datasets: { bar: { grouped: false } },
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { beginAtZero: true, grid: { color: gridColor }, title: { display: true, text: 'จำนวนวัน' } },
                y: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('leaveRadarChart'), {
        type: 'radar',
        data: {
            labels,
            datasets: [
                { label: 'ใช้จริง', data: usedData, backgroundColor: 'rgba(20, 184, 166, 0.22)', borderColor: '#14b8a6', pointBackgroundColor: colors, pointBorderColor: '#fff', borderWidth: 3 },
                { label: 'มาตรฐาน', data: standardData, backgroundColor: 'rgba(245, 158, 11, 0.18)', borderColor: '#f59e0b', pointBackgroundColor: '#f59e0b', borderWidth: 3 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { r: { beginAtZero: true, grid: { color: gridColor }, angleLines: { color: gridColor }, pointLabels: { color: '#334155' } } }
        }
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
