<?php
/**
 * app/Views/rooms/form.php
 * Biến truyền vào từ RoomController::form(): $id, $old, $errors
 */
$page_title = $id ? 'Sửa phòng thực hành' : 'Thêm phòng thực hành';
$active_menu = 'rooms';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="chart-card" style="max-width:560px;">
    <h3><?php echo $id ? 'Sửa phòng thực hành' : 'Thêm phòng thực hành'; ?></h3>

    <form method="post" action="/mvc/rooms/save">
        <?php echo csrf_field(); ?>
        <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>

        <div class="form-group">
            <label>Mã phòng <span class="required">*</span></label>
            <input type="text" name="ma_phong" maxlength="20" value="<?php echo htmlspecialchars($old['ma_phong'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Tên phòng <span class="required">*</span></label>
            <input type="text" name="ten_phong" maxlength="100" value="<?php echo htmlspecialchars($old['ten_phong'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Vị trí <span class="required">*</span></label>
            <input type="text" name="vi_tri" maxlength="150" value="<?php echo htmlspecialchars($old['vi_tri'] ?? ''); ?>" placeholder="VD: Nhà A, Tầng 2">
        </div>

        <div class="form-group">
            <label>Sức chứa <span class="required">*</span></label>
            <input type="number" name="suc_chua" min="1" value="<?php echo htmlspecialchars((string) ($old['suc_chua'] ?? '')); ?>">
        </div>

        <div class="form-group">
            <label>Trạng thái <span class="required">*</span></label>
            <select name="trang_thai">
                <?php $st = $old['trang_thai'] ?? 'available'; ?>
                <option value="available" <?php echo $st === 'available' ? 'selected' : ''; ?>>Sẵn sàng</option>
                <option value="maintenance" <?php echo $st === 'maintenance' ? 'selected' : ''; ?>>Đang bảo trì</option>
                <option value="closed" <?php echo $st === 'closed' ? 'selected' : ''; ?>>Đã đóng</option>
            </select>
        </div>

        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="mo_ta" rows="3"><?php echo htmlspecialchars($old['mo_ta'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><?php echo $id ? 'Lưu thay đổi' : 'Thêm phòng'; ?></button>
        <a href="/mvc/rooms" class="btn btn-secondary">Huỷ</a>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
