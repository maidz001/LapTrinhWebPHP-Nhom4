<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Model.php';

/**
 * app/Models/EquipmentType.php
 * ---------------------------------------------------------------------
 * Bản MVC của phần SQL liên quan `equipment_types` dùng trong
 * equipment/list.php, equipment/form.php và equipment_types/list.php.
 * ---------------------------------------------------------------------
 */
final class EquipmentType extends Model
{
    /** Danh sách loại thiết bị (dùng cho dropdown lọc + dropdown form). */
    public function all(): array
    {
        return $this->db->query("SELECT id, ten_loai FROM equipment_types ORDER BY ten_loai")->fetchAll();
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM equipment_types WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function exists(int $id): bool
    {
        return $this->find($id) !== false;
    }

    /** Tra map [tên loại viết thường => id] — dùng khi import CSV theo tên loại. */
    public function nameToIdMap(): array
    {
        $map = [];
        foreach ($this->db->query("SELECT id, ten_loai FROM equipment_types") as $t) {
            $map[mb_strtolower(trim((string) $t['ten_loai']))] = (int) $t['id'];
        }
        return $map;
    }

    /** Danh mục thiết bị kèm thống kê số lượng theo trạng thái (equipment_types/list.php). */
    public function allWithStats(): array
    {
        $sql = "SELECT
                    et.id, et.ten_loai, et.mo_ta,
                    COUNT(e.id) AS tong_sl,
                    SUM(CASE WHEN e.trang_thai = 'active' THEN 1 ELSE 0 END) AS dang_hoat_dong,
                    SUM(CASE WHEN e.trang_thai = 'broken' THEN 1 ELSE 0 END) AS hong,
                    SUM(CASE WHEN e.trang_thai = 'maintenance' THEN 1 ELSE 0 END) AS bao_tri,
                    SUM(CASE WHEN e.trang_thai = 'borrowed' THEN 1 ELSE 0 END) AS dang_muon
                FROM equipment_types et
                LEFT JOIN equipment e ON e.type_id = et.id
                GROUP BY et.id
                ORDER BY et.ten_loai";

        return $this->db->query($sql)->fetchAll();
    }
}
