<?php
/**
 * app/Views/auth/login.php
 * View thuần hiển thị — không chứa logic nghiệp vụ hay SQL.
 * Biến truyền vào từ AuthController::showLogin()/login():
 *   $errors, $emailOld, $redirectTarget, $registered, $loggedOut, $flashSuccess
 */
$page_title = 'Đăng nhập';
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
        <h2 class="auth-title">Đăng nhập</h2>

        <?php if ($registered || $flashSuccess): ?>
            <div class="alert alert-success" role="status">
                <?php echo htmlspecialchars($flashSuccess ?? 'Đăng ký thành công! Vui lòng đăng nhập.'); ?>
            </div>
        <?php endif; ?>

        <?php if ($loggedOut): ?>
            <div class="alert alert-info" role="status">Bạn đã đăng xuất thành công.</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/mvc/auth/login" class="auth-form" novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget); ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email" id="email" name="email" required autofocus
                    autocomplete="email" maxlength="150"
                    value="<?php echo htmlspecialchars($emailOld); ?>"
                >
            </div>

            <div class="form-group password-group">
                <label for="mat_khau">Mật khẩu</label>
                <div class="password-wrap">
                    <input type="password" id="mat_khau" name="mat_khau" required autocomplete="current-password" maxlength="72">
                    <button type="button" class="password-toggle" data-target="mat_khau" aria-label="Hiện/ẩn mật khẩu">👁</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
        </form>

        <p class="auth-switch">Chưa có tài khoản? <a href="/mvc/auth/register">Đăng ký ngay</a></p>
        <p class="auth-back"><a href="/">&larr; Về trang chủ</a></p>
    </div>
</main>
<script src="/assets/js/script.js"></script>
</body>
</html>
