<?php
/**
 * rooms/list.php
 * ---------------------------------------------------------------------
 * Danh sách và tình trạng các phòng thực hành: sức chứa, vị trí,
 * trạng thái sử dụng. Mọi người dùng đã đăng nhập đều xem được.
 * admin/lab_staff có thêm nút Thêm/Sửa/Xoá.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();
$user = current_user();
$canManage = in_array($user['role'] ?? '', ['admin', 'lab_staff'], true);

// Tìm kiếm phòng theo tên (chỉ theo tên, không tìm theo mã/vị trí)
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE ten_phong LIKE :q ORDER BY ma_phong");
    $stmt->execute(['q' => '%' . $q . '%']);
    $rooms = $stmt->fetchAll();
} else {
    $rooms = $pdo->query("SELECT * FROM rooms ORDER BY ma_phong")->fetchAll();
}

function room_status_label(string $status): string
{
    return match ($status) {
        'available' => 'Sẵn sàng',
        'maintenance' => 'Đang bảo trì',
        'closed' => 'Đã đóng',
        default => $status,
    };
}

function room_status_style(string $status): string
{
    return match ($status) {
        'available' => 'background:var(--color-success-bg);color:#166534;',
        'maintenance' => 'background:var(--color-warning-bg);color:#92400e;',
        'closed' => 'background:var(--color-error-bg);color:#991b1b;',
        default => '',
    };
}

$page_title = 'Phòng thực hành';
$active_menu = 'rooms';
require_once __DIR__ . '/../includes/app_head.php';
?>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<form method="get" class="filter-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <input
        type="text"
        name="q"
        class="search-input"
        placeholder="Tìm phòng theo tên..."
        value="<?php echo htmlspecialchars($q); ?>"
        style="flex:1 1 320px;max-width:420px;"
    >
    <button type="submit" class="btn btn-secondary" style="padding:9px 14px;">Tìm kiếm</button>
    <?php if ($q !== ''): ?>
        <a href="/rooms/list.php" class="btn btn-secondary">Xoá lọc</a>
    <?php endif; ?>
</form>

<p style="margin:0 0 16px;display:flex;gap:10px;flex-wrap:wrap;">
    <?php if ($canManage): ?>
        <a href="/rooms/form.php" class="btn btn-primary">+ Thêm phòng</a>
        <a href="/rooms/import.php" class="btn btn-secondary">⇪ Thêm từ file</a>
    <?php endif; ?>
    <a href="/rooms/export.php?q=<?php echo urlencode($q); ?>" class="btn btn-secondary">⇩ Xuất file</a>
</p>

<?php if (empty($rooms)): ?>
    <div class="empty-state">
        <?php echo $q !== '' ? 'Không tìm thấy phòng nào có tên phù hợp.' : 'Chưa có phòng thực hành nào.'; ?>
    </div>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Mã phòng</th>
            <th>Tên phòng</th>
            <th>Vị trí</th>
            <th>Sức chứa</th>
            <th>Trạng thái</th>
            <th>Mô tả</th>
            <?php if ($canManage): ?><th>Hành động</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rooms as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['ma_phong']); ?></td>
                <td><?php echo htmlspecialchars($r['ten_phong']); ?></td>
                <td><?php echo htmlspecialchars($r['vi_tri']); ?></td>
                <td><?php echo (int) $r['suc_chua']; ?></td>
                <td>
                    <span style="<?php echo room_status_style($r['trang_thai']); ?>padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">
                        <?php echo room_status_label($r['trang_thai']); ?>
                    </span>
                </td>
                <td><?php echo $r['mo_ta'] ? htmlspecialchars($r['mo_ta']) : '&mdash;'; ?></td>
                <?php if ($canManage): ?>
                    <td>
                        <a href="/rooms/form.php?id=<?php echo $r['id']; ?>" class="btn btn-secondary">Sửa</a>
                        <form method="post" action="/rooms/delete.php" style="display:inline;"
                              onsubmit="return confirm('Bạn có chắc muốn xoá phòng này?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                            <button type="submit" class="btn btn-danger">Xoá</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>