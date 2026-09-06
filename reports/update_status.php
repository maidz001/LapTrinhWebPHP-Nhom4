<?php
/**
 * reports/update_status.php
 * ---------------------------------------------------------------------
 * Cập nhật trạng thái báo hỏng (POST từ form inline trong reports/index.php).
 * Chỉ admin và lab_staff được phép.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /reports/index.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
    header('Location: /reports/index.php');
    exit;
}

$id = $_POST['id'] ?? '';
$trangThai = $_POST['trang_thai'] ?? '';

if (!ctype_digit((string) $id) || !in_array($trangThai, ['new', 'processing', 'resolved', 'cancelled'], true)) {
    flash_set('error', 'Dữ liệu cập nhật không hợp lệ.');
    header('Location: /reports/index.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE reports SET trang_thai = :tt WHERE id = :id");
$stmt->execute(['tt' => $trangThai, 'id' => (int) $id]);

flash_set('success', 'Đã cập nhật trạng thái báo cáo.');
header('Location: /reports/index.php');
exit;