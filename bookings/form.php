<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/repository.php';

require_login();

$user = current_user();
$id = max(0, (int) ($_GET['id'] ?? 0));
$booking = null;

if ($id > 0) {
    $booking = findBookingById($pdo, $id);
    if (!$booking || (int) $booking['user_id'] !== (int) $user['id'] || $booking['trang_thai'] !== 'pending') {
        flash_set('error', 'Yêu cầu không tồn tại hoặc không còn được phép sửa.');
        header('Location: /bookings/my_requests.php');
        exit;
    }
}

$errors = $_SESSION['booking_form_errors'] ?? [];
$formData = $_SESSION['booking_form_data'] ?? [];
unset($_SESSION['booking_form_errors'], $_SESSION['booking_form_data']);

$type = (string) ($formData['type'] ?? ($booking['loai_yeu_cau'] ?? 'room'));
$roomId = (int) ($formData['room_id'] ?? ($booking['room_id'] ?? 0));
$equipmentId = (int) ($formData['equipment_id'] ?? ($booking['equipment_id'] ?? 0));
$startTime = (string) ($formData['start_time'] ?? ($booking['thoi_gian_bat_dau'] ?? ''));
$endTime = (string) ($formData['end_time'] ?? ($booking['thoi_gian_ket_thuc'] ?? ''));
$purpose = (string) ($formData['purpose'] ?? ($booking['muc_dich'] ?? ''));

if ($startTime !== '' && !str_contains($startTime, 'T')) {
    $startTime = date('Y-m-d\TH:i', strtotime($startTime));
}
if ($endTime !== '' && !str_contains($endTime, 'T')) {
    $endTime = date('Y-m-d\TH:i', strtotime($endTime));
}

$rooms = findAvailableRooms($pdo);
$equipment = findBorrowableEquipment($pdo);
$flashError = flash_get('error');
$page_title = $id > 0 ? 'Sửa yêu cầu' : 'Tạo yêu cầu';
$active_menu = 'booking';
require_once __DIR__ . '/../includes/app_head.php';
?>

<div class="page-heading">
    <div>
        <p class="breadcrumb"><a href="/bookings/my_requests.php">Yêu cầu của tôi</a> / <?php echo $id > 0 ? 'Chỉnh sửa' : 'Tạo mới'; ?></p>
        <h2><?php echo $id > 0 ? 'Chỉnh sửa yêu cầu' : 'Tạo yêu cầu sử dụng'; ?></h2>
        <p>Chọn phòng hoặc thiết bị, nhập thời gian và mục đích sử dụng.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars((string) $error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
<?php endif; ?>

<section class="content-card form-card">
    <form method="post" action="/bookings/store.php" id="bookingForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="form-group">
            <label for="loai_yeu_cau">Loại yêu cầu <span class="required">*</span></label>
            <select id="loai_yeu_cau" name="loai_yeu_cau" required>
                <option value="room" <?php echo $type === 'room' ? 'selected' : ''; ?>>Đặt phòng</option>
                <option value="equipment" <?php echo $type === 'equipment' ? 'selected' : ''; ?>>Mượn thiết bị</option>
            </select>
        </div>

        <div class="form-group" id="roomGroup">
            <label for="room_id">Phòng <span class="required">*</span></label>
            <select id="room_id" name="room_id">
                <option value="">-- Chọn phòng --</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo (int) $room['id']; ?>" <?php echo $roomId === (int) $room['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($room['ma_phong'] . ' - ' . $room['ten_phong'] . ' (' . $room['vi_tri'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" id="equipmentGroup">
            <label for="equipment_id">Thiết bị <span class="required">*</span></label>
            <select id="equipment_id" name="equipment_id">
                <option value="">-- Chọn thiết bị --</option>
                <?php foreach ($equipment as $item): ?>
                    <option value="<?php echo (int) $item['id']; ?>" <?php echo $equipmentId === (int) $item['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($item['ma_thiet_bi'] . ' - ' . $item['ten_thiet_bi']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grid-two">
            <div class="form-group">
                <label for="thoi_gian_bat_dau">Thời gian bắt đầu <span class="required">*</span></label>
                <input type="datetime-local" id="thoi_gian_bat_dau" name="thoi_gian_bat_dau" value="<?php echo htmlspecialchars($startTime); ?>" required>
            </div>
            <div class="form-group">
                <label for="thoi_gian_ket_thuc">Thời gian kết thúc <span class="required">*</span></label>
                <input type="datetime-local" id="thoi_gian_ket_thuc" name="thoi_gian_ket_thuc" value="<?php echo htmlspecialchars($endTime); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="muc_dich">Mục đích sử dụng <span class="required">*</span></label>
            <textarea id="muc_dich" name="muc_dich" rows="4" maxlength="255" required><?php echo htmlspecialchars($purpose); ?></textarea>
            <p class="field-hint">Nhập từ 5 đến 255 ký tự.</p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $id > 0 ? 'Cập nhật' : 'Gửi yêu cầu'; ?></button>
            <a href="/bookings/my_requests.php" class="btn btn-secondary">Quay lại</a>
        </div>
    </form>
</section>

<script>
const typeSelect = document.getElementById('loai_yeu_cau');
const roomGroup = document.getElementById('roomGroup');
const equipmentGroup = document.getElementById('equipmentGroup');
const roomSelect = document.getElementById('room_id');
const equipmentSelect = document.getElementById('equipment_id');

function showResourceField() {
    const isRoom = typeSelect.value === 'room';
    roomGroup.hidden = !isRoom;
    equipmentGroup.hidden = isRoom;
    roomSelect.required = isRoom;
    equipmentSelect.required = !isRoom;
}

typeSelect.addEventListener('change', showResourceField);
showResourceField();
</script>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
