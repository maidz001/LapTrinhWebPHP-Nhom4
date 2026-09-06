<?php
/**
 * users/delete.php
 * ---------------------------------------------------------------------
 * Theo yêu cầu nghiệp vụ, tài khoản KHÔNG được xoá (chỉ khoá/mở khoá,
 * xem users/list.php + users/toggle_status.php). File này chỉ giữ lại
 * để không vỡ đường dẫn cũ, luôn điều hướng về danh sách người dùng.
 * ---------------------------------------------------------------------
 */
declare(strict_types=1);
header('Location: /users/list.php');
exit;
