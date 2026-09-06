<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Model.php';

/**
 * app/Models/User.php
 * ---------------------------------------------------------------------
 * Toàn bộ truy vấn SQL liên quan tới users + login_attempts, được tách
 * NGUYÊN VẸN (không đổi logic) từ auth/login.php và auth/register.php
 * bản thủ tục cũ, để Controller không còn chứa SQL trực tiếp.
 * ---------------------------------------------------------------------
 */
final class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() !== false;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (ho_ten, email, mat_khau, so_dien_thoai, vai_tro, trang_thai)
             VALUES (:ho_ten, :email, :mat_khau, :so_dien_thoai, :vai_tro, :trang_thai)'
        );
        $stmt->execute($data);
    }

    public function countFailedAttemptsByEmail(string $email, int $minutes): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM login_attempts
             WHERE email = :email AND thanh_cong = 0
               AND created_at >= (NOW() - INTERVAL :minutes MINUTE)"
        );
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function countFailedAttemptsByIp(string $ip, int $minutes): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip_address = :ip AND thanh_cong = 0
               AND created_at >= (NOW() - INTERVAL :minutes MINUTE)"
        );
        $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Trả về số phút còn lại nếu email đang bị khoá tạm thời, null nếu không.
     */
    public function minutesUntilUnlock(string $email, int $maxAttempts, int $lockoutWindowMinutes): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT created_at FROM login_attempts
             WHERE email = :email AND thanh_cong = 0
             ORDER BY created_at DESC
             LIMIT ' . $maxAttempts
        );
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($rows) < $maxAttempts) {
            return null;
        }

        $oldestInWindow = new DateTimeImmutable((string) end($rows));
        $unlockAt = $oldestInWindow->modify('+' . $lockoutWindowMinutes . ' minutes');
        $now = new DateTimeImmutable();

        if ($unlockAt <= $now) {
            return null;
        }

        return (int) ceil(($unlockAt->getTimestamp() - $now->getTimestamp()) / 60);
    }

    public function logAttempt(string $email, string $ip, bool $success): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts (email, ip_address, thanh_cong) VALUES (:email, :ip, :success)'
        );
        $stmt->execute([
            'email' => $email,
            'ip' => $ip,
            'success' => $success ? 1 : 0,
        ]);
    }

    /** Dọn dẹp nhật ký cũ (> 1 ngày), chạy ngẫu nhiên ~2% request — giống bản gốc. */
    public function maybeCleanupOldAttempts(): void
    {
        if (random_int(1, 100) <= 2) {
            $this->db->exec("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)");
        }
    }

    // -------------------------------------------------------------
    // Các phương thức bên dưới tách từ users/list.php, users/toggle_status.php,
    // users/update_role.php, settings/index.php (bản thủ tục cũ), phục vụ
    // quản lý người dùng (admin) + cài đặt tài khoản cá nhân.
    // -------------------------------------------------------------

    /** Toàn bộ tài khoản, dùng cho users/list.php (quản lý bởi admin). */
    public function allForAdmin(): array
    {
        return $this->db->query(
            "SELECT id, ho_ten, email, so_dien_thoai, vai_tro, trang_thai, created_at
             FROM users ORDER BY id"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, ho_ten, email, so_dien_thoai, mat_khau, vai_tro, trang_thai FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Đảo trạng thái active <-> locked, trả về trạng thái mới. */
    public function toggleStatus(int $id, string $currentStatus): string
    {
        $newStatus = $currentStatus === 'active' ? 'locked' : 'active';
        $stmt = $this->db->prepare('UPDATE users SET trang_thai = :st WHERE id = :id');
        $stmt->execute(['st' => $newStatus, 'id' => $id]);
        return $newStatus;
    }

    /** Đảo vai trò user <-> lab_staff, trả về vai trò mới. Không dùng cho admin. */
    public function toggleRole(int $id, string $currentRole): string
    {
        $newRole = $currentRole === 'lab_staff' ? 'user' : 'lab_staff';
        $stmt = $this->db->prepare('UPDATE users SET vai_tro = :role WHERE id = :id');
        $stmt->execute(['role' => $newRole, 'id' => $id]);
        return $newRole;
    }

    /** Cập nhật họ tên + số điện thoại (settings/index.php, action update_info). */
    public function updateContactInfo(int $id, string $hoTen, ?string $soDienThoai): void
    {
        $stmt = $this->db->prepare('UPDATE users SET ho_ten = :ho_ten, so_dien_thoai = :sdt WHERE id = :id');
        $stmt->execute(['ho_ten' => $hoTen, 'sdt' => $soDienThoai, 'id' => $id]);
    }

    /** Đổi mật khẩu (settings/index.php, action change_password). */
    public function updatePassword(int $id, string $newPasswordHash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET mat_khau = :hash WHERE id = :id');
        $stmt->execute(['hash' => $newPasswordHash, 'id' => $id]);
    }
}
