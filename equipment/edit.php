<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin', 'lab_staff']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM equipment_types WHERE id = :id");
$stmt->execute(['id' => $id]);
$type = $stmt->fetch();

if (!$type) {
    header('Location: list.php?msg=' . urlencode('Không tìm thấy loại thiết bị.'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Tên loại thiết bị không được để trống.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE equipment_types SET name = :name, description = :description WHERE id = :id");
        $stmt->execute(['name' => $name, 'description' => $description, 'id' => $id]);

        header('Location: list.php?msg=' . urlencode('Cập nhật thành công.'));
        exit;
    }
    $type = array_merge($type, $_POST);
}

$page_title = 'Sửa loại thiết bị';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Sửa loại thiết bị</h1>

<?php foreach ($errors as $error): ?>
    <p class="alert alert-danger"><?php echo htmlspecialchars($error); ?></p>
<?php endforeach; ?>

    <form method="post" class="form">
        <input type="hidden" name="id" value="<?php echo $type['id']; ?>">
        <div class="form-group">
            <label>Tên loại *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($type['name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="description"><?php echo htmlspecialchars($type['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="list.php" class="btn">Hủy</a>
    </form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>