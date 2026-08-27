<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/repository.php';

require_role(['admin', 'lab_staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Yêu cầu từ chối không hợp lệ.');
    header('Location: /bookings/pending.php');
    exit;
}

$id = max(0, (int) ($_POST['id'] ?? 0));
$reason = trim((string) ($_POST['ly_do_tu_choi'] ?? ''));
$returnUrl = ($_POST['return_to'] ?? '') === 'detail'
    ? '/bookings/detail.php?id=' . $id
    : '/bookings/pending.php';

if (mb_strlen($reason, 'UTF-8') < 5 || mb_strlen($reason, 'UTF-8') > 255) {
    flash_set('error', 'Lý do từ chối phải có từ 5 đến 255 ký tự.');
    header('Location: ' . $returnUrl);
    exit;
}

$user = current_user();
$rejected = rejectBooking($pdo, $id, (int) $user['id'], $reason);
flash_set($rejected ? 'success' : 'error', $rejected ? 'Đã từ chối yêu cầu.' : 'Yêu cầu không tồn tại hoặc đã được xử lý.');
header('Location: ' . $returnUrl);
exit;
