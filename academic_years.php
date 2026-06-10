<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['admin']);
enforce_password_change();

function ensure_academic_year_management_tables(): void
{
    db()->exec("
        CREATE TABLE IF NOT EXISTS academic_years (
            year_be INT UNSIGNED PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            starts_on DATE NULL,
            ends_on DATE NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS leave_type_entitlements (
            academic_year_be INT UNSIGNED NOT NULL,
            leave_type_id INT UNSIGNED NOT NULL,
            entitled_days DECIMAL(6,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (academic_year_be, leave_type_id),
            CONSTRAINT fk_leave_entitlements_year FOREIGN KEY (academic_year_be) REFERENCES academic_years(year_be) ON DELETE CASCADE,
            CONSTRAINT fk_leave_entitlements_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_academic_year_seed(int $yearBe): void
{
    execute_stmt(
        'INSERT INTO academic_years (year_be, title, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_active = 1',
        [$yearBe, 'ปีการศึกษา ' . $yearBe]
    );
    execute_stmt(
        'INSERT INTO leave_type_entitlements (academic_year_be, leave_type_id, entitled_days)
         SELECT ?, lt.id, COALESCE(MAX(lb.entitled_days), 0)
         FROM leave_types lt
         LEFT JOIN leave_balances lb ON lb.leave_type_id = lt.id AND lb.year_be = ?
         GROUP BY lt.id
         ON DUPLICATE KEY UPDATE entitled_days = entitled_days',
        [$yearBe, $yearBe]
    );
}

ensure_academic_year_management_tables();
$defaultYearBe = current_academic_year_be();
ensure_academic_year_seed($defaultYearBe);

if (is_post()) {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_year') {
        $yearBe = normalize_academic_year_be($_POST['year_be'] ?? $defaultYearBe);
        $title = trim((string)($_POST['title'] ?? ''));
        $startsOn = trim((string)($_POST['starts_on'] ?? ''));
        $endsOn = trim((string)($_POST['ends_on'] ?? ''));
        $isActive = empty($_POST['is_active']) ? 0 : 1;
        if ($title === '') {
            $title = 'ปีการศึกษา ' . $yearBe;
        }

        execute_stmt(
            'INSERT INTO academic_years (year_be, title, starts_on, ends_on, is_active)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE title = VALUES(title), starts_on = VALUES(starts_on), ends_on = VALUES(ends_on), is_active = VALUES(is_active)',
            [$yearBe, $title, $startsOn === '' ? null : $startsOn, $endsOn === '' ? null : $endsOn, $isActive]
        );
        ensure_academic_year_seed($yearBe);

        if (!empty($_POST['set_current'])) {
            execute_stmt(
                'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                ['academic_year_be', (string)$yearBe]
            );
        }

        set_flash('success', 'บันทึกปีการศึกษาแล้ว');
        redirect_to('academic_years.php?year_be=' . $yearBe);
    }

    if ($action === 'save_entitlements') {
        $yearBe = normalize_academic_year_be($_POST['academic_year_be'] ?? $defaultYearBe);
        ensure_academic_year_seed($yearBe);
        $entitlements = $_POST['entitled_days'] ?? [];

        if (is_array($entitlements)) {
            foreach ($entitlements as $leaveTypeId => $days) {
                $leaveTypeId = (int)$leaveTypeId;
                $entitledDays = max(0, (float)$days);
                execute_stmt(
                    'INSERT INTO leave_type_entitlements (academic_year_be, leave_type_id, entitled_days)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE entitled_days = VALUES(entitled_days)',
                    [$yearBe, $leaveTypeId, $entitledDays]
                );
            }
        }

        if (!empty($_POST['sync_balances'])) {
            execute_stmt(
                'INSERT INTO leave_balances (user_id, leave_type_id, year_be, entitled_days, used_days)
                 SELECT u.id, e.leave_type_id, e.academic_year_be, e.entitled_days, 0
                 FROM users u
                 JOIN leave_type_entitlements e ON e.academic_year_be = ?
                 WHERE u.is_active = 1
                 ON DUPLICATE KEY UPDATE entitled_days = VALUES(entitled_days)',
                [$yearBe]
            );
        }

        set_flash('success', 'บันทึกมาตรฐานจำนวนวันลาแล้ว');
        redirect_to('academic_years.php?year_be=' . $yearBe);
    }
}

$years = fetch_all('SELECT * FROM academic_years ORDER BY year_be DESC');
$selectedYearBe = normalize_academic_year_be($_GET['year_be'] ?? $defaultYearBe);
ensure_academic_year_seed($selectedYearBe);
$selectedYear = fetch_one('SELECT * FROM academic_years WHERE year_be = ?', [$selectedYearBe]);
$leaveTypes = fetch_all(
    'SELECT lt.*, COALESCE(e.entitled_days, 0) AS entitled_days
     FROM leave_types lt
     LEFT JOIN leave_type_entitlements e ON e.leave_type_id = lt.id AND e.academic_year_be = ?
     ORDER BY lt.id',
    [$selectedYearBe]
);
$currentYearBe = current_academic_year_be();

$pageTitle = 'จัดการปีการศึกษา';
require __DIR__ . '/includes/header.php';
?>
<div class="grid gap-6 xl:grid-cols-[380px_1fr]">
    <section class="space-y-6">
        <form method="post" class="rounded bg-white p-5 shadow-sm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_year">
            <h2 class="font-semibold">เพิ่ม/แก้ไขปีการศึกษา</h2>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="form-label" for="year_be">ปีการศึกษา (พ.ศ.)</label>
                    <input class="form-input" id="year_be" name="year_be" type="number" min="2400" max="2700" value="<?= e((string)$selectedYearBe) ?>" required>
                </div>
                <div>
                    <label class="form-label" for="title">ชื่อแสดงผล</label>
                    <input class="form-input" id="title" name="title" value="<?= e($selectedYear['title'] ?? ('ปีการศึกษา ' . $selectedYearBe)) ?>">
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="starts_on">วันเริ่มต้น</label>
                        <input class="form-input" id="starts_on" name="starts_on" type="date" value="<?= e($selectedYear['starts_on'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label" for="ends_on">วันสิ้นสุด</label>
                        <input class="form-input" id="ends_on" name="ends_on" type="date" value="<?= e($selectedYear['ends_on'] ?? '') ?>">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" <?= empty($selectedYear) || !empty($selectedYear['is_active']) ? 'checked' : '' ?>>
                    เปิดใช้งานปีการศึกษานี้
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="set_current" value="1" <?= $selectedYearBe === $currentYearBe ? 'checked' : '' ?>>
                    ตั้งเป็นปีการศึกษาปัจจุบัน
                </label>
            </div>
            <button class="btn btn-primary mt-4" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึกปีการศึกษา</button>
        </form>

        <section class="rounded bg-white p-5 shadow-sm">
            <h2 class="font-semibold">ปีการศึกษาทั้งหมด</h2>
            <div class="mt-3 space-y-2 text-sm">
                <?php foreach ($years as $year): ?>
                    <a class="flex items-center justify-between rounded border border-slate-200 px-3 py-2 hover:bg-slate-50" href="academic_years.php?year_be=<?= (int)$year['year_be'] ?>">
                        <span class="font-medium"><?= e($year['title']) ?></span>
                        <span class="text-xs text-slate-500"><?= (int)$year['year_be'] === $currentYearBe ? 'ปัจจุบัน' : ((int)$year['is_active'] === 1 ? 'ใช้งาน' : 'ปิด') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </section>

    <form method="post" class="rounded bg-white p-5 shadow-sm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_entitlements">
        <input type="hidden" name="academic_year_be" value="<?= e((string)$selectedYearBe) ?>">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold">มาตรฐานจำนวนวันลา</h2>
                <p class="mt-1 text-sm text-slate-500"><?= e($selectedYear['title'] ?? ('ปีการศึกษา ' . $selectedYearBe)) ?></p>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="sync_balances" value="1">
                อัปเดตโควตาผู้ใช้ตามมาตรฐานนี้
            </label>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr><th class="px-4 py-3">ประเภทการลา</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">วันลามาตรฐาน</th><th class="px-4 py-3">สถานะ</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($leaveTypes as $type): ?>
                    <tr>
                        <td class="px-4 py-3 font-medium"><?= e($type['name']) ?></td>
                        <td class="px-4 py-3"><?= e($type['code']) ?></td>
                        <td class="px-4 py-3">
                            <input class="form-input w-32" name="entitled_days[<?= (int)$type['id'] ?>]" type="number" min="0" step="0.5" value="<?= e((string)$type['entitled_days']) ?>">
                        </td>
                        <td class="px-4 py-3"><?= (int)$type['is_active'] === 1 ? 'ใช้งาน' : 'ปิด' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary mt-4" type="submit"><i data-lucide="save" class="h-4 w-4"></i>บันทึกมาตรฐานวันลา</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
