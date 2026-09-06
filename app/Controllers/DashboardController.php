<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Dashboard.php';

/**
 * app/Controllers/DashboardController.php
 * ---------------------------------------------------------------------
 * Phiên bản MVC của index.php (trang chủ / Tổng quan).
 *
 * File index.php gốc KHÔNG bị xoá hay sửa và vẫn hoạt động bình
 * thường ở "/" ; đây là bản song song để migrate dần (xem README_MVC.md).
 * Toàn bộ số liệu vẫn lấy TRỰC TIẾP từ CSDL bằng truy vấn thật (không
 * có dữ liệu minh hoạ), và cách tính biểu đồ (cột 7 ngày, donut tình
 * trạng thiết bị) được giữ NGUYÊN VẸN như bản gốc.
 * ---------------------------------------------------------------------
 */
final class DashboardController extends Controller
{
    private const DAY_LABELS = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']; // Thứ 2 -> Chủ nhật

    private const STATUS_META = [
        'active' => ['label' => 'Hoạt động tốt', 'color' => '#2563eb'],
        'maintenance' => ['label' => 'Đang bảo trì', 'color' => '#f59e0b'],
        'broken' => ['label' => 'Hỏng', 'color' => '#dc2626'],
        'borrowed' => ['label' => 'Đang cho mượn', 'color' => '#16a34a'],
    ];

    /** GET /mvc/dashboard (và alias /mvc) — trang chủ / tổng quan. */
    public function index(): void
    {
        require_login();
        $user = current_user();

        $dashboard = new Dashboard();

        $equipmentTotal = $dashboard->equipmentTotal();

        $weeklyUsage = $this->buildWeeklyUsage($dashboard->weeklyRoomUsageByDate());
        $totalWeeklyUsage = array_sum(array_column($weeklyUsage, 'value'));
        $maxUsage = $totalWeeklyUsage > 0 ? max(array_column($weeklyUsage, 'value')) : 0;
        $yScaleTop = $maxUsage > 0 ? (int) (ceil($maxUsage / 4) * 4) : 1;

        $equipmentStatus = $this->buildEquipmentStatus($dashboard->equipmentCountByStatus(), $equipmentTotal);

        $this->view('dashboard/index', [
            'user' => $user,
            'roomsTotal' => $dashboard->roomsTotal(),
            'roomsAvailable' => $dashboard->roomsAvailable(),
            'roomsInUseNow' => $dashboard->roomsInUseNow(),
            'equipmentTotal' => $equipmentTotal,
            'equipmentMaintenance' => $dashboard->equipmentMaintenance(),
            'weeklyUsage' => $weeklyUsage,
            'totalWeeklyUsage' => $totalWeeklyUsage,
            'yScaleTop' => $yScaleTop,
            'equipmentStatus' => $equipmentStatus,
            'circumference' => 2 * M_PI * 70, // r = 70
        ]);
    }

    /** @param array<string,int> $usageByDate */
    private function buildWeeklyUsage(array $usageByDate): array
    {
        $weeklyUsage = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new DateTimeImmutable())->modify("-{$i} day");
            $isoWeekday = (int) $date->format('N'); // 1 = Thứ 2 ... 7 = Chủ nhật
            $weeklyUsage[] = [
                'label' => self::DAY_LABELS[$isoWeekday - 1],
                'value' => $usageByDate[$date->format('Y-m-d')] ?? 0,
            ];
        }
        return $weeklyUsage;
    }

    /** @param array<string,int> $statusCounts */
    private function buildEquipmentStatus(array $statusCounts, int $equipmentTotal): array
    {
        $equipmentStatus = [];
        foreach ($statusCounts as $key => $count) {
            $meta = self::STATUS_META[$key] ?? ['label' => $key, 'color' => '#9ca3af'];
            $equipmentStatus[] = [
                'label' => $meta['label'],
                'count' => $count,
                'color' => $meta['color'],
            ];
        }

        $circumference = 2 * M_PI * 70; // r = 70
        $donutOffset = 0;
        if ($equipmentTotal > 0) {
            foreach ($equipmentStatus as $i => $seg) {
                $equipmentStatus[$i]['pct'] = round($seg['count'] / $equipmentTotal * 100);
                $equipmentStatus[$i]['dash'] = round($seg['count'] / $equipmentTotal * $circumference, 2);
                $equipmentStatus[$i]['offset'] = $donutOffset;
                $donutOffset += $equipmentStatus[$i]['dash'];
            }
        }

        return $equipmentStatus;
    }
}
