<?php
/**
 * users/list.php
 * ---------------------------------------------------------------------
 * Quản lý người dùng dành cho admin: xem danh sách, khoá/mở khoá tài
 * khoản, và nâng/hạ vai trò giữa "Người dùng" và "Cán bộ phòng lab"
 * (xem users/update_role.php). KHÔNG có chức năng xoá tài khoản theo
 * đúng yêu cầu nghiệp vụ (tránh mất dữ liệu lịch sử booking/báo hỏng
 * gắn với user). Vai trò "admin" không thể gán/thu hồi qua giao diện.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin']);
$currentUser = current_user();

$users = $pdo->query(
    "SELECT id, ho_ten, email, so_dien_thoai, vai_tro, trang_thai, created_at
     FROM users ORDER BY id"
)->fetchAll();

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Quản trị viên',
        'lab_staff' => 'Cán bộ phòng lab',
        default => 'Người dùng',
    };
}

$page_title = 'Người dùng';
$active_menu = 'users';
require_once __DIR__ . '/../includes/app_head.php';
?>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<?php if (empty($users)): ?>
    <div class="empty-state">Chưa có tài khoản nào.</div>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Họ tên</th>
            <th>Email</th>
            <th>Điện thoại</th>
            <th>Vai trò</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <?php $isSelf = (int) $u['id'] === (int) ($currentUser['id'] ?? 0); ?>
            <tr>
                <td><?php echo htmlspecialchars($u['ho_ten']); ?><?php echo $isSelf ? ' <span class="text-muted">(bạn)</span>' : ''; ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo htmlspecialchars($u['so_dien_thoai'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars(role_label($u['vai_tro'])); ?></td>
                <td>
                    <?php if ($u['trang_thai'] === 'active'): ?>
                        <span style="background:var(--color-success-bg);color:#166534;padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">Đang hoạt động</span>
                    <?php else: ?>
                        <span style="background:var(--color-error-bg);color:#991b1b;padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">Đã khoá</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isSelf): ?>
                        <span class="text-muted">Không thể tự khoá tài khoản của mình</span>
                    <?php else: ?>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <form method="post" action="/users/toggle_status.php" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                                <?php if ($u['trang_thai'] === 'active'): ?>
                                    <button type="submit" class="btn btn-danger"
                                            onclick="return confirm('Khoá tài khoản của <?php echo htmlspecialchars(addslashes($u['ho_ten'])); ?>?');">
                                        Khoá
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-secondary"
                                            onclick="return confirm('Mở khoá tài khoản của <?php echo htmlspecialchars(addslashes($u['ho_ten'])); ?>?');">
                                        Mở khoá
                                    </button>
                                <?php endif; ?>
                            </form>

                            <?php if ($u['vai_tro'] !== 'admin'): ?>
                                <form method="post" action="/users/update_role.php" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                                    <?php if ($u['vai_tro'] === 'lab_staff'): ?>
                                        <button type="submit" class="btn btn-secondary"
                                                onclick="return confirm('Chuyển <?php echo htmlspecialchars(addslashes($u['ho_ten'])); ?> về Người dùng thường?');">
                                            Hạ về người dùng
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-primary"
                                                onclick="return confirm('Nâng <?php echo htmlspecialchars(addslashes($u['ho_ten'])); ?> thành Cán bộ phòng lab?');">
                                            Nâng làm quản lý
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
