<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? null;
?>
<nav class="navbar">
    <a href="/index.php" class="brand">QL Phòng &amp; Thiết bị</a>
    <ul class="nav-links">
        <li><a href="/rooms/calendar.php">Lịch phòng</a></li>
        <li><a href="/rooms/list.php">Danh sách phòng</a></li>
        <li><a href="/equipment/list.php">Danh sách thiết bị</a></li>
        <?php if (in_array($role, ['admin', 'lab_staff'], true)): ?>
            <li><a href="/equipment_types/list.php">Loại thiết bị</a></li>
        <?php endif; ?>
        <?php if ($user): ?>
            <li><span>Xin chào, <?php echo htmlspecialchars($user['full_name']); ?></span></li>
            <li><a href="/auth/logout.php">Đăng xuất</a></li>
        <?php else: ?>
            <li><a href="/auth/login.php">Đăng nhập</a></li>
        <?php endif; ?>
    </ul>
</nav>