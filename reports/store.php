<?php
/**
 * store.php - Xử lý dữ liệu gửi từ form.php
 * Nhận $_POST, validate, dùng hàm nghiệp vụ, lưu vào MySQL, rồi redirect về form.php.
 * KHÔNG có giao diện HTML ở đây.
 */
session_start();

// ===== KẾT NỐI DB =====
$host = 'localhost';
$ten_db = 'qlpttb_buoi2';
$user = 'root';
$mk = '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$ten_db};charset=utf8mb4", $user, $mk, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
}

// ===== HÀM XỬ LÝ NGHIỆP VỤ (TỰ ĐỊNH NGHĨA) =====

/** Hàm tự định nghĩa: xác định hạn xử lý dựa trên mức độ ưu tiên. */
function tinhHanXuLy(string $mucDoUuTien): string
{
    switch ($mucDoUuTien) {
        case 'Cao':
            return 'Trong 24 giờ';
        case 'Trung bình':
            return 'Trong 3 ngày';
        case 'Thấp':
            return 'Trong 7 ngày';
        default:
            return 'Chưa xác định';
    }
}

/** Điều kiện xác định/phân loại trạng thái thiết bị. */
function xacDinhTrangThai(string $mucDoUuTien): string
{
    if ($mucDoUuTien === 'Cao') {
        return 'Khẩn cấp - Ngừng cho mượn';
    } elseif ($mucDoUuTien === 'Trung bình') {
        return 'Chờ bảo trì - Ngừng cho mượn';
    } else {
        return 'Theo dõi - Vẫn có thể cân nhắc';
    }
}

/** Kiểm tra dữ liệu form báo hỏng. Trả về mảng lỗi (rỗng nếu hợp lệ). */
function kiemTraDuLieuBaoHong(array $du_lieu): array
{
    $loi = [];
    if (trim($du_lieu['ma_thiet_bi'] ?? '') === '') $loi[] = 'Vui lòng nhập Mã thiết bị.';
    if (trim($du_lieu['ten_thiet_bi'] ?? '') === '') $loi[] = 'Vui lòng nhập Tên thiết bị.';
    if (trim($du_lieu['nguoi_bao_hong'] ?? '') === '') $loi[] = 'Vui lòng nhập Người báo hỏng.';
    if (trim($du_lieu['mo_ta_loi'] ?? '') === '') $loi[] = 'Vui lòng mô tả lỗi thiết bị.';
    if (!in_array($du_lieu['muc_do_uu_tien'] ?? '', ['Cao', 'Trung bình', 'Thấp'], true)) {
        $loi[] = 'Vui lòng chọn Mức độ ưu tiên hợp lệ.';
    }
    return $loi;
}

/** Thêm 1 phiếu báo hỏng mới vào MySQL. */
function themBaoHong(PDO $pdo, array $phieu): bool
{
    $stmt = $pdo->prepare("INSERT INTO bao_hong
        (ma_thiet_bi, ten_thiet_bi, nguoi_bao_hong, mo_ta_loi, muc_do_uu_tien, han_xu_ly, trang_thai)
        VALUES (:ma_thiet_bi, :ten_thiet_bi, :nguoi_bao_hong, :mo_ta_loi, :muc_do_uu_tien, :han_xu_ly, :trang_thai)");

    return $stmt->execute([
        ':ma_thiet_bi'    => $phieu['ma_thiet_bi'],
        ':ten_thiet_bi'   => $phieu['ten_thiet_bi'],
        ':nguoi_bao_hong' => $phieu['nguoi_bao_hong'],
        ':mo_ta_loi'      => $phieu['mo_ta_loi'],
        ':muc_do_uu_tien' => $phieu['muc_do_uu_tien'],
        ':han_xu_ly'      => $phieu['han_xu_ly'],
        ':trang_thai'     => $phieu['trang_thai'],
    ]);
}

// ===== BẮT ĐẦU XỬ LÝ =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.php');
    exit;
}

// 1) TIẾP NHẬN VÀ VALIDATE DỮ LIỆU NHẬP
$loiValidate = kiemTraDuLieuBaoHong($_POST);

if (!empty($loiValidate)) {
    $_SESSION['loi_validate'] = $loiValidate;
    $_SESSION['du_lieu_cu'] = $_POST;
    header('Location: form.php');
    exit;
}

$mucDoUuTien = $_POST['muc_do_uu_tien'];

// 2) SỬ DỤNG HÀM TỰ ĐỊNH NGHĨA để xử lý nghiệp vụ có ý nghĩa
$hanXuLy = tinhHanXuLy($mucDoUuTien);

// 3) SỬ DỤNG ĐIỀU KIỆN để xác định/phân loại trạng thái thiết bị
$trangThai = xacDinhTrangThai($mucDoUuTien);

// 4) TỔ CHỨC DỮ LIỆU BẰNG MẢNG trước khi lưu
$phieuMoi = [
    'ma_thiet_bi'    => trim($_POST['ma_thiet_bi']),
    'ten_thiet_bi'   => trim($_POST['ten_thiet_bi']),
    'nguoi_bao_hong' => trim($_POST['nguoi_bao_hong']),
    'mo_ta_loi'      => trim($_POST['mo_ta_loi']),
    'muc_do_uu_tien' => $mucDoUuTien,
    'han_xu_ly'      => $hanXuLy,
    'trang_thai'     => $trangThai,
];

// 5) LƯU VÀO MYSQL RỒI QUAY VỀ form.php (tránh lỗi gửi lại dữ liệu khi F5)
if (themBaoHong($pdo, $phieuMoi)) {
    $_SESSION['thong_bao_thanh_cong'] = 'Đã ghi nhận báo hỏng thiết bị "' . $phieuMoi['ten_thiet_bi'] . '" thành công!';
} else {
    $_SESSION['loi_validate'] = ['Có lỗi xảy ra khi lưu dữ liệu vào cơ sở dữ liệu.'];
    $_SESSION['du_lieu_cu'] = $_POST;
}

header('Location: form.php');
exit;