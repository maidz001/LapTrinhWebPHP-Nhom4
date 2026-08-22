<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$page_title = 'Đăng ký phòng';
$active_menu = 'booking';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Đăng ký phòng';
$cs_desc  = 'Gửi yêu cầu đặt phòng thực hành hoặc mượn thiết bị lưu động.';
$cs_icon  = 'plus';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
