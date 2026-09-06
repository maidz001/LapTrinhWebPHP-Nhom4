<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Room.php';

/**
 * app/Controllers/RoomController.php
 * ---------------------------------------------------------------------
 * Phiên bản MVC của rooms/list.php, rooms/form.php, rooms/save.php,
 * rooms/delete.php, rooms/export.php, rooms/import.php.
 *
 * Các file rooms/*.php gốc KHÔNG bị xoá hay sửa và vẫn hoạt động bình
 * thường; đây là bản song song để migrate dần (xem README_MVC.md).
 * Toàn bộ quy tắc nghiệp vụ (validate độ dài, mã phòng không trùng,
 * CSRF, phân quyền admin/lab_staff cho thao tác quản lý...) được giữ
 * NGUYÊN VẸN như bản gốc.
 * ---------------------------------------------------------------------
 */
final class RoomController extends Controller
{
    /** GET /mvc/rooms — danh sách phòng, mọi người dùng đã đăng nhập xem được. */
    public function index(): void
    {
        require_login();
        $user = current_user();
        $canManage = in_array($user['role'] ?? '', ['admin', 'lab_staff'], true);

        $q = trim((string) $this->input('q', ''));
        $rooms = (new Room())->all($q);

        $this->view('rooms/index', [
            'rooms' => $rooms,
            'q' => $q,
            'canManage' => $canManage,
            'flashSuccess' => flash_get('success'),
            'flashError' => flash_get('error'),
        ]);
    }

    /** GET /mvc/rooms/form — form thêm mới / sửa (?id=X để sửa). */
    public function form(): void
    {
        require_role(['admin', 'lab_staff']);

        $id = $this->idParam('id');
        $room = null;

        if ($id) {
            $room = (new Room())->find($id);
            if (!$room) {
                flash_set('error', 'Không tìm thấy phòng cần sửa.');
                $this->redirect('/mvc/rooms');
            }
        }

        $old = $_SESSION['mvc_room_old'] ?? $room ?? [];
        unset($_SESSION['mvc_room_old']);
        $errors = $_SESSION['mvc_room_errors'] ?? [];
        unset($_SESSION['mvc_room_errors']);

        $this->view('rooms/form', [
            'id' => $id,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    /** POST /mvc/rooms/save — xử lý thêm mới / cập nhật. */
    public function save(): void
    {
        require_role(['admin', 'lab_staff']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/rooms');
        }

        $id = $this->idParam('id');

        $old = [
            'ma_phong' => trim((string) $this->input('ma_phong', '')),
            'ten_phong' => trim((string) $this->input('ten_phong', '')),
            'vi_tri' => trim((string) $this->input('vi_tri', '')),
            'suc_chua' => $this->input('suc_chua', ''),
            'trang_thai' => $this->input('trang_thai', ''),
            'mo_ta' => trim((string) $this->input('mo_ta', '')),
        ];

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            $this->backWithErrors(['Phiên làm việc đã hết hạn, vui lòng thử lại.'], $old, $id);
        }

        $errors = [];
        $roomModel = new Room();

        if ($old['ma_phong'] === '' || mb_strlen($old['ma_phong']) > 20) {
            $errors[] = 'Mã phòng không được để trống và tối đa 20 ký tự.';
        }
        if ($old['ten_phong'] === '' || mb_strlen($old['ten_phong']) > 100) {
            $errors[] = 'Tên phòng không được để trống và tối đa 100 ký tự.';
        }
        if ($old['vi_tri'] === '' || mb_strlen($old['vi_tri']) > 150) {
            $errors[] = 'Vị trí không được để trống và tối đa 150 ký tự.';
        }
        if (!ctype_digit((string) $old['suc_chua']) || (int) $old['suc_chua'] < 1) {
            $errors[] = 'Sức chứa phải là số nguyên lớn hơn 0.';
        }
        if (!in_array($old['trang_thai'], Room::TRANG_THAI_HOP_LE, true)) {
            $errors[] = 'Trạng thái không hợp lệ.';
        }
        if ($old['ma_phong'] !== '' && $roomModel->codeExists($old['ma_phong'], $id)) {
            $errors[] = 'Mã phòng này đã tồn tại.';
        }

        if ($errors) {
            $this->backWithErrors($errors, $old, $id);
        }

        if ($id) {
            $roomModel->update($id, $old);
            flash_set('success', 'Đã cập nhật phòng thực hành.');
        } else {
            $roomModel->create($old);
            flash_set('success', 'Đã thêm phòng thực hành mới.');
        }

        $this->redirect('/mvc/rooms');
    }

    /** POST /mvc/rooms/delete */
    public function delete(): void
    {
        require_role(['admin', 'lab_staff']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/rooms');
        }

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/rooms');
        }

        $id = $this->input('id', '');
        if (!ctype_digit((string) $id)) {
            flash_set('error', 'Phòng không hợp lệ.');
            $this->redirect('/mvc/rooms');
        }

        try {
            $deleted = (new Room())->delete((int) $id);
            flash_set($deleted ? 'success' : 'error', $deleted
                ? 'Đã xoá phòng thực hành.'
                : 'Không tìm thấy phòng cần xoá.');
        } catch (PDOException $e) {
            flash_set('error', 'Không thể xoá phòng này vì đang có thiết bị hoặc yêu cầu đặt phòng liên quan.');
        }

        $this->redirect('/mvc/rooms');
    }

    /** GET /mvc/rooms/export — xuất CSV, cột khớp với import(). */
    public function export(): void
    {
        require_login();

        $q = trim((string) $this->input('q', ''));
        $rooms = (new Room())->all($q);

        $tenFile = 'danh_sach_phong_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $tenFile . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ma_phong', 'ten_phong', 'vi_tri', 'suc_chua', 'trang_thai', 'mo_ta']);

