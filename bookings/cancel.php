<?php
/**
 * bookings/cancel.php
 * ---------------------------------------------------------------------
 * Huỷ yêu cầu của chính người dùng đang đăng nhập.
 * Chỉ cho phép huỷ khi yêu cầu đang ở trạng thái 'pending'.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/repository.php';

require_login();

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !csrf_verify($_POST['csrf_token'] ?? null)
) {
    flash_set('error', 'Yêu cầu hủy không hợp lệ.');
    header('Location: /bookings/my_requests.php');
    exit;
}

$id = max(0, (int) ($_POST['id'] ?? 0));

$user = current_user();

$cancelled = cancelBooking(
    $pdo,
    $id,
    (int) $user['id']
);

flash_set(
    $cancelled ? 'success' : 'error',
    $cancelled
        ? 'Hủy yêu cầu thành công.'
        : 'Yêu cầu không tồn tại hoặc không còn được phép hủy.'
);

header('Location: /bookings/my_requests.php');
exit;