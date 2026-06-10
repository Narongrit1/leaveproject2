<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        redirect_to('login.php');
    }
}

function require_roles(array $roles): void
{
    require_login();
    $role = $_SESSION['user']['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function refresh_current_user(): void
{
    if (empty($_SESSION['user']['id'])) {
        return;
    }
    $user = fetch_one('SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.id = ?', [$_SESSION['user']['id']]);
    if ($user) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
    }
}

function enforce_password_change(): void
{
    require_login();
    $page = basename($_SERVER['PHP_SELF'] ?? '');
    if (!empty($_SESSION['user']['must_change_password']) && !in_array($page, ['force_change_password.php', 'logout.php'], true)) {
        redirect_to('force_change_password.php');
    }
}

