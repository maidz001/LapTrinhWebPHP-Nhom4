<?php
/**
 * equipment/form.php
 * ---------------------------------------------------------------------
 * Form thêm mới / sửa thiết bị. Chỉ admin và lab_staff được truy cập.
 * Submit tới equipment/save.php. ?id=X để sửa, không có id để thêm mới.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : null;
$equipment = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $equipment = $stmt->fetch();
    if (!$equipment) {
        flash_set('error', 'Không tìm thấy thiết bị cần sửa.');
        header('Location: /equipment/list.php');
        exit;
    }
}

$old = $_SESSION['equipment_old'] ?? $equipment ?? [];
unset($_SESSION['equipment_old']);
$errors = $_SESSION['equipment_errors'] ?? [];
unset($_SESSION['equipment_errors']);

$types = $pdo->query("SELECT id, ten_loai FROM equipment_types ORDER BY ten_loai")->fetchAll();
$rooms = $pdo->query("SELECT id, ma_phong, ten_phong FROM rooms ORDER BY ma_phong")->fetchAll();

$page_title = $id ? 'Sửa thiết bị' : 'Thêm thiết bị';
$active_menu = 'equipment';
require_once __DIR__ . '/../includes/app_head.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="chart-card" style="max-width:560px;">
    <h3><?php echo $id ? 'Sửa thiết bị' : 'Thêm thiết bị'; ?></h3>

    <form method="post" action="/equipment/save.php">
        <?php echo csrf_field(); ?>
        <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>

        <div class="form-group">
            <label>Mã thiết bị <span class="required">*</span></label>
            <input type="text" name="ma_thiet_bi" maxlength="30" value="<?php echo htmlspecialchars($old['ma_thiet_bi'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Tên thiết bị <span class="required">*</span></label>
            <input type="text" name="ten_thiet_bi" maxlength="150" value="<?php echo htmlspecialchars($old['ten_thiet_bi'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Loại thiết bị <span class="required">*</span></label>
            <select name="type_id">
                <option value="">-- Chọn loại --</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?php echo $t['id']; ?>" <?php echo (($old['type_id'] ?? '') == $t['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['ten_loai']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Phòng đặt thiết bị</label>
            <select name="room_id">
                <option value="">-- Thiết bị lưu động (không gắn phòng) --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo (($old['room_id'] ?? '') == $r['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['ma_phong'] . ' - ' . $r['ten_phong']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="field-hint">Để trống nếu đây là thiết bị lưu động, có thể cho mượn mang đi.</p>
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="co_the_muon" value="1" style="width:auto;display:inline-block;margin-right:6px;"
                          <?php echo !empty($old['co_the_muon']) ? 'checked' : ''; ?>>
                Cho phép mượn qua luồng "Mượn thiết bị"</label>
        </div>

        <div class="form-group">
            <label>Trạng thái <span class="required">*</span></label>
            <select name="trang_thai">
                <?php $st = $old['trang_thai'] ?? 'active'; ?>
                <option value="active" <?php echo $st === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                <option value="broken" <?php echo $st === 'broken' ? 'selected' : ''; ?>>Hỏng</option>
                <option value="maintenance" <?php echo $st === 'maintenance' ? 'selected' : ''; ?>>Đang bảo trì</option>
                <option value="borrowed" <?php echo $st === 'borrowed' ? 'selected' : ''; ?>>Đang được mượn</option>
            </select>
        </div>

        <div class="form-group">
            <label>Ngày mua</label>
            <input type="date" name="ngay_mua" value="<?php echo htmlspecialchars($old['ngay_mua'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="mo_ta" rows="3"><?php echo htmlspecialchars($old['mo_ta'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><?php echo $id ? 'Lưu thay đổi' : 'Thêm thiết bị'; ?></button>
        <a href="/equipment/list.php" class="btn btn-secondary">Huỷ</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>