<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DanhMuc.php';

$dmModel = new DanhMuc($pdo);

$errors = $_SESSION['dm_errors'] ?? [];
$old    = $_SESSION['dm_old'] ?? [];
unset($_SESSION['dm_errors'], $_SESSION['dm_old']);

$id = (int)($_GET['id'] ?? 0);
$dm = $dmModel->getById($id);

if (!$dm) {
    header('Location: index.php?msg=' . urlencode('Không tìm thấy danh mục'));
    exit;
}

$data = !empty($old) ? $old : $dm;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa danh mục thiết bị</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="modal-box" style="margin:40px auto;">
    <div class="modal-header">
        <h2>Sửa danh mục thiết bị</h2>
        <a href="index.php" class="modal-close">&times;</a>
    </div>

    <form method="POST" action="xuly.php" novalidate>
        <input type="hidden" name="mode" value="sua">
        <input type="hidden" name="id" value="<?= (int)$dm['id'] ?>">

        <div class="form-group <?= isset($errors['ma_danh_muc']) ? 'has-error' : '' ?>">
            <label for="ma_danh_muc">Mã danh mục</label>
            <input type="text" id="ma_danh_muc" name="ma_danh_muc" value="<?= htmlspecialchars($data['ma_danh_muc'] ?? '') ?>">
            <div class="error-text"><?= $errors['ma_danh_muc'] ?? '' ?></div>
        </div>

        <div class="form-group <?= isset($errors['ten_loai']) ? 'has-error' : '' ?>">
            <label for="ten_loai">Tên danh mục</label>
            <input type="text" id="ten_loai" name="ten_loai" value="<?= htmlspecialchars($data['ten_loai']) ?>">
            <div class="error-text"><?= $errors['ten_loai'] ?? '' ?></div>
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