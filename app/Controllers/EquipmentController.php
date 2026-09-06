<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Equipment.php';
require_once __DIR__ . '/../Models/EquipmentType.php';
require_once __DIR__ . '/../Models/Room.php';

/**
 * app/Controllers/EquipmentController.php
 * ---------------------------------------------------------------------
 * Phiên bản MVC của equipment/list.php, equipment/form.php,
 * equipment/save.php, equipment/delete.php, equipment/export.php,
 * equipment/import.php, và equipment_types/list.php (Danh mục thiết bị).
 *
 * Các file equipment/*.php, equipment_types/*.php gốc KHÔNG bị xoá hay
 * sửa và vẫn hoạt động bình thường; đây là bản song song để migrate dần
 * (xem README_MVC.md). equipment/handover.php được thêm ở đây trong
 * Phase 3 (đi cùng đợt với Bookings vì cùng ảnh hưởng tới việc kiểm tra
 * tài nguyên khi tạo yêu cầu mượn thiết bị).
 * ---------------------------------------------------------------------
 */
final class EquipmentController extends Controller
{
    /** GET /mvc/equipment — danh sách thiết bị. */
    public function index(): void
    {
        require_login();
        $user = current_user();
        $canManage = in_array($user['role'] ?? '', ['admin', 'lab_staff'], true);

        $typeFilter = $this->idParam('type_id');
        $statusFilter = (string) $this->input('trang_thai', 'all');
        if (!in_array($statusFilter, array_merge(['all'], Equipment::TRANG_THAI_HOP_LE), true)) {
            $statusFilter = 'all';
        }
        $q = trim((string) $this->input('q', ''));

        $equipmentList = (new Equipment())->all([
            'type_id' => $typeFilter,
            'trang_thai' => $statusFilter,
            'q' => $q,
        ]);
        $types = (new EquipmentType())->all();

        $this->view('equipment/index', [
            'equipmentList' => $equipmentList,
            'types' => $types,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
            'q' => $q,
            'canManage' => $canManage,
            'flashSuccess' => flash_get('success'),
            'flashError' => flash_get('error'),
        ]);
    }

    /** GET /mvc/equipment/form — form thêm mới / sửa (?id=X để sửa). */
    public function form(): void
    {
        require_role(['admin', 'lab_staff']);

        $id = $this->idParam('id');
        $equipment = null;

        if ($id) {
            $equipment = (new Equipment())->find($id);
            if (!$equipment) {
                flash_set('error', 'Không tìm thấy thiết bị cần sửa.');
                $this->redirect('/mvc/equipment');
            }
        }

        $old = $_SESSION['mvc_equipment_old'] ?? $equipment ?? [];
        unset($_SESSION['mvc_equipment_old']);
        $errors = $_SESSION['mvc_equipment_errors'] ?? [];
        unset($_SESSION['mvc_equipment_errors']);

        $this->view('equipment/form', [
            'id' => $id,
            'old' => $old,
            'errors' => $errors,
            'types' => (new EquipmentType())->all(),
            'rooms' => (new Room())->allForDropdown(),
        ]);
    }

    /** POST /mvc/equipment/save */
    public function save(): void
    {
        require_role(['admin', 'lab_staff']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/equipment');
        }

        $id = $this->idParam('id');

        $old = [
            'ma_thiet_bi' => trim((string) $this->input('ma_thiet_bi', '')),
            'ten_thiet_bi' => trim((string) $this->input('ten_thiet_bi', '')),
            'type_id' => $this->input('type_id', ''),
            'room_id' => $this->input('room_id', ''),
            'co_the_muon' => $this->input('co_the_muon') !== null ? 1 : 0,
            'trang_thai' => $this->input('trang_thai', ''),
            'ngay_mua' => trim((string) $this->input('ngay_mua', '')),
            'mo_ta' => trim((string) $this->input('mo_ta', '')),
        ];

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            $this->backWithErrors(['Phiên làm việc đã hết hạn, vui lòng thử lại.'], $old, $id);
        }

        $errors = [];
        $equipmentModel = new Equipment();
        $typeModel = new EquipmentType();

        if ($old['ma_thiet_bi'] === '' || mb_strlen($old['ma_thiet_bi']) > 30) {
            $errors[] = 'Mã thiết bị không được để trống và tối đa 30 ký tự.';
        }
        if ($old['ten_thiet_bi'] === '' || mb_strlen($old['ten_thiet_bi']) > 150) {
            $errors[] = 'Tên thiết bị không được để trống và tối đa 150 ký tự.';
        }
        if (!in_array($old['trang_thai'], Equipment::TRANG_THAI_HOP_LE, true)) {
            $errors[] = 'Trạng thái không hợp lệ.';
        }

