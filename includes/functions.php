<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function status_label(string $status): string
{
    $labels = [
        'draft' => 'แบบร่าง',
        'pending_head' => 'รอหัวหน้าภาคอนุมัติ',
        'pending_dean' => 'รอคณบดีอนุมัติ',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ไม่อนุมัติ',
        'cancelled' => 'ยกเลิก',
        'hr_recorded' => 'HR บันทึกแล้ว',
    ];
    return $labels[$status] ?? $status;
}

function status_badge_class(string $status): string
{
    $classes = [
        'draft' => 'bg-slate-100 text-slate-700',
        'pending_head' => 'bg-amber-100 text-amber-800',
        'pending_dean' => 'bg-orange-100 text-orange-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
        'cancelled' => 'bg-slate-200 text-slate-700',
        'hr_recorded' => 'bg-cyan-100 text-cyan-800',
    ];
    return $classes[$status] ?? 'bg-slate-100 text-slate-700';
}

function role_label(string $role): string
{
    $labels = [
        'admin' => 'ผู้ดูแลระบบ',
        'dean' => 'คณบดี',
        'vice_dean' => 'รองคณบดี',
        'assistant_dean' => 'ผู้ช่วยคณบดี',
        'head_department' => 'หัวหน้าภาควิชา',
        'lecturer' => 'อาจารย์',
        'staff' => 'เจ้าหน้าที่',
        'hr' => 'HR',
    ];
    return $labels[$role] ?? $role;
}

function can_manage_all(): bool
{
    $role = $_SESSION['user']['role'] ?? '';
    return in_array($role, ['admin', 'hr'], true);
}

function can_approve(): bool
{
    $role = $_SESSION['user']['role'] ?? '';
    return in_array($role, ['dean', 'vice_dean', 'assistant_dean', 'head_department'], true);
}

function workflow_requester_roles(): array
{
    return ['admin', 'dean', 'vice_dean', 'assistant_dean', 'head_department', 'lecturer', 'staff', 'hr'];
}

function workflow_approver_roles(): array
{
    return ['head_department', 'dean', 'vice_dean', 'assistant_dean'];
}

function workflow_status_for_role(string $role): string
{
    return $role === 'head_department' ? 'pending_head' : 'pending_dean';
}

function ensure_approval_workflows_table(): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS approval_workflows (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_type VARCHAR(30) NOT NULL DEFAULT 'all',
            requester_role VARCHAR(50) NOT NULL,
            step_order INT UNSIGNED NOT NULL,
            approver_role VARCHAR(50) NOT NULL,
            status_code VARCHAR(50) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_approval_workflow (request_type, requester_role, step_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $count = (int)(fetch_one('SELECT COUNT(*) AS total FROM approval_workflows')['total'] ?? 0);
    if ($count === 0) {
        $defaults = [
            'admin' => ['head_department', 'dean'],
            'lecturer' => ['head_department', 'dean'],
            'staff' => ['head_department', 'dean'],
            'hr' => ['head_department', 'dean'],
            'head_department' => ['dean'],
            'vice_dean' => ['dean'],
            'assistant_dean' => ['dean'],
            'dean' => [],
        ];
        foreach ($defaults as $requesterRole => $steps) {
            foreach ($steps as $index => $approverRole) {
                execute_stmt(
                    'INSERT INTO approval_workflows (request_type, requester_role, step_order, approver_role, status_code, is_active) VALUES (?, ?, ?, ?, ?, 1)',
                    ['all', $requesterRole, $index + 1, $approverRole, workflow_status_for_role($approverRole)]
                );
            }
        }
    }

    $ensured = true;
}

function approval_workflow_steps(string $requestType, string $requesterRole): array
{
    ensure_approval_workflows_table();
    $specific = fetch_all(
        'SELECT approver_role, status_code FROM approval_workflows WHERE request_type = ? AND requester_role = ? AND is_active = 1 ORDER BY step_order',
        [$requestType, $requesterRole]
    );
    if ($specific) {
        return $specific;
    }

    return fetch_all(
        'SELECT approver_role, status_code FROM approval_workflows WHERE request_type = ? AND requester_role = ? AND is_active = 1 ORDER BY step_order',
        ['all', $requesterRole]
    );
}

function approver_role_for_status(string $status, array $requestUser, string $requestType = 'leave'): ?string
{
    foreach (approval_workflow_steps($requestType, (string)($requestUser['role'] ?? '')) as $step) {
        if ($step['status_code'] === $status) {
            return $step['approver_role'];
        }
    }
    return null;
}

function next_pending_status(array $user, string $requestType = 'leave'): string
{
    $steps = approval_workflow_steps($requestType, (string)($user['role'] ?? ''));
    return $steps ? (string)$steps[0]['status_code'] : 'approved';
}

function next_status_after_approval(string $currentStatus, array $requestUser, string $requestType = 'leave'): string
{
    $steps = approval_workflow_steps($requestType, (string)($requestUser['role'] ?? ''));
    foreach ($steps as $index => $step) {
        if ($step['status_code'] === $currentStatus) {
            return isset($steps[$index + 1]) ? (string)$steps[$index + 1]['status_code'] : 'approved';
        }
    }
    return 'approved';
}

function calculate_days(string $startDate, string $endDate): float
{
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    if ($end < $start) {
        return 0;
    }
    return (float)$start->diff($end)->days + 1;
}

function working_days_between(DateTimeImmutable $start, DateTimeImmutable $end): int
{
    $days = 0;
    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        if ((int)$date->format('N') < 6) {
            $days++;
        }
    }
    return $days;
}

