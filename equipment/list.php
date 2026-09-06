<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$page_title = 'Thiết bị';
$active_menu = 'equipment';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Thiết bị';
$cs_desc  = 'Danh sách thiết bị theo phòng và thiết bị lưu động có thể cho mượn.';
$cs_icon  = 'monitor';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
