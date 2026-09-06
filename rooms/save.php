<?php
/**
 * rooms/save.php
 * ---------------------------------------------------------------------
 * Xử lý thêm mới / cập nhật phòng thực hành (POST từ rooms/form.php).
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rooms/list.php');
    exit;
}

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : null;

function rooms_back_with_errors(array $errors, array $old, ?int $id): void
{
    $_SESSION['room_errors'] = $errors;
    $_SESSION['room_old'] = $old;
    header('Location: /rooms/form.php' . ($id ? ('?id=' . $id) : ''));
    exit;
}

$old = [
    'ma_phong' => trim((string) ($_POST['ma_phong'] ?? '')),
    'ten_phong' => trim((string) ($_POST['ten_phong'] ?? '')),
    'vi_tri' => trim((string) ($_POST['vi_tri'] ?? '')),
    'suc_chua' => $_POST['suc_chua'] ?? '',
    'trang_thai' => $_POST['trang_thai'] ?? '',
    'mo_ta' => trim((string) ($_POST['mo_ta'] ?? '')),
];

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    rooms_back_with_errors(['Phiên làm việc đã hết hạn, vui lòng thử lại.'], $old, $id);
}

$errors = [];

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
if (!in_array($old['trang_thai'], ['available', 'maintenance', 'closed'], true)) {
    $errors[] = 'Trạng thái không hợp lệ.';
}

if ($old['ma_phong'] !== '') {
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE ma_phong = :ma AND id != :id");
    $stmt->execute(['ma' => $old['ma_phong'], 'id' => $id ?? 0]);
    if ($stmt->fetch()) {
        $errors[] = 'Mã phòng này đã tồn tại.';
    }
}

if ($errors) {
    rooms_back_with_errors($errors, $old, $id);
}

$mo_ta = $old['mo_ta'] !== '' ? $old['mo_ta'] : null;

if ($id) {
    $stmt = $pdo->prepare(
        "UPDATE rooms SET ma_phong = :ma, ten_phong = :ten, vi_tri = :vt, suc_chua = :sc, trang_thai = :tt, mo_ta = :mt
         WHERE id = :id"
    );
    $stmt->execute([
        'ma' => $old['ma_phong'],
        'ten' => $old['ten_phong'],
        'vt' => $old['vi_tri'],
        'sc' => (int) $old['suc_chua'],
        'tt' => $old['trang_thai'],
        'mt' => $mo_ta,
        'id' => $id,
    ]);
    flash_set('success', 'Đã cập nhật phòng thực hành.');
} else {
    $stmt = $pdo->prepare(
        "INSERT INTO rooms (ma_phong, ten_phong, vi_tri, suc_chua, trang_thai, mo_ta)
         VALUES (:ma, :ten, :vt, :sc, :tt, :mt)"
    );
    $stmt->execute([
        'ma' => $old['ma_phong'],
        'ten' => $old['ten_phong'],
        'vt' => $old['vi_tri'],
        'sc' => (int) $old['suc_chua'],
        'tt' => $old['trang_thai'],
        'mt' => $mo_ta,
    ]);
    flash_set('success', 'Đã thêm phòng thực hành mới.');
}

header('Location: /rooms/list.php');
exit;