<?php
/**
 * auth/login.php — Đăng nhập
 * ---------------------------------------------------------------------
 * Theo mục 5.1 SOBOHETHONG.md:
 *  - Sai quá 5 lần trong 15 phút -> tạm khoá đăng nhập 15 phút (theo email).
 *  - Thông báo lỗi dùng chung 1 câu, không tiết lộ email có tồn tại hay không.
 * Ngoài ra bổ sung:
 *  - So khớp mật khẩu bằng password_verify() với thời gian tính toán không đổi
 *    (dùng hash "mồi" khi không tìm thấy user) để giảm rủi ro user-enumeration
 *    qua timing attack.
 *  - session_regenerate_id() sau khi đăng nhập thành công (chống session fixation).
 *  - Redirect an toàn: chỉ cho phép quay lại đường dẫn nội bộ hợp lệ.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';

redirect_if_logged_in();

// Hash bcrypt hợp lệ nhưng "vô chủ", chỉ dùng để password_verify() luôn tốn
// thời gian tương đương dù email có tồn tại trong hệ thống hay không.
const DUMMY_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

const MAX_ATTEMPTS   = 5;   // số lần sai tối đa
const LOCKOUT_WINDOW = 15;  // phút, cửa sổ tính số lần sai
const IP_MAX_ATTEMPTS = 20; // chặn brute-force diện rộng từ 1 IP tới nhiều email
const IP_LOCKOUT_WINDOW = 15;

/**
 * Đường dẫn để quay lại sau khi đăng nhập thành công.
 * Chỉ chấp nhận đường dẫn nội bộ (bắt đầu bằng "/", không phải "//..." hay
 * chứa "://") để chống Open Redirect.
 */
function safe_redirect_target(?string $path): string
{
    $default = '/index.php';
    if (!is_string($path) || $path === '') {
        return $default;
    }
    if (preg_match('#^/(?!/)[A-Za-z0-9_\-./?=&%]*$#', $path)) {
        return $path;
    }
    return $default;
}

function count_failed_by_email(PDO $pdo, string $email, int $minutes): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE email = :email AND thanh_cong = 0
           AND created_at >= (NOW() - INTERVAL :minutes MINUTE)"
    );
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function count_failed_by_ip(PDO $pdo, string $ip, int $minutes): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = :ip AND thanh_cong = 0
           AND created_at >= (NOW() - INTERVAL :minutes MINUTE)"
    );
    $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

/**
 * Trả về số phút còn lại nếu email đang bị khoá tạm thời, hoặc null nếu
 * chưa/không còn bị khoá. Dùng cửa sổ trượt: lấy thời điểm của lần sai
 * thứ MAX_ATTEMPTS gần nhất, khoá đến (thời điểm đó + LOCKOUT_WINDOW phút).
 */
function minutes_until_unlock(PDO $pdo, string $email): ?int
{
    $stmt = $pdo->prepare(
        "SELECT created_at FROM login_attempts
         WHERE email = :email AND thanh_cong = 0
         ORDER BY created_at DESC
         LIMIT " . MAX_ATTEMPTS
    );
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($rows) < MAX_ATTEMPTS) {
        return null;
    }

    $oldestInWindow = new DateTimeImmutable((string) end($rows));
    $unlockAt = $oldestInWindow->modify('+' . LOCKOUT_WINDOW . ' minutes');
    $now = new DateTimeImmutable();

    if ($unlockAt <= $now) {
        return null;
    }

    return (int) ceil(($unlockAt->getTimestamp() - $now->getTimestamp()) / 60);
}

function log_attempt(PDO $pdo, string $email, string $ip, bool $success): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (email, ip_address, thanh_cong) VALUES (:email, :ip, :success)'
    );
    $stmt->execute([
        'email'   => $email,
        'ip'      => $ip,
        'success' => $success ? 1 : 0,
    ]);
}

/** Dọn dẹp nhật ký cũ (> 1 ngày) để bảng không phình to; chạy ngẫu nhiên ~2% request. */
function maybe_cleanup_attempts(PDO $pdo): void
{
    if (random_int(1, 100) <= 2) {
        $pdo->exec("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)");
    }
}

$errors = [];
$emailOld = '';
$redirectTarget = safe_redirect_target($_GET['redirect'] ?? ($_POST['redirect'] ?? null));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    maybe_cleanup_attempts($pdo);

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['mat_khau'] ?? '');
        $emailOld = $email;
        $ip       = client_ip();

        if ($email === '' || $password === '') {
            $errors[] = 'Vui lòng nhập đầy đủ email và mật khẩu.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email hoặc mật khẩu không đúng.';
        } elseif (count_failed_by_ip($pdo, $ip, IP_LOCKOUT_WINDOW) >= IP_MAX_ATTEMPTS) {
            $errors[] = 'Có quá nhiều lượt đăng nhập thất bại từ thiết bị này. Vui lòng thử lại sau ít phút.';
        } else {
            $remainingLock = minutes_until_unlock($pdo, $email);

            if ($remainingLock !== null) {
                $errors[] = 'Tài khoản tạm thời bị khoá do đăng nhập sai quá ' . MAX_ATTEMPTS . ' lần. '
                    . "Vui lòng thử lại sau khoảng {$remainingLock} phút.";
            } else {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                $hashToCheck = (is_array($user) && !empty($user['mat_khau'])) ? $user['mat_khau'] : DUMMY_HASH;
                $passwordOk  = password_verify($password, $hashToCheck);

                if (!is_array($user) || !$passwordOk || $user['trang_thai'] !== 'active') {
                    log_attempt($pdo, $email, $ip, false);
                    $left = MAX_ATTEMPTS - count_failed_by_email($pdo, $email, LOCKOUT_WINDOW);
                    $errors[] = 'Email hoặc mật khẩu không đúng, hoặc tài khoản đã bị khoá.'
                        . ($left > 0 && $left <= 2 ? " (còn {$left} lần thử trước khi tài khoản bị tạm khoá)" : '');
                } else {
                    log_attempt($pdo, $email, $ip, true);

                    // Chống session fixation: đổi session ID ngay khi nâng quyền đăng nhập
                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id'        => (int) $user['id'],
                        'full_name' => $user['ho_ten'],
                        'email'     => $user['email'],
                        'role'      => $user['vai_tro'],
                    ];
                    csrf_regenerate();

                    header('Location: ' . $redirectTarget);
                    exit;
                }
            }
        }
    }
}

$registered = isset($_GET['registered']);
$loggedOut  = isset($_GET['logged_out']);
$flashSuccess = flash_get('success');

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

        <form method="post" action="/auth/login.php" class="auth-form" novalidate>
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

        <p class="auth-switch">Chưa có tài khoản? <a href="/auth/register.php">Đăng ký ngay</a></p>
        <p class="auth-back"><a href="/index.php">&larr; Về trang chủ</a></p>

        <div class="auth-demo-hint">
            <p>Tài khoản demo (dữ liệu mẫu, mật khẩu: <code>Matkhau123</code>):</p>
            <ul>
                <li>Admin: <code>mai.admin@nhom4.edu.vn</code></li>
                <li>Cán bộ lab: <code>ky.labstaff@nhom4.edu.vn</code></li>
                <li>Người dùng: <code>phan.sv@nhom4.edu.vn</code></li>
            </ul>
        </div>
    </div>
</main>
<script src="/assets/js/script.js"></script>
</body>
</html>
