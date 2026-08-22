<?php
/**
 * includes/csrf.php
 * ---------------------------------------------------------------------
 * Chống giả mạo yêu cầu liên trang (CSRF) cho MỌI form POST trong hệ thống.
 *
 * Cách dùng trong form:
 *      <form method="post">
 *          <?php echo csrf_field(); ?>
 *          ...
 *      </form>
 *
 * Cách dùng khi xử lý POST:
 *      if (!csrf_verify($_POST['csrf_token'] ?? null)) {
 *          // từ chối request
 *      }
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Lấy token CSRF hiện tại của phiên, tự sinh nếu chưa có.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Trả về input ẩn sẵn sàng nhúng vào form.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Kiểm tra token gửi lên có khớp với token trong session không.
 * Dùng hash_equals() để tránh timing attack.
 */
function csrf_verify(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || !is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sinh token mới (nên gọi sau khi đăng nhập / đăng ký / đổi quyền thành công
 * để giảm thời gian sống của token cũ).
 */
function csrf_regenerate(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
