<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * app/Core/Model.php
 * Lớp cha cho mọi Model. Mỗi Model đại diện cho 1 bảng/nghiệp vụ dữ liệu
 * và chỉ chứa truy vấn SQL — KHÔNG chứa logic hiển thị hay xử lý HTTP.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::pdo();
    }
}
