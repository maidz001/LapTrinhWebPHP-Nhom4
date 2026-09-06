<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThietBi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$tbModel = new ThietBi($pdo);

$mode         = $_POST['mode'] ?? 'them';
$id           = (int)($_POST['id'] ?? 0);
$ma_thiet_bi  = trim($_POST['ma_thiet_bi']  ?? '');
$ten_thiet_bi = trim($_POST['ten_thiet_bi'] ?? '');
$type_id      = (int)($_POST['type_id']     ?? 0);
$room_id      = (int)($_POST['room_id']     ?? 0);
$so_luong     = (int)($_POST['so_luong']    ?? 0);
$gia_tri      = trim($_POST['gia_tri']      ?? '');
$ngay_mua     = trim($_POST['ngay_mua']     ?? '');
$trang_thai   = trim($_POST['trang_thai']   ?? 'active');
$mo_ta        = trim($_POST['mo_ta']        ?? '');

$errors = [];

if ($ma_thiet_bi === '') {
    $errors['ma_thiet_bi'] = 'Mã thiết bị không được để trống';
}
if ($ten_thiet_bi === '') {
    $errors['ten_thiet_bi'] = 'Tên thiết bị không được để trống';
}
if ($so_luong <= 0) {
    $errors['so_luong'] = 'Số lượng phải lớn hơn 0';
}

$trangThaiHopLe = ($mode === 'sua')
        ? ['active', 'maintenance', 'broken', 'borrowed']
        : ['active', 'maintenance'];

if (!in_array($trang_thai, $trangThaiHopLe, true)) {
    $errors['trang_thai'] = 'Trạng thái không hợp lệ';
}

if (empty($errors['ma_thiet_bi'])) {
    $excludeId = ($mode === 'sua') ? $id : null;
    if ($tbModel->maThietBiTonTai($ma_thiet_bi, $excludeId)) {
        $errors['ma_thiet_bi'] = 'Mã thiết bị "' . $ma_thiet_bi . '" đã tồn tại';
    }
}

if (!empty($errors)) {
    $_SESSION['tb_errors'] = $errors;
    $_SESSION['tb_old'] = [
            'id'           => $id,
            'ma_thiet_bi'  => $ma_thiet_bi,
            'ten_thiet_bi' => $ten_thiet_bi,
            'type_id'      => $type_id,
            'room_id'      => $room_id,
            'so_luong'     => $so_luong,
            'gia_tri'      => $gia_tri,
            'ngay_mua'     => $ngay_mua,
            'trang_thai'   => $trang_thai,
            'mo_ta'        => $mo_ta,
    ];

    $redirectTo = ($mode === 'sua') ? "edit.php?id={$id}" : 'add.php';
    header('Location: ' . $redirectTo);
    exit;
}

$data = [
        'ma_thiet_bi'  => $ma_thiet_bi,
        'ten_thiet_bi' => $ten_thiet_bi,
        'type_id'      => $type_id,
        'room_id'      => $room_id,
        'so_luong'     => $so_luong,
        'gia_tri'      => $gia_tri,
        'ngay_mua'     => $ngay_mua,
        'trang_thai'   => $trang_thai,
        'mo_ta'        => $mo_ta,
];

try {
    if ($mode === 'sua' && $id > 0) {
        $tbModel->update($id, $data);
        $msg = 'Cập nhật thiết bị "' . $ten_thiet_bi . '" thành công!';
    } else {
        $tbModel->insert($data);
        $msg = 'Thêm thiết bị "' . $ten_thiet_bi . '" thành công!';
    }

    header('Location: index.php?msg=' . urlencode($msg));
    exit;

} catch (PDOException $e) {
    header('Location: index.php?msg=' . urlencode('Lỗi: ' . $e->getMessage()));
    exit;
}