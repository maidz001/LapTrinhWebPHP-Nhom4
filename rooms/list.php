<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login(); // ai đăng nhập cũng xem được danh sách phòng

$role = current_user()['role'];
$canManage = in_array($role, ['admin', 'lab_staff'], true);

// Lọc theo trạng thái (tuỳ chọn)
$status = $_GET['status'] ?? '';
$sql = "SELECT * FROM rooms";
$params = [];
if ($status === 'active' || $status === 'inactive') {
    $sql .= " WHERE status = :status";
    $params['status'] = $status;
}
$sql .= " ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$page_title = 'Danh sách phòng';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Danh sách phòng</h1>

<?php if (isset($_GET['msg'])): ?>
    <p class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></p>
<?php endif; ?>

    <form method="get" class="filter-form">
        <label>Trạng thái:
            <select name="status" onchange="this.form.submit()">
                <option value="">-- Tất cả --</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Ngừng hoạt động</option>
            </select>
        </label>
    </form>

<?php if ($canManage): ?>
    <p><a href="add.php" class="btn btn-primary">+ Thêm phòng mới</a></p>
<?php endif; ?>

    <table class="data-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Tên phòng</th>
            <th>Vị trí</th>
            <th>Sức chứa</th>
            <th>Trạng thái</th>
            <?php if ($canManage): ?><th>Thao tác</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rooms)): ?>
            <tr><td colspan="6">Chưa có phòng nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?php echo $room['id']; ?></td>
                <td><?php echo htmlspecialchars($room['name']); ?></td>
                <td><?php echo htmlspecialchars($room['location'] ?? ''); ?></td>
                <td><?php echo (int)$room['capacity']; ?></td>
                <td>
                    <?php echo $room['status'] === 'active'
                        ? '<span class="badge badge-success">Hoạt động</span>'
                        : '<span class="badge badge-secondary">Ngừng hoạt động</span>'; ?>
                </td>
                <?php if ($canManage): ?>
                    <td>
                        <a href="edit.php?id=<?php echo $room['id']; ?>">Sửa</a> |
                        <a href="delete.php?id=<?php echo $room['id']; ?>"
                           onclick="return confirm('Xóa phòng này? Hành động không thể hoàn tác.');">Xóa</a>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>