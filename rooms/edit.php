<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin', 'lab_staff']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id");
$stmt->execute(['id' => $id]);
$room = $stmt->fetch();

if (!$room) {
    header('Location: list.php?msg=' . urlencode('Không tìm thấy phòng.'));
    exit;
}

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
            "UPDATE rooms SET name = :name, location = :location, capacity = :capacity,
             status = :status, description = :description WHERE id = :id"
        );
        $stmt->execute([
            'name'        => $name,
            'location'    => $location,
            'capacity'    => $capacity,
            'status'      => $status,
            'description' => $description,
            'id'          => $id,
        ]);

        header('Location: list.php?msg=' . urlencode('Cập nhật phòng thành công.'));
        exit;
    }
    // giữ lại dữ liệu vừa nhập để hiển thị lại nếu có lỗi
    $room = array_merge($room, $_POST);
}

$page_title = 'Sửa phòng';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Sửa thông tin phòng</h1>

<?php foreach ($errors as $error): ?>
    <p class="alert alert-danger"><?php echo htmlspecialchars($error); ?></p>
<?php endforeach; ?>

    <form method="post" class="form">
        <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
        <div class="form-group">
            <label>Tên phòng *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($room['name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Vị trí</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($room['location'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Sức chứa</label>
            <input type="number" name="capacity" min="0" value="<?php echo (int)$room['capacity']; ?>">
        </div>
        <div class="form-group">
            <label>Trạng thái</label>
            <select name="status">
                <option value="active" <?php echo $room['status'] === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                <option value="inactive" <?php echo $room['status'] === 'inactive' ? 'selected' : ''; ?>>Ngừng hoạt động</option>
            </select>
        </div>
        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="description"><?php echo htmlspecialchars($room['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="list.php" class="btn">Hủy</a>
    </form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>