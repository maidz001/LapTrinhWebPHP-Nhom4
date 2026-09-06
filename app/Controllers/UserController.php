<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/User.php';

/**
 * app/Controllers/UserController.php
 * ---------------------------------------------------------------------
 * Phiên bản MVC của users/list.php, users/toggle_status.php,
 * users/update_role.php (chỉ admin dùng được). users/delete.php gốc
 * không có chức năng thật (chỉ redirect, tài khoản không được xoá theo
 * đúng yêu cầu nghiệp vụ) nên không có route tương ứng ở đây.
 *
 * File users/*.php gốc KHÔNG bị xoá hay sửa và vẫn hoạt động bình
 * thường; đây là bản song song để migrate dần (xem README_MVC.md).
 * Toàn bộ giới hạn nghiệp vụ gốc được giữ NGUYÊN VẸN: không tự khoá/
 * tự đổi vai trò chính mình, không gán/thu hồi vai trò admin qua đây,
 * không có chức năng xoá tài khoản.
 * ---------------------------------------------------------------------
 */
final class UserController extends Controller
{
    /** GET /mvc/users — danh sách tài khoản, chỉ admin. */
    public function index(): void
    {
        require_role(['admin']);

        $users = (new User())->allForAdmin();
        $currentUser = current_user();

        $this->view('users/index', [
            'users' => $users,
            'currentUserId' => (int) ($currentUser['id'] ?? 0),
            'flashSuccess' => flash_get('success'),
            'flashError' => flash_get('error'),
        ]);
    }

    /** POST /mvc/users/toggle-status — khoá/mở khoá tài khoản, chỉ admin. */
    public function toggleStatus(): void
    {
        require_role(['admin']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/users');
        }
        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/users');
        }

        $id = $this->input('id', '');
        if (!ctype_digit((string) $id)) {
            flash_set('error', 'Tài khoản không hợp lệ.');
            $this->redirect('/mvc/users');
        }
        $id = (int) $id;

        $currentUser = current_user();
        if ($id === (int) ($currentUser['id'] ?? 0)) {
            flash_set('error', 'Bạn không thể tự khoá tài khoản của chính mình.');
            $this->redirect('/mvc/users');
        }

        $userModel = new User();
        $target = $userModel->find($id);
        if (!$target) {
            flash_set('error', 'Không tìm thấy tài khoản.');
            $this->redirect('/mvc/users');
        }

        $newStatus = $userModel->toggleStatus($id, $target['trang_thai']);

        flash_set('success', $newStatus === 'locked'
            ? 'Đã khoá tài khoản "' . $target['ho_ten'] . '".'
            : 'Đã mở khoá tài khoản "' . $target['ho_ten'] . '".');

        $this->redirect('/mvc/users');
    }

    /** POST /mvc/users/update-role — đảo vai trò user <-> lab_staff, chỉ admin. */
    public function updateRole(): void
    {
        require_role(['admin']);

        if (!$this->isPost()) {
            $this->redirect('/mvc/users');
        }
        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/users');
        }

        $id = $this->input('id', '');
        if (!ctype_digit((string) $id)) {
            flash_set('error', 'Tài khoản không hợp lệ.');
            $this->redirect('/mvc/users');
        }
        $id = (int) $id;

        $currentUser = current_user();
        if ($id === (int) ($currentUser['id'] ?? 0)) {
            flash_set('error', 'Bạn không thể tự đổi vai trò của chính mình.');
            $this->redirect('/mvc/users');
        }

        $userModel = new User();
        $target = $userModel->find($id);
        if (!$target) {
            flash_set('error', 'Không tìm thấy tài khoản.');
            $this->redirect('/mvc/users');
        }

        if ($target['vai_tro'] === 'admin') {
            flash_set('error', 'Không thể đổi vai trò của quản trị viên qua chức năng này.');
            $this->redirect('/mvc/users');
        }

        $newRole = $userModel->toggleRole($id, $target['vai_tro']);

        flash_set('success', $newRole === 'lab_staff'
            ? 'Đã nâng "' . $target['ho_ten'] . '" thành Cán bộ phòng lab.'
            : 'Đã chuyển "' . $target['ho_ten'] . '" về Người dùng thường.');

        $this->redirect('/mvc/users');
    }

    private function stringInput(string $key): ?string
    {
        $v = $this->input($key);
        return is_string($v) ? $v : null;
    }
}
