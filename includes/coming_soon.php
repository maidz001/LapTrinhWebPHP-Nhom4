<?php
/**
 * includes/coming_soon.php
 * ---------------------------------------------------------------------
 * Khối placeholder dùng chung cho các trang chưa xây dựng chức năng
 * (chỉ hiển thị bố cục, chưa xử lý dữ liệu). Dùng bên trong khung
 * app_head.php / app_foot.php.
 *
 * Cách dùng:
 *   $cs_title = 'Phòng thực hành';
 *   $cs_desc  = 'Danh sách và quản lý phòng thực hành...';
 *   $cs_icon  = 'grid';
 *   require __DIR__ . '/coming_soon.php';
 * ---------------------------------------------------------------------
 */
$cs_title = $cs_title ?? 'Chức năng đang phát triển';
$cs_desc  = $cs_desc ?? 'Tính năng này sẽ sớm được hoàn thiện trong phiên bản tiếp theo.';
$cs_icon  = $cs_icon ?? 'settings';
?>
<div class="coming-soon">
    <div class="cs-icon"><?php echo app_icon($cs_icon); ?></div>
    <h2><?php echo htmlspecialchars($cs_title); ?></h2>
    <p><?php echo htmlspecialchars($cs_desc); ?></p>
    <span class="cs-badge">🚧 Đang phát triển</span>
</div>
