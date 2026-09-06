<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Model.php';

/**
 * app/Models/Report.php
 * ---------------------------------------------------------------------
 * Toàn bộ SQL của bảng `reports` (báo hỏng thiết bị), tách nguyên vẹn
 * từ reports/index.php, reports/create.php, reports/store.php,
 * reports/update_status.php bản thủ tục cũ. Không đổi logic nghiệp vụ.
 * ---------------------------------------------------------------------
 */
final class Report extends Model
{
    public const TRANG_THAI_HOP_LE = ['new', 'processing', 'resolved', 'cancelled'];
    public const MUC_DO_HOP_LE = ['low', 'medium', 'high'];

    /**
     * @param int|null $reportedBy Nếu khác null, chỉ lấy báo cáo của user này
     *                             (dùng cho người dùng thường, không phải canManage).
     */
    public function all(string $statusFilter, ?int $reportedBy): array
    {
        $sql = "SELECT rp.*, e.ma_thiet_bi, e.ten_thiet_bi, u.ho_ten AS nguoi_bao
                FROM reports rp
                JOIN equipment e ON e.id = rp.equipment_id
                JOIN users u ON u.id = rp.reported_by
                WHERE 1 = 1";
        $params = [];

        if ($reportedBy !== null) {
            $sql .= " AND rp.reported_by = :uid";
            $params['uid'] = $reportedBy;
        }
        if ($statusFilter !== 'all') {
            $sql .= " AND rp.trang_thai = :trang_thai";
            $params['trang_thai'] = $statusFilter;
        }
        $sql .= " ORDER BY rp.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function equipmentOptions(): array
    {
        return $this->db->query(
            "SELECT id, ma_thiet_bi, ten_thiet_bi FROM equipment ORDER BY ten_thiet_bi"
        )->fetchAll();
    }

    public function equipmentExists(int $equipmentId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM equipment WHERE id = :id');
        $stmt->execute(['id' => $equipmentId]);
        return (bool) $stmt->fetch();
    }

    public function create(int $equipmentId, int $reportedBy, string $moTa, string $mucDo): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO reports (equipment_id, reported_by, mo_ta_su_co, muc_do, trang_thai)
             VALUES (:equipment_id, :reported_by, :mo_ta, :muc_do, 'new')"
        );
        $stmt->execute([
            'equipment_id' => $equipmentId,
            'reported_by' => $reportedBy,
            'mo_ta' => $moTa,
            'muc_do' => $mucDo,
        ]);

        // Đánh dấu ngay thiết bị là "Hỏng" khi có báo cáo mới, giống bản gốc.
        $stmt = $this->db->prepare("UPDATE equipment SET trang_thai = 'broken' WHERE id = :id");
        $stmt->execute(['id' => $equipmentId]);
    }

    public function findEquipmentId(int $reportId): ?int
    {
        $stmt = $this->db->prepare('SELECT equipment_id FROM reports WHERE id = :id');
        $stmt->execute(['id' => $reportId]);
        $row = $stmt->fetch();
        return $row ? (int) $row['equipment_id'] : null;
    }

    public function updateStatus(int $reportId, string $trangThai): void
    {
        $stmt = $this->db->prepare('UPDATE reports SET trang_thai = :tt WHERE id = :id');
        $stmt->execute(['tt' => $trangThai, 'id' => $reportId]);
    }

    /**
     * Đồng bộ trạng thái thiết bị dựa trên các báo cáo hỏng chưa xử lý xong,
     * y hệt hàm dong_bo_trang_thai_thiet_bi() trong reports/update_status.php gốc:
     * - Còn báo cáo "Mới"        -> thiết bị "Hỏng"
     * - Còn báo cáo "Đang xử lý" -> thiết bị "Bảo trì"
     * - Không còn báo cáo mở     -> thiết bị "Hoạt động"
     * Không đụng vào thiết bị đang "Đang mượn" (borrowed).
     */
    public function syncEquipmentStatus(int $equipmentId): void
    {
        $stmt = $this->db->prepare('SELECT trang_thai FROM equipment WHERE id = :id');
        $stmt->execute(['id' => $equipmentId]);
        $equipment = $stmt->fetch();
        if (!$equipment || $equipment['trang_thai'] === 'borrowed') {
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT trang_thai FROM reports WHERE equipment_id = :id AND trang_thai IN ('new', 'processing')"
        );
        $stmt->execute(['id' => $equipmentId]);
        $openStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (in_array('new', $openStatuses, true)) {
            $newStatus = 'broken';
        } elseif (in_array('processing', $openStatuses, true)) {
            $newStatus = 'maintenance';
        } else {
            $newStatus = 'active';
        }

        $stmt = $this->db->prepare('UPDATE equipment SET trang_thai = :tt WHERE id = :id');
        $stmt->execute(['tt' => $newStatus, 'id' => $equipmentId]);
    }
}
