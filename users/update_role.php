<?php
/**
 * users/update_role.php
 * ---------------------------------------------------------------------
 * Nâng cấp / hạ cấp vai trò giữa "Người dùng" (user) và "Cán bộ phòng
 * lab" (lab_staff). Chỉ admin được dùng.
 *
 * Giới hạn có chủ đích:
 *  - KHÔNG cho gán/thu hồi vai trò "admin" qua chức năng này (tránh
 *    tự leo thang đặc quyền hoặc lỡ tay biến tài khoản khác thành
 *    admin). Muốn đổi quyền admin phải sửa trực tiếp trong CSDL.
 *  - Không tự đổi vai trò của chính mình.
 *  - Không đổi vai trò của một tài khoản admin khác qua đây.
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
    flash_set('error', 'Bạn không thể tự đổi vai trò của chính mình.');
    header('Location: /users/list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, ho_ten, vai_tro FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    flash_set('error', 'Không tìm thấy tài khoản.');
    header('Location: /users/list.php');
    exit;
}

if ($user['vai_tro'] === 'admin') {
    flash_set('error', 'Không thể đổi vai trò của quản trị viên qua chức năng này.');
    header('Location: /users/list.php');
    exit;
}

// Chỉ đảo giữa 'user' <-> 'lab_staff'
$newRole = $user['vai_tro'] === 'lab_staff' ? 'user' : 'lab_staff';

$stmt = $pdo->prepare("UPDATE users SET vai_tro = :role WHERE id = :id");
$stmt->execute(['role' => $newRole, 'id' => $id]);

flash_set('success', $newRole === 'lab_staff'
    ? 'Đã nâng "' . $user['ho_ten'] . '" thành Cán bộ phòng lab.'
    : 'Đã chuyển "' . $user['ho_ten'] . '" về Người dùng thường.');

header('Location: /users/list.php');
exit;
