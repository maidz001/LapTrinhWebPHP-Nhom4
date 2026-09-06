<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();

$page_title = 'Phòng thực hành';
$active_menu = 'rooms';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Phòng thực hành';
$cs_desc  = 'Danh sách và tình trạng các phòng thực hành: sức chứa, vị trí, trạng thái sử dụng.';
$cs_icon  = 'grid';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
