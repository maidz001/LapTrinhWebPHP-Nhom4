<?php
/**
 * includes/app_head.php
 * ---------------------------------------------------------------------
 * Khung giao diện dùng chung cho MỌI trang đã đăng nhập (sidebar bên
 * trái + topbar phía trên). Trang gọi file này PHẢI:
 *   1) require_once config/database.php + includes/auth_check.php
 *   2) gọi require_login() (hoặc require_role([...]))
 *   3) khai báo $page_title và $active_menu TRƯỚC KHI include file này
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/csrf.php';

$__user = current_user();
$__role = $__user['role'] ?? 'user';

// (key nav) => [label, icon, href, roles cho phép rỗng = mọi role đã đăng nhập, badge]
$__menu = [
    'overview'  => ['Tổng quan',        'home',      '/index.php',                 [], null],
    'rooms'     => ['Phòng thực hành',  'grid',      '/rooms/list.php',             [], null],
    'equipment' => ['Thiết bị',         'monitor',   '/equipment/list.php',         [], null],
    'eq_types'  => ['Danh mục thiết bị','list',      '/equipment_types/list.php',   ['admin', 'lab_staff'], null],
    'history'   => ['Lịch sử dụng',     'clock',     '/bookings/history.php',       [], null],
    'booking'   => ['Đăng ký phòng',    'plus',      '/bookings/form.php',          [], null],
    'handover'  => ['Bàn giao thiết bị','swap',      '/equipment/handover.php',     ['admin', 'lab_staff'], null],
    'reports'   => ['Báo cáo',          'bar-chart', '/reports/index.php',          [], null],
    'users'     => ['Người dùng',       'users',     '/users/list.php',             ['admin'], null],
];

function __initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $parts = array_filter($parts);
    if (empty($parts)) return '?';
    $first = mb_substr((string) reset($parts), 0, 1, 'UTF-8');
    $last  = mb_substr((string) end($parts), 0, 1, 'UTF-8');
    return mb_strtoupper($first . $last, 'UTF-8');
}

$page_title = $page_title ?? 'Tổng quan';
$active_menu = $active_menu ?? 'overview';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - LAB MANAGEMENT</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-shell">

<div class="sidebar-scrim" id="sidebarScrim"></div>

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <span class="brand-icon" aria-hidden="true"><?php echo app_icon('wrench'); ?></span>
        <div class="brand-text">
            <strong>LAB MANAGEMENT</strong>
            <span>Quản lý phòng thực hành</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <?php foreach ($__menu as $key => [$label, $icon, $href, $roles, $badge]): ?>
            <?php if (!empty($roles) && !in_array($__role, $roles, true)) continue; ?>
            <li>
                <a href="<?php echo htmlspecialchars($href); ?>"
                   class="<?php echo $active_menu === $key ? 'active' : ''; ?>">
                    <?php echo app_icon($icon); ?>
                    <span><?php echo htmlspecialchars($label); ?></span>
                    <?php if ($badge): ?><span class="nav-badge"><?php echo htmlspecialchars($badge); ?></span><?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-footer">
        <ul class="sidebar-nav" style="padding:0;">
            <li><a href="/settings/index.php" class="<?php echo $active_menu === 'settings' ? 'active' : ''; ?>"><?php echo app_icon('settings'); ?><span>Cài đặt</span></a></li>
            <li>
                <a href="/auth/logout.php?csrf_token=<?php echo urlencode(csrf_token()); ?>"
                   class="nav-logout"
                   onclick="return confirm('Bạn có chắc muốn đăng xuất?');">
                    <?php echo app_icon('logout'); ?><span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </div>
</aside>

<div class="app-content">
    <header class="app-topbar">
        <button class="nav-toggle" id="sidebarToggle" aria-label="Mở menu">☰</button>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <div class="topbar-search">
            <?php echo app_icon('search'); ?>
            <input type="text" placeholder="Tìm kiếm..." disabled title="Sắp ra mắt">
        </div>

        <div class="topbar-actions">
            <button class="topbar-bell" type="button" title="Thông báo" disabled>
                <?php echo app_icon('bell'); ?>
                <span class="dot" aria-hidden="true"></span>
            </button>
            <div class="topbar-user">
                <span class="avatar"><?php echo htmlspecialchars(__initials($__user['full_name'] ?? '?')); ?></span>
                <div class="user-meta">
                    <strong><?php echo htmlspecialchars($__user['full_name'] ?? ''); ?></strong>
                    <span>
                        <?php
                        echo htmlspecialchars(match ($__role) {
                            'admin' => 'Quản trị viên',
                            'lab_staff' => 'Cán bộ phòng lab',
                            default => 'Người dùng',
                        });
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="app-main">
