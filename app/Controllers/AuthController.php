<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/User.php';

/**
 * app/Controllers/AuthController.php
 * ---------------------------------------------------------------------
 * Phiên bản MVC của auth/login.php, auth/register.php, auth/logout.php.
 * Toàn bộ quy tắc bảo mật (giới hạn 5 lần sai/15 phút theo email, chặn
 * brute-force theo IP, honeypot chống bot, session fixation, CSRF...)
 * được giữ NGUYÊN VẸN như bản gốc — chỉ khác cách tổ chức code.
 *
 * File auth/login.php, auth/register.php, auth/logout.php gốc KHÔNG bị
 * xoá hay sửa và vẫn hoạt động bình thường; đây là bản song song để
 * migrate dần (xem README_MVC.md).
 * ---------------------------------------------------------------------
 */
final class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;      // số lần sai tối đa (theo email)
    private const LOCKOUT_WINDOW = 15;   // phút
    private const IP_MAX_ATTEMPTS = 20;  // số lần sai tối đa (theo IP)
    private const IP_LOCKOUT_WINDOW = 15; // phút
    private const DUMMY_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function showLogin(): void
    {
        redirect_if_logged_in();

        $this->view('auth/login', [
            'errors' => [],
            'emailOld' => '',
            'redirectTarget' => $this->safeRedirectTarget($this->input('redirect')),
            'registered' => isset($_GET['registered']),
            'loggedOut' => isset($_GET['logged_out']),
            'flashSuccess' => flash_get('success'),
        ]);
    }

    public function login(): void
    {
        redirect_if_logged_in();

        $userModel = new User();
        $userModel->maybeCleanupOldAttempts();

        $errors = [];
        $emailOld = '';
        $redirectTarget = $this->safeRedirectTarget($this->input('redirect'));

        if (!csrf_verify($this->input('csrf_token'))) {
            $errors[] = 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.';
        } else {
            $email = trim((string) $this->input('email', ''));
            $password = (string) $this->input('mat_khau', '');
            $emailOld = $email;
            $ip = client_ip();

            if ($email === '' || $password === '') {
                $errors[] = 'Vui lòng nhập đầy đủ email và mật khẩu.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email hoặc mật khẩu không đúng.';
            } elseif ($userModel->countFailedAttemptsByIp($ip, self::IP_LOCKOUT_WINDOW) >= self::IP_MAX_ATTEMPTS) {
                $errors[] = 'Có quá nhiều lượt đăng nhập thất bại từ thiết bị này. Vui lòng thử lại sau ít phút.';
            } else {
                $remainingLock = $userModel->minutesUntilUnlock($email, self::MAX_ATTEMPTS, self::LOCKOUT_WINDOW);

                if ($remainingLock !== null) {
                    $errors[] = 'Tài khoản tạm thời bị khoá do đăng nhập sai quá ' . self::MAX_ATTEMPTS . ' lần. '
                        . "Vui lòng thử lại sau khoảng {$remainingLock} phút.";
                } else {
                    $user = $userModel->findByEmail($email);
                    $hashToCheck = (is_array($user) && !empty($user['mat_khau'])) ? $user['mat_khau'] : self::DUMMY_HASH;
                    $passwordOk = password_verify($password, $hashToCheck);

                    if (!is_array($user) || !$passwordOk || $user['trang_thai'] !== 'active') {
                        $userModel->logAttempt($email, $ip, false);
                        $left = self::MAX_ATTEMPTS - $userModel->countFailedAttemptsByEmail($email, self::LOCKOUT_WINDOW);
                        $errors[] = 'Email hoặc mật khẩu không đúng, hoặc tài khoản đã bị khoá.'
                            . ($left > 0 && $left <= 2 ? " (còn {$left} lần thử trước khi tài khoản bị tạm khoá)" : '');
                    } else {
                        $userModel->logAttempt($email, $ip, true);

                        // Chống session fixation
                        session_regenerate_id(true);

                        $_SESSION['user'] = [
                            'id' => (int) $user['id'],
                            'full_name' => $user['ho_ten'],
                            'email' => $user['email'],
                            'role' => $user['vai_tro'],
                        ];
                        csrf_regenerate();

                        $this->redirect($redirectTarget);
                    }
                }
            }
        }

        $this->view('auth/login', [
            'errors' => $errors,
            'emailOld' => $emailOld,
            'redirectTarget' => $redirectTarget,
            'registered' => false,
            'loggedOut' => false,
            'flashSuccess' => null,
        ]);
    }

    public function showRegister(): void
    {
        redirect_if_logged_in();

        $this->view('auth/register', [
            'errors' => [],
            'old' => ['ho_ten' => '', 'email' => '', 'so_dien_thoai' => ''],
        ]);
    }

    public function register(): void
    {
        redirect_if_logged_in();

        $userModel = new User();
        $errors = [];
        $old = ['ho_ten' => '', 'email' => '', 'so_dien_thoai' => ''];

        // Honeypot chống bot: người dùng thật không thấy field này
        $honeypot = trim((string) $this->input('website', ''));

        if (!csrf_verify($this->input('csrf_token'))) {
            $errors[] = 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.';
        } elseif ($honeypot !== '') {
            flash_set('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
            $this->redirect('/mvc/auth/login');
        } else {
            $hoTen = trim((string) $this->input('ho_ten', ''));
            $email = trim((string) $this->input('email', ''));
            $soDienThoai = trim((string) $this->input('so_dien_thoai', ''));
            $matKhau = (string) $this->input('mat_khau', '');
            $xacNhanMk = (string) $this->input('xac_nhan_mat_khau', '');

            $old = ['ho_ten' => $hoTen, 'email' => $email, 'so_dien_thoai' => $soDienThoai];

            $hoTenLen = mb_strlen($hoTen, 'UTF-8');
            if ($hoTen === '') {
                $errors['ho_ten'] = 'Vui lòng nhập họ tên.';
            } elseif ($hoTenLen < 2 || $hoTenLen > 100) {
                $errors['ho_ten'] = 'Họ tên phải từ 2 đến 100 ký tự.';
            } elseif (!preg_match('/^[\p{L}][\p{L}\s]*$/u', $hoTen)) {
                $errors['ho_ten'] = 'Họ tên chỉ được chứa chữ cái và khoảng trắng.';
            }

            if ($email === '') {
                $errors['email'] = 'Vui lòng nhập email.';
            } elseif (mb_strlen($email, 'UTF-8') > 150) {
                $errors['email'] = 'Email tối đa 150 ký tự.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email không đúng định dạng.';
            }

            if ($soDienThoai !== '' && !preg_match('/^0\d{9}$/', $soDienThoai)) {
                $errors['so_dien_thoai'] = 'Số điện thoại phải gồm đúng 10 chữ số và bắt đầu bằng 0.';
            }

            if ($matKhau === '') {
                $errors['mat_khau'] = 'Vui lòng nhập mật khẩu.';
            } elseif (mb_strlen($matKhau, 'UTF-8') < 8) {
                $errors['mat_khau'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } elseif (mb_strlen($matKhau, 'UTF-8') > 72) {
                $errors['mat_khau'] = 'Mật khẩu tối đa 72 ký tự.';
            } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).+$/', $matKhau)) {
                $errors['mat_khau'] = 'Mật khẩu phải có ít nhất 1 chữ cái và 1 chữ số.';
            }

            if (empty($errors['mat_khau']) && $xacNhanMk !== $matKhau) {
                $errors['xac_nhan_mat_khau'] = 'Xác nhận mật khẩu không khớp.';
            }

            if (empty($errors['email']) && $userModel->emailExists($email)) {
                $errors['email'] = 'Email này đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.';
            }

            if (empty($errors)) {
                try {
                    $hash = password_hash($matKhau, PASSWORD_BCRYPT, ['cost' => 12]);
                    $userModel->create([
                        'ho_ten' => $hoTen,
                        'email' => $email,
                        'mat_khau' => $hash,
                        'so_dien_thoai' => $soDienThoai !== '' ? $soDienThoai : null,
                        // Ép cứng phía server, không tin giá trị từ client
                        'vai_tro' => 'user',
                        'trang_thai' => 'active',
                    ]);

                    csrf_regenerate();
                    flash_set('success', 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.');
                    $this->redirect('/mvc/auth/login?registered=1');
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $errors['email'] = 'Email này đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.';
                    } else {
                        error_log('[REGISTER ERROR] ' . $e->getMessage());
                        $errors[] = 'Có lỗi xảy ra khi tạo tài khoản. Vui lòng thử lại sau.';
                    }
                }
            }
        }

        $this->view('auth/register', [
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function logout(): void
    {
        $token = $this->input('csrf_token');

        if (csrf_verify(is_string($token) ? $token : null)) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
        }

        $this->redirect('/mvc/auth/login?logged_out=1');
    }

    /** Chỉ chấp nhận đường dẫn nội bộ để chống Open Redirect — giống bản gốc. */
    private function safeRedirectTarget(?string $path): string
    {
        $default = '/mvc/dashboard';
        if (!is_string($path) || $path === '') {
            return $default;
        }
        if (preg_match('#^/(?!/)[A-Za-z0-9_\-./?=&%]*$#', $path)) {
            return $path;
        }
        return $default;
    }
}
