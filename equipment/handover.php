<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_role(['admin', 'lab_staff']);

$page_title = 'Bàn giao thiết bị';
$active_menu = 'handover';
require_once __DIR__ . '/../includes/app_head.php';

$cs_title = 'Bàn giao thiết bị';
$cs_desc  = 'Xác nhận giao và nhận lại thiết bị cho các yêu cầu mượn đã được duyệt.';
$cs_icon  = 'swap';
require __DIR__ . '/../includes/coming_soon.php';

require_once __DIR__ . '/../includes/app_foot.php';
