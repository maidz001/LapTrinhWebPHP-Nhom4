<?php
/**
 * api/check_availability.php
 * ---------------------------------------------------------------------
 * Endpoint JSON kiểm tra phòng/thiết bị còn trống theo khung giờ.
 * Gọi bằng AJAX (GET) từ bookings/form.php.
 *
 * Params: loai_yeu_cau=room|equipment, id=<int>,
 *         start=<datetime-local>, end=<datetime-local>
 * Trả về: {"available": bool, "message": string}
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/booking_helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Không dùng require_login() ở đây vì hàm đó redirect sang trang login (HTML),
// không phù hợp với một API JSON. Endpoint API phải luôn trả JSON + status code đúng.
if (!current_user()) {
    http_response_code(401);
    echo json_encode(['available' => false, 'message' => 'Vui lòng đăng nhập để kiểm tra.']);
    exit;
}

$loai = $_GET['loai_yeu_cau'] ?? '';
$id = $_GET['id'] ?? '';
$start = booking_to_mysql_datetime((string) ($_GET['start'] ?? ''));
$end = booking_to_mysql_datetime((string) ($_GET['end'] ?? ''));

if (!in_array($loai, ['room', 'equipment'], true) || !ctype_digit((string) $id) || !$start || !$end) {
    http_response_code(400);
    echo json_encode(['available' => false, 'message' => 'Thiếu hoặc sai thông tin kiểm tra.']);
    exit;
}

if ($end <= $start) {
    http_response_code(400);
    echo json_encode(['available' => false, 'message' => 'Thời gian kết thúc phải sau thời gian bắt đầu.']);
    exit;
}

$roomId = $loai === 'room' ? (int) $id : null;
$equipmentId = $loai === 'equipment' ? (int) $id : null;

$conflict = booking_has_conflict($pdo, $loai, $roomId, $equipmentId, $start, $end);

http_response_code(200);
echo json_encode([
    'available' => !$conflict,
    'message' => $conflict
        ? 'Khung giờ này đã có yêu cầu khác trùng lịch.'
        : 'Khung giờ này còn trống.',
]);
