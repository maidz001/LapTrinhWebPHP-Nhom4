<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin', 'lab_staff']);

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM equipment_types WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $msg = 'Xóa loại thiết bị thành công.';
    } catch (PDOException $e) {
        $msg = 'Không thể xóa: đang có thiết bị thuộc loại này.';
    }
} else {
    $msg = 'Yêu cầu không hợp lệ.';
}

header('Location: list.php?msg=' . urlencode($msg));
exit;