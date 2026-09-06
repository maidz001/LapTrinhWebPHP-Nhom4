<?php
/**
 * rooms/import.php
 * ---------------------------------------------------------------------
 * Thêm nhiều phòng thực hành cùng lúc từ file CSV, tránh phải gõ tay
 * từng phòng một. Chỉ admin/lab_staff được dùng.
 *
 * Định dạng file CSV (dòng đầu là tiêu đề, sẽ được bỏ qua):
 *   ma_phong,ten_phong,vi_tri,suc_chua,trang_thai,mo_ta
 *   TH06,Phòng Vật lý,Nhà B - Tầng 3,40,available,Phòng thí nghiệm Vật lý
 *
 * - trang_thai bỏ trống sẽ mặc định là "available".
 * - mo_ta có thể để trống.
 * - Dòng thiếu ma_phong/ten_phong/vi_tri/suc_chua hợp lệ, hoặc mã phòng
 *   đã tồn tại, sẽ bị bỏ qua và liệt kê lại lý do cho người dùng biết.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

$ketQua = null; // ['them' => int, 'loi' => array]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
        header('Location: /rooms/import.php');
        exit;
    }

    if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        flash_set('error', 'Vui lòng chọn một file CSV hợp lệ để tải lên.');
        header('Location: /rooms/import.php');
        exit;
    }

    $tmpPath = $_FILES['file']['tmp_name'];
    $handle = fopen($tmpPath, 'r');

    if ($handle === false) {
        flash_set('error', 'Không thể đọc file đã tải lên.');
        header('Location: /rooms/import.php');
        exit;
    }

    // Bỏ qua BOM UTF-8 ở đầu file (Excel thường tự thêm khi lưu CSV UTF-8),
    // nếu không dòng tiêu đề sẽ không được nhận diện đúng và bị đọc nhầm thành dữ liệu.
    if (fread($handle, 3) !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    $soDong = 0;
    $themThanhCong = 0;
    $loi = [];
    $trangThaiHopLe = ['available', 'maintenance', 'closed'];
    $first = true;

    while (($row = fgetcsv($handle)) !== false) {
        $soDong++;

        // Bỏ qua dòng tiêu đề nếu cột đầu không phải dữ liệu số/mã phòng thực
        if ($first) {
            $first = false;
            $header = array_map(fn($v) => mb_strtolower(trim((string) $v)), $row);
            if (in_array('ma_phong', $header, true) || in_array('mã phòng', $header, true)) {
                continue;
            }
        }

        if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
            continue; // dòng trống
        }

        $ma_phong   = trim((string) ($row[0] ?? ''));
        $ten_phong  = trim((string) ($row[1] ?? ''));
        $vi_tri     = trim((string) ($row[2] ?? ''));
        $suc_chua   = trim((string) ($row[3] ?? ''));
        $trang_thai = trim((string) ($row[4] ?? '')) ?: 'available';
        $mo_ta      = trim((string) ($row[5] ?? ''));

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
        if (!in_array($trang_thai, $trangThaiHopLe, true)) {
            $dongLoi[] = 'trạng thái không hợp lệ';
        }

        if (empty($dongLoi) && $ma_phong !== '') {
            $stmt = $pdo->prepare("SELECT id FROM rooms WHERE ma_phong = :ma");
            $stmt->execute(['ma' => $ma_phong]);
            if ($stmt->fetch()) {
                $dongLoi[] = 'mã phòng "' . $ma_phong . '" đã tồn tại';
            }
        }

        if (!empty($dongLoi)) {
            $loi[] = 'Dòng ' . $soDong . ': ' . implode(', ', $dongLoi) . '.';
            continue;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO rooms (ma_phong, ten_phong, vi_tri, suc_chua, trang_thai, mo_ta)
             VALUES (:ma, :ten, :vt, :sc, :tt, :mt)"
        );
        $stmt->execute([
            'ma' => $ma_phong,
            'ten' => $ten_phong,
            'vt' => $vi_tri,
            'sc' => (int) $suc_chua,
            'tt' => $trang_thai,
            'mt' => $mo_ta !== '' ? $mo_ta : null,
        ]);
        $themThanhCong++;
    }

    fclose($handle);

    $ketQua = ['them' => $themThanhCong, 'loi' => $loi];

    if ($themThanhCong > 0 && empty($loi)) {
        flash_set('success', "Đã thêm {$themThanhCong} phòng từ file thành công.");
        header('Location: /rooms/list.php');
        exit;
    }
}

$page_title = 'Thêm phòng từ file';
$active_menu = 'rooms';
require_once __DIR__ . '/../includes/app_head.php';
?>

<div class="chart-card" style="max-width:640px;">
    <h3>Thêm phòng thực hành từ file CSV</h3>
    <p class="chart-sub">
        File CSV cần có các cột theo đúng thứ tự:
        <code>ma_phong, ten_phong, vi_tri, suc_chua, trang_thai, mo_ta</code>.
        Dòng tiêu đề (nếu có) sẽ tự động được bỏ qua. Cột <code>trang_thai</code>
        chỉ nhận <code>available</code>, <code>maintenance</code> hoặc <code>closed</code>
        (bỏ trống sẽ mặc định là <code>available</code>).
    </p>

    <?php if ($ketQua !== null): ?>
        <div class="alert <?php echo $ketQua['them'] > 0 ? 'alert-success' : 'alert-error'; ?>">
            <p style="margin:0 0 6px;">Đã thêm thành công <strong><?php echo $ketQua['them']; ?></strong> phòng.</p>
            <?php if (!empty($ketQua['loi'])): ?>
                <p style="margin:8px 0 4px;">Các dòng sau bị bỏ qua:</p>
                <ul>
                    <?php foreach ($ketQua['loi'] as $l): ?>
                        <li><?php echo htmlspecialchars($l); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Chọn file CSV <span class="required">*</span></label>
            <input type="file" name="file" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn btn-primary">Tải lên &amp; thêm phòng</button>
        <a href="/rooms/list.php" class="btn btn-secondary">Quay lại danh sách</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
