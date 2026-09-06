<?php
/**
 * app/Views/auth/register.php
 * View thuần hiển thị — không chứa logic nghiệp vụ hay SQL.
 * Biến truyền vào từ AuthController::showRegister()/register(): $errors, $old
 */
$page_title = 'Đăng ký tài khoản';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Quản lý Phòng &amp; Thiết bị</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
<main class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="auth-logo" aria-hidden="true">🏫</span>
            <h1>Quản lý Phòng thực hành &amp; Thiết bị</h1>
        </div>
        <h2 class="auth-title">Đăng ký tài khoản</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <?php if (is_string($err)): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/mvc/auth/register" class="auth-form" id="registerForm" novalidate>
            <?php echo csrf_field(); ?>

            <!-- Honeypot chống bot: ẩn hoàn toàn khỏi người dùng thật bằng CSS -->
            <div class="hp-field" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="ho_ten">Họ tên <span class="required">*</span></label>
                <input
                    type="text" id="ho_ten" name="ho_ten" required
                    minlength="2" maxlength="100" autocomplete="name"
                    value="<?php echo htmlspecialchars($old['ho_ten']); ?>"
                    class="<?php echo isset($errors['ho_ten']) ? 'input-error' : ''; ?>"
                >
                <?php if (isset($errors['ho_ten'])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors['ho_ten']); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input
                    type="email" id="email" name="email" required
                    maxlength="150" autocomplete="email"
                    value="<?php echo htmlspecialchars($old['email']); ?>"
                    class="<?php echo isset($errors['email']) ? 'input-error' : ''; ?>"
                >
                <?php if (isset($errors['email'])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors['email']); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="so_dien_thoai">Số điện thoại</label>
                <input
                    type="tel" id="so_dien_thoai" name="so_dien_thoai"
                    maxlength="10" autocomplete="tel" placeholder="VD: 0912345678"
                    value="<?php echo htmlspecialchars($old['so_dien_thoai']); ?>"
                    class="<?php echo isset($errors['so_dien_thoai']) ? 'input-error' : ''; ?>"
                >
                <?php if (isset($errors['so_dien_thoai'])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors['so_dien_thoai']); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group password-group">
                <label for="mat_khau">Mật khẩu <span class="required">*</span></label>
                <div class="password-wrap">
                    <input
                        type="password" id="mat_khau" name="mat_khau" required
                        minlength="8" maxlength="72" autocomplete="new-password"
                        class="<?php echo isset($errors['mat_khau']) ? 'input-error' : ''; ?>"
                    >
                    <button type="button" class="password-toggle" data-target="mat_khau" aria-label="Hiện/ẩn mật khẩu">👁</button>
                </div>
                <p class="field-hint">Tối thiểu 8 ký tự, gồm ít nhất 1 chữ và 1 số.</p>
                <div class="strength-meter" id="strengthMeter" aria-hidden="true"><span></span></div>
                <?php if (isset($errors['mat_khau'])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors['mat_khau']); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group password-group">
                <label for="xac_nhan_mat_khau">Xác nhận mật khẩu <span class="required">*</span></label>
                <div class="password-wrap">
                    <input
                        type="password" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau" required
                        minlength="8" maxlength="72" autocomplete="new-password"
                        class="<?php echo isset($errors['xac_nhan_mat_khau']) ? 'input-error' : ''; ?>"
                    >
                    <button type="button" class="password-toggle" data-target="xac_nhan_mat_khau" aria-label="Hiện/ẩn mật khẩu">👁</button>
                </div>
                <p class="field-error" id="matchError" hidden>Xác nhận mật khẩu không khớp.</p>
                <?php if (isset($errors['xac_nhan_mat_khau'])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors['xac_nhan_mat_khau']); ?></p>
                <?php endif; ?>
            </div>

            <p class="field-note">Tài khoản mới sẽ luôn có vai trò mặc định là <strong>Người dùng</strong>. Chỉ quản trị viên mới có thể nâng quyền sau khi đăng ký.</p>

            <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
        </form>

        <p class="auth-switch">Đã có tài khoản? <a href="/mvc/auth/login">Đăng nhập</a></p>
        <p class="auth-back"><a href="/">&larr; Về trang chủ</a></p>
    </div>
</main>
<script src="/assets/js/script.js"></script>
</body>
</html>
