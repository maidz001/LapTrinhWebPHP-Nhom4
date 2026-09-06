<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThietBi.php';

$tbModel = new ThietBi($pdo);

$search = trim($_GET['search'] ?? '');
$typeId = (int)($_GET['type_id'] ?? 0);

$danhSach = $tbModel->getAll($search, $typeId);
$cacLoai  = $tbModel->danhSachLoai();
$tongThietBi = $tbModel->demTong();

$nhanTrangThai = [
    'active'      => ['label' => 'Hoạt động', 'class' => 'badge-green'],
    'maintenance' => ['label' => 'Bảo trì',   'class' => 'badge-yellow'],
    'broken'      => ['label' => 'Hỏng',      'class' => 'badge-red'],
    'borrowed'    => ['label' => 'Đang mượn', 'class' => 'badge-blue'],
];

$thongBao = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thiết bị</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div style="max-width:1200px; margin:0 auto; padding:32px 24px;">

    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px;">
        <div>
            <h1>Thiết bị</h1>
            <p style="color:#64748b; margin:0;"><?= $tongThietBi ?> thiết bị</p>
        </div>
        <a href="add.php" class="btn-open-modal">+ Thêm thiết bị</a>
    </div>

    <?php if ($thongBao): ?>
        <div class="toast show" style="position:static; display:block; margin-bottom:16px; background:#16a34a;">
            <?= htmlspecialchars($thongBao) ?>
        </div>
    <?php endif; ?>

    <div class="table-card">

        <form method="GET" style="margin-bottom:16px;">
            <input type="hidden" name="type_id" value="<?= $typeId ?>">
            <input type="text" name="search" class="search-input" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>">
        </form>

        <div class="filter-tabs">
            <a href="?search=<?= urlencode($search) ?>&type_id=0" class="tab <?= $typeId === 0 ? 'active' : '' ?>">Tất cả</a>
            <?php foreach ($cacLoai as $loai): ?>
                <a href="?search=<?= urlencode($search) ?>&type_id=<?= $loai['id'] ?>"
                   class="tab <?= $typeId === (int)$loai['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($loai['ten_loai']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <table class="data-table">
            <thead>
            <tr>
                <th>Mã TB</th>
                <th>Tên thiết bị</th>
                <th>Danh mục</th>
                <th>Phòng</th>
                <th>SL</th>
                <th>Giá trị</th>
                <th>Ngày mua</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($danhSach as $tb): ?>
                <?php $tt = $nhanTrangThai[$tb['trang_thai']] ?? ['label' => $tb['trang_thai'], 'class' => 'badge-gray']; ?>
                <tr>
                    <td class="text-muted"><?= htmlspecialchars($tb['ma_thiet_bi']) ?></td>
                    <td>
                        <b><?= htmlspecialchars($tb['ten_thiet_bi']) ?></b>
                        <?php if (!empty($tb['mo_ta'])): ?>
                            <div class="text-muted small"><?= htmlspecialchars($tb['mo_ta']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($tb['ten_loai']) ?></td>
                    <td><?= htmlspecialchars($tb['ma_phong'] ?? '—') ?></td>
                    <td><b><?= (int)$tb['so_luong'] ?></b></td>
                    <td><?= $tb['gia_tri'] !== null ? number_format($tb['gia_tri'], 0, ',', '.') . 'đ' : '—' ?></td>
                    <td><?= $tb['ngay_mua'] ?? '—' ?></td>
                    <td><span class="badge <?= $tt['class'] ?>"><?= $tt['label'] ?></span></td>
                    <td>
                        <div class="table-actions">
                            <a href="edit.php?id=<?= $tb['id'] ?>" class="icon-btn icon-btn-blue" title="Sửa">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                            </a>
                            <a href="delete.php?id=<?= $tb['id'] ?>"
                               class="icon-btn icon-btn-red btn-xoa-confirm"
                               data-ten="<?= htmlspecialchars($tb['ten_thiet_bi']) ?>"
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
                <tr><td colspan="9" class="text-muted" style="text-align:center; padding:24px;">Không có thiết bị nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

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