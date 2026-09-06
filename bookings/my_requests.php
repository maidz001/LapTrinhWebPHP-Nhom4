<?php
/**
 * bookings/my_requests.php
 * ---------------------------------------------------------------------
 * Danh sách các yêu cầu (đặt phòng / mượn thiết bị) đang chờ duyệt hoặc
 * đã được duyệt của chính người dùng đang đăng nhập. Cho phép huỷ yêu
 * cầu khi còn ở trạng thái 'pending'.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/booking_helpers.php';

require_login();
$user = current_user();

$stmt = $pdo->prepare(
    "SELECT b.*, r.ma_phong, r.ten_phong, e.ma_thiet_bi, e.ten_thiet_bi
     FROM bookings b
     LEFT JOIN rooms r ON r.id = b.room_id
     LEFT JOIN equipment e ON e.id = b.equipment_id
     WHERE b.user_id = :uid AND b.trang_thai IN ('pending','approved')
     ORDER BY b.thoi_gian_bat_dau ASC"
);
$stmt->execute(['uid' => $user['id']]);
$bookings = $stmt->fetchAll();

$page_title = 'Yêu cầu của tôi';
$active_menu = 'booking';
require_once __DIR__ . '/../includes/app_head.php';
?>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<p style="margin:0 0 16px;">
    <a href="/bookings/form.php" class="btn btn-primary">+ Tạo yêu cầu mới</a>
</p>

<?php if (empty($bookings)): ?>
    <div class="empty-state">Bạn chưa có yêu cầu nào đang chờ duyệt hoặc đã được duyệt.</div>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Loại</th>
            <th>Tài nguyên</th>
            <th>Thời gian</th>
            <th>Mục đích</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><?php echo $b['loai_yeu_cau'] === 'room' ? 'Đặt phòng' : 'Mượn thiết bị'; ?></td>
                <td>
                    <?php
                    echo $b['loai_yeu_cau'] === 'room'
                        ? htmlspecialchars($b['ma_phong'] . ' - ' . $b['ten_phong'])
                        : htmlspecialchars($b['ma_thiet_bi'] . ' - ' . $b['ten_thiet_bi']);
                    ?>
                </td>
                <td>
                    <?php
                    echo date('d/m/Y H:i', strtotime($b['thoi_gian_bat_dau']))
                        . ' - ' . date('d/m/Y H:i', strtotime($b['thoi_gian_ket_thuc']));
                    ?>
                </td>
                <td><?php echo htmlspecialchars($b['muc_dich']); ?></td>
                <td>
                    <span style="<?php echo booking_status_style($b['trang_thai']); ?>padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">
                        <?php echo booking_status_label($b['trang_thai']); ?>
                    </span>
                </td>
                <td>
                    <?php if ($b['trang_thai'] === 'pending'): ?>
                        <a href="/bookings/cancel.php?id=<?php echo $b['id']; ?>&csrf_token=<?php echo urlencode(csrf_token()); ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Bạn có chắc muốn huỷ yêu cầu này?');">Huỷ</a>
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
