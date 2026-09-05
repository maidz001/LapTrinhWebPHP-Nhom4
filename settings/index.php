<?php
/**
 * settings/index.php
 * ---------------------------------------------------------------------
 * Cài đặt cá nhân: cập nhật họ tên/số điện thoại và đổi mật khẩu.
 * Email không cho sửa trực tiếp vì đó là định danh đăng nhập.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();
$user = current_user();

$stmt = $pdo->prepare("SELECT id, ho_ten, email, so_dien_thoai, mat_khau FROM users WHERE id = :id");
$stmt->execute(['id' => $user['id']]);
$dbUser = $stmt->fetch();

if (!$dbUser) {
    header('Location: /auth/logout.php?csrf_token=' . urlencode(csrf_token()));
    exit;
}

$infoErrors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
    header('Location: /settings/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_info') {
    $hoTen = trim((string) ($_POST['ho_ten'] ?? ''));
    $soDienThoai = trim((string) ($_POST['so_dien_thoai'] ?? ''));

    if ($hoTen === '' || mb_strlen($hoTen) > 100) {
        $infoErrors[] = 'Họ tên không được để trống và tối đa 100 ký tự.';
    }
    if ($soDienThoai !== '' && !preg_match('/^[0-9+\s]{8,15}$/', $soDienThoai)) {
        $infoErrors[] = 'Số điện thoại không hợp lệ.';
    }

    if (!$infoErrors) {
        $upd = $pdo->prepare("UPDATE users SET ho_ten = :ho_ten, so_dien_thoai = :sdt WHERE id = :id");
        $upd->execute([
            'ho_ten' => $hoTen,
            'sdt' => $soDienThoai !== '' ? $soDienThoai : null,
            'id' => $user['id'],
        ]);
        $_SESSION['user']['full_name'] = $hoTen;
        flash_set('success', 'Đã cập nhật thông tin liên hệ.');
        header('Location: /settings/index.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = (string) ($_POST['mat_khau_hien_tai'] ?? '');
    $new = (string) ($_POST['mat_khau_moi'] ?? '');
    $confirm = (string) ($_POST['xac_nhan_mat_khau'] ?? '');

    if (!password_verify($current, $dbUser['mat_khau'])) {
        $passwordErrors[] = 'Mật khẩu hiện tại không đúng.';
    }
    if (mb_strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
        $passwordErrors[] = 'Mật khẩu mới phải có ít nhất 8 ký tự, gồm cả chữ và số.';
    }
    if ($new !== $confirm) {
        $passwordErrors[] = 'Xác nhận mật khẩu mới không khớp.';
    }

    if (!$passwordErrors) {
        $upd = $pdo->prepare("UPDATE users SET mat_khau = :hash WHERE id = :id");
        $upd->execute([
            'hash' => password_hash($new, PASSWORD_DEFAULT),
            'id' => $user['id'],
        ]);
        csrf_regenerate();
        flash_set('success', 'Đã đổi mật khẩu thành công.');
        header('Location: /settings/index.php');
        exit;
    }
}

$page_title = 'Cài đặt';
$active_menu = 'settings';
require_once __DIR__ . '/../includes/app_head.php';
?>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="chart-card" style="max-width:560px;margin-bottom:20px;">
    <h3>Thông tin liên hệ</h3>
    <?php if ($infoErrors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($infoErrors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_info">

        <div class="form-group">
            <label>Họ tên <span class="required">*</span></label>
            <input type="text" name="ho_ten" value="<?php echo htmlspecialchars($dbUser['ho_ten']); ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="text" value="<?php echo htmlspecialchars($dbUser['email']); ?>" disabled>
            <p class="field-hint">Email đăng nhập không thể tự thay đổi. Liên hệ quản trị viên nếu cần.</p>
        </div>

        <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text" name="so_dien_thoai" value="<?php echo htmlspecialchars($dbUser['so_dien_thoai'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Lưu thông tin</button>
    </form>
</div>

<div class="chart-card" style="max-width:560px;">
    <h3>Đổi mật khẩu</h3>
    <?php if ($passwordErrors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($passwordErrors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
            <label>Mật khẩu hiện tại <span class="required">*</span></label>
            <input type="password" name="mat_khau_hien_tai">
        </div>

        <div class="form-group">
            <label>Mật khẩu mới <span class="required">*</span></label>
            <input type="password" name="mat_khau_moi">
            <p class="field-hint">Tối thiểu 8 ký tự, gồm cả chữ và số.</p>
        </div>

        <div class="form-group">
            <label>Xác nhận mật khẩu mới <span class="required">*</span></label>
            <input type="password" name="xac_nhan_mat_khau">
        </div>

        <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
