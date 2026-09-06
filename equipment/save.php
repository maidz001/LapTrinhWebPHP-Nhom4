<?php
/**
 * equipment/save.php
 * ---------------------------------------------------------------------
 * Xử lý thêm mới / cập nhật thiết bị (POST từ equipment/form.php).
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role(['admin', 'lab_staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /equipment/list.php');
    exit;
}

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : null;

function equipment_back_with_errors(array $errors, array $old, ?int $id): void
{
    $_SESSION['equipment_errors'] = $errors;
    $_SESSION['equipment_old'] = $old;
    header('Location: /equipment/form.php' . ($id ? ('?id=' . $id) : ''));
    exit;
}

$old = [
    'ma_thiet_bi' => trim((string) ($_POST['ma_thiet_bi'] ?? '')),
    'ten_thiet_bi' => trim((string) ($_POST['ten_thiet_bi'] ?? '')),
    'type_id' => $_POST['type_id'] ?? '',
    'room_id' => $_POST['room_id'] ?? '',
    'co_the_muon' => isset($_POST['co_the_muon']) ? 1 : 0,
    'trang_thai' => $_POST['trang_thai'] ?? '',
    'ngay_mua' => trim((string) ($_POST['ngay_mua'] ?? '')),
    'mo_ta' => trim((string) ($_POST['mo_ta'] ?? '')),
];

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    equipment_back_with_errors(['Phiên làm việc đã hết hạn, vui lòng thử lại.'], $old, $id);
}

$errors = [];

if ($old['ma_thiet_bi'] === '' || mb_strlen($old['ma_thiet_bi']) > 30) {
    $errors[] = 'Mã thiết bị không được để trống và tối đa 30 ký tự.';
}
if ($old['ten_thiet_bi'] === '' || mb_strlen($old['ten_thiet_bi']) > 150) {
    $errors[] = 'Tên thiết bị không được để trống và tối đa 150 ký tự.';
}
if (!in_array($old['trang_thai'], ['active', 'broken', 'maintenance', 'borrowed'], true)) {
    $errors[] = 'Trạng thái không hợp lệ.';
}

$typeId = ctype_digit((string) $old['type_id']) ? (int) $old['type_id'] : null;
if (!$typeId) {
    $errors[] = 'Vui lòng chọn loại thiết bị.';
} else {
    $stmt = $pdo->prepare("SELECT id FROM equipment_types WHERE id = :id");
    $stmt->execute(['id' => $typeId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Loại thiết bị không tồn tại.';
    }
}

$roomId = null;
if ($old['room_id'] !== '') {
    $roomId = ctype_digit((string) $old['room_id']) ? (int) $old['room_id'] : null;
    if (!$roomId) {
        $errors[] = 'Phòng đã chọn không hợp lệ.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $roomId]);
        if (!$stmt->fetch()) {
            $errors[] = 'Phòng đã chọn không tồn tại.';
        }
    }
}

$ngayMua = null;
if ($old['ngay_mua'] !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $old['ngay_mua']);
    if (!$d || $d->format('Y-m-d') !== $old['ngay_mua']) {
        $errors[] = 'Ngày mua không hợp lệ.';
    } else {
        $ngayMua = $old['ngay_mua'];
    }
}

if ($old['ma_thiet_bi'] !== '') {
    $stmt = $pdo->prepare("SELECT id FROM equipment WHERE ma_thiet_bi = :ma AND id != :id");
    $stmt->execute(['ma' => $old['ma_thiet_bi'], 'id' => $id ?? 0]);
    if ($stmt->fetch()) {
        $errors[] = 'Mã thiết bị này đã tồn tại.';
    }
}

if ($errors) {
    equipment_back_with_errors($errors, $old, $id);
}

$moTa = $old['mo_ta'] !== '' ? $old['mo_ta'] : null;

$params = [
    'ma' => $old['ma_thiet_bi'],
    'ten' => $old['ten_thiet_bi'],
    'type_id' => $typeId,
    'room_id' => $roomId,
    'muon' => $old['co_the_muon'],
    'tt' => $old['trang_thai'],
    'ngay_mua' => $ngayMua,
    'mt' => $moTa,
];

if ($id) {
    $params['id'] = $id;
    $stmt = $pdo->prepare(
        "UPDATE equipment SET ma_thiet_bi = :ma, ten_thiet_bi = :ten, type_id = :type_id, room_id = :room_id,
                co_the_muon = :muon, trang_thai = :tt, ngay_mua = :ngay_mua, mo_ta = :mt
         WHERE id = :id"
    );
    $stmt->execute($params);
    flash_set('success', 'Đã cập nhật thiết bị.');
} else {
    $stmt = $pdo->prepare(
        "INSERT INTO equipment (ma_thiet_bi, ten_thiet_bi, type_id, room_id, co_the_muon, trang_thai, ngay_mua, mo_ta)
         VALUES (:ma, :ten, :type_id, :room_id, :muon, :tt, :ngay_mua, :mt)"
    );
    $stmt->execute($params);
    flash_set('success', 'Đã thêm thiết bị mới.');
}

header('Location: /equipment/list.php');
exit;