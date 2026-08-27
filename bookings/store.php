<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/repository.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /bookings/form.php');
    exit;
}

$id = max(0, (int) ($_POST['id'] ?? 0));
$formUrl = '/bookings/form.php' . ($id > 0 ? '?id=' . $id : '');

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    header('Location: ' . $formUrl);
    exit;
}

$type = (string) ($_POST['loai_yeu_cau'] ?? '');
$roomId = max(0, (int) ($_POST['room_id'] ?? 0));
$equipmentId = max(0, (int) ($_POST['equipment_id'] ?? 0));
$startInput = trim((string) ($_POST['thoi_gian_bat_dau'] ?? ''));
$endInput = trim((string) ($_POST['thoi_gian_ket_thuc'] ?? ''));
$purpose = trim((string) ($_POST['muc_dich'] ?? ''));
$errors = [];

if (!in_array($type, ['room', 'equipment'], true)) {
    $errors[] = 'Loại yêu cầu không hợp lệ.';
}

$resourceId = $type === 'equipment' ? $equipmentId : $roomId;
if ($resourceId <= 0) {
    $errors[] = $type === 'equipment' ? 'Vui lòng chọn thiết bị.' : 'Vui lòng chọn phòng.';
} elseif (in_array($type, ['room', 'equipment'], true) && !bookingResourceExists($pdo, $type, $resourceId)) {
    $errors[] = 'Phòng hoặc thiết bị đã chọn hiện không thể sử dụng.';
}

$startDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $startInput);
$endDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $endInput);
if (!$startDate || $startDate->format('Y-m-d\TH:i') !== $startInput) {
    $errors[] = 'Thời gian bắt đầu không hợp lệ.';
}
if (!$endDate || $endDate->format('Y-m-d\TH:i') !== $endInput) {
    $errors[] = 'Thời gian kết thúc không hợp lệ.';
}
if ($startDate && $endDate && $endDate <= $startDate) {
    $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
}

$purposeLength = mb_strlen($purpose, 'UTF-8');
if ($purposeLength < 5 || $purposeLength > 255) {
    $errors[] = 'Mục đích sử dụng phải có từ 5 đến 255 ký tự.';
}

$startSql = $startDate ? $startDate->format('Y-m-d H:i:s') : '';
$endSql = $endDate ? $endDate->format('Y-m-d H:i:s') : '';
if (
    empty($errors)
    && bookingHasTimeConflict($pdo, $type, $resourceId, $startSql, $endSql, $id > 0 ? $id : null)
) {
    $errors[] = 'Phòng hoặc thiết bị đã có yêu cầu trùng thời gian.';
}

if (!empty($errors)) {
    $_SESSION['booking_form_errors'] = $errors;
    $_SESSION['booking_form_data'] = [
        'type' => $type,
        'room_id' => $roomId,
        'equipment_id' => $equipmentId,
        'start_time' => $startInput,
        'end_time' => $endInput,
        'purpose' => $purpose,
    ];
    header('Location: ' . $formUrl);
    exit;
}

$data = [
    'type' => $type,
    'room_id' => $type === 'room' ? $roomId : null,
    'equipment_id' => $type === 'equipment' ? $equipmentId : null,
    'start_time' => $startSql,
    'end_time' => $endSql,
    'purpose' => $purpose,
];
$user = current_user();

if ($id > 0) {
    $updated = updateBooking($pdo, $id, (int) $user['id'], $data);
    flash_set($updated ? 'success' : 'error', $updated ? 'Cập nhật yêu cầu thành công.' : 'Yêu cầu không còn được phép sửa.');
} else {
    createBooking($pdo, (int) $user['id'], $data);
    flash_set('success', 'Tạo yêu cầu thành công và đang chờ duyệt.');
}

header('Location: /bookings/my_requests.php');
exit;
