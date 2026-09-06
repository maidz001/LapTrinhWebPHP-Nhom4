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
        $current = $_SERVER['REQUEST_URI'] ?? '/mvc/dashboard';
        header('Location: /mvc/auth/login?redirect=' . urlencode($current));
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

/**
 * Dùng ở đầu auth/login.php và auth/register.php: nếu người dùng đã
 * đăng nhập rồi thì đưa thẳng về dashboard, không cho xem lại form.
 */
function redirect_if_logged_in(string $to = '/mvc/dashboard')
{
    if (isset($_SESSION['user'])) {
        header('Location: ' . $to);
        exit;
    }
}

/**
 * Lấy địa chỉ IP thực của client (dùng để log đăng nhập / chống brute-force).
 * Chỉ tin REMOTE_ADDR vì header X-Forwarded-For có thể bị giả mạo khi
 * không chạy sau một reverse proxy đáng tin cậy đã được cấu hình riêng.
 */
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return is_string($ip) && $ip !== '' ? $ip : '0.0.0.0';
}