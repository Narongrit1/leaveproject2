<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect_to('dashboard.php');
}

$error = '';

if (is_post()) {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $user = fetch_one('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.email = ? AND u.is_active = 1', [$email]);

    $valid = false;
    if ($user) {
        $valid = password_verify($password, $user['password_hash']);
        if (!$valid && $user['password_hash'] === 'INITIAL_123456_REHASH_ON_LOGIN' && $password === '123456') {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            execute_stmt('UPDATE users SET password_hash = ? WHERE id = ?', [$newHash, $user['id']]);
            $valid = true;
        }
    }

    if ($valid && $user) {
        unset($user['password_hash']);
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        redirect_to(!empty($user['must_change_password']) ? 'force_change_password.php' : 'dashboard.php');
    }

    $error = 'Email หรือรหัสผ่านไม่ถูกต้อง';
}

$pageTitle = 'เข้าสู่ระบบ';
require __DIR__ . '/includes/header.php';
?>
<section class="login-screen grid min-h-screen lg:grid-cols-[1fr_460px]">
    <div class="login-visual hidden bg-slate-900 px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="flex items-center gap-3">
            <div class="brand-mark grid h-11 w-11 place-items-center rounded bg-blue-600"><i data-lucide="building-2"></i></div>
            <div>
                <div class="font-semibold">มหาวิทยาลัยสยาม</div>
                <div class="text-sm text-slate-300">Faculty Leave System</div>
            </div>
        </div>
        <div class="max-w-2xl">
            <div class="mb-4 inline-flex rounded border border-slate-600 px-3 py-1 text-sm text-slate-200">คณะเทคโนโลยีสารสนเทศ</div>
            <h1 class="text-4xl font-semibold leading-tight">ระบบใบลาออนไลน์และใบรับรองเวลาปฏิบัติงาน</h1>
            <p class="mt-4 text-slate-300">จัดการคำขอ อนุมัติ รายงาน และพิมพ์เอกสารตามฟอร์มมหาวิทยาลัยสยาม</p>
        </div>
        <div class="login-visual-footer text-sm text-slate-400">Pure PHP + MySQL</div>
    </div>
    <div class="login-form-panel flex items-center justify-center bg-white px-6 py-10">
        <div class="login-card w-full max-w-sm">
            <div class="mb-8 lg:hidden">
                <div class="brand-mark mb-3 grid h-11 w-11 place-items-center rounded bg-blue-700 text-white"><i data-lucide="building-2"></i></div>
                <h1 class="text-2xl font-semibold">เข้าสู่ระบบ</h1>
            </div>
            <div class="mb-8 hidden lg:block">
                <h2 class="text-2xl font-semibold text-slate-950">เข้าสู่ระบบ</h2>
                <p class="mt-2 text-sm text-slate-500">ใช้ email ของมหาวิทยาลัยในการเข้าสู่ระบบ</p>
            </div>
            <?php if ($error): ?>
                <div class="mb-4 rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-input" required autofocus>
                </div>
                <div>
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input id="password" name="password" type="password" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary w-full"><i data-lucide="log-in" class="h-4 w-4"></i>เข้าสู่ระบบ</button>
            </form>
            <div class="login-note mt-5 rounded bg-slate-50 p-4 text-sm text-slate-600">
                รหัสเริ่มต้นสำหรับ seed data คือ <strong>123456</strong>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
