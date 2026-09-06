<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Phong.php';

$phongModel = new Phong($pdo);

$id = (int)($_GET['id'] ?? 0);
$phong = $phongModel->getById($id);

if (!$phong) {
    header('Location: index.php?msg=' . urlencode('Không tìm thấy phòng'));
    exit;
}

$nhanTrangThai = [
    'available'   => 'Hoạt động',
    'maintenance' => 'Bảo trì',
    'closed'      => 'Đã đóng',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết phòng - <?= htmlspecialchars($phong['ten_phong']) ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="modal-box" style="margin:40px auto;">
    <div class="modal-header">
        <h2><?= htmlspecialchars($phong['ten_phong']) ?></h2>
        <a href="index.php" class="modal-close">&times;</a>
    </div>

    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0; color:#64748b;">Mã phòng</td>
            <td><b><?= htmlspecialchars($phong['ma_phong']) ?></b></td>
        </tr>
        <tr>
            <td style="padding:8px 0; color:#64748b;">Vị trí</td>
            <td><?= htmlspecialchars($phong['vi_tri']) ?></td>
        </tr>
        <tr>
            <td style="padding:8px 0; color:#64748b;">Sức chứa</td>
            <td><?= (int)$phong['suc_chua'] ?> chỗ ngồi</td>
        </tr>
        <tr>
            <td style="padding:8px 0; color:#64748b;">Trạng thái</td>
            <td><?= $nhanTrangThai[$phong['trang_thai']] ?? $phong['trang_thai'] ?></td>
        </tr>
        <tr>
            <td style="padding:8px 0; color:#64748b;">Mô tả</td>
            <td><?= nl2br(htmlspecialchars($phong['mo_ta'] ?? '(không có)')) ?></td>
        </tr>
        <tr>
            <td style="padding:8px 0; color:#64748b;">Ngày tạo</td>
            <td><?= htmlspecialchars($phong['created_at']) ?></td>
        </tr>
    </table>

    <div class="modal-footer">
        <a href="index.php" class="btn btn-cancel">Quay lại</a>
        <a href="edit.php?id=<?= $phong['id'] ?>" class="btn btn-primary">Sửa phòng</a>
    </div>
</div>

</body>
</html>