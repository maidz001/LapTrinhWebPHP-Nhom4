<?php
/**
 * reports/store.php
 * ---------------------------------------------------------------------
 * Xử lý lưu báo hỏng thiết bị (POST từ reports/create.php).
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /reports/create.php');
    exit;
}

function reports_back_with_errors(array $errors, array $old): void
{
    $_SESSION['report_errors'] = $errors;
    $_SESSION['report_old'] = $old;
    header('Location: /reports/create.php');
    exit;
}

$old = [
    'equipment_id' => $_POST['equipment_id'] ?? '',
    'mo_ta_su_co' => trim((string) ($_POST['mo_ta_su_co'] ?? '')),
    'muc_do' => $_POST['muc_do'] ?? '',
];

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    reports_back_with_errors(['Phiên làm việc đã hết hạn, vui lòng thử lại.'], $old);
}

$errors = [];

$equipmentId = ctype_digit((string) $old['equipment_id']) ? (int) $old['equipment_id'] : null;
if (!$equipmentId) {
    $errors[] = 'Vui lòng chọn thiết bị cần báo hỏng.';
} else {
    $stmt = $pdo->prepare("SELECT id FROM equipment WHERE id = :id");
    $stmt->execute(['id' => $equipmentId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Thiết bị đã chọn không tồn tại.';
    }
}

if ($old['mo_ta_su_co'] === '') {
    $errors[] = 'Vui lòng mô tả sự cố.';
}
if (!in_array($old['muc_do'], ['low', 'medium', 'high'], true)) {
    $errors[] = 'Mức độ không hợp lệ.';
}

if ($errors) {
    reports_back_with_errors($errors, $old);
}

$user = current_user();
$stmt = $pdo->prepare(
    "INSERT INTO reports (equipment_id, reported_by, mo_ta_su_co, muc_do, trang_thai)
     VALUES (:equipment_id, :reported_by, :mo_ta, :muc_do, 'new')"
);
$stmt->execute([
    'equipment_id' => $equipmentId,
    'reported_by' => $user['id'],
    'mo_ta' => $old['mo_ta_su_co'],
    'muc_do' => $old['muc_do'],
]);

flash_set('success', 'Đã gửi báo cáo sự cố thành công.');
header('Location: /reports/index.php');
exit;
