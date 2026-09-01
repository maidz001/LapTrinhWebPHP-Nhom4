<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DanhMuc.php';

$dmModel = new DanhMuc($pdo);
$id = (int)($_GET['id'] ?? 0);

$dm = $dmModel->getById($id);
if (!$dm) {
    header('Location: index.php?msg=' . urlencode('Không tìm thấy danh mục'));
    exit;
}

try {
    $dmModel->delete($id);
    header('Location: index.php?msg=' . urlencode('Đã xóa danh mục "' . $dm['ten_loai'] . '"'));
    exit;
} catch (PDOException $e) {
    // FK constraint: equipment.type_id RESTRICT -> không xóa được nếu còn thiết bị thuộc danh mục này
    header('Location: index.php?msg=' . urlencode('Không thể xóa: danh mục đang có thiết bị sử dụng'));
    exit;
}