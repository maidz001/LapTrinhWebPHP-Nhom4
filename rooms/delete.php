<?php
/**
 * rooms/delete.php
 * ---------------------------------------------------------------------
 * Xoá phòng thực hành (POST có kèm csrf_token, theo mẫu equipment/delete.php).
 * Nếu phòng đang được thiết bị/booking tham chiếu (FK RESTRICT), báo lỗi
 * thay vì để lộ lỗi CSDL. Luôn quay lại /rooms/list.php (đúng giao diện
 * khung sườn có sidebar/nút Trang chủ), không văng ra trang rời rạc khác.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rooms/list.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
    header('Location: /rooms/list.php');
    exit;
}

$id = $_POST['id'] ?? '';
if (!ctype_digit((string) $id)) {
    flash_set('error', 'Phòng không hợp lệ.');
    header('Location: /rooms/list.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = :id");
    $stmt->execute(['id' => (int) $id]);
    if ($stmt->rowCount() > 0) {
        flash_set('success', 'Đã xoá phòng thực hành.');
    } else {
        flash_set('error', 'Không tìm thấy phòng cần xoá.');
    }
} catch (PDOException $e) {
    flash_set('error', 'Không thể xoá phòng này vì đang có thiết bị hoặc yêu cầu đặt phòng liên quan.');
}

header('Location: /rooms/list.php');
exit;
