<?php
/**
 * equipment/list.php
 * ---------------------------------------------------------------------
 * Danh sách thiết bị theo phòng và thiết bị lưu động có thể cho mượn.
 * Mọi người dùng đã đăng nhập xem được và có thể báo hỏng.
 * admin/lab_staff có thêm nút Thêm/Sửa/Xoá.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();
$user = current_user();
$canManage = in_array($user['role'] ?? '', ['admin', 'lab_staff'], true);

$typeFilter = isset($_GET['type_id']) && ctype_digit((string) $_GET['type_id']) ? (int) $_GET['type_id'] : null;
$statusFilter = $_GET['trang_thai'] ?? 'all';
$allowedStatus = ['all', 'active', 'broken', 'maintenance', 'borrowed'];
if (!in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = 'all';
}

$sql = "SELECT e.*, t.ten_loai, r.ma_phong, r.ten_phong
        FROM equipment e
        JOIN equipment_types t ON t.id = e.type_id
        LEFT JOIN rooms r ON r.id = e.room_id
        WHERE 1 = 1";
$params = [];

if ($typeFilter) {
    $sql .= " AND e.type_id = :type_id";
    $params['type_id'] = $typeFilter;
}
if ($statusFilter !== 'all') {
    $sql .= " AND e.trang_thai = :trang_thai";
    $params['trang_thai'] = $statusFilter;
}
$sql .= " ORDER BY e.ma_thiet_bi";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipmentList = $stmt->fetchAll();

$types = $pdo->query("SELECT id, ten_loai FROM equipment_types ORDER BY ten_loai")->fetchAll();

function equipment_status_label(string $status): string
{
    return match ($status) {
        'active' => 'Hoạt động',
        'broken' => 'Hỏng',
        'maintenance' => 'Đang bảo trì',
        'borrowed' => 'Đang được mượn',
        default => $status,
    };
}

function equipment_status_style(string $status): string
{
    return match ($status) {
        'active' => 'background:var(--color-success-bg);color:#166534;',
        'broken' => 'background:var(--color-error-bg);color:#991b1b;',
        'maintenance' => 'background:var(--color-warning-bg);color:#92400e;',
        'borrowed' => 'background:var(--color-info-bg);color:#155e75;',
        default => '',
    };
}

$page_title = 'Thiết bị';
$active_menu = 'equipment';
require_once __DIR__ . '/../includes/app_head.php';
?>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<form method="get" class="filter-form">
    <label style="display:inline-block;margin-right:8px;">Loại</label>
    <select name="type_id" onchange="this.form.submit()">
        <option value="">Tất cả</option>
        <?php foreach ($types as $t): ?>
            <option value="<?php echo $t['id']; ?>" <?php echo $typeFilter === (int) $t['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($t['ten_loai']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label style="display:inline-block;margin:0 8px 0 16px;">Trạng thái</label>
    <select name="trang_thai" onchange="this.form.submit()">
        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
        <option value="broken" <?php echo $statusFilter === 'broken' ? 'selected' : ''; ?>>Hỏng</option>
        <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Đang bảo trì</option>
        <option value="borrowed" <?php echo $statusFilter === 'borrowed' ? 'selected' : ''; ?>>Đang được mượn</option>
    </select>
</form>

<?php if ($canManage): ?>
    <p style="margin:0 0 16px;">
        <a href="/equipment/form.php" class="btn btn-primary">+ Thêm thiết bị</a>
    </p>
<?php endif; ?>

<?php if (empty($equipmentList)): ?>
    <div class="empty-state">Không có thiết bị nào phù hợp với bộ lọc.</div>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Mã</th>
            <th>Tên thiết bị</th>
            <th>Loại</th>
            <th>Vị trí</th>
            <th>Cho mượn?</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($equipmentList as $e): ?>
            <tr>
                <td><?php echo htmlspecialchars($e['ma_thiet_bi']); ?></td>
                <td><?php echo htmlspecialchars($e['ten_thiet_bi']); ?></td>
                <td><?php echo htmlspecialchars($e['ten_loai']); ?></td>
                <td><?php echo $e['room_id'] ? htmlspecialchars($e['ma_phong'] . ' - ' . $e['ten_phong']) : 'Thiết bị lưu động'; ?></td>
                <td><?php echo $e['co_the_muon'] ? 'Có' : 'Không'; ?></td>
                <td>
                    <span style="<?php echo equipment_status_style($e['trang_thai']); ?>padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">
                        <?php echo equipment_status_label($e['trang_thai']); ?>
                    </span>
                </td>
                <td>
                    <a href="/reports/create.php?equipment_id=<?php echo $e['id']; ?>" class="btn btn-secondary">Báo hỏng</a>
                    <?php if ($canManage): ?>
                        <a href="/equipment/form.php?id=<?php echo $e['id']; ?>" class="btn btn-secondary">Sửa</a>
                        <a href="/equipment/delete.php?id=<?php echo $e['id']; ?>&csrf_token=<?php echo urlencode(csrf_token()); ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Bạn có chắc muốn xoá thiết bị này?');">Xoá</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>