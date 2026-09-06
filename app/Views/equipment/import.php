<?php
/**
 * app/Views/equipment/import.php
 * Biến truyền vào từ EquipmentController::showImport()/import(): $ketQua
 */
$page_title = 'Thêm thiết bị từ file';
$active_menu = 'equipment';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<div class="chart-card" style="max-width:680px;">
    <h3>Thêm thiết bị từ file CSV</h3>
    <p class="chart-sub">
        File CSV cần có các cột theo đúng thứ tự:
        <code>ma_thiet_bi, ten_thiet_bi, ten_loai, ma_phong, co_the_muon, trang_thai, ngay_mua, mo_ta</code>.
        Dòng tiêu đề (nếu có) sẽ tự động được bỏ qua.
        Cột <code>ten_loai</code> phải trùng với một danh mục thiết bị đã có sẵn.
        Cột <code>ma_phong</code> để trống nếu là thiết bị lưu động.
    </p>

    <?php if ($ketQua !== null): ?>
        <div class="alert <?php echo $ketQua['them'] > 0 ? 'alert-success' : 'alert-error'; ?>">
            <p style="margin:0 0 6px;">Đã thêm thành công <strong><?php echo $ketQua['them']; ?></strong> thiết bị.</p>
            <?php if (!empty($ketQua['loi'])): ?>
                <p style="margin:8px 0 4px;">Các dòng sau bị bỏ qua:</p>
                <ul>
                    <?php foreach ($ketQua['loi'] as $l): ?>
                        <li><?php echo htmlspecialchars($l); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/mvc/equipment/import" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Chọn file CSV <span class="required">*</span></label>
            <input type="file" name="file" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn btn-primary">Tải lên &amp; thêm thiết bị</button>
        <a href="/mvc/equipment" class="btn btn-secondary">Quay lại danh sách</a>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
