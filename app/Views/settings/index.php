<?php
/**
 * app/Views/settings/index.php
 * View thuần hiển thị — không chứa SQL. Biến truyền vào từ
 * SettingsController::index(): $dbUser, $infoErrors, $passwordErrors,
 * $flashSuccess, $flashError
 */
$page_title = 'Cài đặt';
$active_menu = 'settings';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
<?php endif; ?>

<div class="chart-card" style="max-width:560px;margin-bottom:20px;">
    <h3>Thông tin liên hệ</h3>
    <?php if ($infoErrors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($infoErrors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" action="/mvc/settings/update-info">
        <?php echo csrf_field(); ?>

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
    <form method="post" action="/mvc/settings/change-password">
        <?php echo csrf_field(); ?>

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

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
