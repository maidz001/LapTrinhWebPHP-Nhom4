<?php
/**
 * equipment/delete.php
 * ---------------------------------------------------------------------
 * Xoá thiết bị (POST có kèm csrf_token, theo mẫu users/toggle_status.php).
 * Nếu thiết bị đang có lịch sử mượn (bookings.equipment_id RESTRICT),
 * DB sẽ từ chối xoá — bắt lỗi và báo cho người dùng.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /equipment/list.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
    header('Location: /equipment/list.php');
    exit;
}

$id = $_POST['id'] ?? '';
if (!ctype_digit((string) $id)) {
    flash_set('error', 'Thiết bị không hợp lệ.');
    header('Location: /equipment/list.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = :id");
    $stmt->execute(['id' => (int) $id]);
    if ($stmt->rowCount() > 0) {
        flash_set('success', 'Đã xoá thiết bị. Các báo hỏng liên quan cũng đã được xoá theo.');
    } else {
        flash_set('error', 'Không tìm thấy thiết bị cần xoá.');
    }
} catch (PDOException $e) {
    flash_set('error', 'Không thể xoá thiết bị này vì đã có lịch sử mượn liên quan.');
}

header('Location: /equipment/list.php');
exit;
