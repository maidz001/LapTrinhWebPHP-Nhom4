<?php
/**
 * app/Views/equipment/index.php
 * Biến truyền vào từ EquipmentController::index():
 *   $equipmentList, $types, $typeFilter, $statusFilter, $q, $canManage,
 *   $flashSuccess, $flashError
 */
function mvc_equipment_status_label(string $status): string
{
    return match ($status) {
        'active' => 'Hoạt động',
        'broken' => 'Hỏng',
        'maintenance' => 'Đang bảo trì',
        'borrowed' => 'Đang được mượn',
        default => $status,
    };
}

function mvc_equipment_status_style(string $status): string
{
    return match ($status) {
        'active' => 'background:var(--color-success-bg);color:#166534;',
        'broken' => 'background:var(--color-error-bg);color:#991b1b;',
        'maintenance' => 'background:var(--color-warning-bg);color:#92400e;',
        'borrowed' => 'background:var(--color-info-bg);color:#155e75;',
        default => '',
    };
}

$page_title = 'Thiết bị';
$active_menu = 'equipment';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
<?php endif; ?>

<form method="get" class="filter-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <input
        type="text"
        name="q"
        class="search-input"
        placeholder="Tìm thiết bị theo tên..."
        value="<?php echo htmlspecialchars($q); ?>"
        style="flex:1 1 320px;max-width:420px;"
    >
    <button type="submit" class="btn btn-secondary" style="padding:9px 14px;">Tìm kiếm</button>
    <?php if ($q !== ''): ?>
        <a href="/mvc/equipment" class="btn btn-secondary">Xoá lọc</a>
    <?php endif; ?>
</form>

<form method="get" class="filter-form">
    <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
    <label style="display:inline-block;margin-right:8px;">Loại</label>
    <select name="type_id" onchange="this.form.submit()">
        <option value="">Tất cả</option>
        <?php foreach ($types as $t): ?>
            <option value="<?php echo $t['id']; ?>" <?php echo $typeFilter === (int) $t['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($t['ten_loai']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label style="display:inline-block;margin:0 8px 0 16px;">Trạng thái</label>
    <select name="trang_thai" onchange="this.form.submit()">
        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
        <option value="broken" <?php echo $statusFilter === 'broken' ? 'selected' : ''; ?>>Hỏng</option>
        <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Đang bảo trì</option>
        <option value="borrowed" <?php echo $statusFilter === 'borrowed' ? 'selected' : ''; ?>>Đang được mượn</option>
    </select>
</form>

<p style="margin:0 0 16px;display:flex;gap:10px;flex-wrap:wrap;">
    <?php if ($canManage): ?>
        <a href="/mvc/equipment/form" class="btn btn-primary">+ Thêm thiết bị</a>
        <a href="/mvc/equipment/import" class="btn btn-secondary">⇪ Thêm từ file</a>
    <?php endif; ?>
    <a href="/mvc/equipment/export?q=<?php echo urlencode($q); ?>&type_id=<?php echo $typeFilter ? (int) $typeFilter : ''; ?>&trang_thai=<?php echo urlencode($statusFilter); ?>" class="btn btn-secondary">⇩ Xuất file</a>
</p>

<?php if (empty($equipmentList)): ?>
    <div class="empty-state">Không có thiết bị nào phù hợp với bộ lọc.</div>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Mã</th>
            <th>Tên thiết bị</th>
            <th>Loại</th>
            <th>Vị trí</th>
            <th>Cho mượn?</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($equipmentList as $e): ?>
            <tr>
                <td><?php echo htmlspecialchars($e['ma_thiet_bi']); ?></td>
                <td><?php echo htmlspecialchars($e['ten_thiet_bi']); ?></td>
                <td><?php echo htmlspecialchars($e['ten_loai']); ?></td>
                <td><?php echo $e['room_id'] ? htmlspecialchars($e['ma_phong'] . ' - ' . $e['ten_phong']) : 'Thiết bị lưu động'; ?></td>
                <td><?php echo $e['co_the_muon'] ? 'Có' : 'Không'; ?></td>
                <td>
                    <span style="<?php echo mvc_equipment_status_style($e['trang_thai']); ?>padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">
                        <?php echo mvc_equipment_status_label($e['trang_thai']); ?>
                    </span>
                </td>
                <td>
                    <a href="/mvc/reports/create?equipment_id=<?php echo $e['id']; ?>" class="btn btn-secondary">Báo hỏng</a>
                    <?php if ($canManage): ?>
                        <a href="/mvc/equipment/form?id=<?php echo $e['id']; ?>" class="btn btn-secondary">Sửa</a>
                        <form method="post" action="/mvc/equipment/delete" style="display:inline;"
                              onsubmit="return confirm('Bạn có chắc muốn xoá thiết bị này?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $e['id']; ?>">
                            <button type="submit" class="btn btn-danger">Xoá</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
