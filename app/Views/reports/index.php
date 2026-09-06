<?php
/**
 * app/Views/reports/index.php
 * View thuần hiển thị — không chứa SQL. Biến truyền vào từ
 * ReportController::index(): $reports, $statusFilter, $canManage,
 * $flashSuccess, $flashError
 */
function mvc_report_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Mới',
        'processing' => 'Đang xử lý',
        'resolved' => 'Đã xử lý',
        'cancelled' => 'Đã huỷ',
        default => $status,
    };
}

function mvc_report_status_style(string $status): string
{
    return match ($status) {
        'new' => 'background:var(--color-warning-bg);color:#92400e;',
        'processing' => 'background:var(--color-info-bg);color:#155e75;',
        'resolved' => 'background:var(--color-success-bg);color:#166534;',
        'cancelled' => 'background:#f1f5f9;color:var(--color-text-muted);',
        default => '',
    };
}

function mvc_report_level_label(string $level): string
{
    return match ($level) {
        'low' => 'Thấp',
        'medium' => 'Trung bình',
        'high' => 'Cao',
        default => $level,
    };
}

$page_title = 'Báo cáo';
$active_menu = 'reports';
require_once __DIR__ . '/../../../includes/app_head.php';
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
<?php endif; ?>

<form method="get" class="filter-form" style="display:inline-block;">
    <label style="display:inline-block;margin-right:8px;">Trạng thái</label>
    <select name="trang_thai" onchange="this.form.submit()">
        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
        <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>Mới</option>
        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
        <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Đã xử lý</option>
        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Đã huỷ</option>
    </select>
</form>

<p style="margin:16px 0;">
    <a href="/mvc/reports/create" class="btn btn-primary">+ Báo hỏng thiết bị</a>
</p>

<?php if (empty($reports)): ?>
    <div class="empty-state">Không có báo cáo nào phù hợp.</div>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Thiết bị</th>
            <?php if ($canManage): ?><th>Người báo</th><?php endif; ?>
            <th>Mô tả sự cố</th>
            <th>Mức độ</th>
            <th>Trạng thái</th>
            <th>Thời gian</th>
            <?php if ($canManage): ?><th>Cập nhật</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['ma_thiet_bi'] . ' - ' . $r['ten_thiet_bi']); ?></td>
                <?php if ($canManage): ?><td><?php echo htmlspecialchars($r['nguoi_bao']); ?></td><?php endif; ?>
                <td><?php echo htmlspecialchars($r['mo_ta_su_co']); ?></td>
                <td><?php echo mvc_report_level_label($r['muc_do']); ?></td>
                <td>
                    <span style="<?php echo mvc_report_status_style($r['trang_thai']); ?>padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:600;">
                        <?php echo mvc_report_status_label($r['trang_thai']); ?>
                    </span>
                </td>
                <td><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
                <?php if ($canManage): ?>
                    <td>
                        <form method="post" action="/mvc/reports/update-status" style="display:flex;gap:6px;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <select name="trang_thai" style="width:auto;">
                                <?php foreach (['new', 'processing', 'resolved', 'cancelled'] as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $r['trang_thai'] === $s ? 'selected' : ''; ?>>
                                        <?php echo mvc_report_status_label($s); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-secondary">Cập nhật</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
