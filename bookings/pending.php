<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/repository.php';

require_role(['admin', 'lab_staff']);

$keyword = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? 'pending');
$type = (string) ($_GET['type'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 3;
$filters = ['keyword' => $keyword, 'status' => $status, 'type' => $type];
$total = countBookings($pdo, $filters);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$bookings = findBookings($pdo, $filters, $page, $perPage);
$success = flash_get('success');
$error = flash_get('error');
$page_title = 'Quản lý yêu cầu';
$active_menu = 'approval';
require_once __DIR__ . '/../includes/app_head.php';
?>

<div class="page-heading">
    <div>
        <h2>Quản lý yêu cầu</h2>
        <p>Tìm kiếm, xem chi tiết, duyệt hoặc từ chối yêu cầu sử dụng.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<section class="content-card">
    <form method="get" class="booking-filter">
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
        <div class="form-group">
            <label for="status">Trạng thái</label>
            <select id="status" name="status">
                <option value="">Tất cả</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="/bookings/pending.php" class="btn btn-secondary">Đặt lại</a>
        </div>
    </form>
</section>

<section class="content-card table-card">
    <div class="table-summary">Tìm thấy <strong><?php echo $total; ?></strong> yêu cầu</div>
    <?php if (empty($bookings)): ?>
        <div class="empty-state">Không có yêu cầu phù hợp.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Mã</th>
                    <th>Người gửi</th>
                    <th>Loại</th>
                    <th>Tài nguyên</th>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $item): ?>
                    <tr>
                        <td>#<?php echo (int) $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['nguoi_gui']); ?></td>
                        <td><?php echo htmlspecialchars(bookingTypeLabel($item['loai_yeu_cau'])); ?></td>
                        <td><?php echo htmlspecialchars((string) $item['tai_nguyen']); ?></td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($item['thoi_gian_bat_dau'])); ?><br>
                            <span class="text-muted">đến <?php echo date('d/m/Y H:i', strtotime($item['thoi_gian_ket_thuc'])); ?></span>
                        </td>
                        <td><span class="status-pill <?php echo htmlspecialchars($item['trang_thai']); ?>"><?php echo htmlspecialchars(bookingStatusLabel($item['trang_thai'])); ?></span></td>
                        <td>
                            <div class="row-actions">
                                <a href="/bookings/detail.php?id=<?php echo (int) $item['id']; ?>" class="btn btn-secondary btn-small">Chi tiết</a>
                                <?php if ($item['trang_thai'] === 'pending'): ?>
                                    <form method="post" action="/bookings/approve.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-small">Duyệt</button>
                                    </form>
                                <?php endif; ?>
                            </div>
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
                <?php $query = http_build_query(['q' => $keyword, 'type' => $type, 'status' => $status, 'page' => $i]); ?>
                <a href="?<?php echo htmlspecialchars($query); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>
