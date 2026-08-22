<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_role(['admin']);

$page_title = 'Người dùng';
$active_menu = 'users';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Người dùng';
$cs_desc  = 'Quản lý tài khoản người dùng: phân quyền, khoá/mở khoá tài khoản.';
$cs_icon  = 'users';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
