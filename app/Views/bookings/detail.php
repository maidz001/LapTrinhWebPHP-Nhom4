<?php
/**
 * app/Views/bookings/detail.php
 * Biến truyền vào từ BookingController::detail():
 *   $id, $booking, $resourceName, $isStaff, $isOwner, $flashSuccess, $flashError
 */
require_once __DIR__ . '/_status_style.php';

$page_title = 'Chi tiết yêu cầu #' . $id;
$active_menu = $isStaff ? 'approval' : 'my_requests';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<div class="page-heading">
    <div>
        <p class="breadcrumb">
            <a href="<?php echo $isStaff ? '/mvc/bookings/pending' : '/mvc/bookings'; ?>">Danh sách yêu cầu</a> / Chi tiết
        </p>
        <h2>Yêu cầu #<?php echo $id; ?></h2>
        <p>Thông tin đầy đủ của yêu cầu sử dụng phòng hoặc thiết bị.</p>
    </div>
    <span class="status-pill <?php echo htmlspecialchars($booking['trang_thai']); ?>"
          style="<?php echo mvc_booking_status_style($booking['trang_thai']); ?>padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">
        <?php echo htmlspecialchars(Booking::statusLabel($booking['trang_thai'])); ?>
    </span>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
<?php endif; ?>

<section class="content-card">
    <div class="detail-grid">
        <div class="detail-item"><span>Người gửi</span><strong><?php echo htmlspecialchars($booking['nguoi_gui']); ?></strong></div>
        <div class="detail-item"><span>Email</span><strong><?php echo htmlspecialchars($booking['email_nguoi_gui']); ?></strong></div>
        <div class="detail-item"><span>Loại yêu cầu</span><strong><?php echo htmlspecialchars(Booking::typeLabel($booking['loai_yeu_cau'])); ?></strong></div>
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
                <a href="/mvc/bookings/form?id=<?php echo $id; ?>" class="btn btn-primary">Chỉnh sửa</a>
                <form method="post" action="/mvc/bookings/cancel" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu này?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <button type="submit" class="btn btn-danger">Hủy yêu cầu</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($isStaff): ?>
            <h3>Xử lý yêu cầu</h3>
            <div class="approval-actions">
                <form method="post" action="/mvc/bookings/approve">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="return_to" value="detail">
                    <button type="submit" class="btn btn-primary">Duyệt yêu cầu</button>
                </form>
                <form method="post" action="/mvc/bookings/reject" class="reject-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
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

<a href="<?php echo $isStaff ? '/mvc/bookings/pending' : '/mvc/bookings'; ?>" class="btn btn-secondary">Quay lại danh sách</a>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
