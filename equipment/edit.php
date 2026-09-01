<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThietBi.php';

$tbModel = new ThietBi($pdo);
$cacLoai  = $tbModel->danhSachLoai();
$cacPhong = $tbModel->danhSachPhong();

$errors = $_SESSION['tb_errors'] ?? [];
$old    = $_SESSION['tb_old'] ?? [];
unset($_SESSION['tb_errors'], $_SESSION['tb_old']);

$id = (int)($_GET['id'] ?? 0);
$tb = $tbModel->getById($id);

if (!$tb) {
    header('Location: index.php?msg=' . urlencode('Không tìm thấy thiết bị'));
    exit;
}

$data = !empty($old) ? $old : $tb;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa thiết bị</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="modal-box" style="margin:40px auto;">
    <div class="modal-header">
        <h2>Sửa thiết bị</h2>
        <a href="index.php" class="modal-close">&times;</a>
    </div>

    <form method="POST" action="xuly.php" novalidate>
        <input type="hidden" name="mode" value="sua">
        <input type="hidden" name="id" value="<?= (int)$tb['id'] ?>">

        <div class="form-row">
            <div class="form-group <?= isset($errors['ma_thiet_bi']) ? 'has-error' : '' ?>">
                <label for="ma_thiet_bi">Mã thiết bị</label>
                <input type="text" id="ma_thiet_bi" name="ma_thiet_bi" value="<?= htmlspecialchars($data['ma_thiet_bi']) ?>">
                <div class="error-text"><?= $errors['ma_thiet_bi'] ?? '' ?></div>
            </div>

            <div class="form-group <?= isset($errors['ten_thiet_bi']) ? 'has-error' : '' ?>">
                <label for="ten_thiet_bi">Tên thiết bị</label>
                <input type="text" id="ten_thiet_bi" name="ten_thiet_bi" value="<?= htmlspecialchars($data['ten_thiet_bi']) ?>">
                <div class="error-text"><?= $errors['ten_thiet_bi'] ?? '' ?></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="type_id">Danh mục</label>
                <select id="type_id" name="type_id">
                    <?php foreach ($cacLoai as $loai): ?>
                        <option value="<?= $loai['id'] ?>" <?= (int)$data['type_id'] === (int)$loai['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loai['ten_loai']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="room_id">Phòng</label>
                <select id="room_id" name="room_id">
                    <option value="">-- Không gắn phòng --</option>
                    <?php foreach ($cacPhong as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (int)($data['room_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['ma_phong']) ?> — <?= htmlspecialchars($p['ten_phong']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group <?= isset($errors['so_luong']) ? 'has-error' : '' ?>">
                <label for="so_luong">Số lượng</label>
                <input type="number" id="so_luong" name="so_luong" min="1" value="<?= htmlspecialchars($data['so_luong']) ?>">
                <div class="error-text"><?= $errors['so_luong'] ?? '' ?></div>
            </div>

            <div class="form-group">
                <label for="gia_tri">Giá trị (đ)</label>
                <input type="number" id="gia_tri" name="gia_tri" min="0" value="<?= htmlspecialchars($data['gia_tri'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="ngay_mua">Ngày mua</label>
                <input type="date" id="ngay_mua" name="ngay_mua" value="<?= htmlspecialchars($data['ngay_mua'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="trang_thai">Trạng thái</label>
                <select id="trang_thai" name="trang_thai">
                    <option value="active" <?= $data['trang_thai'] === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="maintenance" <?= $data['trang_thai'] === 'maintenance' ? 'selected' : '' ?>>Bảo trì</option>
                    <option value="broken" <?= $data['trang_thai'] === 'broken' ? 'selected' : '' ?>>Hỏng</option>
                    <option value="borrowed" <?= $data['trang_thai'] === 'borrowed' ? 'selected' : '' ?>>Đang mượn</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="mo_ta">Ghi chú</label>
            <textarea id="mo_ta" name="mo_ta"><?= htmlspecialchars($data['mo_ta'] ?? '') ?></textarea>
        </div>

        <div class="modal-footer">
            <a href="index.php" class="btn btn-cancel">Hủy</a>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </div>
    </form>
</div>

</body>
</html>