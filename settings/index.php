<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$page_title = 'Cài đặt';
$active_menu = 'settings';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Cài đặt';
$cs_desc  = 'Cập nhật thông tin cá nhân và đổi mật khẩu tài khoản.';
$cs_icon  = 'settings';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
