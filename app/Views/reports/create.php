<?php
/**
 * app/Views/reports/create.php
 * View thuần hiển thị — không chứa SQL. Biến truyền vào từ
 * ReportController::create(): $equipmentList, $old, $errors, $preselectId
 */
$page_title = 'Báo hỏng thiết bị';
$active_menu = 'reports';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="chart-card" style="max-width:560px;">
    <h3>Báo hỏng thiết bị</h3>

    <form method="post" action="/mvc/reports/store">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label>Thiết bị <span class="required">*</span></label>
            <select name="equipment_id">
                <option value="">-- Chọn thiết bị --</option>
                <?php foreach ($equipmentList as $e): ?>
                    <option value="<?php echo $e['id']; ?>" <?php echo ((string) $preselectId === (string) $e['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($e['ma_thiet_bi'] . ' - ' . $e['ten_thiet_bi']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Mô tả sự cố <span class="required">*</span></label>
            <textarea name="mo_ta_su_co" rows="4"><?php echo htmlspecialchars($old['mo_ta_su_co'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Mức độ <span class="required">*</span></label>
            <select name="muc_do">
                <?php $lv = $old['muc_do'] ?? 'medium'; ?>
                <option value="low" <?php echo $lv === 'low' ? 'selected' : ''; ?>>Thấp</option>
                <option value="medium" <?php echo $lv === 'medium' ? 'selected' : ''; ?>>Trung bình</option>
                <option value="high" <?php echo $lv === 'high' ? 'selected' : ''; ?>>Cao</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Gửi báo cáo</button>
        <a href="/mvc/reports" class="btn btn-secondary">Huỷ</a>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
