<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Model.php';

/**
 * app/Models/Room.php
 * ---------------------------------------------------------------------
 * Bản MVC của toàn bộ SQL trong rooms/list.php, rooms/form.php,
 * rooms/save.php, rooms/delete.php, rooms/export.php, rooms/import.php.
 * Khớp CHÍNH XÁC với schema bảng `rooms` hiện có (database/database.sql):
 *   id, ma_phong, ten_phong, vi_tri, suc_chua, trang_thai, mo_ta.
 *
 * Lưu ý: models/Phong.php (bản cũ, thủ tục) có cột `loai_phong` không
 * tồn tại trong schema hiện tại — đó là model của 1 phiên bản khác,
 * KHÔNG dùng để migrate module Rooms này (xem README_MVC.md, phần
 * ghi chú Phase 2 cập nhật).
 * ---------------------------------------------------------------------
 */
final class Room extends Model
{
    public const TRANG_THAI_HOP_LE = ['available', 'maintenance', 'closed'];

    /** Danh sách phòng, tìm theo tên (giống hệt rooms/list.php). */
    public function all(string $q = ''): array
    {
        if ($q !== '') {
            $stmt = $this->db->prepare("SELECT * FROM rooms WHERE ten_phong LIKE :q ORDER BY ma_phong");
            $stmt->execute(['q' => '%' . $q . '%']);
            return $stmt->fetchAll();
        }

        return $this->db->query("SELECT * FROM rooms ORDER BY ma_phong")->fetchAll();
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /** Danh sách rút gọn (id, ma_phong, ten_phong) — dùng cho dropdown chọn phòng ở Equipment. */
    public function allForDropdown(): array
    {
        return $this->db->query("SELECT id, ma_phong, ten_phong FROM rooms ORDER BY ma_phong")->fetchAll();
    }

    public function codeExists(string $maPhong, ?int $excludeId = null): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM rooms WHERE ma_phong = :ma AND id != :id");
        $stmt->execute(['ma' => $maPhong, 'id' => $excludeId ?? 0]);
        return (bool) $stmt->fetch();
    }

    public function create(array $d): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO rooms (ma_phong, ten_phong, vi_tri, suc_chua, trang_thai, mo_ta)
             VALUES (:ma, :ten, :vt, :sc, :tt, :mt)"
        );
        $stmt->execute([
            'ma' => $d['ma_phong'],
            'ten' => $d['ten_phong'],
            'vt' => $d['vi_tri'],
            'sc' => (int) $d['suc_chua'],
            'tt' => $d['trang_thai'],
            'mt' => ($d['mo_ta'] ?? '') !== '' ? $d['mo_ta'] : null,
        ]);
    }

    public function update(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            "UPDATE rooms SET ma_phong = :ma, ten_phong = :ten, vi_tri = :vt, suc_chua = :sc,
                    trang_thai = :tt, mo_ta = :mt
             WHERE id = :id"
        );
        $stmt->execute([
            'ma' => $d['ma_phong'],
            'ten' => $d['ten_phong'],
            'vt' => $d['vi_tri'],
            'sc' => (int) $d['suc_chua'],
            'tt' => $d['trang_thai'],
            'mt' => ($d['mo_ta'] ?? '') !== '' ? $d['mo_ta'] : null,
            'id' => $id,
        ]);
    }

    /** @return bool true nếu xoá được (rowCount > 0). Ném PDOException nếu bị FK RESTRICT chặn. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
