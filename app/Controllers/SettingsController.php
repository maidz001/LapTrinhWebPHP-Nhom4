<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/User.php';

/**
 * app/Controllers/SettingsController.php
 * ---------------------------------------------------------------------
 * Phiên bản MVC của settings/index.php: cập nhật thông tin liên hệ +
 * đổi mật khẩu cho tài khoản đang đăng nhập.
 *
 * File settings/index.php gốc KHÔNG bị xoá hay sửa và vẫn hoạt động
 * bình thường; đây là bản song song để migrate dần (xem README_MVC.md).
 * Toàn bộ quy tắc nghiệp vụ gốc được giữ NGUYÊN VẸN: email không sửa
 * được qua đây, mật khẩu mới tối thiểu 8 ký tự gồm chữ + số, phải nhập
 * đúng mật khẩu hiện tại, CSRF, session full_name được đồng bộ ngay
 * sau khi đổi tên.
 * ---------------------------------------------------------------------
 */
final class SettingsController extends Controller
{
    /** GET /mvc/settings */
    public function index(): void
    {
        require_login();
        $user = current_user();

        $dbUser = (new User())->find((int) $user['id']);
        if (!$dbUser) {
            $this->redirect('/mvc/auth/logout');
        }

        $infoErrors = $_SESSION['mvc_settings_info_errors'] ?? [];
        unset($_SESSION['mvc_settings_info_errors']);
        $passwordErrors = $_SESSION['mvc_settings_password_errors'] ?? [];
        unset($_SESSION['mvc_settings_password_errors']);

        $this->view('settings/index', [
            'dbUser' => $dbUser,
            'infoErrors' => $infoErrors,
            'passwordErrors' => $passwordErrors,
            'flashSuccess' => flash_get('success'),
            'flashError' => flash_get('error'),
        ]);
    }

    /** POST /mvc/settings/update-info */
    public function updateInfo(): void
    {
        require_login();

        if (!$this->isPost()) {
            $this->redirect('/mvc/settings');
        }
        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/settings');
        }

        $user = current_user();
        $hoTen = trim((string) $this->input('ho_ten', ''));
        $soDienThoai = trim((string) $this->input('so_dien_thoai', ''));

        $errors = [];
        if ($hoTen === '' || mb_strlen($hoTen) > 100) {
            $errors[] = 'Họ tên không được để trống và tối đa 100 ký tự.';
        }
        if ($soDienThoai !== '' && !preg_match('/^[0-9+\s]{8,15}$/', $soDienThoai)) {
            $errors[] = 'Số điện thoại không hợp lệ.';
        }

        if ($errors) {
            $_SESSION['mvc_settings_info_errors'] = $errors;
            $this->redirect('/mvc/settings');
        }

        (new User())->updateContactInfo((int) $user['id'], $hoTen, $soDienThoai !== '' ? $soDienThoai : null);
        $_SESSION['user']['full_name'] = $hoTen;

        flash_set('success', 'Đã cập nhật thông tin liên hệ.');
        $this->redirect('/mvc/settings');
    }

    /** POST /mvc/settings/change-password */
    public function changePassword(): void
    {
        require_login();

        if (!$this->isPost()) {
            $this->redirect('/mvc/settings');
        }
        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
            $this->redirect('/mvc/settings');
        }

        $user = current_user();
        $userModel = new User();
        $dbUser = $userModel->find((int) $user['id']);

        $current = (string) $this->input('mat_khau_hien_tai', '');
        $new = (string) $this->input('mat_khau_moi', '');
        $confirm = (string) $this->input('xac_nhan_mat_khau', '');

        $errors = [];
        if (!$dbUser || !password_verify($current, $dbUser['mat_khau'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        }
        if (mb_strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 8 ký tự, gồm cả chữ và số.';
        }
        if ($new !== $confirm) {
            $errors[] = 'Xác nhận mật khẩu mới không khớp.';
        }

        if ($errors) {
            $_SESSION['mvc_settings_password_errors'] = $errors;
            $this->redirect('/mvc/settings');
        }

        $userModel->updatePassword((int) $user['id'], password_hash($new, PASSWORD_DEFAULT));
        csrf_regenerate();

        flash_set('success', 'Đã đổi mật khẩu thành công.');
        $this->redirect('/mvc/settings');
    }

    private function stringInput(string $key): ?string
    {
        $v = $this->input($key);
        return is_string($v) ? $v : null;
    }
}
