<?php
/**
 * reports/create.php
 * ---------------------------------------------------------------------
 * Form báo hỏng thiết bị. Mọi người dùng đã đăng nhập đều dùng được.
 * Có thể mở kèm ?equipment_id=X để chọn sẵn thiết bị (từ equipment/list.php).
 * Submit tới reports/store.php.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

$equipmentList = $pdo->query(
    "SELECT id, ma_thiet_bi, ten_thiet_bi FROM equipment ORDER BY ten_thiet_bi"
)->fetchAll();

$old = $_SESSION['report_old'] ?? [];
unset($_SESSION['report_old']);
$errors = $_SESSION['report_errors'] ?? [];
unset($_SESSION['report_errors']);

$preselectId = $old['equipment_id'] ?? ($_GET['equipment_id'] ?? '');

$page_title = 'Báo hỏng thiết bị';
$active_menu = 'reports';
require_once __DIR__ . '/../includes/app_head.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="chart-card" style="max-width:560px;">
    <h3>Báo hỏng thiết bị</h3>

    <form method="post" action="/reports/store.php">
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
        <a href="/reports/index.php" class="btn btn-secondary">Huỷ</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>