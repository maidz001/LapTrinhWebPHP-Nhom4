<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Model.php';

/**
 * app/Models/Equipment.php
 * ---------------------------------------------------------------------
 * Bản MVC của toàn bộ SQL trong equipment/list.php, equipment/form.php,
 * equipment/save.php, equipment/delete.php, equipment/export.php,
 * equipment/import.php. Khớp CHÍNH XÁC với schema bảng `equipment`
 * hiện có (database/database.sql): id, ma_thiet_bi, ten_thiet_bi,
 * type_id, room_id, co_the_muon, trang_thai, ngay_mua, mo_ta.
 *
 * Lưu ý: models/ThietBi.php (bản cũ, thủ tục) dùng các cột so_luong,
 * gia_tri không tồn tại trong schema hiện tại — model đó thuộc phiên
 * bản khác, KHÔNG dùng để migrate module Equipment này (xem
 * README_MVC.md, phần ghi chú Phase 2 cập nhật).
 * ---------------------------------------------------------------------
 */
final class Equipment extends Model
{
    public const TRANG_THAI_HOP_LE = ['active', 'broken', 'maintenance', 'borrowed'];

    /**
     * Danh sách thiết bị kèm tên loại + mã/tên phòng, có lọc theo loại,
     * trạng thái và tìm theo tên (giống hệt equipment/list.php).
     *
     * @param array{type_id?: int|null, trang_thai?: string, q?: string} $filters
     */
    public function all(array $filters = []): array
    {
        $typeId = $filters['type_id'] ?? null;
        $trangThai = $filters['trang_thai'] ?? 'all';
        $q = $filters['q'] ?? '';

        $sql = "SELECT e.*, t.ten_loai, r.ma_phong, r.ten_phong
                FROM equipment e
                JOIN equipment_types t ON t.id = e.type_id
                LEFT JOIN rooms r ON r.id = e.room_id
                WHERE 1 = 1";
        $params = [];

        if ($typeId) {
            $sql .= " AND e.type_id = :type_id";
            $params['type_id'] = $typeId;
        }
        if ($trangThai !== 'all') {
            $sql .= " AND e.trang_thai = :trang_thai";
            $params['trang_thai'] = $trangThai;
        }
        if ($q !== '') {
            $sql .= " AND e.ten_thiet_bi LIKE :q";
            $params['q'] = '%' . $q . '%';
        }
        $sql .= " ORDER BY e.ma_thiet_bi";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM equipment WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /** Danh sách rút gọn kèm phòng hiện tại — dùng cho trang bàn giao thiết bị (Phase 3). */
    public function allWithRoom(): array
    {
        return $this->db->query(
            "SELECT e.id, e.ma_thiet_bi, e.ten_thiet_bi, e.room_id, r.ma_phong, r.ten_phong
             FROM equipment e LEFT JOIN rooms r ON r.id = e.room_id
             ORDER BY e.ten_thiet_bi"
        )->fetchAll();
    }

    /** 1 thiết bị kèm mã phòng hiện tại (nếu có) — dùng khi xử lý bàn giao (Phase 3). */
    public function findWithCurrentRoom(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT e.id, e.ten_thiet_bi, e.room_id, r.ma_phong AS ma_phong_cu
             FROM equipment e LEFT JOIN rooms r ON r.id = e.room_id
             WHERE e.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /** Chuyển thiết bị sang phòng khác (hoặc null = thiết bị lưu động) — dùng khi bàn giao (Phase 3). */
    public function moveToRoom(int $id, ?int $roomId): void
    {
        $stmt = $this->db->prepare("UPDATE equipment SET room_id = :room_id WHERE id = :id");
        $stmt->execute(['room_id' => $roomId, 'id' => $id]);
    }

    public function codeExists(string $maThietBi, ?int $excludeId = null): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM equipment WHERE ma_thiet_bi = :ma AND id != :id");
        $stmt->execute(['ma' => $maThietBi, 'id' => $excludeId ?? 0]);
        return (bool) $stmt->fetch();
    }

    /** @param array{ma_thiet_bi:string,ten_thiet_bi:string,type_id:int,room_id:?int,co_the_muon:int,trang_thai:string,ngay_mua:?string,mo_ta:?string} $d */
    public function create(array $d): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO equipment (ma_thiet_bi, ten_thiet_bi, type_id, room_id, co_the_muon, trang_thai, ngay_mua, mo_ta)
             VALUES (:ma, :ten, :type_id, :room_id, :muon, :tt, :ngay_mua, :mt)"
        );
        $stmt->execute($this->bindParams($d));
    }

    public function update(int $id, array $d): void
    {
        $params = $this->bindParams($d);
        $params['id'] = $id;

        $stmt = $this->db->prepare(
            "UPDATE equipment SET ma_thiet_bi = :ma, ten_thiet_bi = :ten, type_id = :type_id, room_id = :room_id,
                    co_the_muon = :muon, trang_thai = :tt, ngay_mua = :ngay_mua, mo_ta = :mt
             WHERE id = :id"
        );
        $stmt->execute($params);
    }

    /** @return bool true nếu xoá được (rowCount > 0). Ném PDOException nếu bị FK RESTRICT chặn. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM equipment WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function bindParams(array $d): array
    {
        return [
            'ma' => $d['ma_thiet_bi'],
            'ten' => $d['ten_thiet_bi'],
            'type_id' => $d['type_id'],
            'room_id' => $d['room_id'],
            'muon' => $d['co_the_muon'],
            'tt' => $d['trang_thai'],
            'ngay_mua' => $d['ngay_mua'],
            'mt' => $d['mo_ta'],
        ];
    }
}