function validate_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return 'อัปโหลดไฟล์ไม่สำเร็จ';
    }
    if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        return 'ไฟล์ต้องมีขนาดไม่เกิน 5MB';
    }
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        return 'รองรับเฉพาะไฟล์ PDF, JPG, JPEG, PNG';
    }
    return null;
}

function save_upload(array $file, string $targetDir): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $safeName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        throw new RuntimeException('Cannot save uploaded file.');
    }

    return [
        'original_name' => (string)$file['name'],
        'stored_name' => $safeName,
        'file_path' => str_replace(BASE_PATH . '/', '', str_replace('\\', '/', $target)),
        'mime_type' => (string)($file['type'] ?? ''),
        'file_size' => (int)$file['size'],
    ];
}

function thai_date(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $dt = new DateTimeImmutable($date);
    return $dt->format('d/m/') . ((int)$dt->format('Y') + 543);
}

function csv_download_headers(string $filename): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
}

function system_setting(string $key, ?string $default = null): ?string
{
    $row = fetch_one('SELECT setting_value FROM system_settings WHERE setting_key = ?', [$key]);
    if (!$row || $row['setting_value'] === null || $row['setting_value'] === '') {
        return $default;
    }
    return (string)$row['setting_value'];
}

function current_academic_year_be(): int
{
    $configured = (int)system_setting('academic_year_be', '');
    if ($configured >= 2400 && $configured <= 2700) {
        return $configured;
    }
    return (int)date('Y') + 543;
}

function normalize_academic_year_be($value): int
{
    $year = (int)$value;
    if ($year < 2400 || $year > 2700) {
        return current_academic_year_be();
    }
    return $year;
}

function attendance_request_type_label(?string $type): string
{
    $labels = [
        'time_record' => 'ขออนุมัติบันทึกเวลาการมาปฏิบัติงาน',
        'workday_swap' => 'สลับวันทำงาน',
    ];
    return $labels[$type ?? ''] ?? 'ขออนุมัติบันทึกเวลาการมาปฏิบัติงาน';
}

function attendance_reason_label(?string $reasonType, ?string $otherReason = null): string
{
    $labels = [
        'forgot_check_in' => 'ลืมบันทึกเวลามาเริ่มปฏิบัติงาน',
        'forgot_check_out' => 'ลืมบันทึกเวลาเลิกปฏิบัติงาน',
        'scanner_error' => 'ระบบสแกนลงเวลาขัดข้อง',
        'other' => 'อื่น ๆ',
    ];
    $label = $labels[$reasonType ?? ''] ?? '-';
    if ($reasonType === 'other' && trim((string)$otherReason) !== '') {
        return $label . ': ' . trim((string)$otherReason);
    }
    return $label;
}

function generate_request_no(string $prefix): string
{
    return $prefix . date('YmdHis') . random_int(10, 99);
}

function overlapping_leave_request(int $userId, string $startDate, string $endDate, int $excludeId = 0): ?array
{
    if ($userId <= 0 || $startDate === '' || $endDate === '') {
        return null;
    }

    $sql = 'SELECT lr.id, lr.request_no, lr.start_date, lr.end_date, lr.status, lt.name AS leave_type_name
            FROM leave_requests lr
            JOIN leave_types lt ON lt.id = lr.leave_type_id
            WHERE lr.user_id = ?
              AND lr.status NOT IN ("rejected", "cancelled")
              AND lr.start_date <= ?
              AND lr.end_date >= ?';
    $params = [$userId, $endDate, $startDate];

    if ($excludeId > 0) {
        $sql .= ' AND lr.id <> ?';
        $params[] = $excludeId;
    }

    $sql .= ' ORDER BY lr.start_date ASC LIMIT 1';
    return fetch_one($sql, $params);
}

