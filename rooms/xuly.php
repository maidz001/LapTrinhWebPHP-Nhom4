<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Phong.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$phongModel = new Phong($pdo);

$mode       = $_POST['mode'] ?? 'them';
$id         = (int)($_POST['id'] ?? 0);
$ma_phong   = trim($_POST['ma_phong']   ?? '');
$ten_phong  = trim($_POST['ten_phong']  ?? '');
$vi_tri     = trim($_POST['vi_tri']     ?? '');
$loai_phong = trim($_POST['loai_phong'] ?? 'Lập trình');
$suc_chua   = (int)($_POST['suc_chua']  ?? 0);
$trang_thai = trim($_POST['trang_thai'] ?? 'available');
$mo_ta      = trim($_POST['mo_ta']      ?? '');

$errors = [];

if ($ma_phong === '') {
    $errors['ma_phong'] = 'Mã phòng không được để trống';
} elseif (mb_strlen($ma_phong) > 20) {
    $errors['ma_phong'] = 'Mã phòng tối đa 20 ký tự';
}

if ($ten_phong === '') {
    $errors['ten_phong'] = 'Tên phòng không được để trống';
}

if ($vi_tri === '') {
    $errors['vi_tri'] = 'Vui lòng nhập vị trí';
}

if ($suc_chua <= 0) {
    $errors['suc_chua'] = 'Sức chứa phải lớn hơn 0';
}

// Form Thêm/Sửa chỉ cho phép 2 trạng thái: Hoạt động / Bảo trì
// Thêm mới chỉ cho 2 trạng thái; Sửa thì cho phép cả "Đã đóng"
$trangThaiHopLe = ($mode === 'sua')
    ? ['available', 'maintenance', 'closed']
    : ['available', 'maintenance'];

if (!in_array($trang_thai, $trangThaiHopLe, true)) {
    $errors['trang_thai'] = 'Trạng thái không hợp lệ';
}


if (empty($errors['ma_phong'])) {
    $excludeId = ($mode === 'sua') ? $id : null;
    if ($phongModel->maPhongTonTai($ma_phong, $excludeId)) {
        $errors['ma_phong'] = 'Mã phòng "' . $ma_phong . '" đã tồn tại';
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old'] = [
        'id'         => $id,
        'ma_phong'   => $ma_phong,
        'ten_phong'  => $ten_phong,
        'vi_tri'     => $vi_tri,
        'loai_phong' => $loai_phong,
        'suc_chua'   => $suc_chua,
        'trang_thai' => $trang_thai,
        'mo_ta'      => $mo_ta,
    ];

    $redirectTo = ($mode === 'sua') ? "edit.php?id={$id}" : 'add.php';
    header('Location: ' . $redirectTo);
    exit;
}

$data = [
    'ma_phong'   => $ma_phong,
    'ten_phong'  => $ten_phong,
    'vi_tri'     => $vi_tri,
    'loai_phong' => $loai_phong,
    'suc_chua'   => $suc_chua,
    'trang_thai' => $trang_thai,
    'mo_ta'      => $mo_ta,
];

try {
    if ($mode === 'sua' && $id > 0) {
        $phongModel->update($id, $data);
        $msg = 'Cập nhật phòng "' . $ten_phong . '" thành công!';
    } else {
        $phongModel->insert($data);
        $msg = 'Thêm phòng "' . $ten_phong . '" thành công!';
    }

    header('Location: index.php?msg=' . urlencode($msg));
    exit;

} catch (PDOException $e) {
    header('Location: index.php?msg=' . urlencode('Lỗi: ' . $e->getMessage()));
    exit;
}