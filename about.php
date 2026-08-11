<?php
$ten_nhom = "Nhóm 4";
$thanh_vien = ["Nguyễn Hồng Mai", "Nguyễn Kỳ", "Triệu Văn Phấn","Đặng Quang Trung","Nguyễn Mạnh Hiếu"];
$de_tai = "Website Hệ thống quản lý phòng thực hành và thiết bị";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giới thiệu nhóm</title>
</head>
<body>
    <h1>Giới thiệu nhóm <?php echo $ten_nhom; ?></h1>

    <h2>Thành viên</h2>
    <ul>
        <?php foreach ($thanh_vien as $tv): ?>
            <li><?php echo $tv; ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Đề tài dự kiến</h2>
    <p><?php echo $de_tai; ?></p>
</body>
</html>