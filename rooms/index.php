<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Phong.php';

$phongModel = new Phong($pdo);

$search    = trim($_GET['search'] ?? '');
$trangThai = trim($_GET['trang_thai'] ?? '');

$danhSachPhong = $phongModel->getAll($search, $trangThai);
$thongKe       = $phongModel->thongKe();

$nhanTrangThai = [
        'available'   => ['label' => 'Hoạt động', 'class' => 'badge-green'],
        'maintenance' => ['label' => 'Bảo trì',   'class' => 'badge-yellow'],
        'closed'      => ['label' => 'Đã đóng',   'class' => 'badge-gray'],
];

$thongBao = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phòng thực hành</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div style="max-width:1200px; margin:0 auto; padding:32px 24px;">

    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px;">
        <div>
            <h1>Phòng thực hành</h1>
            <p style="color:#64748b; margin:0;">
                <?= $thongKe['tong_phong'] ?> phòng · <?= $thongKe['hoat_dong'] ?> đang hoạt động
            </p>
        </div>
        <a href="add.php" class="btn-open-modal">+ Thêm phòng</a>
    </div>

    <?php if ($thongBao): ?>
        <div class="toast show" style="position:static; display:block; margin-bottom:16px; background:#16a34a;">
            <?= htmlspecialchars($thongBao) ?>
        </div>
    <?php endif; ?>

    <div class="stat-cards">
        <div class="stat-card">
            <span class="stat-icon stat-icon-blue"><?= $thongKe['tong_phong'] ?></span>
            <span class="stat-label">Tổng phòng</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon stat-icon-green"><?= $thongKe['hoat_dong'] ?></span>
            <span class="stat-label">Hoạt động</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon stat-icon-yellow"><?= $thongKe['bao_tri'] ?></span>
            <span class="stat-label">Bảo trì</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon stat-icon-gray"><?= $thongKe['tong_suc_chua'] ?></span>
            <span class="stat-label">Tổng sức chứa</span>
        </div>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>">
        <select name="trang_thai" onchange="this.form.submit()">
            <option value="">Tất cả</option>
            <option value="available" <?= $trangThai === 'available' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="maintenance" <?= $trangThai === 'maintenance' ? 'selected' : '' ?>>Bảo trì</option>
            <option value="closed" <?= $trangThai === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
        </select>
        <button type="submit" class="btn btn-cancel">Lọc</button>
    </form>

    <div class="room-grid">
        <?php foreach ($danhSachPhong as $p): ?>
            <?php $tt = $nhanTrangThai[$p['trang_thai']] ?? ['label' => $p['trang_thai'], 'class' => 'badge-gray']; ?>

            <div class="room-card">
                <div class="room-card-header">
                    <div>
                        <h3><?= htmlspecialchars($p['ten_phong']) ?></h3>
                        <small><?= htmlspecialchars($p['ma_phong']) ?> · <?= htmlspecialchars($p['vi_tri']) ?></small>
                    </div>
                    <span class="badge <?= $tt['class'] ?>"><?= $tt['label'] ?></span>
                </div>
                <div class="room-card-body">
                    <p><?= htmlspecialchars($p['mo_ta'] ?? '') ?></p>
                    <div class="room-stats">
                        <div><b class="blue"><?= (int)$p['suc_chua'] ?></b><span>Chỗ ngồi</span></div>
                    </div>
                    <div class="room-actions">
                        <a href="list.php?id=<?= $p['id'] ?>" class="btn btn-outline-blue">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            Chi tiết
                        </a>
                        <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-outline-gray">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                            </svg>
                            Sửa
                        </a>
                        <a href="delete.php?id=<?= $p['id'] ?>"
                           class="btn btn-outline-red btn-xoa-confirm"
                           data-ten="<?= htmlspecialchars($p['ten_phong']) ?>">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                            Xóa
                        </a>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>

        <?php if (empty($danhSachPhong)): ?>
            <p style="color:#64748b;">Không tìm thấy phòng nào.</p>
        <?php endif; ?>
    </div>
</div>
<div class="modal-overlay" id="modalXacNhanXoa">
    <div class="modal-box" style="max-width:420px; text-align:center;">
        <div class="confirm-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v4"/><path d="M12 17h.01"/>
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            </svg>
        </div>
        <h2 style="margin:16px 0 8px; font-size:18px;">Xác nhận xóa</h2>
        <p id="xoaText" style="color:#64748b; font-size:14px; margin:0 0 22px;">
            Bạn có chắc chắn muốn xóa mục này? Hành động này không thể hoàn tác.
        </p>
        <div class="modal-footer" style="justify-content:center;">
            <button type="button" class="btn btn-cancel" id="btnHuyXoa">Hủy</button>
            <a href="#" id="btnXacNhanXoa" class="btn" style="background:#dc2626; color:#fff;">Xóa</a>
        </div>
    </div>
</div>

<script>
    const modalXoa      = document.getElementById('modalXacNhanXoa');
    const xoaText        = document.getElementById('xoaText');
    const btnHuyXoa      = document.getElementById('btnHuyXoa');
    const btnXacNhanXoa  = document.getElementById('btnXacNhanXoa');

    document.querySelectorAll('.btn-xoa-confirm').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const ten = this.dataset.ten || 'mục này';
            xoaText.textContent = `Bạn có chắc chắn muốn xóa "${ten}"? Hành động này không thể hoàn tác.`;
            btnXacNhanXoa.href = this.getAttribute('href');
            modalXoa.classList.add('active');
        });
    });

    btnHuyXoa.addEventListener('click', () => modalXoa.classList.remove('active'));
    modalXoa.addEventListener('click', (e) => {
        if (e.target === modalXoa) modalXoa.classList.remove('active');
    });
</script>
</body>
</html>