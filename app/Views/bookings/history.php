<?php
/**
 * app/Views/bookings/history.php
 * Biến truyền vào từ BookingController::history():
 *   $bookings, $isStaff, $total, $page, $totalPages, $keyword, $type, $status
 */
require_once __DIR__ . '/_status_style.php';

$page_title = 'Lịch sử sử dụng';
$active_menu = 'history';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<div class="page-heading">
    <div>
        <h2>Lịch sử sử dụng</h2>
        <p>Các yêu cầu đã được duyệt, từ chối, hủy hoặc đang chờ xử lý.</p>
    </div>
</div>

<section class="content-card">
    <form method="get" class="booking-filter history-filter">
        <div class="form-group">
            <label for="q">Tìm kiếm</label>
            <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Mục đích hoặc tài nguyên">
        </div>
        <div class="form-group">
            <label for="type">Loại yêu cầu</label>
            <select id="type" name="type">
                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                <option value="room" <?php echo $type === 'room' ? 'selected' : ''; ?>>Đặt phòng</option>
                <option value="equipment" <?php echo $type === 'equipment' ? 'selected' : ''; ?>>Mượn thiết bị</option>
            </select>
        </div>
        <div class="form-group">
            <label for="trang_thai">Trạng thái</label>
            <select id="trang_thai" name="trang_thai">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Đã huỷ</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            <a href="/mvc/bookings/history" class="btn btn-secondary">Đặt lại</a>
        </div>
    </form>
</section>

<section class="content-card table-card">
    <div class="table-summary">Có <strong><?php echo (int) $total; ?></strong> yêu cầu</div>

    <?php if (empty($bookings)): ?>
        <div class="empty-state">Không có yêu cầu nào phù hợp với bộ lọc.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Mã</th>
                    <?php if ($isStaff): ?><th>Người gửi</th><?php endif; ?>
                    <th>Loại</th>
                    <th>Tài nguyên</th>
                    <th>Thời gian</th>
                    <th>Mục đích</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $item): ?>
                    <tr>
                        <td>#<?php echo (int) $item['id']; ?></td>
                        <?php if ($isStaff): ?>
                            <td><?php echo htmlspecialchars((string) ($item['nguoi_gui'] ?? '-')); ?></td>
                        <?php endif; ?>
                        <td><?php echo htmlspecialchars(Booking::typeLabel($item['loai_yeu_cau'])); ?></td>
                        <td>
                            <?php
                            if (!empty($item['tai_nguyen'])) {
                                echo htmlspecialchars((string) $item['tai_nguyen']);
                            } elseif ($item['loai_yeu_cau'] === 'room') {
                                echo htmlspecialchars(($item['ma_phong'] ?? '') . ' - ' . ($item['ten_phong'] ?? ''));
                            } else {
                                echo htmlspecialchars(($item['ma_thiet_bi'] ?? '') . ' - ' . ($item['ten_thiet_bi'] ?? ''));
                            }
                            ?>
                        </td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($item['thoi_gian_bat_dau'])); ?><br>
                            <small>đến <?php echo date('d/m/Y H:i', strtotime($item['thoi_gian_ket_thuc'])); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars((string) ($item['muc_dich'] ?? '')); ?></td>
                        <td>
                            <span class="status-pill <?php echo htmlspecialchars($item['trang_thai']); ?>"
                                  style="<?php echo mvc_booking_status_style($item['trang_thai']); ?>padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">
                                <?php echo htmlspecialchars(Booking::statusLabel($item['trang_thai'])); ?>
                            </span>
                        </td>
                        <td><?php echo !empty($item['ly_do_tu_choi']) ? htmlspecialchars($item['ly_do_tu_choi']) : '&mdash;'; ?></td>
                        <td>
                            <a href="/mvc/bookings/detail?id=<?php echo (int) $item['id']; ?>" class="btn btn-secondary btn-small">Chi tiết</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Phân trang">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $query = http_build_query(['q' => $keyword, 'type' => $type, 'trang_thai' => $status, 'page' => $i]); ?>
                <a href="?<?php echo htmlspecialchars($query); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
