<?php
/**
 * app/Views/equipment/handover.php
 * Biến truyền vào từ EquipmentController::showHandover(): $equipmentList, $rooms
 */
$page_title = 'Bàn giao thiết bị';
$active_menu = 'handover';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="chart-card" style="max-width:640px;">
    <h3>Chuyển thiết bị sang phòng khác</h3>
    <p class="chart-sub">Chọn thiết bị và phòng đích để bàn giao. Vị trí hiện tại sẽ được cập nhật ngay.</p>

    <form method="post" action="/mvc/equipment/handover">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

        <div class="form-group">
            <label>Thiết bị <span class="required">*</span></label>
            <select name="equipment_id" required>
                <option value="">-- Chọn thiết bị --</option>
                <?php foreach ($equipmentList as $e): ?>
                    <option value="<?php echo $e['id']; ?>">
                        <?php echo htmlspecialchars($e['ma_thiet_bi'] . ' - ' . $e['ten_thiet_bi']); ?>
                        (hiện tại: <?php echo $e['room_id'] ? htmlspecialchars($e['ma_phong']) : 'lưu động'; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Chuyển đến phòng</label>
            <select name="room_id">
                <option value="">-- Thiết bị lưu động (không gắn phòng) --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>">
                        <?php echo htmlspecialchars($r['ma_phong'] . ' - ' . $r['ten_phong']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Bàn giao</button>
        <a href="/mvc/equipment" class="btn btn-secondary">Quay lại danh sách thiết bị</a>
    </form>
</div>

<div class="table-card" style="margin-top:24px;">
    <h3 style="margin:0 0 12px;">Vị trí hiện tại của thiết bị</h3>
    <?php if (empty($equipmentList)): ?>
        <div class="empty-state">Chưa có thiết bị nào.</div>
    <?php else: ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>Mã</th>
                <th>Tên thiết bị</th>
                <th>Vị trí hiện tại</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($equipmentList as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['ma_thiet_bi']); ?></td>
                    <td><?php echo htmlspecialchars($e['ten_thiet_bi']); ?></td>
                    <td><?php echo $e['room_id'] ? htmlspecialchars($e['ma_phong'] . ' - ' . $e['ten_phong']) : 'Thiết bị lưu động'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
