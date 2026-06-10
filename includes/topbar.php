<?php $user = current_user(); ?>
<header class="app-topbar border-b border-slate-200 bg-white">
    <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div>
            <div class="text-sm text-slate-500">คณะเทคโนโลยีสารสนเทศ</div>
            <h1 class="text-lg font-semibold text-slate-950"><?= e($pageTitle ?? APP_TITLE) ?></h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <div class="text-sm font-medium"><?= e($user['full_name'] ?? '') ?></div>
                <div class="text-xs text-slate-500"><?= e(role_label($user['role'] ?? '')) ?></div>
            </div>
            <a href="change_password.php" class="topbar-action rounded border border-slate-300 p-2 text-slate-600 hover:bg-slate-50" aria-label="เปลี่ยนรหัสผ่าน"><i data-lucide="key-round" class="h-4 w-4"></i></a>
            <a href="logout.php" class="topbar-action topbar-action-primary rounded bg-slate-900 p-2 text-white hover:bg-slate-700" aria-label="ออกจากระบบ"><i data-lucide="log-out" class="h-4 w-4"></i></a>
        </div>
    </div>
</header>
