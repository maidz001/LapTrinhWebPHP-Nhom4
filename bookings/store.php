<?php
/**
 * bookings/store.php
 * ---------------------------------------------------------------------
 * Xử lý lưu yêu cầu đặt phòng / mượn thiết bị (POST từ bookings/form.php).
 * Validate dữ liệu, kiểm tra trùng lịch, sau đó INSERT vào bảng bookings
 * với trang_thai = 'pending'.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/booking_helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /bookings/form.php');
    exit;
}

function bookings_store_back_with_errors(array $errors, array $old): void
{
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_old'] = $old;
    header('Location: /bookings/form.php');
    exit;
}

$old = [
    'loai_yeu_cau' => $_POST['loai_yeu_cau'] ?? '',
    'room_id' => $_POST['room_id'] ?? '',
    'equipment_id' => $_POST['equipment_id'] ?? '',
    'thoi_gian_bat_dau' => $_POST['thoi_gian_bat_dau'] ?? '',
    'thoi_gian_ket_thuc' => $_POST['thoi_gian_ket_thuc'] ?? '',
    'muc_dich' => $_POST['muc_dich'] ?? '',
];

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    bookings_store_back_with_errors(['Phiên làm việc đã hết hạn, vui lòng thử lại.'], $old);
}

$errors = [];
$loai = $old['loai_yeu_cau'];

if (!in_array($loai, ['room', 'equipment'], true)) {
    $errors[] = 'Loại yêu cầu không hợp lệ.';
}

$roomId = null;
$equipmentId = null;

if ($loai === 'room') {
    $roomId = ctype_digit((string) $old['room_id']) ? (int) $old['room_id'] : null;
    if (!$roomId) {
        $errors[] = 'Vui lòng chọn phòng thực hành.';
    }
} elseif ($loai === 'equipment') {
    $equipmentId = ctype_digit((string) $old['equipment_id']) ? (int) $old['equipment_id'] : null;
    if (!$equipmentId) {
        $errors[] = 'Vui lòng chọn thiết bị.';
    }
}

$mucDich = trim((string) $old['muc_dich']);
if ($mucDich === '') {
    $errors[] = 'Vui lòng nhập mục đích sử dụng.';
} elseif (mb_strlen($mucDich) > 255) {
    $errors[] = 'Mục đích sử dụng không được vượt quá 255 ký tự.';
}

$start = booking_to_mysql_datetime((string) $old['thoi_gian_bat_dau']);
$end = booking_to_mysql_datetime((string) $old['thoi_gian_ket_thuc']);

if (!$start || !$end) {
    $errors[] = 'Thời gian bắt đầu/kết thúc không hợp lệ.';
} elseif ($end <= $start) {
    $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
} elseif ($start < date('Y-m-d H:i:00')) {
    $errors[] = 'Thời gian bắt đầu không được ở trong quá khứ.';
}

if ($errors) {
    bookings_store_back_with_errors($errors, $old);
}

// Kiểm tra phòng/thiết bị có tồn tại và đang khả dụng không
if ($loai === 'room') {
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE id = :id AND trang_thai = 'available'");
    $stmt->execute(['id' => $roomId]);
    if (!$stmt->fetch()) {
        bookings_store_back_with_errors(['Phòng đã chọn không tồn tại hoặc không còn khả dụng.'], $old);
    }
} else {
    $stmt = $pdo->prepare("SELECT id FROM equipment WHERE id = :id AND co_the_muon = 1 AND trang_thai = 'active'");
    $stmt->execute(['id' => $equipmentId]);
    if (!$stmt->fetch()) {
        bookings_store_back_with_errors(['Thiết bị đã chọn không tồn tại hoặc không còn khả dụng.'], $old);
    }
}

// Kiểm tra trùng lịch (chỉ tính các yêu cầu đang chờ hoặc đã duyệt)
if (booking_has_conflict($pdo, $loai, $roomId, $equipmentId, $start, $end)) {
    bookings_store_back_with_errors(['Khung giờ này đã có yêu cầu khác (đang chờ hoặc đã duyệt) trùng lịch.'], $old);
}

$user = current_user();
$stmt = $pdo->prepare(
    "INSERT INTO bookings (user_id, loai_yeu_cau, room_id, equipment_id, thoi_gian_bat_dau, thoi_gian_ket_thuc, muc_dich, trang_thai)
     VALUES (:user_id, :loai, :room_id, :equipment_id, :start, :end, :muc_dich, 'pending')"
);
$stmt->execute([
    'user_id' => $user['id'],
    'loai' => $loai,
    'room_id' => $roomId,
    'equipment_id' => $equipmentId,
    'start' => $start,
    'end' => $end,
    'muc_dich' => $mucDich,
]);

flash_set('success', 'Đã gửi yêu cầu thành công, vui lòng chờ duyệt.');
header('Location: /bookings/my_requests.php');
exit;
