<?php
declare(strict_types=1);

/**
 * app/Core/Database.php
 * ---------------------------------------------------------------------
 * Điểm truy cập PDO duy nhất cho lớp MVC (Model).
 *
 * QUAN TRỌNG: file này KHÔNG tự mở kết nối riêng và KHÔNG chứa thông tin
 * đăng nhập CSDL. Nó chỉ "mượn" lại biến $pdo đã được khởi tạo bởi
 * config/database.php (nơi DUY NHẤT chứa cấu hình kết nối của cả dự án).
 * Nhờ vậy: đổi cấu hình DB chỉ cần sửa 1 chỗ như trước, không có nguy cơ
 * lệch cấu hình giữa code cũ (thủ tục) và code mới (MVC).
 * ---------------------------------------------------------------------
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            global $pdo;
            if (!$pdo instanceof PDO) {
                throw new RuntimeException(
                    'config/database.php phải được require_once TRƯỚC khi dùng Database::pdo().'
                );
            }
            self::$instance = $pdo;
        }

        return self::$instance;
    }
}