        foreach ($rooms as $r) {
            fputcsv($output, [
                $r['ma_phong'],
                $r['ten_phong'],
                $r['vi_tri'],
                $r['suc_chua'],
                $r['trang_thai'],
                $r['mo_ta'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /** GET /mvc/rooms/import — form tải file CSV lên. */
    public function showImport(): void
    {
        require_role(['admin', 'lab_staff']);

        $this->view('rooms/import', ['ketQua' => null]);
    }

    /** POST /mvc/rooms/import — xử lý file CSV, giống hệt rooms/import.php gốc. */
    public function import(): void
    {
        require_role(['admin', 'lab_staff']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/rooms/import');
        }

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/rooms/import');
        }

        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'Vui lòng chọn một file CSV hợp lệ để tải lên.');
            $this->redirect('/mvc/rooms/import');
        }

        $handle = fopen($_FILES['file']['tmp_name'], 'r');
        if ($handle === false) {
            flash_set('error', 'Không thể đọc file đã tải lên.');
            $this->redirect('/mvc/rooms/import');
        }

        // Bỏ qua BOM UTF-8 ở đầu file nếu có.
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $roomModel = new Room();
        $soDong = 0;
        $themThanhCong = 0;
        $loi = [];
        $first = true;

        while (($row = fgetcsv($handle)) !== false) {
            $soDong++;

            if ($first) {
                $first = false;
                $header = array_map(fn($v) => mb_strtolower(trim((string) $v)), $row);
                if (in_array('ma_phong', $header, true) || in_array('mã phòng', $header, true)) {
                    continue;
                }
            }

            if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $ma_phong = trim((string) ($row[0] ?? ''));
            $ten_phong = trim((string) ($row[1] ?? ''));
            $vi_tri = trim((string) ($row[2] ?? ''));
            $suc_chua = trim((string) ($row[3] ?? ''));
            $trang_thai = trim((string) ($row[4] ?? '')) ?: 'available';
            $mo_ta = trim((string) ($row[5] ?? ''));

            $dongLoi = [];
            if ($ma_phong === '' || mb_strlen($ma_phong) > 20) {
                $dongLoi[] = 'mã phòng trống hoặc quá 20 ký tự';
            }
            if ($ten_phong === '' || mb_strlen($ten_phong) > 100) {
                $dongLoi[] = 'tên phòng trống hoặc quá 100 ký tự';
            }
            if ($vi_tri === '' || mb_strlen($vi_tri) > 150) {
                $dongLoi[] = 'vị trí trống hoặc quá 150 ký tự';
            }
            if (!ctype_digit($suc_chua) || (int) $suc_chua < 1) {
                $dongLoi[] = 'sức chứa phải là số nguyên lớn hơn 0';
            }
            if (!in_array($trang_thai, Room::TRANG_THAI_HOP_LE, true)) {
                $dongLoi[] = 'trạng thái không hợp lệ';
            }
            if (empty($dongLoi) && $ma_phong !== '' && $roomModel->codeExists($ma_phong)) {
                $dongLoi[] = 'mã phòng "' . $ma_phong . '" đã tồn tại';
            }

            if (!empty($dongLoi)) {
                $loi[] = 'Dòng ' . $soDong . ': ' . implode(', ', $dongLoi) . '.';
                continue;
            }

            $roomModel->create([
                'ma_phong' => $ma_phong,
                'ten_phong' => $ten_phong,
                'vi_tri' => $vi_tri,
                'suc_chua' => (int) $suc_chua,
                'trang_thai' => $trang_thai,
                'mo_ta' => $mo_ta,
            ]);
            $themThanhCong++;
        }

        fclose($handle);

        $ketQua = ['them' => $themThanhCong, 'loi' => $loi];

        if ($themThanhCong > 0 && empty($loi)) {
            flash_set('success', "Đã thêm {$themThanhCong} phòng từ file thành công.");
            $this->redirect('/mvc/rooms');
        }

        $this->view('rooms/import', ['ketQua' => $ketQua]);
    }

    private function idParam(string $key): ?int
    {
        $v = $this->input($key);
        return ($v !== null && ctype_digit((string) $v)) ? (int) $v : null;
    }

    private function stringInput(string $key): ?string
    {
        $v = $this->input($key);
        return is_string($v) ? $v : null;
    }

    private function backWithErrors(array $errors, array $old, ?int $id): void
    {
        $_SESSION['mvc_room_errors'] = $errors;
        $_SESSION['mvc_room_old'] = $old;
        $this->redirect('/mvc/rooms/form' . ($id ? ('?id=' . $id) : ''));
    }
}
