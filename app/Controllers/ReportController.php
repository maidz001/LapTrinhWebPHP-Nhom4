<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Report.php';

/**
 * app/Controllers/ReportController.php
 * ---------------------------------------------------------------------
 * Phiên bản MVC của reports/index.php, reports/create.php,
 * reports/store.php, reports/update_status.php.
 *
 * Các file reports/*.php gốc KHÔNG bị xoá hay sửa và vẫn hoạt động bình
 * thường; đây là bản song song để migrate dần (xem README_MVC.md).
 * Toàn bộ quy tắc nghiệp vụ (người dùng thường chỉ thấy báo cáo của
 * mình, chỉ admin/lab_staff cập nhật trạng thái, đồng bộ trạng thái
 * thiết bị theo báo cáo mở, CSRF...) được giữ NGUYÊN VẸN như bản gốc.
 * ---------------------------------------------------------------------
 */
final class ReportController extends Controller
{
    /** GET /mvc/reports — danh sách báo cáo (lọc theo vai trò + trạng thái). */
    public function index(): void
    {
        require_login();
        $user = current_user();
        $canManage = in_array($user['role'] ?? '', ['admin', 'lab_staff'], true);

        $statusFilter = (string) $this->input('trang_thai', 'all');
        if (!in_array($statusFilter, array_merge(['all'], Report::TRANG_THAI_HOP_LE), true)) {
            $statusFilter = 'all';
        }

        $reports = (new Report())->all($statusFilter, $canManage ? null : (int) $user['id']);

        $this->view('reports/index', [
            'reports' => $reports,
            'statusFilter' => $statusFilter,
            'canManage' => $canManage,
            'flashSuccess' => flash_get('success'),
            'flashError' => flash_get('error'),
        ]);
    }

    /** GET /mvc/reports/create — form báo hỏng, có thể mở kèm ?equipment_id=X. */
    public function create(): void
    {
        require_login();

        $equipmentList = (new Report())->equipmentOptions();

        $old = $_SESSION['mvc_report_old'] ?? [];
        unset($_SESSION['mvc_report_old']);
        $errors = $_SESSION['mvc_report_errors'] ?? [];
        unset($_SESSION['mvc_report_errors']);

        $preselectId = $old['equipment_id'] ?? $this->input('equipment_id', '');

        $this->view('reports/create', [
            'equipmentList' => $equipmentList,
            'old' => $old,
            'errors' => $errors,
            'preselectId' => $preselectId,
        ]);
    }

    /** POST /mvc/reports/store — xử lý lưu báo hỏng. */
    public function store(): void
    {
        require_login();

        if (!$this->isPost()) {
            $this->redirect('/mvc/reports/create');
        }

        $old = [
            'equipment_id' => $this->input('equipment_id', ''),
            'mo_ta_su_co' => trim((string) $this->input('mo_ta_su_co', '')),
            'muc_do' => $this->input('muc_do', ''),
        ];

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            $this->backWithErrors(['Phiên làm việc đã hết hạn, vui lòng thử lại.'], $old);
        }

        $errors = [];
        $reportModel = new Report();

        $equipmentId = ctype_digit((string) $old['equipment_id']) ? (int) $old['equipment_id'] : null;
        if (!$equipmentId) {
            $errors[] = 'Vui lòng chọn thiết bị cần báo hỏng.';
        } elseif (!$reportModel->equipmentExists($equipmentId)) {
            $errors[] = 'Thiết bị đã chọn không tồn tại.';
        }

        if ($old['mo_ta_su_co'] === '') {
            $errors[] = 'Vui lòng mô tả sự cố.';
        }
        if (!in_array($old['muc_do'], Report::MUC_DO_HOP_LE, true)) {
            $errors[] = 'Mức độ không hợp lệ.';
        }

        if ($errors) {
            $this->backWithErrors($errors, $old);
        }

        $user = current_user();
        $reportModel->create((int) $equipmentId, (int) $user['id'], $old['mo_ta_su_co'], $old['muc_do']);

        flash_set('success', 'Đã gửi báo cáo sự cố thành công.');
        $this->redirect('/mvc/reports');
    }

    /** POST /mvc/reports/update-status — chỉ admin/lab_staff. */
    public function updateStatus(): void
    {
        require_role(['admin', 'lab_staff']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/reports');
        }

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/reports');
        }

        $id = $this->input('id', '');
        $trangThai = (string) $this->input('trang_thai', '');

        if (!ctype_digit((string) $id) || !in_array($trangThai, Report::TRANG_THAI_HOP_LE, true)) {
            flash_set('error', 'Dữ liệu cập nhật không hợp lệ.');
            $this->redirect('/mvc/reports');
        }

        $reportModel = new Report();
        $equipmentId = $reportModel->findEquipmentId((int) $id);

        $reportModel->updateStatus((int) $id, $trangThai);

        if ($equipmentId !== null) {
            $reportModel->syncEquipmentStatus($equipmentId);
        }

        flash_set('success', 'Đã cập nhật trạng thái báo cáo.');
        $this->redirect('/mvc/reports');
    }

    private function stringInput(string $key): ?string
    {
        $v = $this->input($key);
        return is_string($v) ? $v : null;
    }

    private function backWithErrors(array $errors, array $old): void
    {
        $_SESSION['mvc_report_errors'] = $errors;
        $_SESSION['mvc_report_old'] = $old;
        $this->redirect('/mvc/reports/create');
    }
}
