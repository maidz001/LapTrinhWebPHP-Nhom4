<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Phong.php';

$phongModel = new Phong($pdo);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?msg=' . urlencode('ID không hợp lệ'));
    exit;
}

$phong = $phongModel->getById($id);

if (!$phong) {
    header('Location: index.php?msg=' . urlencode('Không tìm thấy phòng'));
    exit;
}

try {
    $phongModel->delete($id);
    header('Location: index.php?msg=' . urlencode('Đã xóa phòng "' . $phong['ten_phong'] . '"'));
    exit;
} catch (PDOException $e) {
    // Trường hợp phòng đang được tham chiếu ở bảng khác (equipment, bookings...)
    header('Location: index.php?msg=' . urlencode('Không thể xóa: phòng đang được sử dụng ở nơi khác'));
    exit;
}