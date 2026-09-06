<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DanhMuc.php';

$dmModel = new DanhMuc($pdo);
$danhSach = $dmModel->getAll();
$thongKe  = $dmModel->thongKe();

$thongBao = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh mục thiết bị</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div style="max-width:1200px; margin:0 auto; padding:32px 24px;">

    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px;">
        <div>
            <h1>Danh mục thiết bị</h1>
            <p style="color:#64748b; margin:0;">Phân loại và quản lý danh mục</p>
        </div>
        <a href="add.php" class="btn-open-modal">+ Thêm danh mục</a>
    </div>

    <?php if ($thongBao): ?>
        <div class="toast show" style="position:static; display:block; margin-bottom:16px; background:#16a34a;">
            <?= htmlspecialchars($thongBao) ?>
        </div>
    <?php endif; ?>

    <div class="stat-cards" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
            <span class="stat-icon stat-icon-blue"><?= $thongKe['tong_danh_muc'] ?></span>
            <span class="stat-label">Tổng danh mục</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon stat-icon-green"><?= $thongKe['tong_thiet_bi'] ?></span>
            <span class="stat-label">Tổng thiết bị</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon stat-icon-yellow"><?= $thongKe['can_kiem_tra'] ?></span>
            <span class="stat-label">Cần kiểm tra</span>
        </div>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
            <tr>
                <th>Mã DM</th>
                <th>Tên danh mục</th>
                <th>Tổng SL</th>
                <th>Đang dùng</th>
                <th>Cần sửa</th>
                <th>Ghi chú</th>
                <th>Thao tác</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($danhSach as $dm): ?>
                <tr>
                    <td class="text-muted"><?= htmlspecialchars($dm['ma_danh_muc'] ?? '—') ?></td>
                    <td><b><?= htmlspecialchars($dm['ten_loai']) ?></b></td>
                    <td><?= (int)$dm['tong_sl'] ?></td>
                    <td><?= (int)$dm['dang_dung'] ?></td>
                    <td>
                        <?php if ((int)$dm['can_sua'] > 0): ?>
                            <span style="color:#d97706; font-weight:600;"><?= (int)$dm['can_sua'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= $dm['mo_ta'] ? htmlspecialchars($dm['mo_ta']) : '—' ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="edit.php?id=<?= $dm['id'] ?>" class="icon-btn icon-btn-blue" title="Sửa">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                            </a>
                            <a href="delete.php?id=<?= $dm['id'] ?>"
                               class="icon-btn icon-btn-red btn-xoa-confirm"
                               data-ten="<?= htmlspecialchars($dm['ten_loai']) ?>"
                               title="Xóa">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($danhSach)): ?>
                <tr><td colspan="7" class="text-muted" style="text-align:center; padding:24px;">Chưa có danh mục nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Modal xác nhận xóa (dùng chung pattern) -->
<div class="modal-overlay" id="modalXacNhanXoa">
    <div class="modal-box" style="max-width:420px; text-align:center;">
        <div class="confirm-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v4"/><path d="M12 17h.01"/>
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            </svg>
        </div>
        <h2 style="margin:16px 0 8px; font-size:18px;">Xác nhận xóa</h2>
        <p id="xoaText" style="color:#64748b; font-size:14px; margin:0 0 22px;"></p>
        <div class="modal-footer" style="justify-content:center;">
            <button type="button" class="btn btn-cancel" id="btnHuyXoa">Hủy</button>
            <a href="#" id="btnXacNhanXoa" class="btn" style="background:#dc2626; color:#fff;">Xóa</a>
        </div>
    </div>
</div>

<script>
    const modalXoa = document.getElementById('modalXacNhanXoa');
    const xoaText = document.getElementById('xoaText');
    const btnHuyXoa = document.getElementById('btnHuyXoa');
    const btnXacNhanXoa = document.getElementById('btnXacNhanXoa');

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
    modalXoa.addEventListener('click', (e) => { if (e.target === modalXoa) modalXoa.classList.remove('active'); });
</script>

</body>
</html>