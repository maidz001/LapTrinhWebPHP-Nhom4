<?php
/**
 * form.php
 * =====================================================
 * VIEW: Hiển thị giao diện báo hỏng & Lịch sử báo hỏng.
 * =====================================================
 */
require_once __DIR__ . '/store.php';

// ===== Lấy thông báo/lỗi từ session =====
$loiTruong = $_SESSION['loi_truong'] ?? [];
$loiValidate = $_SESSION['loi_validate'] ?? [];
$duLieuCu = $_SESSION['du_lieu_cu'] ?? [];
$thongBaoThanhCong = $_SESSION['thong_bao_thanh_cong'] ?? '';
$loiDangNhap = $_SESSION['loi_dang_nhap'] ?? '';
unset(
    $_SESSION['loi_truong'], $_SESSION['loi_validate'], $_SESSION['du_lieu_cu'],
    $_SESSION['thong_bao_thanh_cong'], $_SESSION['loi_dang_nhap']
);

$daDangNhap = isset($_SESSION['can_bo']);
$tenCanBo = $_SESSION['can_bo']['ho_ten'] ?? '';

/** Helper hiển thị: mã hóa chống XSS */
function giaTri(array $duLieuCu, string $ten): string
{
    return htmlspecialchars($duLieuCu[$ten] ?? '', ENT_QUOTES, 'UTF-8');
}

// ===== Lấy từ khóa và tham số tìm kiếm từ GET =====
$tuKhoaTimKiem = trim($_GET['tu_khoa'] ?? '');
$locUuTien = trim($_GET['uu_tien'] ?? '');
$locTrangThai = trim($_GET['trang_thai_xu_ly'] ?? '');

// ===== Lấy danh sách phiếu báo hỏng (Có áp dụng tìm kiếm & lọc) =====
$danhSachBaoHong = $repo->timKiemDanhSach($tuKhoaTimKiem, $locUuTien, $locTrangThai);

