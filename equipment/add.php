<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThietBi.php';

$tbModel = new ThietBi($pdo);
$cacLoai = $tbModel->danhSachLoai();
$cacPhong = $tbModel->danhSachPhong();

$errors = $_SESSION['tb_errors'] ?? [];
$old    = $_SESSION['tb_old'] ?? [];
unset($_SESSION['tb_errors'], $_SESSION['tb_old']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm thiết bị</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="modal-box" style="margin:40px auto;">
    <div class="modal-header">
        <h2>Thêm thiết bị</h2>
        <a href="index.php" class="modal-close">&times;</a>
    </div>

    <form method="POST" action="xuly.php" novalidate>
        <input type="hidden" name="mode" value="them">

        <div class="form-row">
            <div class="form-group <?= isset($errors['ma_thiet_bi']) ? 'has-error' : '' ?>">
                <label for="ma_thiet_bi">Mã thiết bị</label>
                <input type="text" id="ma_thiet_bi" name="ma_thiet_bi" placeholder="TB011" value="<?= htmlspecialchars($old['ma_thiet_bi'] ?? '') ?>">
                <div class="error-text"><?= $errors['ma_thiet_bi'] ?? '' ?></div>
            </div>

            <div class="form-group <?= isset($errors['ten_thiet_bi']) ? 'has-error' : '' ?>">
                <label for="ten_thiet_bi">Tên thiết bị</label>
                <input type="text" id="ten_thiet_bi" name="ten_thiet_bi" placeholder="Tên thiết bị" value="<?= htmlspecialchars($old['ten_thiet_bi'] ?? '') ?>">
                <div class="error-text"><?= $errors['ten_thiet_bi'] ?? '' ?></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="type_id">Danh mục</label>
                <select id="type_id" name="type_id">
                    <?php foreach ($cacLoai as $loai): ?>
                        <option value="<?= $loai['id'] ?>" <?= (int)($old['type_id'] ?? 0) === (int)$loai['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loai['ten_loai']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="room_id">Phòng</label>
                <select id="room_id" name="room_id">
                    <option value="">-- Không gắn phòng (thiết bị lưu động) --</option>
                    <?php foreach ($cacPhong as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (int)($old['room_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['ma_phong']) ?> — <?= htmlspecialchars($p['ten_phong']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group <?= isset($errors['so_luong']) ? 'has-error' : '' ?>">
                <label for="so_luong">Số lượng</label>
                <input type="number" id="so_luong" name="so_luong" placeholder="1" min="1" value="<?= htmlspecialchars($old['so_luong'] ?? '1') ?>">
                <div class="error-text"><?= $errors['so_luong'] ?? '' ?></div>
            </div>

            <div class="form-group">
                <label for="gia_tri">Giá trị (đ)</label>
                <input type="number" id="gia_tri" name="gia_tri" placeholder="0" min="0" value="<?= htmlspecialchars($old['gia_tri'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="ngay_mua">Ngày mua</label>
                <input type="date" id="ngay_mua" name="ngay_mua" value="<?= htmlspecialchars($old['ngay_mua'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="trang_thai">Trạng thái</label>
                <select id="trang_thai" name="trang_thai">
                    <option value="active" <?= ($old['trang_thai'] ?? 'active') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="maintenance" <?= ($old['trang_thai'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Bảo trì</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="mo_ta">Ghi chú</label>
            <textarea id="mo_ta" name="mo_ta" placeholder="Ghi chú thêm..."><?= htmlspecialchars($old['mo_ta'] ?? '') ?></textarea>
        </div>

        <div class="modal-footer">
            <a href="index.php" class="btn btn-cancel">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu thiết bị</button>
        </div>
    </form>
</div>

</body>
</html>