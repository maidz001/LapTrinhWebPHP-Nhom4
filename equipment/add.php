<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin', 'lab_staff']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Tên loại thiết bị không được để trống.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO equipment_types (name, description) VALUES (:name, :description)");
        $stmt->execute(['name' => $name, 'description' => $description]);

        header('Location: list.php?msg=' . urlencode('Thêm loại thiết bị thành công.'));
        exit;
    }
}

$page_title = 'Thêm loại thiết bị';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Thêm loại thiết bị</h1>

<?php foreach ($errors as $error): ?>
    <p class="alert alert-danger"><?php echo htmlspecialchars($error); ?></p>
<?php endforeach; ?>

    <form method="post" class="form">
        <div class="form-group">
            <label>Tên loại *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Lưu</button>
        <a href="list.php" class="btn">Hủy</a>
    </form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>