// Tính toán thống kê trên toàn bộ dữ liệu gốc
$tatCaPhieu = $repo->layDanhSach();
$soLuongTheoMuc = ['Cao' => 0, 'Trung bình' => 0, 'Thấp' => 0];
foreach ($tatCaPhieu as $phieu) {
    if (isset($soLuongTheoMuc[$phieu['muc_do_uu_tien']])) {
        $soLuongTheoMuc[$phieu['muc_do_uu_tien']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Báo hỏng thiết bị</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #f4f6f9; color: #1f2937; }
.header { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; padding: 24px 20px; }
.header-top { display: flex; justify-content: space-between; align-items: center; max-width: 1100px; margin: 0 auto; flex-wrap: wrap; gap: 10px; }
.header h1 { margin: 0 0 4px; font-size: 24px; }
.subtitle { margin: 0; opacity: 0.9; font-size: 13px; }
.auth-box { font-size: 13px; background: rgba(255,255,255,0.12); padding: 8px 14px; border-radius: 8px; }
.auth-box a { color: #fff; font-weight: 600; text-decoration: underline; }
.auth-box input { padding: 6px 8px; border-radius: 5px; border: none; font-size: 13px; margin-left: 4px; width: 110px; }
.auth-box button { padding: 6px 10px; border-radius: 5px; border: none; background: #fff; color: #1e3a8a; font-weight: 700; cursor: pointer; margin-left: 4px; }
.container { max-width: 1100px; margin: 30px auto; padding: 0 16px 60px; display: flex; flex-direction: column; gap: 24px; }
.card { background: #fff; border-radius: 10px; padding: 22px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.card h2 { margin-top: 0; font-size: 19px; border-left: 4px solid #2563eb; padding-left: 10px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { margin-bottom: 16px; }
label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; }
.required { color: #dc2626; }
.hint { display: block; font-size: 12px; color: #6b7280; margin-top: 4px; }
input[type="text"], select, textarea, input[type="password"] { width: 100%; padding: 9px 11px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; font-family: inherit; }
input:focus, select:focus, textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,0.15); }
.invalid { border-color: #dc2626 !important; background: #fef2f2; }
.error-text { display: block; color: #dc2626; font-size: 12.5px; margin-top: 5px; font-weight: 600; }
.btn-submit { background: #2563eb; color: #fff; border: none; padding: 11px 22px; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; }
.btn-submit:hover { background: #1d4ed8; }
.thong-bao { padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
.thong-bao ul { margin: 6px 0 0 18px; padding: 0; }
.thong-bao-loi { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.thong-bao-thanh-cong { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.thong-ke { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
.badge { padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; }
.badge-cao { background: #fee2e2; color: #b91c1c; }
.badge-tb { background: #fef3c7; color: #92400e; }
.badge-thap { background: #dcfce7; color: #166534; }
.badge-tong { background: #e0e7ff; color: #3730a3; }

/* CSS Khung Tìm kiếm */
.search-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px; }
.search-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.search-form input[type="text"] { flex: 2; min-width: 200px; }
.search-form select { flex: 1; min-width: 140px; }
.btn-search { background: #0284c7; color: #fff; border: none; padding: 9px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-search:hover { background: #0369a1; }
.btn-reset { background: #64748b; color: #fff; text-decoration: none; padding: 9px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th, td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: middle; }
th { background: #f9fafb; font-weight: 700; white-space: nowrap; }
tbody tr:hover { background: #f9fafb; }
.tag { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
.tag-cao { background: #fee2e2; color: #b91c1c; }
.tag-tb { background: #fef3c7; color: #92400e; }
.tag-thap { background: #dcfce7; color: #166534; }
.tag-khong-cho-muon { background: #fee2e2; color: #b91c1c; }
.tag-cho-muon { background: #dcfce7; color: #166534; }
.tag-chua-xu-ly { background: #f3f4f6; color: #374151; }
.tag-dang-xu-ly { background: #fef3c7; color: #92400e; }
.tag-da-xu-ly { background: #dcfce7; color: #166534; }
.rong { color: #6b7280; font-style: italic; }

/* CSS Input khi chỉnh sửa trực tiếp trên bảng */
.edit-input { width: 100%; min-width: 120px; font-size: 12px; padding: 5px 6px; }
.edit-select { font-size: 12px; padding: 5px 6px; width: auto; }
.btn-save { padding: 5px 10px; font-size: 12px; border: none; border-radius: 5px; background: #16a34a; color: #fff; cursor: pointer; font-weight: 600; }
.btn-save:hover { background: #15803d; }
.khoa { color: #9ca3af; font-size: 11px; font-style: italic; margin-top: 2px; }

#ket-qua-api { font-size: 13px; }
#ket-qua-api .dong { padding: 6px 0; border-bottom: 1px dashed #e5e7eb; }
.api-btn { padding: 8px 16px; border-radius: 6px; border: none; background: #374151; color: #fff; font-size: 13px; cursor: pointer; }
@media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } .search-form { flex-direction: column; align-items: stretch; } }
</style>
</head>
<body>

<header class="header">
    <div class="header-top">
        <div>
            <h1>🛠️ Báo hỏng thiết bị</h1>
            <p class="subtitle">Hệ thống quản lý phòng thực hành và thiết bị &middot; Báo hỏng &amp; Bảo trì</p>
        </div>

        <?php if ($daDangNhap): ?>
            <div class="auth-box">
                👷 Xin chào <b><?= htmlspecialchars($tenCanBo) ?></b> (cán bộ lab)
                &nbsp;|&nbsp; <a href="store.php?action=dang_xuat">Đăng xuất</a>
            </div>
        <?php else: ?>
            <form class="auth-box" method="POST" action="store.php">
                <input type="hidden" name="hanh_dong" value="dang_nhap">
                <span>Đăng nhập cán bộ lab:</span>
                <input type="text" name="ten_dang_nhap" placeholder="Tài khoản" required>
                <input type="password" name="mat_khau" placeholder="Mật khẩu" required>
                <button type="submit">Vào</button>
            </form>
        <?php endif; ?>
    </div>
</header>

<main class="container">

    <?php if ($loiDangNhap): ?>
        <div class="thong-bao thong-bao-loi"><?= htmlspecialchars($loiDangNhap) ?>
            <span class="hint">(Tài khoản demo: <b>canbo1</b> / mật khẩu <b>123456</b>)</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($loiValidate)): ?>
        <div class="thong-bao thong-bao-loi">
            <ul><?php foreach ($loiValidate as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($thongBaoThanhCong): ?>
        <div class="thong-bao thong-bao-thanh-cong"><?= htmlspecialchars($thongBaoThanhCong) ?></div>
    <?php endif; ?>

    <!-- FORM BÁO HỎNG -->
    <section class="card">
        <h2>Phiếu báo hỏng thiết bị</h2>

        <form method="POST" action="store.php" novalidate>
            <input type="hidden" name="hanh_dong" value="gui_bao_hong">

            <div class="grid-2">
                <div class="form-group">
                    <label>Mã thiết bị <span class="required">*</span></label>
                    <input type="text" name="ma_thiet_bi" class="<?= isset($loiTruong['ma_thiet_bi']) ? 'invalid' : '' ?>"
                           value="<?= giaTri($duLieuCu, 'ma_thiet_bi') ?>" placeholder="VD: TB004">
                    <span class="hint">Chỉ chữ và số, 3-20 ký tự.</span>
                    <?php if (isset($loiTruong['ma_thiet_bi'])): ?><span class="error-text"><?= htmlspecialchars($loiTruong['ma_thiet_bi']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Tên thiết bị <span class="required">*</span></label>
                    <input type="text" name="ten_thiet_bi" class="<?= isset($loiTruong['ten_thiet_bi']) ? 'invalid' : '' ?>"
                           value="<?= giaTri($duLieuCu, 'ten_thiet_bi') ?>" placeholder="VD: Máy chiếu Epson EB-X05">
                    <?php if (isset($loiTruong['ten_thiet_bi'])): ?><span class="error-text"><?= htmlspecialchars($loiTruong['ten_thiet_bi']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Người báo hỏng <span class="required">*</span></label>
                    <input type="text" name="nguoi_bao_hong" class="<?= isset($loiTruong['nguoi_bao_hong']) ? 'invalid' : '' ?>"
                           value="<?= giaTri($duLieuCu, 'nguoi_bao_hong') ?>" placeholder="Họ tên (chỉ chữ cái)">
                    <?php if (isset($loiTruong['nguoi_bao_hong'])): ?><span class="error-text"><?= htmlspecialchars($loiTruong['nguoi_bao_hong']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Mức độ ưu tiên <span class="required">*</span></label>
                    <select name="muc_do_uu_tien" class="<?= isset($loiTruong['muc_do_uu_tien']) ? 'invalid' : '' ?>">
                        <option value="">-- Chọn mức độ --</option>
                        <?php foreach (['Cao', 'Trung bình', 'Thấp'] as $muc): ?>
                            <option value="<?= $muc ?>" <?= (($duLieuCu['muc_do_uu_tien'] ?? '') === $muc) ? 'selected' : '' ?>><?= $muc ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($loiTruong['muc_do_uu_tien'])): ?><span class="error-text"><?= htmlspecialchars($loiTruong['muc_do_uu_tien']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Mô tả lỗi <span class="required">*</span></label>
                <textarea name="mo_ta_loi" rows="3" class="<?= isset($loiTruong['mo_ta_loi']) ? 'invalid' : '' ?>"
                          placeholder="Mô tả chi tiết hiện tượng hỏng hóc (10-500 ký tự)..."><?= giaTri($duLieuCu, 'mo_ta_loi') ?></textarea>
                <?php if (isset($loiTruong['mo_ta_loi'])): ?><span class="error-text"><?= htmlspecialchars($loiTruong['mo_ta_loi']) ?></span><?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">Gửi báo hỏng</button>
        </form>
    </section>

    <!-- BẢNG LỊCH SỬ VÀ TÌM KIẾM -->
    <section class="card">
        <h2>Lịch sử báo hỏng / bảo trì</h2>

        <div class="thong-ke">
            <span class="badge badge-cao">Cao: <?= $soLuongTheoMuc['Cao'] ?></span>
            <span class="badge badge-tb">Trung bình: <?= $soLuongTheoMuc['Trung bình'] ?></span>
            <span class="badge badge-thap">Thấp: <?= $soLuongTheoMuc['Thấp'] ?></span>
            <span class="badge badge-tong">Tổng: <?= count($tatCaPhieu) ?></span>
        </div>

        <!-- THANH TÌM KIẾM VÀ LỌC -->
        <div class="search-box">
            <form method="GET" action="form.php" class="search-form">
                <input type="text" name="tu_khoa" value="<?= htmlspecialchars($tuKhoaTimKiem) ?>" placeholder="🔍 Tìm theo mã TB, tên TB, người báo...">
                
                <select name="uu_tien">
                    <option value="">-- Tất cả mức độ --</option>
                    <?php foreach (['Cao', 'Trung bình', 'Thấp'] as $m): ?>
                        <option value="<?= $m ?>" <?= $locUuTien === $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="trang_thai_xu_ly">
                    <option value="">-- Tất cả trạng thái --</option>
                    <?php foreach (['Chưa xử lý', 'Đang xử lý', 'Đã xử lý'] as $tt): ?>
                        <option value="<?= $tt ?>" <?= $locTrangThai === $tt ? 'selected' : '' ?>><?= $tt ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-search">Tìm kiếm</button>
                <?php if ($tuKhoaTimKiem !== '' || $locUuTien !== '' || $locTrangThai !== ''): ?>
                    <a href="form.php" class="btn-reset">Đặt lại</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($danhSachBaoHong)): ?>
            <p class="rong">Không tìm thấy phiếu báo hỏng nào phù hợp.</p>
        <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mã TB</th>
                    <th>Tên thiết bị</th>
                    <th>Người báo hỏng</th>
                    <th>Mô tả lỗi</th>
                    <th>Ưu tiên</th>
                    <th>Hạn xử lý</th>
                    <th>Cho mượn?</th>
                    <th>Trạng thái xử lý</th>
                    <th>Thời gian</th>
                    <?php if ($daDangNhap): ?><th>Thao tác</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $stt = 1; ?>
                <?php foreach ($danhSachBaoHong as $phieu): ?>
                    <?php
                        $khongChoMuon = BaoHongRepository::khongDuocChoMuon($phieu['trang_thai']);
                        $trangThaiXuLy = $phieu['trang_thai_xu_ly'] ?? 'Chưa xử lý';
                        $mapClass = ['Chưa xử lý' => 'chua-xu-ly', 'Đang xử lý' => 'dang-xu-ly', 'Đã xử lý' => 'da-xu-ly'];
                    ?>
                    <tr>
                        <?php if ($daDangNhap): ?>
                            <!-- FORM CẬP NHẬT TOÀN BỘ KHI ĐÃ ĐĂNG NHẬP -->
                            <form method="POST" action="store.php">
                                <input type="hidden" name="hanh_dong" value="cap_nhat_phieu">
                                <input type="hidden" name="id" value="<?= (int)$phieu['id'] ?>">
                                
                                <td><?= $stt++ ?></td>
                                <td><b><?= htmlspecialchars($phieu['ma_thiet_bi']) ?></b></td>
                                <td><?= htmlspecialchars($phieu['ten_thiet_bi']) ?></td>
                                <td><?= htmlspecialchars($phieu['nguoi_bao_hong']) ?></td>
                                <td>
                                    <input type="text" name="mo_ta_loi" class="edit-input" value="<?= htmlspecialchars($phieu['mo_ta_loi']) ?>" required>
                                </td>
                                <td>
                                    <select name="muc_do_uu_tien" class="edit-select">
                                        <?php foreach (['Cao', 'Trung bình', 'Thấp'] as $muc): ?>
                                            <option value="<?= $muc ?>" <?= $phieu['muc_do_uu_tien'] === $muc ? 'selected' : '' ?>><?= $muc ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><?= htmlspecialchars($phieu['han_xu_ly']) ?></td>
                                <td><?php if ($khongChoMuon): ?><span class="tag tag-khong-cho-muon">Không</span><?php else: ?><span class="tag tag-cho-muon">Có</span><?php endif; ?></td>
                                <td>
                                    <select name="trang_thai_xu_ly" class="edit-select">
                                        <?php foreach (['Chưa xử lý', 'Đang xử lý', 'Đã xử lý'] as $tt): ?>
                                            <option value="<?= $tt ?>" <?= $trangThaiXuLy === $tt ? 'selected' : '' ?>><?= $tt ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($phieu['ngay_bao_hong']))) ?></td>
                                <td>
                                    <button type="submit" class="btn-save">Lưu</button>
                                </td>
                            </form>
                        <?php else: ?>
                            <!-- HIỂN THỊ TĨNH KHI CHƯA ĐĂNG NHẬP -->
                            <td><?= $stt++ ?></td>
                            <td><b><?= htmlspecialchars($phieu['ma_thiet_bi']) ?></b></td>
                            <td><?= htmlspecialchars($phieu['ten_thiet_bi']) ?></td>
                            <td><?= htmlspecialchars($phieu['nguoi_bao_hong']) ?></td>
                            <td><?= htmlspecialchars($phieu['mo_ta_loi']) ?></td>
                            <td><span class="tag tag-<?= $phieu['muc_do_uu_tien'] === 'Cao' ? 'cao' : ($phieu['muc_do_uu_tien'] === 'Trung bình' ? 'tb' : 'thap') ?>"><?= htmlspecialchars($phieu['muc_do_uu_tien']) ?></span></td>
                            <td><?= htmlspecialchars($phieu['han_xu_ly']) ?></td>
                            <td><?php if ($khongChoMuon): ?><span class="tag tag-khong-cho-muon">Không</span><?php else: ?><span class="tag tag-cho-muon">Có</span><?php endif; ?></td>
                            <td><span class="tag tag-<?= $mapClass[$trangThaiXuLy] ?>"><?= htmlspecialchars($trangThaiXuLy) ?></span></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($phieu['ngay_bao_hong']))) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- DEMO FETCH API -->
    <section class="card">
        <h2>Xem nhanh qua API (demo Fetch)</h2>
        <p class="hint" style="margin-bottom:12px;">Gọi endpoint JSON <code>store.php?action=api</code> bằng JavaScript Fetch.</p>
        <button type="button" class="api-btn" id="btn-tai-api">Tải danh sách qua API</button>
        <div id="ket-qua-api" style="margin-top:14px;"></div>
    </section>

</main>

<script>
document.getElementById('btn-tai-api').addEventListener('click', function () {
    var ketQuaEl = document.getElementById('ket-qua-api');
    ketQuaEl.innerHTML = '⏳ Đang tải dữ liệu...';

    fetch('store.php?action=api')
        .then(function (res) {
            if (!res.ok) {
                throw new Error('Server trả về lỗi HTTP ' + res.status);
            }
            return res.json();
        })
        .then(function (data) {
            if (!data.thanh_cong) {
                throw new Error('API báo lỗi.');
            }
            if (data.du_lieu.length === 0) {
                ketQuaEl.innerHTML = '<i>Không có dữ liệu.</i>';
                return;
            }
            var html = '';
            data.du_lieu.forEach(function (item) {
                html += '<div class="dong"><b>' + item.ma_thiet_bi + '</b> - ' + item.ten_thiet_bi +
                        ' (' + item.muc_do_uu_tien + ')</div>';
            });
            ketQuaEl.innerHTML = html;
        })
        .catch(function (err) {
            ketQuaEl.innerHTML = '<span style="color:#b91c1c;">❌ Lỗi tải dữ liệu: ' + err.message + '</span>';
        });
});
</script>
</body>
</html>