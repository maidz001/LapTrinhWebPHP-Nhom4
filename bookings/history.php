<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/repository.php';

require_login();

$user = current_user();
$isStaff = in_array($user['role'], ['admin', 'lab_staff'], true);
$keyword = trim((string) ($_GET['q'] ?? ''));
$type = (string) ($_GET['type'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 3;
$filters = [
    'owner_id' => $isStaff ? 0 : (int) $user['id'],
    'keyword' => $keyword,
    'status' => 'processed',
    'type' => $type,
];
$total = countBookings($pdo, $filters);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$bookings = findBookings($pdo, $filters, $page, $perPage);
$page_title = 'Lịch sử dụng';
$active_menu = 'history';
require_once __DIR__ . '/../includes/app_head.php';
?>

<div class="page-heading">
    <div>
        <h2>Lịch sử sử dụng</h2>
        <p>Các yêu cầu đã được duyệt, từ chối hoặc hủy.</p>
    </div>
</div>

<section class="content-card">
    <form method="get" class="booking-filter history-filter">
        <div class="form-group">
            <label for="q">Tìm kiếm</label>
            <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Người gửi, mục đích hoặc tài nguyên">
        </div>
        <div class="form-group">
            <label for="type">Loại yêu cầu</label>
            <select id="type" name="type">
                <option value="">Tất cả</option>
                <option value="room" <?php echo $type === 'room' ? 'selected' : ''; ?>>Đặt phòng</option>
                <option value="equipment" <?php echo $type === 'equipment' ? 'selected' : ''; ?>>Mượn thiết bị</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Tìm</button>
            <a href="/bookings/history.php" class="btn btn-secondary">Đặt lại</a>
        </div>
    </form>
</section>

<section class="content-card table-card">
    <div class="table-summary">Có <strong><?php echo $total; ?></strong> yêu cầu đã xử lý</div>
    <?php if (empty($bookings)): ?>
        <div class="empty-state">Chưa có lịch sử phù hợp.</div>
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
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $item): ?>
                    <tr>
                        <td>#<?php echo (int) $item['id']; ?></td>
                        <?php if ($isStaff): ?><td><?php echo htmlspecialchars($item['nguoi_gui']); ?></td><?php endif; ?>
                        <td><?php echo htmlspecialchars(bookingTypeLabel($item['loai_yeu_cau'])); ?></td>
                        <td><?php echo htmlspecialchars((string) $item['tai_nguyen']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($item['thoi_gian_bat_dau'])); ?></td>
                        <td><span class="status-pill <?php echo htmlspecialchars($item['trang_thai']); ?>"><?php echo htmlspecialchars(bookingStatusLabel($item['trang_thai'])); ?></span></td>
                        <td><a href="/bookings/detail.php?id=<?php echo (int) $item['id']; ?>" class="btn btn-secondary btn-small">Chi tiết</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Phân trang">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $query = http_build_query(['q' => $keyword, 'type' => $type, 'page' => $i]); ?>
                <a href="?<?php echo htmlspecialchars($query); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
