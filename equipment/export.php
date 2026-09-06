<?php
/**
 * equipment/export.php
 * ---------------------------------------------------------------------
 * Xuất danh sách thiết bị ra file CSV.
 * Cột và thứ tự khớp CHÍNH XÁC với những gì equipment/import.php mong
 * đợi (ma_thiet_bi, ten_thiet_bi, ten_loai, ma_phong, co_the_muon,
 * trang_thai, ngay_mua, mo_ta), để file xuất ra có thể dùng lại ngay
 * cho chức năng "Thêm từ file".
 *
 * Lưu ý: import.php sẽ báo lỗi "mã thiết bị đã tồn tại" nếu bạn import
 * lại nguyên file vừa xuất mà không xoá/sửa các thiết bị đó trước -
 * đây là hành vi có chủ đích của import để tránh tạo trùng dữ liệu.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

// Lọc giống hệt logic ở equipment/list.php
$typeFilter = isset($_GET['type_id']) && ctype_digit((string) $_GET['type_id']) ? (int) $_GET['type_id'] : null;
$statusFilter = $_GET['trang_thai'] ?? 'all';
$allowedStatus = ['all', 'active', 'broken', 'maintenance', 'borrowed'];
if (!in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = 'all';
}
$q = trim($_GET['q'] ?? '');

$sql = "SELECT e.*, t.ten_loai, r.ma_phong
        FROM equipment e
        JOIN equipment_types t ON t.id = e.type_id
        LEFT JOIN rooms r ON r.id = e.room_id
        WHERE 1 = 1";
$params = [];

if ($typeFilter) {
    $sql .= " AND e.type_id = :type_id";
    $params['type_id'] = $typeFilter;
}
if ($statusFilter !== 'all') {
    $sql .= " AND e.trang_thai = :trang_thai";
    $params['trang_thai'] = $statusFilter;
}
if ($q !== '') {
    $sql .= " AND e.ten_thiet_bi LIKE :q";
    $params['q'] = '%' . $q . '%';
}
$sql .= " ORDER BY e.ma_thiet_bi";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipmentList = $stmt->fetchAll();

$tenFile = 'danh_sach_thiet_bi_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $tenFile . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Ghi BOM để Excel nhận đúng font tiếng Việt UTF-8
fwrite($output, "\xEF\xBB\xBF");

// Header đúng tên cột mà equipment/import.php nhận diện để bỏ qua dòng tiêu đề
fputcsv($output, ['ma_thiet_bi', 'ten_thiet_bi', 'ten_loai', 'ma_phong', 'co_the_muon', 'trang_thai', 'ngay_mua', 'mo_ta']);

foreach ($equipmentList as $e) {
    fputcsv($output, [
        $e['ma_thiet_bi'],
        $e['ten_thiet_bi'],
        $e['ten_loai'],
        $e['ma_phong'] ?? '',
        $e['co_the_muon'] ? '1' : '0',
        $e['trang_thai'],
        $e['ngay_mua'] ?? '',
        $e['mo_ta'] ?? '',
    ]);
}

fclose($output);
exit;
