<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$page_title = 'Lịch sử dụng';
$active_menu = 'history';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Lịch sử dụng';
$cs_desc  = 'Lịch sử các lượt đặt phòng và mượn thiết bị đã được xử lý (đã duyệt/từ chối/huỷ).';
$cs_icon  = 'clock';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
