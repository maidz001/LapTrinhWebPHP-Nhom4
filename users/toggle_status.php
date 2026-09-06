<?php
/**
 * users/toggle_status.php
 * ---------------------------------------------------------------------
 * Khoá hoặc mở khoá một tài khoản người dùng. Chỉ admin được dùng.
 * Không có chức năng xoá tài khoản (theo đúng yêu cầu nghiệp vụ).
 * Admin không thể tự khoá chính tài khoản đang đăng nhập của mình.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /users/list.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
    header('Location: /users/list.php');
    exit;
}

$id = $_POST['id'] ?? '';
if (!ctype_digit((string) $id)) {
    flash_set('error', 'Tài khoản không hợp lệ.');
    header('Location: /users/list.php');
    exit;
}
$id = (int) $id;

$currentUser = current_user();
if ($id === (int) ($currentUser['id'] ?? 0)) {
    flash_set('error', 'Bạn không thể tự khoá tài khoản của chính mình.');
    header('Location: /users/list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, ho_ten, trang_thai FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    flash_set('error', 'Không tìm thấy tài khoản.');
    header('Location: /users/list.php');
    exit;
}

$newStatus = $user['trang_thai'] === 'active' ? 'locked' : 'active';

$stmt = $pdo->prepare("UPDATE users SET trang_thai = :st WHERE id = :id");
$stmt->execute(['st' => $newStatus, 'id' => $id]);

flash_set('success', $newStatus === 'locked'
    ? 'Đã khoá tài khoản "' . $user['ho_ten'] . '".'
    : 'Đã mở khoá tài khoản "' . $user['ho_ten'] . '".');

header('Location: /users/list.php');
exit;
