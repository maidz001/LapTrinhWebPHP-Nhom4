<?php
/**
 * Kiểm tra đăng nhập & phân quyền.
 * Include ở ĐẦU mỗi file cần bảo vệ (trước khi có bất kỳ output nào):
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   require_login();               // chỉ cần đăng nhập
 *   require_role(['admin']);       // chỉ admin
 *   require_role(['admin','lab_staff']); // admin hoặc cán bộ lab
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    if (!isset($_SESSION['user'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function require_role(array $roles)
{
    require_login();
    $role = $_SESSION['user']['role'] ?? null;
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        echo 'Bạn không có quyền truy cập chức năng này.';
        exit;
    }
}