<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

$errors = $_SESSION['dm_errors'] ?? [];
$old    = $_SESSION['dm_old'] ?? [];
unset($_SESSION['dm_errors'], $_SESSION['dm_old']);

// Gợi ý mã danh mục kế tiếp
$soLuong = (int) $pdo->query("SELECT COUNT(*) FROM equipment_types")->fetchColumn();
$maGoiY = 'DM' . str_pad($soLuong + 1, 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm danh mục thiết bị</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="modal-box" style="margin:40px auto;">
    <div class="modal-header">
        <h2>Thêm danh mục thiết bị</h2>
        <a href="index.php" class="modal-close">&times;</a>
    </div>

    <form method="POST" action="xuly.php" novalidate>
        <input type="hidden" name="mode" value="them">

        <div class="form-group <?= isset($errors['ma_danh_muc']) ? 'has-error' : '' ?>">
            <label for="ma_danh_muc">Mã danh mục</label>
            <input type="text" id="ma_danh_muc" name="ma_danh_muc" placeholder="<?= $maGoiY ?>" value="<?= htmlspecialchars($old['ma_danh_muc'] ?? '') ?>">
            <div class="error-text"><?= $errors['ma_danh_muc'] ?? '' ?></div>
        </div>

        <div class="form-group <?= isset($errors['ten_loai']) ? 'has-error' : '' ?>">
            <label for="ten_loai">Tên danh mục</label>
            <input type="text" id="ten_loai" name="ten_loai" placeholder="Tên danh mục..." value="<?= htmlspecialchars($old['ten_loai'] ?? '') ?>">
            <div class="error-text"><?= $errors['ten_loai'] ?? '' ?></div>
        </div>

        <div class="form-group">
            <label for="mo_ta">Ghi chú</label>
            <textarea id="mo_ta" name="mo_ta" placeholder="Mô tả ngắn về danh mục..."><?= htmlspecialchars($old['mo_ta'] ?? '') ?></textarea>
        </div>

        <div class="modal-footer">
            <a href="index.php" class="btn btn-cancel">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu danh mục</button>
        </div>
    </form>
</div>

</body>
</html>