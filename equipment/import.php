<?php
/**
 * equipment/import.php
 * ---------------------------------------------------------------------
 * Thêm nhiều thiết bị cùng lúc từ file CSV, tránh phải gõ tay từng
 * thiết bị một. Chỉ admin/lab_staff được dùng.
 *
 * Định dạng file CSV (dòng đầu là tiêu đề, sẽ được bỏ qua):
 *   ma_thiet_bi,ten_thiet_bi,ten_loai,ma_phong,co_the_muon,trang_thai,ngay_mua,mo_ta
 *   TB011,Máy chiếu Epson,Máy chiếu,TH01,0,active,2024-01-10,Phòng học 1
 *
 * - ten_loai phải khớp (không phân biệt hoa/thường) với một loại thiết bị
 *   đã có sẵn trong Danh mục thiết bị.
 * - ma_phong có thể để trống (thiết bị lưu động, không gắn phòng cố định).
 * - co_the_muon: 1 = có thể cho mượn, 0 hoặc để trống = không.
 * - trang_thai bỏ trống sẽ mặc định là "active".
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

$ketQua = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
        header('Location: /equipment/import.php');
        exit;
    }

    if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        flash_set('error', 'Vui lòng chọn một file CSV hợp lệ để tải lên.');
        header('Location: /equipment/import.php');
        exit;
    }

    $handle = fopen($_FILES['file']['tmp_name'], 'r');
    if ($handle === false) {
        flash_set('error', 'Không thể đọc file đã tải lên.');
        header('Location: /equipment/import.php');
        exit;
    }

    // Bỏ qua BOM UTF-8 ở đầu file (Excel thường tự thêm khi lưu CSV UTF-8),
    // nếu không dòng tiêu đề sẽ không được nhận diện đúng và bị đọc nhầm thành dữ liệu.
    if (fread($handle, 3) !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    // Tra cứu nhanh loại thiết bị theo tên (không phân biệt hoa thường)
    $typeMap = [];
    foreach ($pdo->query("SELECT id, ten_loai FROM equipment_types") as $t) {
        $typeMap[mb_strtolower(trim($t['ten_loai']))] = (int) $t['id'];
    }
    // Tra cứu nhanh phòng theo mã phòng
    $roomMap = [];
    foreach ($pdo->query("SELECT id, ma_phong FROM rooms") as $r) {
        $roomMap[mb_strtolower(trim($r['ma_phong']))] = (int) $r['id'];
    }

    $soDong = 0;
    $themThanhCong = 0;
    $loi = [];
    $trangThaiHopLe = ['active', 'broken', 'maintenance', 'borrowed'];
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

        $ma_thiet_bi  = trim((string) ($row[0] ?? ''));
        $ten_thiet_bi = trim((string) ($row[1] ?? ''));
        $ten_loai     = trim((string) ($row[2] ?? ''));
        $ma_phong     = trim((string) ($row[3] ?? ''));
        $co_the_muon  = trim((string) ($row[4] ?? '')) === '1' ? 1 : 0;
        $trang_thai   = trim((string) ($row[5] ?? '')) ?: 'active';
        $ngay_mua     = trim((string) ($row[6] ?? ''));
        $mo_ta        = trim((string) ($row[7] ?? ''));

        $dongLoi = [];
        if ($ma_thiet_bi === '' || mb_strlen($ma_thiet_bi) > 30) {
            $dongLoi[] = 'mã thiết bị trống hoặc quá 30 ký tự';
        }
        if ($ten_thiet_bi === '' || mb_strlen($ten_thiet_bi) > 150) {
            $dongLoi[] = 'tên thiết bị trống hoặc quá 150 ký tự';
        }
        if (!in_array($trang_thai, $trangThaiHopLe, true)) {
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

        if (empty($dongLoi) && $ma_thiet_bi !== '') {
            $stmt = $pdo->prepare("SELECT id FROM equipment WHERE ma_thiet_bi = :ma");
            $stmt->execute(['ma' => $ma_thiet_bi]);
            if ($stmt->fetch()) {
                $dongLoi[] = 'mã thiết bị "' . $ma_thiet_bi . '" đã tồn tại';
            }
        }

        if (!empty($dongLoi)) {
            $loi[] = 'Dòng ' . $soDong . ': ' . implode(', ', $dongLoi) . '.';
            continue;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO equipment (ma_thiet_bi, ten_thiet_bi, type_id, room_id, co_the_muon, trang_thai, ngay_mua, mo_ta)
             VALUES (:ma, :ten, :type_id, :room_id, :muon, :tt, :ngay_mua, :mt)"
        );
        $stmt->execute([
            'ma' => $ma_thiet_bi,
            'ten' => $ten_thiet_bi,
            'type_id' => $typeId,
            'room_id' => $roomId,
            'muon' => $co_the_muon,
            'tt' => $trang_thai,
            'ngay_mua' => $ngayMuaHopLe,
            'mt' => $mo_ta !== '' ? $mo_ta : null,
        ]);
        $themThanhCong++;
    }

    fclose($handle);

    $ketQua = ['them' => $themThanhCong, 'loi' => $loi];

    if ($themThanhCong > 0 && empty($loi)) {
        flash_set('success', "Đã thêm {$themThanhCong} thiết bị từ file thành công.");
        header('Location: /equipment/list.php');
        exit;
    }
}

$page_title = 'Thêm thiết bị từ file';
$active_menu = 'equipment';
require_once __DIR__ . '/../includes/app_head.php';
?>

<div class="chart-card" style="max-width:680px;">
    <h3>Thêm thiết bị từ file CSV</h3>
    <p class="chart-sub">
        File CSV cần có các cột theo đúng thứ tự:
        <code>ma_thiet_bi, ten_thiet_bi, ten_loai, ma_phong, co_the_muon, trang_thai, ngay_mua, mo_ta</code>.
        Dòng tiêu đề (nếu có) sẽ tự động được bỏ qua.
        Cột <code>ten_loai</code> phải trùng với một danh mục thiết bị đã có sẵn.
        Cột <code>ma_phong</code> để trống nếu là thiết bị lưu động.
    </p>

    <?php if ($ketQua !== null): ?>
        <div class="alert <?php echo $ketQua['them'] > 0 ? 'alert-success' : 'alert-error'; ?>">
            <p style="margin:0 0 6px;">Đã thêm thành công <strong><?php echo $ketQua['them']; ?></strong> thiết bị.</p>
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
        <button type="submit" class="btn btn-primary">Tải lên &amp; thêm thiết bị</button>
        <a href="/equipment/list.php" class="btn btn-secondary">Quay lại danh sách</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
