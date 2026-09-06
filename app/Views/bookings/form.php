<?php
/**
 * app/Views/bookings/form.php
 * Biến truyền vào từ BookingController::form():
 *   $id, $type, $roomId, $equipmentId, $startTime, $endTime, $purpose,
 *   $errors, $rooms, $equipment, $flashError, $flashSuccess
 */
$page_title = $id > 0 ? 'Sửa yêu cầu' : 'Đăng ký phòng / Mượn thiết bị';
$active_menu = 'booking';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<div class="page-heading">
    <div>
        <p class="breadcrumb">
            <a href="/mvc/bookings">Yêu cầu của tôi</a> /
            <?php echo $id > 0 ? 'Chỉnh sửa' : 'Tạo mới'; ?>
        </p>
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
<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
<?php endif; ?>

<section class="content-card form-card">

    <div class="chart-card">
        <h3>Thông tin yêu cầu</h3>
        <p class="chart-sub">
            Chọn loại yêu cầu, tài nguyên và khung giờ sử dụng.
            Yêu cầu sẽ ở trạng thái <strong>chờ duyệt</strong> cho tới khi cán bộ phòng lab xác nhận.
        </p>
    </div>

    <form method="post" action="/mvc/bookings/store" id="bookingForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <?php if ($id > 0): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="loai_yeu_cau">Loại yêu cầu <span class="required">*</span></label>
            <select id="loai_yeu_cau" name="loai_yeu_cau" required>
                <option value="room" <?php echo $type === 'room' ? 'selected' : ''; ?>>Đặt phòng thực hành</option>
                <option value="equipment" <?php echo $type === 'equipment' ? 'selected' : ''; ?>>Mượn thiết bị</option>
            </select>
        </div>

        <div class="form-group" id="roomGroup">
            <label for="room_id">Phòng thực hành <span class="required">*</span></label>
            <select id="room_id" name="room_id">
                <option value="">-- Chọn phòng --</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo (int) $room['id']; ?>" <?php echo $roomId === (int) $room['id'] ? 'selected' : ''; ?>>
                        <?php
                        echo htmlspecialchars(
                            $room['ma_phong'] . ' - ' . $room['ten_phong']
                            . ' (' . $room['vi_tri'] . ', sức chứa ' . ($room['suc_chua'] ?? '') . ')'
                        );
                        ?>
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
                <input type="datetime-local" id="thoi_gian_bat_dau" name="thoi_gian_bat_dau"
                       value="<?php echo htmlspecialchars($startTime); ?>" required>
            </div>
            <div class="form-group">
                <label for="thoi_gian_ket_thuc">Thời gian kết thúc <span class="required">*</span></label>
                <input type="datetime-local" id="thoi_gian_ket_thuc" name="thoi_gian_ket_thuc"
                       value="<?php echo htmlspecialchars($endTime); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="muc_dich">Mục đích sử dụng <span class="required">*</span></label>
            <textarea id="muc_dich" name="muc_dich" rows="4" maxlength="255" required><?php echo htmlspecialchars($purpose); ?></textarea>
            <p class="field-hint">Nhập từ 5 đến 255 ký tự.</p>
        </div>

        <p class="field-hint" id="availabilityResult"></p>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $id > 0 ? 'Cập nhật' : 'Gửi yêu cầu'; ?></button>
            <a href="/mvc/bookings" class="btn btn-secondary">Quay lại</a>
        </div>
    </form>

</section>

<script>
(function () {
    const typeSelect = document.getElementById('loai_yeu_cau');
    const roomGroup = document.getElementById('roomGroup');
    const equipmentGroup = document.getElementById('equipmentGroup');
    const roomSelect = document.getElementById('room_id');
    const equipmentSelect = document.getElementById('equipment_id');
    const startInput = document.getElementById('thoi_gian_bat_dau');
    const endInput = document.getElementById('thoi_gian_ket_thuc');
    const resultBox = document.getElementById('availabilityResult');

    function toggleGroups() {
        const isRoom = typeSelect.value === 'room';
        roomGroup.hidden = !isRoom;
        equipmentGroup.hidden = isRoom;
        roomSelect.required = isRoom;
        equipmentSelect.required = !isRoom;
        if (!isRoom) roomSelect.value = '';
        if (isRoom) equipmentSelect.value = '';
        resultBox.textContent = '';
    }

    function checkAvailability() {
        const isRoom = typeSelect.value === 'room';
        const resourceId = isRoom ? roomSelect.value : equipmentSelect.value;
        const start = startInput.value;
        const end = endInput.value;
        if (!resourceId || !start || !end) {
            resultBox.textContent = '';
            return;
        }
        const params = new URLSearchParams({ loai_yeu_cau: typeSelect.value, id: resourceId, start: start, end: end });
        fetch('/api/check_availability.php?' + params.toString())
            .then(function (res) { return res.json(); })
            .then(function (data) {
                resultBox.textContent = data.message || '';
                resultBox.style.color = data.available ? 'var(--color-success)' : 'var(--color-error)';
            })
            .catch(function () { resultBox.textContent = ''; });
    }

    typeSelect.addEventListener('change', function () { toggleGroups(); checkAvailability(); });
    [roomSelect, equipmentSelect, startInput, endInput].forEach(function (el) {
        el.addEventListener('change', checkAvailability);
    });

    toggleGroups();
    checkAvailability();
})();
</script>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
