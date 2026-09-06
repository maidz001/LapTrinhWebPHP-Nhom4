<?php
/**
 * bookings/store.php
 * ---------------------------------------------------------------------
 * Xử lý tạo mới / cập nhật yêu cầu đặt phòng hoặc mượn thiết bị.
 *
 * - Validate dữ liệu
 * - Kiểm tra tài nguyên
 * - Kiểm tra thời gian
 * - Kiểm tra trùng lịch
 * - Tạo yêu cầu mới với trạng thái pending
 * - Cho phép người dùng cập nhật yêu cầu khi còn pending
 * ---------------------------------------------------------------------
 */

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

/*
 * Nếu có id thì đây là thao tác sửa.
 * Nếu không có id thì đây là tạo mới.
 */
$id = max(0, (int) ($_POST['id'] ?? 0));

$formUrl = '/bookings/form.php' . (
    $id > 0
        ? '?id=' . $id
        : ''
);

/*
 * Kiểm tra CSRF
 */
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set(
        'error',
        'Phiên làm việc đã hết hạn. Vui lòng thử lại.'
    );

    header('Location: ' . $formUrl);
    exit;
}


/*
 * Lấy dữ liệu form
 */
$type = (string) ($_POST['loai_yeu_cau'] ?? '');

$roomId = max(
    0,
    (int) ($_POST['room_id'] ?? 0)
);

$equipmentId = max(
    0,
    (int) ($_POST['equipment_id'] ?? 0)
);

$startInput = trim(
    (string) ($_POST['thoi_gian_bat_dau'] ?? '')
);

$endInput = trim(
    (string) ($_POST['thoi_gian_ket_thuc'] ?? '')
);

$purpose = trim(
    (string) ($_POST['muc_dich'] ?? '')
);

$errors = [];


/*
 * Validate loại yêu cầu
 */
if (!in_array($type, ['room', 'equipment'], true)) {
    $errors[] = 'Loại yêu cầu không hợp lệ.';
}


/*
 * Chỉ cho phép chọn đúng tài nguyên tương ứng
 */
$resourceId = $type === 'equipment'
    ? $equipmentId
    : $roomId;

if ($resourceId <= 0) {

    if ($type === 'equipment') {
        $errors[] = 'Vui lòng chọn thiết bị.';
    } else {
        $errors[] = 'Vui lòng chọn phòng.';
    }

} elseif (
    in_array($type, ['room', 'equipment'], true)
    && !bookingResourceExists(
        $pdo,
        $type,
        $resourceId
    )
) {

    $errors[] =
        'Phòng hoặc thiết bị đã chọn hiện không thể sử dụng.';
}


/*
 * Kiểm tra mục đích
 */
$purposeLength = mb_strlen(
    $purpose,
    'UTF-8'
);

if ($purposeLength < 5 || $purposeLength > 255) {
    $errors[] =
        'Mục đích sử dụng phải có từ 5 đến 255 ký tự.';
}


/*
 * Parse thời gian
 */
$startDate = DateTimeImmutable::createFromFormat(
    'Y-m-d\TH:i',
    $startInput
);

$endDate = DateTimeImmutable::createFromFormat(
    'Y-m-d\TH:i',
    $endInput
);


/*
 * Kiểm tra thời gian bắt đầu
 */
if (
    !$startDate
    || $startDate->format('Y-m-d\TH:i') !== $startInput
) {
    $errors[] =
        'Thời gian bắt đầu không hợp lệ.';
}


/*
 * Kiểm tra thời gian kết thúc
 */
if (
    !$endDate
    || $endDate->format('Y-m-d\TH:i') !== $endInput
) {
    $errors[] =
        'Thời gian kết thúc không hợp lệ.';
}


/*
 * Kiểm tra thứ tự thời gian
 */
if (
    $startDate
    && $endDate
    && $endDate <= $startDate
) {
    $errors[] =
        'Thời gian kết thúc phải sau thời gian bắt đầu.';
}


/*
 * Không cho đặt thời gian trong quá khứ
 */
if (
    $startDate
    && $startDate->format('Y-m-d H:i:00')
        < date('Y-m-d H:i:00')
) {
    $errors[] =
        'Thời gian bắt đầu không được ở trong quá khứ.';
}


/*
 * Chuyển sang format MySQL
 */
$startSql = $startDate
    ? $startDate->format('Y-m-d H:i:s')
    : '';

$endSql = $endDate
    ? $endDate->format('Y-m-d H:i:s')
    : '';


/*
 * Kiểm tra trùng lịch.
 *
 * Khi sửa:
 * - truyền $id để repository bỏ qua chính booking đang sửa.
 *
 * Khi tạo:
 * - $id = 0 nên không loại trừ booking nào.
 */
if (
    empty($errors)
    && bookingHasTimeConflict(
        $pdo,
        $type,
        $resourceId,
        $startSql,
        $endSql,
        $id > 0 ? $id : null
    )
) {
    $errors[] =
        'Phòng hoặc thiết bị đã có yêu cầu trùng thời gian.';
}


/*
 * Nếu có lỗi -> quay lại form và giữ dữ liệu người dùng nhập
 */
if (!empty($errors)) {

    $_SESSION['booking_form_errors'] = $errors;

    $_SESSION['booking_form_data'] = [
        'type' => $type,
        'loai_yeu_cau' => $type,

        'room_id' => $roomId,
        'equipment_id' => $equipmentId,

        'start_time' => $startInput,
        'end_time' => $endInput,

        'thoi_gian_bat_dau' => $startInput,
        'thoi_gian_ket_thuc' => $endInput,

        'purpose' => $purpose,
        'muc_dich' => $purpose,
    ];

    header('Location: ' . $formUrl);
    exit;
}


/*
 * Dữ liệu chuẩn để repository xử lý
 */
$data = [
    'type' => $type,

    'room_id' => $type === 'room'
        ? $roomId
        : null,

    'equipment_id' => $type === 'equipment'
        ? $equipmentId
        : null,

    'start_time' => $startSql,
    'end_time' => $endSql,

    'purpose' => $purpose,
];


$user = current_user();


/*
 * Nếu có ID -> cập nhật booking
 */
if ($id > 0) {

    $updated = updateBooking(
        $pdo,
        $id,
        (int) $user['id'],
        $data
    );

    if ($updated) {

        flash_set(
            'success',
            'Cập nhật yêu cầu thành công.'
        );

    } else {

        flash_set(
            'error',
            'Yêu cầu không còn được phép sửa.'
        );
    }


/*
 * Không có ID -> tạo booking mới
 */
} else {

    createBooking(
        $pdo,
        (int) $user['id'],
        $data
    );

    flash_set(
        'success',
        'Tạo yêu cầu thành công và đang chờ duyệt.'
    );
}


/*
 * Quay về danh sách yêu cầu
 */
header('Location: /bookings/my_requests.php');
exit;