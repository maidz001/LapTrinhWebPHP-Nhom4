<?php
/**
 * auth/register.php — Đăng ký tài khoản mới
 * ---------------------------------------------------------------------
 * Quy tắc áp dụng theo mục 5.1 SOBOHETHONG.md:
 *  - ho_ten: bắt buộc, 2-100 ký tự, chỉ chữ cái (có dấu) + khoảng trắng
 *  - email: bắt buộc, đúng định dạng, duy nhất, tối đa 150 ký tự
 *  - mat_khau: bắt buộc, >= 8 ký tự, có ít nhất 1 chữ + 1 số, hash bcrypt
 *  - xac_nhan_mat_khau: khớp với mat_khau (chỉ dùng ở form, không lưu DB)
 *  - so_dien_thoai: không bắt buộc, nếu nhập phải đúng 10 số bắt đầu bằng 0
 *  - vai_tro: LUÔN ép cứng 'user' phía server, không tin giá trị từ client
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';

redirect_if_logged_in();

$errors = [];
$old = [
    'ho_ten'        => '',
    'email'         => '',
    'so_dien_thoai' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Chống bot đơn giản: honeypot field ẩn bằng CSS, người dùng thật sẽ không điền ---
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.';
    } elseif ($honeypot !== '') {
        // Bot điền vào honeypot -> âm thầm coi như thành công, không xử lý gì thêm
        flash_set('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
        header('Location: /auth/login.php');
        exit;
    } else {
        $hoTen        = trim((string) ($_POST['ho_ten'] ?? ''));
        $email        = trim((string) ($_POST['email'] ?? ''));
        $soDienThoai  = trim((string) ($_POST['so_dien_thoai'] ?? ''));
        $matKhau      = (string) ($_POST['mat_khau'] ?? '');
        $xacNhanMk    = (string) ($_POST['xac_nhan_mat_khau'] ?? '');

        $old['ho_ten']        = $hoTen;
        $old['email']         = $email;
        $old['so_dien_thoai'] = $soDienThoai;

        // --- Họ tên: 2-100 ký tự, chỉ chữ cái Unicode + khoảng trắng ---
        $hoTenLen = mb_strlen($hoTen, 'UTF-8');
        if ($hoTen === '') {
            $errors['ho_ten'] = 'Vui lòng nhập họ tên.';
        } elseif ($hoTenLen < 2 || $hoTenLen > 100) {
            $errors['ho_ten'] = 'Họ tên phải từ 2 đến 100 ký tự.';
        } elseif (!preg_match('/^[\p{L}][\p{L}\s]*$/u', $hoTen)) {
            $errors['ho_ten'] = 'Họ tên chỉ được chứa chữ cái và khoảng trắng.';
        }

        // --- Email: định dạng hợp lệ, tối đa 150 ký tự ---
        if ($email === '') {
            $errors['email'] = 'Vui lòng nhập email.';
        } elseif (mb_strlen($email, 'UTF-8') > 150) {
            $errors['email'] = 'Email tối đa 150 ký tự.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không đúng định dạng.';
        }

        // --- Số điện thoại: không bắt buộc, nếu có phải đúng 10 số, bắt đầu bằng 0 ---
        if ($soDienThoai !== '' && !preg_match('/^0\d{9}$/', $soDienThoai)) {
            $errors['so_dien_thoai'] = 'Số điện thoại phải gồm đúng 10 chữ số và bắt đầu bằng 0.';
        }

        // --- Mật khẩu: >= 8 ký tự, có ít nhất 1 chữ và 1 số, tối đa 72 ký tự (giới hạn bcrypt) ---
        if ($matKhau === '') {
            $errors['mat_khau'] = 'Vui lòng nhập mật khẩu.';
        } elseif (mb_strlen($matKhau, 'UTF-8') < 8) {
            $errors['mat_khau'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
        } elseif (mb_strlen($matKhau, 'UTF-8') > 72) {
            $errors['mat_khau'] = 'Mật khẩu tối đa 72 ký tự.';
        } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).+$/', $matKhau)) {
            $errors['mat_khau'] = 'Mật khẩu phải có ít nhất 1 chữ cái và 1 chữ số.';
        }

        // --- Xác nhận mật khẩu ---
        if (empty($errors['mat_khau']) && $xacNhanMk !== $matKhau) {
            $errors['xac_nhan_mat_khau'] = 'Xác nhận mật khẩu không khớp.';
        }

        // --- Kiểm tra trùng email (chỉ khi các trường khác đã hợp lệ) ---
        if (empty($errors['email'])) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch() !== false) {
                $errors['email'] = 'Email này đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.';
            }
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($matKhau, PASSWORD_BCRYPT, ['cost' => 12]);

                $stmt = $pdo->prepare(
                    'INSERT INTO users (ho_ten, email, mat_khau, so_dien_thoai, vai_tro, trang_thai)
                     VALUES (:ho_ten, :email, :mat_khau, :so_dien_thoai, :vai_tro, :trang_thai)'
                );
                $stmt->execute([
                    'ho_ten'        => $hoTen,
                    'email'         => $email,
                    'mat_khau'      => $hash,
                    'so_dien_thoai' => $soDienThoai !== '' ? $soDienThoai : null,
                    // Ép cứng phía server — KHÔNG bao giờ lấy vai_tro từ $_POST,
                    // đề phòng người dùng sửa HTML để tự cấp quyền admin.
                    'vai_tro'       => 'user',
                    'trang_thai'    => 'active',
                ]);

                csrf_regenerate();
                flash_set('success', 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.');
                header('Location: /auth/login.php?registered=1');
                exit;
            } catch (PDOException $e) {
                // 23000 = vi phạm ràng buộc duy nhất (trường hợp 2 request đăng ký cùng email gần như đồng thời)
                if ($e->getCode() === '23000') {
                    $errors['email'] = 'Email này đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.';
                } else {
                    error_log('[REGISTER ERROR] ' . $e->getMessage());
                    $errors[] = 'Có lỗi xảy ra khi tạo tài khoản. Vui lòng thử lại sau.';
                }
            }
        }
    }
}

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

        <form method="post" action="/auth/register.php" class="auth-form" id="registerForm" novalidate>
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

        <p class="auth-switch">Đã có tài khoản? <a href="/auth/login.php">Đăng nhập</a></p>
        <p class="auth-back"><a href="/index.php">&larr; Về trang chủ</a></p>
    </div>
</main>
<script src="/assets/js/script.js"></script>
</body>
</html>
