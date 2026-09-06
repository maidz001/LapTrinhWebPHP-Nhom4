<?php
/**
 * rooms/export.php
 * ---------------------------------------------------------------------
 * Xuất danh sách phòng thực hành ra file CSV.
 * Cột và thứ tự khớp CHÍNH XÁC với những gì rooms/import.php mong đợi
 * (ma_phong, ten_phong, vi_tri, suc_chua, trang_thai, mo_ta), để file
 * xuất ra có thể dùng lại ngay cho chức năng "Thêm từ file" (ví dụ sau
 * khi chỉnh sửa dữ liệu trong Excel rồi import lại).
 *
 * Lưu ý: import.php sẽ báo lỗi "mã phòng đã tồn tại" nếu bạn import lại
 * nguyên file vừa xuất mà không xoá/sửa các phòng đó trước - đây là
 * hành vi có chủ đích của import để tránh tạo trùng dữ liệu.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

// Tìm kiếm phòng theo tên - giống hệt logic ở rooms/list.php
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE ten_phong LIKE :q ORDER BY ma_phong");
    $stmt->execute(['q' => '%' . $q . '%']);
    $rooms = $stmt->fetchAll();
} else {
    $rooms = $pdo->query("SELECT * FROM rooms ORDER BY ma_phong")->fetchAll();
}

$tenFile = 'danh_sach_phong_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $tenFile . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Ghi BOM để Excel nhận đúng font tiếng Việt UTF-8
fwrite($output, "\xEF\xBB\xBF");

// Header đúng tên cột mà rooms/import.php nhận diện để bỏ qua dòng tiêu đề
fputcsv($output, ['ma_phong', 'ten_phong', 'vi_tri', 'suc_chua', 'trang_thai', 'mo_ta']);

foreach ($rooms as $r) {
    fputcsv($output, [
        $r['ma_phong'],
        $r['ten_phong'],
        $r['vi_tri'],
        $r['suc_chua'],
        $r['trang_thai'],
        $r['mo_ta'] ?? '',
    ]);
}

fclose($output);
exit;
