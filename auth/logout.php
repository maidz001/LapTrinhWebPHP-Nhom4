<?php
/**
 * auth/logout.php — Đăng xuất
 * ---------------------------------------------------------------------
 * Chấp nhận cả GET (link trong navbar) và POST, nhưng bắt buộc phải kèm
 * csrf_token hợp lệ mới thực sự huỷ phiên — tránh việc kẻ tấn công ép
 * người dùng đăng xuất hàng loạt bằng link/ảnh giả (CSRF logout).
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

$token = $_REQUEST['csrf_token'] ?? null;

if (csrf_verify(is_string($token) ? $token : null)) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

header('Location: /auth/login.php?logged_out=1');
exit;
