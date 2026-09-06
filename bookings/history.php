<?php
/**
 * bookings/history.php
 * ---------------------------------------------------------------------
 * Lịch sử toàn bộ yêu cầu (mọi trạng thái) của chính người dùng đang
 * đăng nhập, có thể lọc theo loại yêu cầu và trạng thái. Chỉ xem, không
 * có hành động chỉnh sửa/huỷ (để huỷ yêu cầu đang chờ, dùng my_requests.php).
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/booking_helpers.php';

require_login();
$user = current_user();

$loaiFilter = $_GET['loai'] ?? 'all';
if (!in_array($loaiFilter, ['all', 'room', 'equipment'], true)) {
    $loaiFilter = 'all';
}

$trangThaiFilter = $_GET['trang_thai'] ?? 'all';
$allowedStatus = ['all', 'pending', 'approved', 'rejected', 'cancelled'];
if (!in_array($trangThaiFilter, $allowedStatus, true)) {
    $trangThaiFilter = 'all';
}

$sql = "SELECT b.*, r.ma_phong, r.ten_phong, e.ma_thiet_bi, e.ten_thiet_bi
        FROM bookings b
        LEFT JOIN rooms r ON r.id = b.room_id
        LEFT JOIN equipment e ON e.id = b.equipment_id
        WHERE b.user_id = :uid";
$params = ['uid' => $user['id']];

if ($loaiFilter !== 'all') {
    $sql .= " AND b.loai_yeu_cau = :loai";
    $params['loai'] = $loaiFilter;
}
if ($trangThaiFilter !== 'all') {
    $sql .= " AND b.trang_thai = :trang_thai";
    $params['trang_thai'] = $trangThaiFilter;
}
$sql .= " ORDER BY b.thoi_gian_bat_dau DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = 'Lịch sử sử dụng';
$active_menu = 'history';
require_once __DIR__ . '/../includes/app_head.php';
?>

<form method="get" class="filter-form">
    <label style="display:inline-block;margin-right:8px;">Loại</label>
    <select name="loai" onchange="this.form.submit()">
        <option value="all" <?php echo $loaiFilter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
        <option value="room" <?php echo $loaiFilter === 'room' ? 'selected' : ''; ?>>Đặt phòng</option>
        <option value="equipment" <?php echo $loaiFilter === 'equipment' ? 'selected' : ''; ?>>Mượn thiết bị</option>
    </select>

    <label style="display:inline-block;margin:0 8px 0 16px;">Trạng thái</label>
    <select name="trang_thai" onchange="this.form.submit()">
        <option value="all" <?php echo $trangThaiFilter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
        <option value="pending" <?php echo $trangThaiFilter === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
        <option value="approved" <?php echo $trangThaiFilter === 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
        <option value="rejected" <?php echo $trangThaiFilter === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
        <option value="cancelled" <?php echo $trangThaiFilter === 'cancelled' ? 'selected' : ''; ?>>Đã huỷ</option>
    </select>
</form>

<?php if (empty($bookings)): ?>
    <div class="empty-state">Không có yêu cầu nào phù hợp với bộ lọc.</div>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Loại</th>
            <th>Tài nguyên</th>
            <th>Thời gian</th>
            <th>Mục đích</th>
            <th>Trạng thái</th>
            <th>Ghi chú</th>
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
                <td><?php echo ($b['ly_do_tu_choi'] ? htmlspecialchars($b['ly_do_tu_choi']) : '&mdash;'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>