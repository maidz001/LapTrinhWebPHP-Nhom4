<?php
/**
 * config/database.php
 * ---------------------------------------------------------------------
 * Khởi tạo kết nối PDO tới MySQL/MariaDB cho toàn bộ hệ thống.
 * Mọi trang cần truy vấn CSDL chỉ cần:
 *      require_once __DIR__ . '/../config/database.php';
 * và dùng biến $pdo (PDO::ERRMODE_EXCEPTION, FETCH_ASSOC mặc định).
 *
 * Đổi thông tin kết nối bằng biến môi trường khi triển khai thật
 * (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS). Nếu không có biến môi
 * trường, hệ thống dùng cấu hình mặc định phù hợp với XAMPP/local dev
 * theo hướng dẫn trong README.md.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/session.php';

// ---- Thông tin kết nối ----
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'quanly_phongthuchanh';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '123456';
$dbCharset = 'utf8mb4';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbHost,
    $dbPort,
    $dbName,
    $dbCharset
);

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // dùng prepared statement thật của MySQL, chống SQL Injection triệt để
    PDO::ATTR_STRINGIFY_FETCHES  => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $e) {
    // Không lộ chi tiết lỗi (host/user/pass) ra ngoài trình duyệt, chỉ ghi log server
    error_log('[DB CONNECTION ERROR] ' . $e->getMessage());
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    die(
        '<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center;color:#7a1f1f">'
        . '<h2>Không thể kết nối cơ sở dữ liệu</h2>'
        . '<p>Hệ thống đang gặp sự cố. Vui lòng kiểm tra lại cấu hình trong <code>config/database.php</code> '
        . 'hoặc liên hệ quản trị viên.</p></div>'
    );
}
