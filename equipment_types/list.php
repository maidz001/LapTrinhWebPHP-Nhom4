<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_role(['admin', 'lab_staff']);

$page_title = 'Danh mục thiết bị';
$active_menu = 'eq_types';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Danh mục thiết bị';
$cs_desc  = 'Quản lý các loại thiết bị dùng để phân loại khi thêm thiết bị mới.';
$cs_icon  = 'list';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
