<?php
/**
 * app/Views/dashboard/index.php
 * View thuần hiển thị — không chứa SQL. Biến truyền vào từ
 * DashboardController::index(): $user, $roomsTotal, $roomsAvailable,
 * $roomsInUseNow, $equipmentTotal, $equipmentMaintenance, $weeklyUsage,
 * $totalWeeklyUsage, $yScaleTop, $equipmentStatus, $circumference
 */
$page_title = 'Tổng quan';
$active_menu = 'overview';
require_once __DIR__ . '/../../../includes/app_head.php';
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
<?php require_once __DIR__ . '/../../../includes/app_foot.php'; ?>
