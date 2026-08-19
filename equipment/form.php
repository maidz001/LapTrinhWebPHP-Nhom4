<?php

session_start();

// Khởi tạo mảng lưu danh sách thiết bị trong session (nếu chưa có)
if (!isset($_SESSION['danh_sach_thiet_bi'])) {
    $_SESSION['danh_sach_thiet_bi'] = [];
}

$thongBao = '';
$loaiThongBao = ''; // 'thanh-cong' hoặc 'loi'

// Mảng lỗi theo từng trường, dùng để hiển thị lỗi ngay tại trường tương ứng
// Cấu trúc: ['ten_thiet_bi' => 'thông báo lỗi', 'loai_thiet_bi' => '...', ...]
$loi = [];

// Dữ liệu người dùng đã nhập, dùng để hiển thị lại trên form khi có lỗi
// (giữ lại giá trị hợp lệ / đã nhập, người dùng không phải gõ lại từ đầu)
$duLieuNhap = [
    'ten_thiet_bi'  => '',
    'loai_thiet_bi' => '',
    'trang_thai'    => '',
    'phong_dat'     => '',
];

// Danh sách trạng thái hợp lệ (whitelist) - dùng để kiểm tra chống dữ liệu
// bị can thiệp (vd người dùng tự sửa value trong DevTools rồi submit)
$cacTrangThaiHopLe = ['Hoạt động', 'Hỏng', 'Đang bảo trì'];


function xacDinhKhaNangChoMuon(string $trangThai): string
{
    if ($trangThai === 'Hoạt động') {
        return 'Có thể cho mượn';
    } elseif ($trangThai === 'Hỏng' || $trangThai === 'Đang bảo trì') {
        return 'Không thể cho mượn';
    }
    return 'Không xác định';
}

// Chuẩn hóa chuỗi nhập vào: cắt khoảng trắng đầu/cuối,
// gộp nhiều khoảng trắng liên tiếp ở giữa thành 1 khoảng trắng
function chuanHoaChuoi(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s;
}

// Xóa toàn bộ danh sách (phục vụ demo/test lại từ đầu)
if (isset($_GET['xoa'])) {
    $_SESSION['danh_sach_thiet_bi'] = [];
    header('Location: buoi3_thietbi.php');
    exit;
}

// Xử lý khi người dùng submit form thêm thiết bị
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them_thiet_bi'])) {

    // --- Bước 1: Chuẩn hóa dữ liệu đầu vào trước khi xử lý ---
    $tenThietBi  = chuanHoaChuoi($_POST['ten_thiet_bi'] ?? '');
    $loaiThietBi = chuanHoaChuoi($_POST['loai_thiet_bi'] ?? '');
    $trangThai   = trim($_POST['trang_thai'] ?? '');
    $phongDat    = strtoupper(chuanHoaChuoi($_POST['phong_dat'] ?? ''));

    // Lưu lại dữ liệu đã chuẩn hóa để hiển thị lại trên form nếu có lỗi
    $duLieuNhap['ten_thiet_bi']  = $tenThietBi;
    $duLieuNhap['loai_thiet_bi'] = $loaiThietBi;
    $duLieuNhap['trang_thai']    = $trangThai;
    $duLieuNhap['phong_dat']     = $phongDat;

    // --- Bước 2: Kiểm tra dữ liệu phía server cho từng trường ---

    // Tên thiết bị: bắt buộc, độ dài 3-100 ký tự
    if ($tenThietBi === '') {
        $loi['ten_thiet_bi'] = 'Vui lòng nhập tên thiết bị.';
    } elseif (mb_strlen($tenThietBi) < 3 || mb_strlen($tenThietBi) > 100) {
        $loi['ten_thiet_bi'] = 'Tên thiết bị phải có độ dài từ 3 đến 100 ký tự.';
    }

    // Loại thiết bị: bắt buộc, 2-50 ký tự, chỉ gồm chữ cái (có dấu) và khoảng trắng
    if ($loaiThietBi === '') {
        $loi['loai_thiet_bi'] = 'Vui lòng nhập loại thiết bị.';
    } elseif (mb_strlen($loaiThietBi) < 2 || mb_strlen($loaiThietBi) > 50) {
        $loi['loai_thiet_bi'] = 'Loại thiết bị phải có độ dài từ 2 đến 50 ký tự.';
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $loaiThietBi)) {
        $loi['loai_thiet_bi'] = 'Loại thiết bị chỉ được chứa chữ cái và khoảng trắng (không chứa số, ký tự đặc biệt hay thẻ HTML).';
    }

    // Trạng thái: bắt buộc, phải thuộc danh sách hợp lệ (whitelist)
    if ($trangThai === '') {
        $loi['trang_thai'] = 'Vui lòng chọn trạng thái.';
    } elseif (!in_array($trangThai, $cacTrangThaiHopLe, true)) {
        $loi['trang_thai'] = 'Trạng thái không hợp lệ.';
    }

    // Phòng đặt: bắt buộc, đúng định dạng 1-5 chữ cái + 1-4 chữ số, VD: A305, B12
    if ($phongDat === '') {
        $loi['phong_dat'] = 'Vui lòng nhập phòng đặt.';
    } elseif (!preg_match('/^[A-Z]{1,5}[0-9]{1,4}$/', $phongDat)) {
        $loi['phong_dat'] = 'Phòng đặt không đúng định dạng. Ví dụ hợp lệ: A305, B12.';
    }

    // --- Bước 3: Nếu không có lỗi thì lưu dữ liệu, ngược lại giữ lại dữ liệu đã nhập ---
    if (empty($loi)) {
        $khaNangChoMuon = xacDinhKhaNangChoMuon($trangThai);

        $thietBiMoi = [
            'ten'               => $tenThietBi,
            'loai'              => $loaiThietBi,
            'trang_thai'        => $trangThai,
            'phong'             => $phongDat,
            'kha_nang_cho_muon' => $khaNangChoMuon,
        ];

        $_SESSION['danh_sach_thiet_bi'][] = $thietBiMoi;

        $thongBao = 'Đã thêm thiết bị "' . htmlspecialchars($tenThietBi, ENT_QUOTES, 'UTF-8') . '" thành công.';
        $loaiThongBao = 'thanh-cong';

        // Thêm thành công thì làm trống lại dữ liệu nhập trên form
        $duLieuNhap = [
            'ten_thiet_bi'  => '',
            'loai_thiet_bi' => '',
            'trang_thai'    => '',
            'phong_dat'     => '',
        ];
    } else {
        $thongBao = 'Dữ liệu chưa hợp lệ. Vui lòng kiểm tra lại các trường được đánh dấu lỗi bên dưới.';
        $loaiThongBao = 'loi';
    }
}

