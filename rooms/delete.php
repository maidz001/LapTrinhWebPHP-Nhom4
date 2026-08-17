<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin', 'lab_staff']);

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $msg = 'Xóa phòng thành công.';
    } catch (PDOException $e) {
        // Vướng khóa ngoại (đang có thiết bị/booking gắn với phòng)
        $msg = 'Không thể xóa: phòng đang được sử dụng bởi thiết bị hoặc lịch đặt phòng.';
    }
} else {
    $msg = 'Yêu cầu không hợp lệ.';
}

header('Location: list.php?msg=' . urlencode($msg));
exit;