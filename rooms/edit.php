<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Phong.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$phongModel = new Phong($pdo);

$id = (int)($_GET['id'] ?? 0);
$phong = $phongModel->getById($id);

if (!$phong) {
    header('Location: index.php?msg=' . urlencode('Không tìm thấy phòng'));
    exit;
}

// Nếu có dữ liệu cũ do lỗi submit thì dùng, không thì dùng dữ liệu từ CSDL
$data = !empty($old) ? $old : $phong;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa phòng thực hành</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="modal-box" style="margin:40px auto;">
    <div class="modal-header">
        <h2>Sửa phòng thực hành</h2>
        <a href="index.php" class="modal-close">&times;</a>
    </div>

    <form method="POST" action="xuly.php" novalidate>
        <input type="hidden" name="mode" value="sua">
        <input type="hidden" name="id" value="<?= (int)$phong['id'] ?>">

        <div class="form-group <?= isset($errors['ma_phong']) ? 'has-error' : '' ?>">
            <label for="ma_phong">Mã phòng</label>
            <input type="text" id="ma_phong" name="ma_phong" value="<?= htmlspecialchars($data['ma_phong']) ?>">
            <div class="error-text"><?= $errors['ma_phong'] ?? '' ?></div>
        </div>

        <div class="form-group <?= isset($errors['ten_phong']) ? 'has-error' : '' ?>">
            <label for="ten_phong">Tên phòng</label>
            <input type="text" id="ten_phong" name="ten_phong" value="<?= htmlspecialchars($data['ten_phong']) ?>">
            <div class="error-text"><?= $errors['ten_phong'] ?? '' ?></div>
        </div>

        <div class="form-group <?= isset($errors['vi_tri']) ? 'has-error' : '' ?>">
            <label for="vi_tri">Vị trí</label>
            <input type="text" id="vi_tri" name="vi_tri" value="<?= htmlspecialchars($data['vi_tri']) ?>">
            <div class="error-text"><?= $errors['vi_tri'] ?? '' ?></div>
        </div>

        <div class="form-row">
            <div class="form-group <?= isset($errors['suc_chua']) ? 'has-error' : '' ?>">
                <label for="suc_chua">Sức chứa</label>
                <input type="number" id="suc_chua" name="suc_chua" min="1" value="<?= htmlspecialchars($data['suc_chua']) ?>">
                <div class="error-text"><?= $errors['suc_chua'] ?? '' ?></div>
            </div>

            <div class="form-group">
                <label for="trang_thai">Trạng thái</label>
                <select id="trang_thai" name="trang_thai">
                    <option value="available" <?= $data['trang_thai'] === 'available' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="maintenance" <?= $data['trang_thai'] === 'maintenance' ? 'selected' : '' ?>>Bảo trì</option>
                    <option value="closed" <?= $data['trang_thai'] === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="mo_ta">Mô tả</label>
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