<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Model.php';

/**
 * app/Models/Dashboard.php
 * ---------------------------------------------------------------------
 * Toàn bộ SQL của trang tổng quan (index.php gốc): 4 thẻ số liệu,
 * lượt sử dụng phòng 7 ngày gần nhất, tình trạng thiết bị theo
 * trang_thai. Tách nguyên vẹn, không đổi logic/điều kiện truy vấn.
 * ---------------------------------------------------------------------
 */
final class Dashboard extends Model
{
    public function roomsAvailable(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM rooms WHERE trang_thai = 'available'")->fetchColumn();
    }

    public function roomsTotal(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    }

    public function equipmentTotal(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM equipment')->fetchColumn();
    }

    public function roomsInUseNow(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(DISTINCT room_id) FROM bookings
             WHERE loai_yeu_cau = 'room' AND trang_thai = 'approved'
               AND NOW() BETWEEN thoi_gian_bat_dau AND thoi_gian_ket_thuc"
        )->fetchColumn();
    }

    public function equipmentMaintenance(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM equipment WHERE trang_thai IN ('broken','maintenance')"
        )->fetchColumn();
    }

    /**
     * Đếm số booking phòng có thoi_gian_bat_dau rơi vào từng ngày trong
     * 7 ngày qua (tính cả hôm nay), không phân biệt trạng thái duyệt hay
     * chưa. Trả về mảng ['Y-m-d' => số lượt].
     */
    public function weeklyRoomUsageByDate(): array
    {
        $stmt = $this->db->prepare(
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
        return $usageByDate;
    }

    /** Số lượng thiết bị nhóm theo trang_thai. Trả về ['trang_thai' => số lượng]. */
    public function equipmentCountByStatus(): array
    {
        $stmt = $this->db->query('SELECT trang_thai, COUNT(*) AS so_luong FROM equipment GROUP BY trang_thai');

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['trang_thai']] = (int) $row['so_luong'];
        }
        return $counts;
    }
}
