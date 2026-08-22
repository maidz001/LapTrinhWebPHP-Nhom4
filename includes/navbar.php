<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/csrf.php';

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? null;
?>
<nav class="navbar">
    <a href="/index.php" class="brand">QL Phòng &amp; Thiết bị</a>
    <button class="nav-toggle" id="navToggle" aria-label="Mở menu" aria-expanded="false">☰</button>
    <ul class="nav-links" id="navLinks">
        <li><a href="/rooms/calendar.php">Lịch phòng</a></li>
        <li><a href="/rooms/list.php">Danh sách phòng</a></li>
        <li><a href="/equipment/list.php">Danh sách thiết bị</a></li>
        <?php if ($user): ?>
            <li><a href="/bookings/my_requests.php">Yêu cầu của tôi</a></li>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'lab_staff'], true)): ?>
            <li><a href="/bookings/pending.php">Duyệt yêu cầu</a></li>
            <li><a href="/equipment_types/list.php">Loại thiết bị</a></li>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
            <li><a href="/users/list.php">Người dùng</a></li>
        <?php endif; ?>
        <?php if ($user): ?>
            <li class="nav-user"><span>Xin chào, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span></li>
            <li>
                <a
                    href="/auth/logout.php?csrf_token=<?php echo urlencode(csrf_token()); ?>"
                    class="nav-logout"
                    onclick="return confirm('Bạn có chắc muốn đăng xuất?');"
                >Đăng xuất</a>
            </li>
        <?php else: ?>
            <li><a href="/auth/login.php">Đăng nhập</a></li>
            <li><a href="/auth/register.php" class="btn-nav-primary">Đăng ký</a></li>
        <?php endif; ?>
    </ul>
</nav>
