<?php
/**
 * equipment_types/list.php
 * ---------------------------------------------------------------------
 * Tổng quan danh mục thiết bị: mỗi danh mục (loại thiết bị) hiển thị
 * dưới dạng 1 div/card riêng, cho biết tên danh mục và số lượng thiết
 * bị tương ứng (tổng số + phân theo trạng thái). Đây là màn hình xem
 * thông tin sơ bộ, KHÔNG có nút thêm/sửa/xoá.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_role(['admin', 'lab_staff']);

$sql = "SELECT
            et.id,
            et.ten_loai,
            et.mo_ta,
            COUNT(e.id) AS tong_sl,
            SUM(CASE WHEN e.trang_thai = 'active' THEN 1 ELSE 0 END) AS dang_hoat_dong,
            SUM(CASE WHEN e.trang_thai = 'broken' THEN 1 ELSE 0 END) AS hong,
            SUM(CASE WHEN e.trang_thai = 'maintenance' THEN 1 ELSE 0 END) AS bao_tri,
            SUM(CASE WHEN e.trang_thai = 'borrowed' THEN 1 ELSE 0 END) AS dang_muon
        FROM equipment_types et
        LEFT JOIN equipment e ON e.type_id = et.id
        GROUP BY et.id
        ORDER BY et.ten_loai";
$danhMucList = $pdo->query($sql)->fetchAll();

$page_title = 'Danh mục thiết bị';
$active_menu = 'eq_types';
require_once __DIR__ . '/../includes/app_head.php';
?>

<p class="chart-sub" style="margin-top:-8px;">
    Thông tin sơ bộ về từng loại thiết bị trong hệ thống: tổng số lượng và tình trạng hiện tại.
</p>

<?php if (empty($danhMucList)): ?>
    <div class="empty-state">Chưa có danh mục thiết bị nào trong hệ thống.</div>
<?php else: ?>
    <div class="overview-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
        <?php foreach ($danhMucList as $dm): ?>
            <div class="overview-card">
                <span class="card-icon blue"><?php echo app_icon('list'); ?></span>
                <div class="card-label" style="font-size:1.05rem;font-weight:700;color:var(--color-text);">
                    <?php echo htmlspecialchars($dm['ten_loai']); ?>
                </div>
                <div class="card-value"><?php echo (int) $dm['tong_sl']; ?></div>
                <div class="card-label">thiết bị</div>

                <?php if ((int) $dm['tong_sl'] > 0): ?>
                    <div class="card-delta up" style="margin-top:8px;">
                        <?php echo (int) $dm['dang_hoat_dong']; ?> đang hoạt động
                    </div>
                    <?php if ((int) $dm['bao_tri'] > 0): ?>
                        <div class="card-delta warn"><?php echo (int) $dm['bao_tri']; ?> đang bảo trì</div>
                    <?php endif; ?>
                    <?php if ((int) $dm['hong'] > 0): ?>
                        <div class="card-delta danger"><?php echo (int) $dm['hong']; ?> bị hỏng</div>
                    <?php endif; ?>
                    <?php if ((int) $dm['dang_muon'] > 0): ?>
                        <div class="card-delta" style="color:#155e75;"><?php echo (int) $dm['dang_muon']; ?> đang được mượn</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="card-delta" style="color:var(--color-text-muted, #64748b);margin-top:8px;">Chưa có thiết bị nào</div>
                <?php endif; ?>

                <?php if (!empty($dm['mo_ta'])): ?>
                    <p class="text-muted" style="margin-top:12px;font-size:.85rem;">
                        <?php echo htmlspecialchars($dm['mo_ta']); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
