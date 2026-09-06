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

// Lấy thiết bị gắn với báo cáo này để đồng bộ lại trạng thái sau khi cập nhật
$stmt = $pdo->prepare("SELECT equipment_id FROM reports WHERE id = :id");
$stmt->execute(['id' => (int) $id]);
$report = $stmt->fetch();

$stmt = $pdo->prepare("UPDATE reports SET trang_thai = :tt WHERE id = :id");
$stmt->execute(['tt' => $trangThai, 'id' => (int) $id]);

if ($report) {
    dong_bo_trang_thai_thiet_bi($pdo, (int) $report['equipment_id']);
}

flash_set('success', 'Đã cập nhật trạng thái báo cáo.');
header('Location: /reports/index.php');
exit;

/**
 * Đồng bộ trạng thái thiết bị dựa trên các báo cáo hỏng chưa xử lý xong:
 * - Còn báo cáo "Mới"        -> thiết bị "Hỏng"
 * - Còn báo cáo "Đang xử lý" -> thiết bị "Bảo trì"
 * - Không còn báo cáo mở     -> thiết bị "Hoạt động"
 * Không đụng vào thiết bị đang ở trạng thái "Đang mượn" (borrowed), vì đó là
 * trạng thái quản lý riêng bởi luồng mượn/trả, không liên quan báo hỏng.
 */
function dong_bo_trang_thai_thiet_bi(PDO $pdo, int $equipmentId): void
{
    $stmt = $pdo->prepare("SELECT trang_thai FROM equipment WHERE id = :id");
    $stmt->execute(['id' => $equipmentId]);
    $equipment = $stmt->fetch();
    if (!$equipment || $equipment['trang_thai'] === 'borrowed') {
        return;
    }

    $stmt = $pdo->prepare("SELECT trang_thai FROM reports WHERE equipment_id = :id AND trang_thai IN ('new', 'processing')");
    $stmt->execute(['id' => $equipmentId]);
    $openStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('new', $openStatuses, true)) {
        $trangThaiMoi = 'broken';
    } elseif (in_array('processing', $openStatuses, true)) {
        $trangThaiMoi = 'maintenance';
    } else {
        $trangThaiMoi = 'active';
    }

    $stmt = $pdo->prepare("UPDATE equipment SET trang_thai = :tt WHERE id = :id");
    $stmt->execute(['tt' => $trangThaiMoi, 'id' => $equipmentId]);
}