function overlapping_attendance_request(int $userId, array $dates, int $excludeId = 0): ?array
{
    $dates = array_values(array_filter(array_unique($dates), static fn($date) => is_string($date) && $date !== ''));
    if ($userId <= 0 || !$dates) {
        return null;
    }

    $datePlaceholders = implode(',', array_fill(0, count($dates), '?'));
    $sql = 'SELECT id, request_no, request_type, work_date, absent_date, makeup_date, status
            FROM attendance_requests
            WHERE user_id = ?
              AND status NOT IN ("rejected", "cancelled")
              AND (
                  work_date IN (' . $datePlaceholders . ')
                  OR absent_date IN (' . $datePlaceholders . ')
                  OR makeup_date IN (' . $datePlaceholders . ')
              )';
    $params = array_merge([$userId], $dates, $dates, $dates);

    if ($excludeId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeId;
    }

    $sql .= ' ORDER BY created_at ASC LIMIT 1';
    return fetch_one($sql, $params);
}

function overlapping_leave_on_dates(int $userId, array $dates): ?array
{
    $dates = array_values(array_filter(array_unique($dates), static fn($date) => is_string($date) && $date !== ''));
    if ($userId <= 0 || !$dates) {
        return null;
    }

    $dateConditions = implode(' OR ', array_fill(0, count($dates), '? BETWEEN lr.start_date AND lr.end_date'));
    $sql = 'SELECT lr.id, lr.request_no, lr.start_date, lr.end_date, lr.status, lt.name AS leave_type_name
            FROM leave_requests lr
            JOIN leave_types lt ON lt.id = lr.leave_type_id
            WHERE lr.user_id = ?
              AND lr.status NOT IN ("rejected", "cancelled")
              AND (' . $dateConditions . ')
            ORDER BY lr.start_date ASC LIMIT 1';
    return fetch_one($sql, array_merge([$userId], $dates));
}

function can_view_user_record(array $owner): bool
{
    $current = current_user();
    if (!$current) {
        return false;
    }
    if ((int)$current['id'] === (int)$owner['id']) {
        return true;
    }
    if (can_manage_all() || in_array($current['role'], ['dean', 'vice_dean', 'assistant_dean'], true)) {
        return true;
    }
    return $current['role'] === 'head_department' && (int)($current['department_id'] ?? 0) === (int)($owner['department_id'] ?? 0);
}

function can_approve_status(string $status, array $owner, string $requestType = 'leave', ?array $requestRow = null): bool
{
    $current = current_user();
    if (!$current) {
        return false;
    }
    if (can_manage_all()) {
        return in_array($status, ['pending_head', 'pending_dean', 'approved'], true);
    }
    $expectedRole = $requestRow['current_approver_role'] ?? approver_role_for_status($status, $owner, $requestType);
    if ($expectedRole) {
        if ($expectedRole === 'head_department') {
            return $current['role'] === 'head_department' && (int)($current['department_id'] ?? 0) === (int)($owner['department_id'] ?? 0);
        }
        if (in_array($expectedRole, ['dean', 'vice_dean', 'assistant_dean'], true)) {
            return in_array($current['role'], ['dean', 'vice_dean', 'assistant_dean'], true);
        }
        return $current['role'] === $expectedRole;
    }
    if ($status === 'pending_head' && $current['role'] === 'head_department') {
        return (int)($current['department_id'] ?? 0) === (int)($owner['department_id'] ?? 0);
    }
    if ($status === 'pending_dean') {
        return in_array($current['role'], ['dean', 'vice_dean', 'assistant_dean'], true);
    }
    return false;
}

function request_scope_sql(string $alias = 'r'): array
{
    $user = current_user();
    if (!$user) {
        return ['1=0', []];
    }
    if (can_manage_all() || in_array($user['role'], ['dean', 'vice_dean', 'assistant_dean'], true)) {
        return ['1=1', []];
    }
    if ($user['role'] === 'head_department') {
        return ["(u.department_id = ? OR {$alias}.user_id = ?)", [(int)$user['department_id'], (int)$user['id']]];
    }
    return ["{$alias}.user_id = ?", [(int)$user['id']]];
}
