<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/repository.php';

require_login();

$id = max(0, (int) ($_GET['id'] ?? 0));
$booking = findBookingById($pdo, $id);
$user = current_user();
$isStaff = in_array($user['role'], ['admin', 'lab_staff'], true);
$isOwner = $booking && (int) $booking['user_id'] === (int) $user['id'];

if (!$booking || (!$isStaff && !$isOwner)) {
    http_response_code(404);
    $page_title = 'Không tìm thấy yêu cầu';
    $active_menu = 'my_requests';
    require_once __DIR__ . '/../includes/app_head.php';
    echo '<div class="alert alert-error">Không tìm thấy yêu cầu hoặc bạn không có quyền xem.</div>';
    echo '<a class="btn btn-secondary" href="/bookings/my_requests.php">Quay lại</a>';
    require_once __DIR__ . '/../includes/app_foot.php';
    exit;
}

$resourceName = $booking['loai_yeu_cau'] === 'room'
    ? $booking['ma_phong'] . ' - ' . $booking['ten_phong']
    : $booking['ma_thiet_bi'] . ' - ' . $booking['ten_thiet_bi'];
$success = flash_get('success');
$error = flash_get('error');
$page_title = 'Chi tiết yêu cầu #' . $id;
$active_menu = $isStaff ? 'approval' : 'my_requests';
require_once __DIR__ . '/../includes/app_head.php';
?>

<div class="page-heading">
    <div>
        <p class="breadcrumb"><a href="<?php echo $isStaff ? '/bookings/pending.php' : '/bookings/my_requests.php'; ?>">Danh sách yêu cầu</a> / Chi tiết</p>
        <h2>Yêu cầu #<?php echo $id; ?></h2>
        <p>Thông tin đầy đủ của yêu cầu sử dụng phòng hoặc thiết bị.</p>
    </div>
    <span class="status-pill <?php echo htmlspecialchars($booking['trang_thai']); ?>"><?php echo htmlspecialchars(bookingStatusLabel($booking['trang_thai'])); ?></span>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<section class="content-card">
    <div class="detail-grid">
        <div class="detail-item"><span>Người gửi</span><strong><?php echo htmlspecialchars($booking['nguoi_gui']); ?></strong></div>
        <div class="detail-item"><span>Email</span><strong><?php echo htmlspecialchars($booking['email_nguoi_gui']); ?></strong></div>
        <div class="detail-item"><span>Loại yêu cầu</span><strong><?php echo htmlspecialchars(bookingTypeLabel($booking['loai_yeu_cau'])); ?></strong></div>
        <div class="detail-item"><span>Phòng hoặc thiết bị</span><strong><?php echo htmlspecialchars($resourceName); ?></strong></div>
        <div class="detail-item"><span>Bắt đầu</span><strong><?php echo date('d/m/Y H:i', strtotime($booking['thoi_gian_bat_dau'])); ?></strong></div>
        <div class="detail-item"><span>Kết thúc</span><strong><?php echo date('d/m/Y H:i', strtotime($booking['thoi_gian_ket_thuc'])); ?></strong></div>
        <div class="detail-item detail-wide"><span>Mục đích</span><strong><?php echo nl2br(htmlspecialchars($booking['muc_dich'])); ?></strong></div>
        <div class="detail-item"><span>Ngày tạo</span><strong><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></strong></div>
        <div class="detail-item"><span>Người duyệt</span><strong><?php echo htmlspecialchars($booking['nguoi_duyet'] ?: 'Chưa có'); ?></strong></div>
        <?php if (!empty($booking['approved_at'])): ?>
            <div class="detail-item"><span>Thời gian xử lý</span><strong><?php echo date('d/m/Y H:i', strtotime($booking['approved_at'])); ?></strong></div>
        <?php endif; ?>
        <?php if (!empty($booking['ly_do_tu_choi'])): ?>
            <div class="detail-item detail-wide"><span>Lý do từ chối</span><strong><?php echo htmlspecialchars($booking['ly_do_tu_choi']); ?></strong></div>
        <?php endif; ?>
    </div>
</section>

<?php if ($booking['trang_thai'] === 'pending'): ?>
    <section class="content-card action-card">
        <?php if ($isOwner): ?>
            <h3>Thao tác của người gửi</h3>
            <div class="row-actions">
                <a href="/bookings/form.php?id=<?php echo $id; ?>" class="btn btn-primary">Chỉnh sửa</a>
                <form method="post" action="/bookings/cancel.php" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu này?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <button type="submit" class="btn btn-danger">Hủy yêu cầu</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($isStaff): ?>
            <h3>Xử lý yêu cầu</h3>
            <div class="approval-actions">
                <form method="post" action="/bookings/approve.php">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="return_to" value="detail">
                    <button type="submit" class="btn btn-primary">Duyệt yêu cầu</button>
                </form>
                <form method="post" action="/bookings/reject.php" class="reject-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="return_to" value="detail">
                    <label for="ly_do_tu_choi">Lý do từ chối</label>
                    <div class="reject-row">
                        <input type="text" id="ly_do_tu_choi" name="ly_do_tu_choi" minlength="5" maxlength="255" required placeholder="Nhập lý do từ chối">
                        <button type="submit" class="btn btn-danger">Từ chối</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<a href="<?php echo $isStaff ? '/bookings/pending.php' : '/bookings/my_requests.php'; ?>" class="btn btn-secondary">Quay lại danh sách</a>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