        $typeId = ctype_digit((string) $old['type_id']) ? (int) $old['type_id'] : null;
        if (!$typeId) {
            $errors[] = 'Vui lòng chọn loại thiết bị.';
        } elseif (!$typeModel->exists($typeId)) {
            $errors[] = 'Loại thiết bị không tồn tại.';
        }

        $roomId = null;
        if ($old['room_id'] !== '') {
            $roomId = ctype_digit((string) $old['room_id']) ? (int) $old['room_id'] : null;
            if (!$roomId || !(new Room())->find($roomId)) {
                $errors[] = 'Phòng đã chọn không hợp lệ hoặc không tồn tại.';
                $roomId = null;
            }
        }

        $ngayMua = null;
        if ($old['ngay_mua'] !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $old['ngay_mua']);
            if (!$d || $d->format('Y-m-d') !== $old['ngay_mua']) {
                $errors[] = 'Ngày mua không hợp lệ.';
            } else {
                $ngayMua = $old['ngay_mua'];
            }
        }

        if ($old['ma_thiet_bi'] !== '' && $equipmentModel->codeExists($old['ma_thiet_bi'], $id)) {
            $errors[] = 'Mã thiết bị này đã tồn tại.';
        }

        if ($errors) {
            $this->backWithErrors($errors, $old, $id);
        }

        $data = [
            'ma_thiet_bi' => $old['ma_thiet_bi'],
            'ten_thiet_bi' => $old['ten_thiet_bi'],
            'type_id' => $typeId,
            'room_id' => $roomId,
            'co_the_muon' => $old['co_the_muon'],
            'trang_thai' => $old['trang_thai'],
            'ngay_mua' => $ngayMua,
            'mo_ta' => $old['mo_ta'] !== '' ? $old['mo_ta'] : null,
        ];

        if ($id) {
            $equipmentModel->update($id, $data);
            flash_set('success', 'Đã cập nhật thiết bị.');
        } else {
            $equipmentModel->create($data);
            flash_set('success', 'Đã thêm thiết bị mới.');
        }

        $this->redirect('/mvc/equipment');
    }

    /** POST /mvc/equipment/delete */
    public function delete(): void
    {
        require_role(['admin', 'lab_staff']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/equipment');
        }

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/equipment');
        }

        $id = $this->input('id', '');
        if (!ctype_digit((string) $id)) {
            flash_set('error', 'Thiết bị không hợp lệ.');
            $this->redirect('/mvc/equipment');
        }

        try {
            $deleted = (new Equipment())->delete((int) $id);
            flash_set($deleted ? 'success' : 'error', $deleted
                ? 'Đã xoá thiết bị. Các báo hỏng liên quan cũng đã được xoá theo.'
                : 'Không tìm thấy thiết bị cần xoá.');
        } catch (PDOException $e) {
            flash_set('error', 'Không thể xoá thiết bị này vì đã có lịch sử mượn liên quan.');
        }

        $this->redirect('/mvc/equipment');
    }

    /** GET /mvc/equipment/export */
    public function export(): void
    {
        require_login();

        $typeFilter = $this->idParam('type_id');
        $statusFilter = (string) $this->input('trang_thai', 'all');
        if (!in_array($statusFilter, array_merge(['all'], Equipment::TRANG_THAI_HOP_LE), true)) {
            $statusFilter = 'all';
        }
        $q = trim((string) $this->input('q', ''));

        $equipmentList = (new Equipment())->all([
            'type_id' => $typeFilter,
            'trang_thai' => $statusFilter,
            'q' => $q,
        ]);

        $tenFile = 'danh_sach_thiet_bi_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $tenFile . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ma_thiet_bi', 'ten_thiet_bi', 'ten_loai', 'ma_phong', 'co_the_muon', 'trang_thai', 'ngay_mua', 'mo_ta']);

        foreach ($equipmentList as $e) {
            fputcsv($output, [
                $e['ma_thiet_bi'],
                $e['ten_thiet_bi'],
                $e['ten_loai'],
                $e['ma_phong'] ?? '',
                $e['co_the_muon'] ? '1' : '0',
                $e['trang_thai'],
                $e['ngay_mua'] ?? '',
                $e['mo_ta'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /** GET /mvc/equipment/import */
    public function showImport(): void
    {
        require_role(['admin', 'lab_staff']);

        $this->view('equipment/import', ['ketQua' => null]);
    }

    /** POST /mvc/equipment/import — xử lý file CSV, giống hệt equipment/import.php gốc. */
    public function import(): void
    {
        require_role(['admin', 'lab_staff']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/equipment/import');
        }

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/equipment/import');
        }

        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'Vui lòng chọn một file CSV hợp lệ để tải lên.');
            $this->redirect('/mvc/equipment/import');
        }

        $handle = fopen($_FILES['file']['tmp_name'], 'r');
        if ($handle === false) {
            flash_set('error', 'Không thể đọc file đã tải lên.');
            $this->redirect('/mvc/equipment/import');
        }

        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $equipmentModel = new Equipment();
        $typeMap = (new EquipmentType())->nameToIdMap();

        $roomMap = [];
        foreach ((new Room())->allForDropdown() as $r) {
            $roomMap[mb_strtolower(trim((string) $r['ma_phong']))] = (int) $r['id'];
        }

        $soDong = 0;
        $themThanhCong = 0;
        $loi = [];
        $first = true;

        while (($row = fgetcsv($handle)) !== false) {
            $soDong++;

            if ($first) {
                $first = false;
                $header = array_map(fn($v) => mb_strtolower(trim((string) $v)), $row);
                if (in_array('ma_thiet_bi', $header, true) || in_array('mã thiết bị', $header, true)) {
                    continue;
                }
            }

            if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $ma_thiet_bi = trim((string) ($row[0] ?? ''));
            $ten_thiet_bi = trim((string) ($row[1] ?? ''));
            $ten_loai = trim((string) ($row[2] ?? ''));
            $ma_phong = trim((string) ($row[3] ?? ''));
            $co_the_muon = trim((string) ($row[4] ?? '')) === '1' ? 1 : 0;
            $trang_thai = trim((string) ($row[5] ?? '')) ?: 'active';
            $ngay_mua = trim((string) ($row[6] ?? ''));
            $mo_ta = trim((string) ($row[7] ?? ''));

            $dongLoi = [];
            if ($ma_thiet_bi === '' || mb_strlen($ma_thiet_bi) > 30) {
                $dongLoi[] = 'mã thiết bị trống hoặc quá 30 ký tự';
            }
            if ($ten_thiet_bi === '' || mb_strlen($ten_thiet_bi) > 150) {
                $dongLoi[] = 'tên thiết bị trống hoặc quá 150 ký tự';
            }
            if (!in_array($trang_thai, Equipment::TRANG_THAI_HOP_LE, true)) {
                $dongLoi[] = 'trạng thái không hợp lệ';
            }

            $typeId = null;
            if ($ten_loai === '') {
                $dongLoi[] = 'thiếu tên danh mục/loại thiết bị';
            } else {
                $typeId = $typeMap[mb_strtolower($ten_loai)] ?? null;
                if ($typeId === null) {
                    $dongLoi[] = 'không tìm thấy danh mục "' . $ten_loai . '"';
                }
            }

            $roomId = null;
            if ($ma_phong !== '') {
                $roomId = $roomMap[mb_strtolower($ma_phong)] ?? null;
                if ($roomId === null) {
                    $dongLoi[] = 'không tìm thấy phòng "' . $ma_phong . '"';
                }
            }

            $ngayMuaHopLe = null;
            if ($ngay_mua !== '') {
                $d = DateTime::createFromFormat('Y-m-d', $ngay_mua);
                if (!$d || $d->format('Y-m-d') !== $ngay_mua) {
                    $dongLoi[] = 'ngày mua không đúng định dạng YYYY-MM-DD';
                } else {
                    $ngayMuaHopLe = $ngay_mua;
                }
            }

            if (empty($dongLoi) && $ma_thiet_bi !== '' && $equipmentModel->codeExists($ma_thiet_bi)) {
                $dongLoi[] = 'mã thiết bị "' . $ma_thiet_bi . '" đã tồn tại';
            }

            if (!empty($dongLoi)) {
                $loi[] = 'Dòng ' . $soDong . ': ' . implode(', ', $dongLoi) . '.';
                continue;
            }

            $equipmentModel->create([
                'ma_thiet_bi' => $ma_thiet_bi,
                'ten_thiet_bi' => $ten_thiet_bi,
                'type_id' => $typeId,
                'room_id' => $roomId,
                'co_the_muon' => $co_the_muon,
                'trang_thai' => $trang_thai,
                'ngay_mua' => $ngayMuaHopLe,
                'mo_ta' => $mo_ta !== '' ? $mo_ta : null,
            ]);
            $themThanhCong++;
        }

        fclose($handle);

        $ketQua = ['them' => $themThanhCong, 'loi' => $loi];

        if ($themThanhCong > 0 && empty($loi)) {
            flash_set('success', "Đã thêm {$themThanhCong} thiết bị từ file thành công.");
            $this->redirect('/mvc/equipment');
        }

        $this->view('equipment/import', ['ketQua' => $ketQua]);
    }

    /** GET /mvc/equipment-types — Danh mục thiết bị (chỉ xem, không CRUD). */
    public function types(): void
    {
        require_role(['admin', 'lab_staff']);

        $this->view('equipment/types', [
            'danhMucList' => (new EquipmentType())->allWithStats(),
        ]);
    }

    /**
     * GET /mvc/equipment/handover — bàn giao thiết bị (Phase 3).
     * Bản MVC của equipment/handover.php gốc (tách phần hiển thị GET ra
     * khỏi xử lý POST, giống cách showImport()/import() đã làm ở trên).
     */
    public function showHandover(): void
    {
        require_role(['admin', 'lab_staff']);

        $this->view('equipment/handover', [
            'equipmentList' => (new Equipment())->allWithRoom(),
            'rooms' => (new Room())->allForDropdown(),
        ]);
    }

    /** POST /mvc/equipment/handover — xử lý bàn giao, giống hệt equipment/handover.php gốc. */
    public function handover(): void
    {
        require_role(['admin', 'lab_staff']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/equipment/handover');
        }

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/equipment/handover');
        }

        $equipmentId = $this->input('equipment_id', '');
        $newRoomId = trim((string) $this->input('room_id', ''));

        if (!ctype_digit((string) $equipmentId)) {
            flash_set('error', 'Vui lòng chọn thiết bị cần bàn giao.');
            $this->redirect('/mvc/equipment/handover');
        }
        $equipmentId = (int) $equipmentId;

        $equipmentModel = new Equipment();
        $equipment = $equipmentModel->findWithCurrentRoom($equipmentId);

        if (!$equipment) {
            flash_set('error', 'Không tìm thấy thiết bị.');
            $this->redirect('/mvc/equipment/handover');
        }

        $newRoomIdInt = null;
        $tenPhongMoi = 'thiết bị lưu động (không gắn phòng)';

        if ($newRoomId !== '') {
            if (!ctype_digit($newRoomId)) {
                flash_set('error', 'Phòng đích không hợp lệ.');
                $this->redirect('/mvc/equipment/handover');
            }
            $newRoomIdInt = (int) $newRoomId;

            $room = (new Room())->find($newRoomIdInt);
            if (!$room) {
                flash_set('error', 'Phòng đích không tồn tại.');
                $this->redirect('/mvc/equipment/handover');
            }
            $tenPhongMoi = $room['ma_phong'] . ' - ' . $room['ten_phong'];
        }

        if ($newRoomIdInt === $equipment['room_id']) {
            flash_set('error', 'Thiết bị đã ở đúng vị trí này rồi, không cần bàn giao.');
            $this->redirect('/mvc/equipment/handover');
        }

        $equipmentModel->moveToRoom($equipmentId, $newRoomIdInt);

        flash_set(
            'success',
            'Đã chuyển thiết bị "' . $equipment['ten_thiet_bi'] . '" từ '
            . ($equipment['ma_phong_cu'] ?? 'thiết bị lưu động') . ' sang ' . $tenPhongMoi . '.'
        );
        $this->redirect('/mvc/equipment/handover');
    }

    private function idParam(string $key): ?int
    {
        $v = $this->input($key);
        return ($v !== null && $v !== '' && ctype_digit((string) $v)) ? (int) $v : null;
    }

    private function stringInput(string $key): ?string
    {
        $v = $this->input($key);
        return is_string($v) ? $v : null;
    }

    private function backWithErrors(array $errors, array $old, ?int $id): void
    {
        $_SESSION['mvc_equipment_errors'] = $errors;
        $_SESSION['mvc_equipment_old'] = $old;
        $this->redirect('/mvc/equipment/form' . ($id ? ('?id=' . $id) : ''));
    }
}
