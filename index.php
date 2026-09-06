<?php
/**
 * index.php — Trang chủ (Tổng quan)
 * Chỉ người dùng đã đăng nhập mới xem được; khách chưa đăng nhập sẽ bị
 * chuyển hướng sang auth/login.php (xem require_login() trong auth_check.php).
 *
 * QUAN TRỌNG: Toàn bộ số liệu trên trang này lấy TRỰC TIẾP từ CSDL bằng
 * truy vấn thật (không có dữ liệu minh hoạ/giả). Nếu bảng chưa có dữ liệu,
 * giao diện hiển thị rõ "Chưa có dữ liệu" thay vì bịa số.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

// ---- 4 thẻ số liệu: truy vấn thật ----
$roomsAvailable = (int) $pdo->query("SELECT COUNT(*) FROM rooms WHERE trang_thai = 'available'")->fetchColumn();
$roomsTotal     = (int) $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$equipmentTotal = (int) $pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
$roomsInUseNow  = (int) $pdo->query(
    "SELECT COUNT(DISTINCT room_id) FROM bookings
     WHERE loai_yeu_cau = 'room' AND trang_thai = 'approved'
       AND NOW() BETWEEN thoi_gian_bat_dau AND thoi_gian_ket_thuc"
)->fetchColumn();
$equipmentMaintenance = (int) $pdo->query(
    "SELECT COUNT(*) FROM equipment WHERE trang_thai IN ('broken','maintenance')"
)->fetchColumn();

// ---- Biểu đồ 1: Lượt sử dụng phòng thực hành 7 ngày gần nhất ----
// Đếm số booking phòng có thoi_gian_bat_dau rơi vào từng ngày trong 7 ngày qua
// (tính cả hôm nay), không phân biệt trạng thái duyệt hay chưa.
$stmt = $pdo->prepare(
    "SELECT DATE(thoi_gian_bat_dau) AS ngay, COUNT(*) AS so_luot
     FROM bookings
     WHERE loai_yeu_cau = 'room'
       AND thoi_gian_bat_dau >= (CURDATE() - INTERVAL 6 DAY)
       AND thoi_gian_bat_dau <  (CURDATE() + INTERVAL 1 DAY)
     GROUP BY DATE(thoi_gian_bat_dau)"
);
$stmt->execute();
$usageByDate = [];
foreach ($stmt->fetchAll() as $row) {
    $usageByDate[$row['ngay']] = (int) $row['so_luot'];
}

$dayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']; // Thứ 2 -> Chủ nhật
$weeklyUsage = [];
for ($i = 6; $i >= 0; $i--) {
    $date = (new DateTimeImmutable())->modify("-{$i} day");
    $isoWeekday = (int) $date->format('N'); // 1 = Thứ 2 ... 7 = Chủ nhật
    $weeklyUsage[] = [
        'label' => $dayLabels[$isoWeekday - 1],
        'value' => $usageByDate[$date->format('Y-m-d')] ?? 0,
    ];
}
$totalWeeklyUsage = array_sum(array_column($weeklyUsage, 'value'));
$maxUsage  = $totalWeeklyUsage > 0 ? max(array_column($weeklyUsage, 'value')) : 0;
$yScaleTop = $maxUsage > 0 ? (int) (ceil($maxUsage / 4) * 4) : 1;

// ---- Biểu đồ 2: Tình trạng thiết bị — nhóm thật theo cột trang_thai ----
$stmt = $pdo->query("SELECT trang_thai, COUNT(*) AS so_luong FROM equipment GROUP BY trang_thai");
$statusRows = $stmt->fetchAll();

$statusMeta = [
    'active'      => ['label' => 'Hoạt động tốt', 'color' => '#2563eb'],
    'maintenance' => ['label' => 'Đang bảo trì',   'color' => '#f59e0b'],
    'broken'      => ['label' => 'Hỏng',           'color' => '#dc2626'],
    'borrowed'    => ['label' => 'Đang cho mượn',  'color' => '#16a34a'],
];

$equipmentStatus = [];
foreach ($statusRows as $row) {
    $key  = $row['trang_thai'];
    $meta = $statusMeta[$key] ?? ['label' => $key, 'color' => '#9ca3af'];
    $equipmentStatus[] = [
        'label' => $meta['label'],
        'count' => (int) $row['so_luong'],
        'color' => $meta['color'],
    ];
}

$circumference = 2 * M_PI * 70; // r = 70
$donutOffset = 0;
if ($equipmentTotal > 0) {
    foreach ($equipmentStatus as $i => $seg) {
        $equipmentStatus[$i]['pct']    = round($seg['count'] / $equipmentTotal * 100);
        $equipmentStatus[$i]['dash']   = round($seg['count'] / $equipmentTotal * $circumference, 2);
        $equipmentStatus[$i]['offset'] = $donutOffset;
        $donutOffset += $equipmentStatus[$i]['dash'];
    }
}

$page_title = 'Tổng quan';
$active_menu = 'overview';
require_once __DIR__ . '/includes/app_head.php';
?>
        <h2 style="margin-bottom:4px;">Xin chào, <?php echo htmlspecialchars($user['full_name']); ?> 👋</h2>
        <p style="color:var(--color-text-muted); margin-top:0;">Đây là tổng quan hoạt động của hệ thống phòng thực hành.</p>

        <section class="overview-grid">
            <div class="overview-card">
                <span class="card-icon blue"><?php echo app_icon('grid'); ?></span>
                <div class="card-value"><?php echo $roomsTotal; ?></div>
                <div class="card-label">Phòng thực hành</div>
                <div class="card-delta up"><?php echo $roomsAvailable; ?> phòng đang sẵn sàng</div>
            </div>
            <div class="overview-card">
                <span class="card-icon green"><?php echo app_icon('monitor'); ?></span>
                <div class="card-value"><?php echo $equipmentTotal; ?></div>
                <div class="card-label">Tổng thiết bị</div>
                <div class="card-delta up">Đang theo dõi toàn bộ hệ thống</div>
            </div>
            <div class="overview-card">
                <span class="card-icon amber"><?php echo app_icon('calendar'); ?></span>
                <div class="card-value"><?php echo $roomsInUseNow; ?></div>
                <div class="card-label">Phòng đang sử dụng</div>
                <div class="card-delta warn">Tại thời điểm hiện tại</div>
            </div>
            <div class="overview-card">
                <span class="card-icon red"><?php echo app_icon('wrench'); ?></span>
                <div class="card-value"><?php echo $equipmentMaintenance; ?></div>
                <div class="card-label">Thiết bị bảo trì</div>
                <div class="card-delta danger">Cần theo dõi xử lý</div>
            </div>
        </section>

        <section class="charts-row">
            <div class="chart-card">
                <h3>Lượt sử dụng phòng thực hành</h3>
                <p class="chart-sub">Thống kê trong 7 ngày gần nhất (dữ liệu thật từ bảng <code>bookings</code>)</p>

                <?php if ($totalWeeklyUsage === 0): ?>
                    <div class="empty-state">
                        <p>Chưa có dữ liệu đặt phòng nào trong 7 ngày gần nhất.</p>
                    </div>
                <?php else: ?>
                    <div class="bar-chart">
                        <div class="y-labels">
                            <?php for ($v = $yScaleTop; $v >= 0; $v -= $yScaleTop / 4): ?>
                                <span><?php echo (int) $v; ?></span>
                            <?php endfor; ?>
                        </div>
                        <div class="grid-lines">
                            <?php for ($i = 0; $i <= 4; $i++): ?><span></span><?php endfor; ?>
                        </div>
                        <div class="bars">
                            <?php foreach ($weeklyUsage as $day): ?>
                                <div class="bar-col">
                                    <div class="bar" style="height: <?php echo $day['value'] > 0 ? round($day['value'] / $yScaleTop * 100) : 0; ?>%"
                                         title="<?php echo $day['value']; ?> lượt"></div>
                                    <span class="bar-label"><?php echo htmlspecialchars($day['label']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="chart-card">
                <h3>Tình trạng thiết bị</h3>
                <p class="chart-sub">Tổng: <?php echo $equipmentTotal; ?> thiết bị (dữ liệu thật từ bảng <code>equipment</code>)</p>

                <?php if ($equipmentTotal === 0): ?>
                    <div class="empty-state">
                        <p>Chưa có thiết bị nào trong hệ thống.</p>
                    </div>
                <?php else: ?>
                    <div class="donut-wrap">
                        <svg class="donut-svg" viewBox="0 0 180 180">
                            <circle cx="90" cy="90" r="70" fill="none" stroke="#eef2f7" stroke-width="20" />
                            <?php foreach ($equipmentStatus as $seg): if ($seg['count'] <= 0) continue; ?>
                                <circle
                                    cx="90" cy="90" r="70" fill="none"
                                    stroke="<?php echo $seg['color']; ?>" stroke-width="20"
                                    stroke-dasharray="<?php echo $seg['dash']; ?> <?php echo round($circumference, 2); ?>"
                                    stroke-dashoffset="<?php echo -$seg['offset']; ?>"
                                    transform="rotate(-90 90 90)"
                                    stroke-linecap="butt"
                                />
                            <?php endforeach; ?>
                            <text x="90" y="86" text-anchor="middle" class="donut-center-value"><?php echo $equipmentTotal; ?></text>
                            <text x="90" y="106" text-anchor="middle" class="donut-center-label">thiết bị</text>
                        </svg>

                        <div class="donut-legend">
                            <?php foreach ($equipmentStatus as $seg): ?>
                                <div class="legend-row">
                                    <span class="dot" style="background: <?php echo $seg['color']; ?>"></span>
                                    <span class="legend-name"><?php echo htmlspecialchars($seg['label']); ?></span>
                                    <span class="legend-count"><?php echo $seg['count']; ?></span>
                                    <span class="legend-pct"><?php echo $seg['pct']; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
<?php require_once __DIR__ . '/includes/app_foot.php'; ?>




