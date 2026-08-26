<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/repository.php';

require_role(['admin', 'lab_staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Yêu cầu duyệt không hợp lệ.');
    header('Location: /bookings/pending.php');
    exit;
}

$id = max(0, (int) ($_POST['id'] ?? 0));
$booking = findBookingById($pdo, $id);
$returnUrl = ($_POST['return_to'] ?? '') === 'detail'
    ? '/bookings/detail.php?id=' . $id
    : '/bookings/pending.php';

if (!$booking || $booking['trang_thai'] !== 'pending') {
    flash_set('error', 'Yêu cầu không tồn tại hoặc đã được xử lý.');
    header('Location: ' . $returnUrl);
    exit;
}

$resourceId = $booking['loai_yeu_cau'] === 'room'
    ? (int) $booking['room_id']
    : (int) $booking['equipment_id'];
$conflict = bookingHasTimeConflict(
    $pdo,
    $booking['loai_yeu_cau'],
    $resourceId,
    $booking['thoi_gian_bat_dau'],
    $booking['thoi_gian_ket_thuc'],
    $id,
    true
);

if ($conflict) {
    flash_set('error', 'Không thể duyệt vì đã có lịch được duyệt trùng thời gian.');
} else {
    $user = current_user();
    $approved = approveBooking($pdo, $id, (int) $user['id']);
    flash_set($approved ? 'success' : 'error', $approved ? 'Duyệt yêu cầu thành công.' : 'Yêu cầu đã được người khác xử lý.');
}

header('Location: ' . $returnUrl);
exit;
