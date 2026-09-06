<?php
/**
 * store.php
 * =====================================================
 * Class BaoHongRepository + Controller xử lý Backend
 * =====================================================
 */

session_start();

// ================== 1. REPOSITORY CLASS ==================
class BaoHongRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Tạo bảng nếu chưa có + seed dữ liệu mẫu */
    public function khoiTaoCoSoDuLieu(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS bao_hong (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ma_thiet_bi VARCHAR(20) NOT NULL,
            ten_thiet_bi VARCHAR(150) NOT NULL,
            nguoi_bao_hong VARCHAR(100) NOT NULL,
            mo_ta_loi TEXT NOT NULL,
            muc_do_uu_tien ENUM('Cao','Trung bình','Thấp') NOT NULL,
            han_xu_ly VARCHAR(50) NOT NULL,
            trang_thai VARCHAR(100) NOT NULL,
            trang_thai_xu_ly ENUM('Chưa xử lý','Đang xử lý','Đã xử lý') NOT NULL DEFAULT 'Chưa xử lý',
            ngay_bao_hong DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->themCotNeuChuaCo('bao_hong', 'trang_thai_xu_ly',
            "ALTER TABLE bao_hong ADD COLUMN trang_thai_xu_ly
             ENUM('Chưa xử lý','Đang xử lý','Đã xử lý') NOT NULL DEFAULT 'Chưa xử lý' AFTER trang_thai");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS can_bo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ten_dang_nhap VARCHAR(50) NOT NULL,
            mat_khau_hash VARCHAR(255) NOT NULL,
            ho_ten VARCHAR(100) NOT NULL,
            UNIQUE KEY uq_ten_dang_nhap (ten_dang_nhap)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $soTaiKhoan = $this->pdo->query("SELECT COUNT(*) AS tong FROM can_bo")->fetch()['tong'];
        if ($soTaiKhoan == 0) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO can_bo (ten_dang_nhap, mat_khau_hash, ho_ten) VALUES (:tdn, :mkh, :ht)"
            );
            $stmt->execute([
                'tdn' => 'canbo1',
                'mkh' => password_hash('123456', PASSWORD_DEFAULT),
                'ht'  => 'Phạm Văn Kỹ Thuật',
            ]);
        }

        $soDong = $this->pdo->query("SELECT COUNT(*) AS tong FROM bao_hong")->fetch()['tong'];
        if ($soDong == 0) {
            $this->pdo->exec("INSERT INTO bao_hong
                (ma_thiet_bi, ten_thiet_bi, nguoi_bao_hong, mo_ta_loi, muc_do_uu_tien, han_xu_ly, trang_thai)
                VALUES
                ('TB001', 'Máy chiếu Epson EB-X05', 'Nguyễn Văn A', 'Máy chiếu không lên hình, đèn báo đỏ nhấp nháy', 'Cao', 'Trong 24 giờ', 'Khẩn cấp - Ngừng cho mượn'),
                ('TB002', 'Laptop Dell Vostro 15', 'Trần Thị B', 'Pin laptop chai, không giữ được nguồn quá 10 phút', 'Trung bình', 'Trong 3 ngày', 'Chờ bảo trì - Ngừng cho mượn'),
                ('TB003', 'Bàn phím cơ Logitech', 'Lê Văn C', 'Một vài phím bị liệt, gõ không ăn', 'Thấp', 'Trong 7 ngày', 'Theo dõi - Vẫn có thể cân nhắc')");
        }
    }

    private function themCotNeuChuaCo(string $tenBang, string $tenCot, string $cauLenhAlter): void
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS tong
            FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = :bang AND column_name = :cot");
        $stmt->execute(['bang' => $tenBang, 'cot' => $tenCot]);

        if ((int) $stmt->fetch()['tong'] === 0) {
            $this->pdo->exec($cauLenhAlter);
        }
    }

    /** READ (Tất cả bản ghi) */
    public function layDanhSach(): array
    {
        return $this->pdo->query("SELECT * FROM bao_hong ORDER BY ngay_bao_hong DESC, id DESC")->fetchAll();
    }

    /** READ (Tìm kiếm & Lọc) */
    public function timKiemDanhSach(string $tuKhoa = '', string $uuTien = '', string $trangThaiXuLy = ''): array
    {
        $sql = "SELECT * FROM bao_hong WHERE 1=1";
        $params = [];

        if ($tuKhoa !== '') {
            $sql .= " AND (ma_thiet_bi LIKE :tk OR ten_thiet_bi LIKE :tk OR nguoi_bao_hong LIKE :tk OR mo_ta_loi LIKE :tk)";
            $params['tk'] = '%' . $tuKhoa . '%';
        }

        if ($uuTien !== '') {
            $sql .= " AND muc_do_uu_tien = :uuTien";
            $params['uuTien'] = $uuTien;
        }

        if ($trangThaiXuLy !== '') {
            $sql .= " AND trang_thai_xu_ly = :trangThai";
            $params['trangThai'] = $trangThaiXuLy;
        }

        $sql .= " ORDER BY ngay_bao_hong DESC, id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** READ (1 bản ghi) */
    public function timTheoId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM bao_hong WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $kq = $stmt->fetch();
        return $kq ?: null;
    }

    /** CREATE */
    public function themMoi(array $phieu): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO bao_hong
            (ma_thiet_bi, ten_thiet_bi, nguoi_bao_hong, mo_ta_loi, muc_do_uu_tien, han_xu_ly, trang_thai)
            VALUES (:ma_thiet_bi, :ten_thiet_bi, :nguoi_bao_hong, :mo_ta_loi, :muc_do_uu_tien, :han_xu_ly, :trang_thai)");

        return $stmt->execute([
            'ma_thiet_bi'    => $phieu['ma_thiet_bi'],
            'ten_thiet_bi'   => $phieu['ten_thiet_bi'],
            'nguoi_bao_hong' => $phieu['nguoi_bao_hong'],
            'mo_ta_loi'      => $phieu['mo_ta_loi'],
            'muc_do_uu_tien' => $phieu['muc_do_uu_tien'],
            'han_xu_ly'      => $phieu['han_xu_ly'],
            'trang_thai'     => $phieu['trang_thai'],
        ]);
    }

    /** UPDATE - cập nhật toàn bộ thông tin phiếu */
    public function capNhatPhieu(int $id, string $moTaLoi, string $mucDoUuTien, string $trangThaiXuLy): bool
    {
        $hanXuLy = self::tinhHanXuLy($mucDoUuTien);
        $trangThai = self::xacDinhTrangThai($mucDoUuTien);

        $stmt = $this->pdo->prepare("UPDATE bao_hong SET 
            mo_ta_loi = :mo_ta, 
            muc_do_uu_tien = :uu_tien, 
            han_xu_ly = :han_xu_ly, 
            trang_thai = :trang_thai, 
            trang_thai_xu_ly = :trang_thai_xu_ly 
            WHERE id = :id");

        return $stmt->execute([
            'mo_ta'           => $moTaLoi,
            'uu_tien'         => $mucDoUuTien,
            'han_xu_ly'       => $hanXuLy,
            'trang_thai'      => $trangThai,
            'trang_thai_xu_ly' => $trangThaiXuLy,
            'id'              => $id
        ]);
    }

    /** Tìm tài khoản cán bộ lab */
    public function timTaiKhoan(string $tenDangNhap): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM can_bo WHERE ten_dang_nhap = :tdn");
        $stmt->execute(['tdn' => $tenDangNhap]);
        $kq = $stmt->fetch();
        return $kq ?: null;
    }

    // ---------- Hàm nghiệp vụ ----------

    public static function tinhHanXuLy(string $mucDoUuTien): string
    {
        switch ($mucDoUuTien) {
            case 'Cao':        return 'Trong 24 giờ';
            case 'Trung bình': return 'Trong 3 ngày';
            case 'Thấp':       return 'Trong 7 ngày';
            default:           return 'Chưa xác định';
        }
    }

    public static function xacDinhTrangThai(string $mucDoUuTien): string
    {
        if ($mucDoUuTien === 'Cao') return 'Khẩn cấp - Ngừng cho mượn';
        if ($mucDoUuTien === 'Trung bình') return 'Chờ bảo trì - Ngừng cho mượn';
        return 'Theo dõi - Vẫn có thể cân nhắc';
    }

    public static function khongDuocChoMuon(string $trangThai): bool
    {
        return (strpos($trangThai, 'Ngừng cho mượn') !== false);
    }

    // ---------- Validate + chuẩn hóa ----------

    public static function chuanHoaChuoi(string $s): string
    {
        $s = trim($s);
        return preg_replace('/\s+/', ' ', $s);
    }

    public static function kiemTraDuLieu(array $d): array
    {
        $loi = [];

        if ($d['ma_thiet_bi'] === '') {
            $loi['ma_thiet_bi'] = 'Vui lòng nhập Mã thiết bị.';
        } elseif (!preg_match('/^[A-Z0-9]+$/', $d['ma_thiet_bi'])) {
            $loi['ma_thiet_bi'] = 'Mã thiết bị chỉ được chứa chữ cái không dấu và số.';
        } elseif (strlen($d['ma_thiet_bi']) < 3 || strlen($d['ma_thiet_bi']) > 20) {
            $loi['ma_thiet_bi'] = 'Mã thiết bị phải có độ dài từ 3 đến 20 ký tự.';
        }

        if ($d['ten_thiet_bi'] === '') {
            $loi['ten_thiet_bi'] = 'Vui lòng nhập Tên thiết bị.';
        } elseif (mb_strlen($d['ten_thiet_bi']) < 5 || mb_strlen($d['ten_thiet_bi']) > 150) {
            $loi['ten_thiet_bi'] = 'Tên thiết bị phải có độ dài từ 5 đến 150 ký tự.';
        }

        if ($d['nguoi_bao_hong'] === '') {
            $loi['nguoi_bao_hong'] = 'Vui lòng nhập Người báo hỏng.';
        } elseif (!preg_match('/^[\p{L}\s]+$/u', $d['nguoi_bao_hong'])) {
            $loi['nguoi_bao_hong'] = 'Tên chỉ được chứa chữ cái và khoảng trắng.';
        } elseif (mb_strlen($d['nguoi_bao_hong']) < 2 || mb_strlen($d['nguoi_bao_hong']) > 100) {
            $loi['nguoi_bao_hong'] = 'Tên phải có độ dài từ 2 đến 100 ký tự.';
        }

        if ($d['mo_ta_loi'] === '') {
            $loi['mo_ta_loi'] = 'Vui lòng mô tả lỗi thiết bị.';
        } elseif (mb_strlen($d['mo_ta_loi']) < 10 || mb_strlen($d['mo_ta_loi']) > 500) {
            $loi['mo_ta_loi'] = 'Mô tả lỗi phải có độ dài từ 10 đến 500 ký tự.';
        }

        if (!in_array($d['muc_do_uu_tien'], ['Cao', 'Trung bình', 'Thấp'], true)) {
            $loi['muc_do_uu_tien'] = 'Vui lòng chọn Mức độ ưu tiên hợp lệ.';
        }

        return $loi;
    }
}

