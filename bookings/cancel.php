<?php
/**
 * bookings/cancel.php
 * ---------------------------------------------------------------------
 * Huỷ yêu cầu của chính người dùng đang đăng nhập, chỉ khi yêu cầu đó
 * còn ở trạng thái 'pending'. Gọi qua liên kết GET có kèm csrf_token,
 * theo đúng mẫu đã dùng ở auth/logout.php.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();
$user = current_user();

if (!csrf_verify($_GET['csrf_token'] ?? null)) {
    flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
    header('Location: /bookings/my_requests.php');
    exit;
}

$id = $_GET['id'] ?? '';
if (!ctype_digit((string) $id)) {
    flash_set('error', 'Yêu cầu không hợp lệ.');
    header('Location: /bookings/my_requests.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, trang_thai FROM bookings WHERE id = :id AND user_id = :uid");
$stmt->execute(['id' => (int) $id, 'uid' => $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    flash_set('error', 'Không tìm thấy yêu cầu hoặc yêu cầu không thuộc về bạn.');
} elseif ($booking['trang_thai'] !== 'pending') {
    flash_set('error', 'Chỉ có thể huỷ yêu cầu đang ở trạng thái chờ duyệt.');
} else {
    $upd = $pdo->prepare("UPDATE bookings SET trang_thai = 'cancelled' WHERE id = :id");
    $upd->execute(['id' => (int) $id]);
    flash_set('success', 'Đã huỷ yêu cầu thành công.');
}

header('Location: /bookings/my_requests.php');
exit;