$danhSachThietBi = $_SESSION['danh_sach_thiet_bi'];

// Vòng lặp thống kê nhanh theo trạng thái (dùng để hiển thị dashboard mini)
$soHoatDong = 0;
$soHong = 0;
$soBaoTri = 0;
foreach ($danhSachThietBi as $tb) {
    if ($tb['trang_thai'] === 'Hoạt động') {
        $soHoatDong++;
    } elseif ($tb['trang_thai'] === 'Hỏng') {
        $soHong++;
    } elseif ($tb['trang_thai'] === 'Đang bảo trì') {
        $soBaoTri++;
    }
}

// Hàm rút gọn để escape dữ liệu trước khi hiển thị ra HTML (chống XSS)
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý thiết bị - Bài cá nhân buổi 3</title>
    <style>
        body { font-family: Segoe UI, Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; color: #1f2933; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        p.mota { color: #52606d; margin-top: 0; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 10px; padding: 20px 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; margin-top: 12px; color: #334e68; }
        input[type=text], select {
            width: 100%; padding: 8px 10px; border: 1px solid #cbd2d9; border-radius: 6px; font-size: 14px; box-sizing: border-box;
        }
        input.loi-input, select.loi-input {
            border-color: #b3271b; background: #fff8f7;
        }
        .loi-text { color: #b3271b; font-size: 12px; margin-top: 4px; }
        button {
            margin-top: 18px; background: #2f6feb; color: #fff; border: none; padding: 10px 18px;
            border-radius: 6px; font-size: 14px; cursor: pointer;
        }
        button:hover { background: #2554b7; }
        .thongbao { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .thanh-cong { background: #e3f9e5; color: #1b7a2c; border: 1px solid #a9e6ac; }
        .loi { background: #fdecea; color: #b3271b; border: 1px solid #f5c6c1; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e4e7eb; font-size: 13px; }
        th { background: #f4f6f8; color: #334e68; }
        .badge { padding: 3px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-hoatdong { background: #e3f9e5; color: #1b7a2c; }
        .badge-hong { background: #fdecea; color: #b3271b; }
        .badge-baotri { background: #fff4e0; color: #a15c00; }
        .stats { display: flex; gap: 16px; margin-bottom: 16px; }
        .stat-box { flex: 1; background: #fff; border-radius: 8px; padding: 14px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-box .so { font-size: 22px; font-weight: 700; }
        .stat-box .nhan { font-size: 12px; color: #7b8794; }
        .xoa-link { font-size: 12px; color: #b3271b; text-decoration: none; }
        .ghi-chu { font-size: 12px; color: #7b8794; margin-top: 2px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Quản lý thiết bị phòng thực hành</h1>
    <p class="mota">Bài cá nhân buổi 3 — kiểm tra dữ liệu phía server &amp; chống XSS</p>

    <?php if ($thongBao): ?>
        <div class="thongbao <?php echo $loaiThongBao; ?>"><?php echo e($thongBao); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top:0;">Thêm thiết bị mới</h3>
        <form method="POST" action="" novalidate>
            <label for="ten_thiet_bi">Tên thiết bị</label>
            <input
                type="text"
                id="ten_thiet_bi"
                name="ten_thiet_bi"
                placeholder="VD: Máy chiếu Epson EB-X05"
                value="<?php echo e($duLieuNhap['ten_thiet_bi']); ?>"
                class="<?php echo isset($loi['ten_thiet_bi']) ? 'loi-input' : ''; ?>"
            >
            <?php if (isset($loi['ten_thiet_bi'])): ?>
                <div class="loi-text"><?php echo e($loi['ten_thiet_bi']); ?></div>
            <?php endif; ?>

            <label for="loai_thiet_bi">Loại thiết bị</label>
            <input
                type="text"
                id="loai_thiet_bi"
                name="loai_thiet_bi"
                placeholder="VD: Máy chiếu, Laptop, Micro..."
                value="<?php echo e($duLieuNhap['loai_thiet_bi']); ?>"
                class="<?php echo isset($loi['loai_thiet_bi']) ? 'loi-input' : ''; ?>"
            >
            <?php if (isset($loi['loai_thiet_bi'])): ?>
                <div class="loi-text"><?php echo e($loi['loai_thiet_bi']); ?></div>
            <?php endif; ?>

            <label for="trang_thai">Trạng thái</label>
            <select
                id="trang_thai"
                name="trang_thai"
                class="<?php echo isset($loi['trang_thai']) ? 'loi-input' : ''; ?>"
            >
                <option value="">-- Chọn trạng thái --</option>
                <?php foreach ($cacTrangThaiHopLe as $tt): ?>
                    <option value="<?php echo e($tt); ?>" <?php echo ($duLieuNhap['trang_thai'] === $tt) ? 'selected' : ''; ?>>
                        <?php echo e($tt); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($loi['trang_thai'])): ?>
                <div class="loi-text"><?php echo e($loi['trang_thai']); ?></div>
            <?php endif; ?>

            <label for="phong_dat">Phòng đặt</label>
            <input
                type="text"
                id="phong_dat"
                name="phong_dat"
                placeholder="VD: A305"
                value="<?php echo e($duLieuNhap['phong_dat']); ?>"
                class="<?php echo isset($loi['phong_dat']) ? 'loi-input' : ''; ?>"
            >
            <?php if (isset($loi['phong_dat'])): ?>
                <div class="loi-text"><?php echo e($loi['phong_dat']); ?></div>
            <?php else: ?>
                <div class="ghi-chu">Định dạng: 1-5 chữ cái + 1-4 chữ số, VD: A305, B12.</div>
            <?php endif; ?>

            <button type="submit" name="them_thiet_bi">Thêm thiết bị</button>
        </form>
    </div>

    <div class="stats">
        <div class="stat-box"><div class="so"><?php echo count($danhSachThietBi); ?></div><div class="nhan">Tổng thiết bị</div></div>
        <div class="stat-box"><div class="so"><?php echo $soHoatDong; ?></div><div class="nhan">Hoạt động</div></div>
        <div class="stat-box"><div class="so"><?php echo $soHong; ?></div><div class="nhan">Hỏng</div></div>
        <div class="stat-box"><div class="so"><?php echo $soBaoTri; ?></div><div class="nhan">Đang bảo trì</div></div>
    </div>

    <div class="card">
        <h3 style="margin-top:0; display:flex; justify-content:space-between; align-items:center;">
            Danh sách thiết bị
            <a class="xoa-link" href="?xoa=1" onclick="return confirm('Xóa toàn bộ danh sách?');">Xóa toàn bộ</a>
        </h3>

        <?php if (empty($danhSachThietBi)): ?>
            <p style="color:#7b8794;">Chưa có thiết bị nào. Hãy thêm thiết bị ở form phía trên.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Tên thiết bị</th>
                    <th>Loại</th>
                    <th>Phòng</th>
                    <th>Trạng thái</th>
                    <th>Khả năng cho mượn</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $stt = 1;
                foreach ($danhSachThietBi as $tb):
                    if ($tb['trang_thai'] === 'Hoạt động') {
                        $badgeClass = 'badge-hoatdong';
                    } elseif ($tb['trang_thai'] === 'Hỏng') {
                        $badgeClass = 'badge-hong';
                    } else {
                        $badgeClass = 'badge-baotri';
                    }
                    ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><?php echo e($tb['ten']); ?></td>
                        <td><?php echo e($tb['loai']); ?></td>
                        <td><?php echo e($tb['phong']); ?></td>
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo e($tb['trang_thai']); ?></span></td>
                        <td><?php echo e($tb['kha_nang_cho_muon']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>