<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$page_title = 'Báo cáo';
$active_menu = 'reports';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Báo cáo';
$cs_desc  = 'Gửi báo hỏng thiết bị và xem thống kê tình trạng sử dụng phòng/thiết bị.';
$cs_icon  = 'bar-chart';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
