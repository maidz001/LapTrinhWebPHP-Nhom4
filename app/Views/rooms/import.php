<?php
/**
 * app/Views/rooms/import.php
 * Biến truyền vào từ RoomController::showImport()/import(): $ketQua
 */
$page_title = 'Thêm phòng từ file';
$active_menu = 'rooms';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<div class="chart-card" style="max-width:640px;">
    <h3>Thêm phòng thực hành từ file CSV</h3>
    <p class="chart-sub">
        File CSV cần có các cột theo đúng thứ tự:
        <code>ma_phong, ten_phong, vi_tri, suc_chua, trang_thai, mo_ta</code>.
        Dòng tiêu đề (nếu có) sẽ tự động được bỏ qua. Cột <code>trang_thai</code>
        chỉ nhận <code>available</code>, <code>maintenance</code> hoặc <code>closed</code>
        (bỏ trống sẽ mặc định là <code>available</code>).
    </p>

    <?php if ($ketQua !== null): ?>
        <div class="alert <?php echo $ketQua['them'] > 0 ? 'alert-success' : 'alert-error'; ?>">
            <p style="margin:0 0 6px;">Đã thêm thành công <strong><?php echo $ketQua['them']; ?></strong> phòng.</p>
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

    <form method="post" action="/mvc/rooms/import" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Chọn file CSV <span class="required">*</span></label>
            <input type="file" name="file" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn btn-primary">Tải lên &amp; thêm phòng</button>
        <a href="/mvc/rooms" class="btn btn-secondary">Quay lại danh sách</a>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
