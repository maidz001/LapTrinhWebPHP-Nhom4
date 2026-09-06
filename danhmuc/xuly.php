<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DanhMuc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$dmModel = new DanhMuc($pdo);

$mode        = $_POST['mode'] ?? 'them';
$id          = (int)($_POST['id'] ?? 0);
$ma_danh_muc = trim($_POST['ma_danh_muc'] ?? '');
$ten_loai    = trim($_POST['ten_loai']    ?? '');
$mo_ta       = trim($_POST['mo_ta']       ?? '');

$errors = [];

if ($ma_danh_muc === '') {
    $errors['ma_danh_muc'] = 'Mã danh mục không được để trống';
}
if ($ten_loai === '') {
    $errors['ten_loai'] = 'Tên danh mục không được để trống';
}

if (empty($errors['ma_danh_muc'])) {
    $excludeId = ($mode === 'sua') ? $id : null;
    if ($dmModel->maTonTai($ma_danh_muc, $excludeId)) {
        $errors['ma_danh_muc'] = 'Mã danh mục "' . $ma_danh_muc . '" đã tồn tại';
    }
}

if (empty($errors['ten_loai'])) {
    $excludeId = ($mode === 'sua') ? $id : null;
    if ($dmModel->tenTonTai($ten_loai, $excludeId)) {
        $errors['ten_loai'] = 'Tên danh mục "' . $ten_loai . '" đã tồn tại';
    }
}

if (!empty($errors)) {
    $_SESSION['dm_errors'] = $errors;
    $_SESSION['dm_old'] = [
        'id'          => $id,
        'ma_danh_muc' => $ma_danh_muc,
        'ten_loai'    => $ten_loai,
        'mo_ta'       => $mo_ta,
    ];

    $redirectTo = ($mode === 'sua') ? "edit.php?id={$id}" : 'add.php';
    header('Location: ' . $redirectTo);
    exit;
}

$data = [
    'ma_danh_muc' => $ma_danh_muc,
    'ten_loai'    => $ten_loai,
    'mo_ta'       => $mo_ta,
];

try {
    if ($mode === 'sua' && $id > 0) {
        $dmModel->update($id, $data);
        $msg = 'Cập nhật danh mục "' . $ten_loai . '" thành công!';
    } else {
        $dmModel->insert($data);
        $msg = 'Thêm danh mục "' . $ten_loai . '" thành công!';
    }

    header('Location: index.php?msg=' . urlencode($msg));
    exit;

} catch (PDOException $e) {
    header('Location: index.php?msg=' . urlencode('Lỗi: ' . $e->getMessage()));
    exit;
}