<?php
/**
 * includes/flash.php
 * ---------------------------------------------------------------------
 * Thông báo "flash" dùng theo mẫu Post/Redirect/Get: lưu vào session ở
 * request này, hiển thị và tự xoá ở request kế tiếp (tránh việc F5 lại
 * gửi lại form / hiện lại thông báo cũ).
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Lấy và xoá thông báo flash theo loại ('success', 'error', 'info'...).
 */
function flash_get(string $type): ?string
{
    if (!empty($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}
