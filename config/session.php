<?php
/**
 * config/session.php
 * ---------------------------------------------------------------------
 * Khởi tạo session một cách an toàn cho toàn bộ hệ thống.
 * File này được require_once đầu tiên trong config/database.php nên
 * MỌI trang có require config/database.php đều tự động có session an toàn,
 * không cần khai báo lại session_start() rải rác nhiều nơi.
 *
 * Các biện pháp bảo mật áp dụng:
 *  - session.use_strict_mode: từ chối session ID do client tự đặt (chống fixation)
 *  - Cookie session: HttpOnly (JS không đọc được), SameSite=Lax (giảm CSRF),
 *    Secure tự bật khi chạy HTTPS
 *  - Đổi tên cookie mặc định (giảm khả năng bị nhận diện là ứng dụng PHP mặc định)
 *  - Tự động hết hạn phiên sau thời gian không hoạt động (idle timeout)
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {

    // Chỉ chấp nhận session ID do server sinh ra
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,        // hết khi đóng trình duyệt
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps, // chỉ gửi cookie qua HTTPS khi đang chạy HTTPS
        'httponly' => true,     // JavaScript không thể đọc cookie session
        'samesite' => 'Lax',    // giảm thiểu CSRF khi có liên kết từ site khác
    ]);

    session_name('QLPTH_SESSID');
    session_start();

    // --- Idle timeout: tự đăng xuất sau 30 phút không hoạt động ---
    $idleTimeoutSeconds = 1800;
    if (
        isset($_SESSION['last_activity']) &&
        (time() - (int) $_SESSION['last_activity']) > $idleTimeoutSeconds
    ) {
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
        session_start();
    }
    $_SESSION['last_activity'] = time();

    // --- Chống session fixation: định kỳ đổi session ID còn hiệu lực ---
    if (empty($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    } elseif (time() - (int) $_SESSION['created_at'] > 900) {
        // Đổi ID mỗi 15 phút nếu phiên vẫn đang hoạt động, giữ nguyên dữ liệu
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
    }
}
