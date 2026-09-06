<?php
/**
 * app/Views/users/index.php
 * View thuần hiển thị — không chứa SQL. Biến truyền vào từ
 * UserController::index(): $users, $currentUserId, $flashSuccess, $flashError
 */
function mvc_role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Quản trị viên',
        'lab_staff' => 'Cán bộ phòng lab',
        default => 'Người dùng',
    };
}

$page_title = 'Người dùng';
$active_menu = 'users';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
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
            <?php $isSelf = (int) $u['id'] === $currentUserId; ?>
            <tr>
                <td><?php echo htmlspecialchars($u['ho_ten']); ?><?php echo $isSelf ? ' <span class="text-muted">(bạn)</span>' : ''; ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo htmlspecialchars($u['so_dien_thoai'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars(mvc_role_label($u['vai_tro'])); ?></td>
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
                            <form method="post" action="/mvc/users/toggle-status" style="display:inline;">
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
                                <form method="post" action="/mvc/users/update-role" style="display:inline;">
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

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
