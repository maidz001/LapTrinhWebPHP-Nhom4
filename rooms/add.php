<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm phòng thực hành</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="modal-box" style="margin:40px auto;">
    <div class="modal-header">
        <h2>Thêm phòng thực hành</h2>
        <a href="index.php" class="modal-close">&times;</a>
    </div>

    <form method="POST" action="xuly.php" novalidate>
        <input type="hidden" name="mode" value="them">

        <div class="form-group <?= isset($errors['ma_phong']) ? 'has-error' : '' ?>">
            <label for="ma_phong">Mã phòng</label>
            <input type="text" id="ma_phong" name="ma_phong" placeholder="LAB103" value="<?= htmlspecialchars($old['ma_phong'] ?? '') ?>">
            <div class="error-text"><?= $errors['ma_phong'] ?? '' ?></div>
        </div>

        <div class="form-group <?= isset($errors['ten_phong']) ? 'has-error' : '' ?>">
            <label for="ten_phong">Tên phòng</label>
            <input type="text" id="ten_phong" name="ten_phong" placeholder="Lab 103" value="<?= htmlspecialchars($old['ten_phong'] ?? '') ?>">
            <div class="error-text"><?= $errors['ten_phong'] ?? '' ?></div>
        </div>

        <div class="form-group <?= isset($errors['vi_tri']) ? 'has-error' : '' ?>">
            <label for="vi_tri">Vị trí</label>
            <input type="text" id="vi_tri" name="vi_tri" placeholder="Nhà A, Tầng 2" value="<?= htmlspecialchars($old['vi_tri'] ?? '') ?>">
            <div class="error-text"><?= $errors['vi_tri'] ?? '' ?></div>
        </div>

        <div class="form-row">
            <div class="form-group <?= isset($errors['suc_chua']) ? 'has-error' : '' ?>">
                <label for="suc_chua">Sức chứa</label>
                <input type="number" id="suc_chua" name="suc_chua" placeholder="40" min="1" value="<?= htmlspecialchars($old['suc_chua'] ?? '') ?>">
                <div class="error-text"><?= $errors['suc_chua'] ?? '' ?></div>
            </div>

            <div class="form-group">
                <label for="loai_phong">Loại phòng</label>
                <select id="loai_phong" name="loai_phong">
                    <?php
                    $cacLoai = ['Lập trình', 'Mạng máy tính', 'CSDL', 'Điện tử', 'AI/ML'];
                    $loaiDaChon = $old['loai_phong'] ?? 'Lập trình';
                    foreach ($cacLoai as $loai):
                        ?>
                        <option value="<?= $loai ?>" <?= $loaiDaChon === $loai ? 'selected' : '' ?>><?= $loai ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="trang_thai">Trạng thái</label>
            <select id="trang_thai" name="trang_thai">
                <option value="available" <?= ($old['trang_thai'] ?? 'available') === 'available' ? 'selected' : '' ?>>Hoạt động</option>
                <option value="maintenance" <?= ($old['trang_thai'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Bảo trì</option>
            </select>
        </div>

        <div class="form-group">
            <label for="mo_ta">Mô tả</label>
            <textarea id="mo_ta" name="mo_ta" placeholder="Mô tả phòng thực hành..."><?= htmlspecialchars($old['mo_ta'] ?? '') ?></textarea>
        </div>

        <div class="modal-footer">
            <a href="index.php" class="btn btn-cancel">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu phòng</button>
        </div>
    </form>
</div>

</body>
</html>