<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin', 'lab_staff']); // chỉ cán bộ lab/admin quản lý loại thiết bị

$stmt = $pdo->query("SELECT * FROM equipment_types ORDER BY id ASC");
$types = $stmt->fetchAll();

$page_title = 'Loại thiết bị';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Danh sách loại thiết bị</h1>

<?php if (isset($_GET['msg'])): ?>
    <p class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></p>
<?php endif; ?>

    <p><a href="add.php" class="btn btn-primary">+ Thêm loại thiết bị</a></p>

    <table class="data-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Tên loại</th>
            <th>Mô tả</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($types)): ?>
            <tr><td colspan="4">Chưa có loại thiết bị nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($types as $type): ?>
            <tr>
                <td><?php echo $type['id']; ?></td>
                <td><?php echo htmlspecialchars($type['name']); ?></td>
                <td><?php echo htmlspecialchars($type['description'] ?? ''); ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $type['id']; ?>">Sửa</a> |
                    <a href="delete.php?id=<?php echo $type['id']; ?>"
                       onclick="return confirm('Xóa loại thiết bị này?');">Xóa</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>