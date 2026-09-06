<?php
/**
 * app/Views/bookings/not_found.php
 * Hiển thị khi không tìm thấy yêu cầu hoặc người dùng không có quyền xem.
 * Biến truyền vào từ BookingController::detail(): $isStaff
 */
$page_title = 'Không tìm thấy yêu cầu';
$active_menu = 'my_requests';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<div class="alert alert-error">Không tìm thấy yêu cầu hoặc bạn không có quyền xem.</div>
<a class="btn btn-secondary" href="<?php echo $isStaff ? '/mvc/bookings/pending' : '/mvc/bookings'; ?>">Quay lại</a>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
