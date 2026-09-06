<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app_foot.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';
 
require_login();
 
$rooms = $pdo->query(
    "SELECT id, ma_phong, ten_phong, vi_tri, suc_chua
     FROM rooms
     WHERE trang_thai = 'available'
     ORDER BY ten_phong"
)->fetchAll();
 
$equipmentList = $pdo->query(
    "SELECT id, ma_thiet_bi, ten_thiet_bi
     FROM equipment
     WHERE co_the_muon = 1 AND trang_thai = 'active'
     ORDER BY ten_thiet_bi"
)->fetchAll();
 
// Dữ liệu cũ + lỗi nếu vừa quay lại từ store.php do validate thất bại
$old = $_SESSION['booking_old'] ?? [];
unset($_SESSION['booking_old']);
$errors = $_SESSION['booking_errors'] ?? [];
unset($_SESSION['booking_errors']);
 
$loaiMacDinh = $old['loai_yeu_cau'] ?? ($_GET['loai'] ?? 'room');
if (!in_array($loaiMacDinh, ['room', 'equipment'], true)) {
    $loaiMacDinh = 'room';
}
 
$page_title = 'Đăng ký phòng / Mượn thiết bị';
$active_menu = 'booking';
require_once __DIR__ . '/../includes/app_head.php';
?>
 
<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
 
<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
 
<div class="chart-card" style="max-width:640px;">
    <h3>Thông tin yêu cầu</h3>
    <p class="chart-sub">
        Chọn loại yêu cầu, tài nguyên và khung giờ sử dụng. Yêu cầu sẽ ở trạng thái
        <strong>chờ duyệt</strong> cho tới khi cán bộ phòng lab xác nhận.
    </p>
 
    <form method="post" action="/bookings/store.php" id="bookingForm">
        <?php echo csrf_field(); ?>
 
        <div class="form-group">
            <label>Loại yêu cầu <span class="required">*</span></label>
            <select name="loai_yeu_cau" id="loaiYeuCau">
                <option value="room" <?php echo $loaiMacDinh === 'room' ? 'selected' : ''; ?>>Đặt phòng thực hành</option>
                <option value="equipment" <?php echo $loaiMacDinh === 'equipment' ? 'selected' : ''; ?>>Mượn thiết bị</option>
            </select>
        </div>
 
        <div class="form-group" id="roomGroup">
            <label>Phòng thực hành <span class="required">*</span></label>
            <select name="room_id" id="roomSelect">
                <option value="">-- Chọn phòng --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo (($old['room_id'] ?? '') == $r['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['ma_phong'] . ' - ' . $r['ten_phong'] . ' (' . $r['vi_tri'] . ', sức chứa ' . $r['suc_chua'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
 
        <div class="form-group" id="equipmentGroup" style="display:none;">
            <label>Thiết bị <span class="required">*</span></label>
            <select name="equipment_id" id="equipmentSelect">
                <option value="">-- Chọn thiết bị --</option>
                <?php foreach ($equipmentList as $e): ?>
                    <option value="<?php echo $e['id']; ?>" <?php echo (($old['equipment_id'] ?? '') == $e['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($e['ma_thiet_bi'] . ' - ' . $e['ten_thiet_bi']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
 
        <div class="form-group">
            <label>Thời gian bắt đầu <span class="required">*</span></label>
            <input type="datetime-local" name="thoi_gian_bat_dau" id="tgBatDau"
                   value="<?php echo htmlspecialchars($old['thoi_gian_bat_dau'] ?? ''); ?>">
        </div>
 
        <div class="form-group">
            <label>Thời gian kết thúc <span class="required">*</span></label>
            <input type="datetime-local" name="thoi_gian_ket_thuc" id="tgKetThuc"
                   value="<?php echo htmlspecialchars($old['thoi_gian_ket_thuc'] ?? ''); ?>">
        </div>
 
        <div class="form-group">
            <label>Mục đích sử dụng <span class="required">*</span></label>
            <textarea name="muc_dich" rows="3" maxlength="255"><?php echo htmlspecialchars($old['muc_dich'] ?? ''); ?></textarea>
        </div>
 
        <p class="field-hint" id="availabilityResult"></p>
 
        <button type="submit" class="btn btn-primary">Gửi yêu cầu</button>
        <a href="/bookings/my_requests.php" class="btn btn-secondary">Xem yêu cầu của tôi</a>
    </form>
</div>
 
<script>
(function () {
    var loaiSelect = document.getElementById('loaiYeuCau');
    var roomGroup = document.getElementById('roomGroup');
    var equipmentGroup = document.getElementById('equipmentGroup');
    var roomSelect = document.getElementById('roomSelect');
    var equipmentSelect = document.getElementById('equipmentSelect');
    var startInput = document.getElementById('tgBatDau');
    var endInput = document.getElementById('tgKetThuc');
    var resultBox = document.getElementById('availabilityResult');
 
    function toggleGroups() {
        var isRoom = loaiSelect.value === 'room';
        roomGroup.style.display = isRoom ? '' : 'none';
        equipmentGroup.style.display = isRoom ? 'none' : '';
        resultBox.textContent = '';
    }
 
    function checkAvailability() {
        var isRoom = loaiSelect.value === 'room';
        var resourceId = isRoom ? roomSelect.value : equipmentSelect.value;
        var start = startInput.value;
        var end = endInput.value;
 
        if (!resourceId || !start || !end) {
            resultBox.textContent = '';
            return;
        }
 
        var params = new URLSearchParams({
            loai_yeu_cau: loaiSelect.value,
            id: resourceId,
            start: start,
            end: end
        });
 
        fetch('/api/check_availability.php?' + params.toString())
            .then(function (res) { return res.json(); })
            .then(function (data) {
                resultBox.textContent = data.message || '';
                resultBox.style.color = data.available ? 'var(--color-success)' : 'var(--color-error)';
            })
            .catch(function () {
                resultBox.textContent = '';
            });
    }
 
    loaiSelect.addEventListener('change', function () {
        toggleGroups();
        checkAvailability();
    });
    [roomSelect, equipmentSelect, startInput, endInput].forEach(function (el) {
        el.addEventListener('change', checkAvailability);
    });
 
    toggleGroups();
})();
</script>
 
<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
