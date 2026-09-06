<?php
/**
 * equipment/handover.php
 * ---------------------------------------------------------------------
 * Bàn giao thiết bị: chuyển một thiết bị từ phòng hiện tại sang phòng
 * khác (hoặc chuyển thành thiết bị lưu động, không gắn phòng nào).
 * Chỉ admin/lab_staff được dùng.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
        header('Location: /equipment/handover.php');
        exit;
    }

    $equipmentId = $_POST['equipment_id'] ?? '';
    $newRoomId   = trim((string) ($_POST['room_id'] ?? ''));

    if (!ctype_digit((string) $equipmentId)) {
        flash_set('error', 'Vui lòng chọn thiết bị cần bàn giao.');
        header('Location: /equipment/handover.php');
        exit;
    }
    $equipmentId = (int) $equipmentId;

    $stmt = $pdo->prepare(
        "SELECT e.id, e.ten_thiet_bi, e.room_id, r.ma_phong AS ma_phong_cu
         FROM equipment e LEFT JOIN rooms r ON r.id = e.room_id
         WHERE e.id = :id"
    );
    $stmt->execute(['id' => $equipmentId]);
    $equipment = $stmt->fetch();

    if (!$equipment) {
        flash_set('error', 'Không tìm thấy thiết bị.');
        header('Location: /equipment/handover.php');
        exit;
    }

    $newRoomIdInt = null;
    $tenPhongMoi = 'thiết bị lưu động (không gắn phòng)';
    if ($newRoomId !== '') {
        if (!ctype_digit($newRoomId)) {
            flash_set('error', 'Phòng đích không hợp lệ.');
            header('Location: /equipment/handover.php');
            exit;
        }
        $newRoomIdInt = (int) $newRoomId;

        $stmt = $pdo->prepare("SELECT id, ma_phong, ten_phong FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $newRoomIdInt]);
        $room = $stmt->fetch();
        if (!$room) {
            flash_set('error', 'Phòng đích không tồn tại.');
            header('Location: /equipment/handover.php');
            exit;
        }
        $tenPhongMoi = $room['ma_phong'] . ' - ' . $room['ten_phong'];
    }

    if ($newRoomIdInt === $equipment['room_id']) {
        flash_set('error', 'Thiết bị đã ở đúng vị trí này rồi, không cần bàn giao.');
        header('Location: /equipment/handover.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE equipment SET room_id = :room_id WHERE id = :id");
    $stmt->execute(['room_id' => $newRoomIdInt, 'id' => $equipmentId]);

    flash_set(
        'success',
        'Đã chuyển thiết bị "' . $equipment['ten_thiet_bi'] . '" từ '
        . ($equipment['ma_phong_cu'] ?? 'thiết bị lưu động') . ' sang ' . $tenPhongMoi . '.'
    );
    header('Location: /equipment/handover.php');
    exit;
}

$equipmentList = $pdo->query(
    "SELECT e.id, e.ma_thiet_bi, e.ten_thiet_bi, e.room_id, r.ma_phong, r.ten_phong
     FROM equipment e LEFT JOIN rooms r ON r.id = e.room_id
     ORDER BY e.ten_thiet_bi"
)->fetchAll();

$rooms = $pdo->query("SELECT id, ma_phong, ten_phong FROM rooms ORDER BY ma_phong")->fetchAll();

$page_title = 'Bàn giao thiết bị';
$active_menu = 'handover';
require_once __DIR__ . '/../includes/app_head.php';
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

    <form method="post">
        <?php echo csrf_field(); ?>

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
        <a href="/equipment/list.php" class="btn btn-secondary">Quay lại danh sách thiết bị</a>
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

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
