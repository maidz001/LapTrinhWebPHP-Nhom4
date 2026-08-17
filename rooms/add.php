<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin', 'lab_staff']); // chỉ admin / cán bộ lab được thêm phòng

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Tên phòng không được để trống.';
    }
    if ($capacity < 0) {
        $errors[] = 'Sức chứa không hợp lệ.';
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Trạng thái không hợp lệ.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO rooms (name, location, capacity, status, description)
             VALUES (:name, :location, :capacity, :status, :description)"
        );
        $stmt->execute([
            'name'        => $name,
            'location'    => $location,
            'capacity'    => $capacity,
            'status'      => $status,
            'description' => $description,
        ]);

        header('Location: list.php?msg=' . urlencode('Thêm phòng thành công.'));
        exit;
    }
}

$page_title = 'Thêm phòng';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Thêm phòng mới</h1>

<?php foreach ($errors as $error): ?>
    <p class="alert alert-danger"><?php echo htmlspecialchars($error); ?></p>
<?php endforeach; ?>

    <form method="post" class="form">
        <div class="form-group">
            <label>Tên phòng *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Vị trí</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Sức chứa</label>
            <input type="number" name="capacity" min="0" value="<?php echo htmlspecialchars($_POST['capacity'] ?? 0); ?>">
        </div>
        <div class="form-group">
            <label>Trạng thái</label>
            <select name="status">
                <option value="active">Đang hoạt động</option>
                <option value="inactive">Ngừng hoạt động</option>
            </select>
        </div>
        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Lưu</button>
        <a href="list.php" class="btn">Hủy</a>
    </form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>