// ================== 2. KẾT NỐI DB + KHỞI TẠO ==================
$host = 'localhost';
$ten_db = 'qlpttb_buoi2';
$user = 'root';
$mk = '';

try {
    $pdoGoc = new PDO("mysql:host={$host};charset=utf8mb4", $user, $mk, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdoGoc->exec("CREATE DATABASE IF NOT EXISTS {$ten_db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO("mysql:host={$host};dbname={$ten_db};charset=utf8mb4", $user, $mk, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
}

$repo = new BaoHongRepository($pdo);
$repo->khoiTaoCoSoDuLieu();

// ================== 3. CONTROLLER ==================
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {

    // Endpoint API JSON
    if (($_GET['action'] ?? '') === 'api') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'thanh_cong' => true,
            'du_lieu'    => $repo->layDanhSach(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Đăng nhập
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanh_dong'] ?? '') === 'dang_nhap') {
        $taiKhoan = $repo->timTaiKhoan(trim($_POST['ten_dang_nhap'] ?? ''));

        if ($taiKhoan && password_verify($_POST['mat_khau'] ?? '', $taiKhoan['mat_khau_hash'])) {
            $_SESSION['can_bo'] = ['id' => $taiKhoan['id'], 'ho_ten' => $taiKhoan['ho_ten']];
            $_SESSION['thong_bao_thanh_cong'] = 'Đăng nhập thành công! Xin chào ' . $taiKhoan['ho_ten'] . '.';
        } else {
            $_SESSION['loi_dang_nhap'] = 'Sai tên đăng nhập hoặc mật khẩu.';
        }
        header('Location: form.php');
        exit;
    }

    // Đăng xuất
    if (($_GET['action'] ?? '') === 'dang_xuat') {
        unset($_SESSION['can_bo']);
        $_SESSION['thong_bao_thanh_cong'] = 'Đã đăng xuất.';
        header('Location: form.php');
        exit;
    }

    // Cập nhật chi tiết phiếu báo hỏng
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanh_dong'] ?? '') === 'cap_nhat_phieu') {
        if (!isset($_SESSION['can_bo'])) {
            $_SESSION['loi_validate'] = ['Bạn cần đăng nhập để thực hiện cập nhật.'];
            header('Location: form.php');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $moTaLoi = BaoHongRepository::chuanHoaChuoi($_POST['mo_ta_loi'] ?? '');
        $mucDoUuTien = $_POST['muc_do_uu_tien'] ?? '';
        $trangThaiXuLy = $_POST['trang_thai_xu_ly'] ?? '';

        if ($repo->timTheoId($id) 
            && $moTaLoi !== '' 
            && in_array($mucDoUuTien, ['Cao', 'Trung bình', 'Thấp'], true)
            && in_array($trangThaiXuLy, ['Chưa xử lý', 'Đang xử lý', 'Đã xử lý'], true)) {
            
            $repo->capNhatPhieu($id, $moTaLoi, $mucDoUuTien, $trangThaiXuLy);
            $_SESSION['thong_bao_thanh_cong'] = 'Đã cập nhật thành công thông tin cho phiếu #' . $id . '.';
        } else {
            $_SESSION['loi_validate'] = ['Dữ liệu cập nhật không hợp lệ. Vui lòng kiểm tra lại.'];
        }
        header('Location: form.php');
        exit;
    }

    // Gửi báo hỏng
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanh_dong'] ?? '') === 'gui_bao_hong') {
        $duLieu = [
            'ma_thiet_bi'    => strtoupper(BaoHongRepository::chuanHoaChuoi($_POST['ma_thiet_bi'] ?? '')),
            'ten_thiet_bi'   => BaoHongRepository::chuanHoaChuoi($_POST['ten_thiet_bi'] ?? ''),
            'nguoi_bao_hong' => BaoHongRepository::chuanHoaChuoi($_POST['nguoi_bao_hong'] ?? ''),
            'mo_ta_loi'      => BaoHongRepository::chuanHoaChuoi($_POST['mo_ta_loi'] ?? ''),
            'muc_do_uu_tien' => trim($_POST['muc_do_uu_tien'] ?? ''),
        ];

        $loi = BaoHongRepository::kiemTraDuLieu($duLieu);

        if (!empty($loi)) {
            $_SESSION['loi_truong'] = $loi;
            $_SESSION['du_lieu_cu'] = $duLieu;
            header('Location: form.php');
            exit;
        }

        $duLieu['han_xu_ly']  = BaoHongRepository::tinhHanXuLy($duLieu['muc_do_uu_tien']);
        $duLieu['trang_thai'] = BaoHongRepository::xacDinhTrangThai($duLieu['muc_do_uu_tien']);

        if ($repo->themMoi($duLieu)) {
            $_SESSION['thong_bao_thanh_cong'] = 'Đã ghi nhận báo hỏng thiết bị "' . $duLieu['ten_thiet_bi'] . '" thành công!';
        } else {
            $_SESSION['loi_validate'] = ['Có lỗi xảy ra khi lưu dữ liệu.'];
            $_SESSION['du_lieu_cu'] = $duLieu;
        }
        header('Location: form.php');
        exit;
    }

    header('Location: form.php');
    exit;
}