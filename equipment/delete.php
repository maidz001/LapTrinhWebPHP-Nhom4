<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThietBi.php';

$tbModel = new ThietBi($pdo);
$id = (int)($_GET['id'] ?? 0);

$tb = $tbModel->getById($id);
if (!$tb) {
    header('Location: index.php?msg=' . urlencode('Không tìm thấy thiết bị'));
    exit;
}

try {
    $tbModel->delete($id);
    header('Location: index.php?msg=' . urlencode('Đã xóa thiết bị "' . $tb['ten_thiet_bi'] . '"'));
    exit;
} catch (PDOException $e) {
    header('Location: index.php?msg=' . urlencode('Không thể xóa: thiết bị đang được tham chiếu ở nơi khác'));
    exit;
}