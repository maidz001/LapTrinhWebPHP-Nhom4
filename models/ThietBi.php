<?php
class ThietBi
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Lấy tất cả thiết bị, join sang loại + phòng, có tìm kiếm + lọc theo loại */
    public function getAll(string $search = '', int $typeId = 0): array
    {
        $sql = "SELECT e.*, et.ten_loai, r.ma_phong
                FROM equipment e
                JOIN equipment_types et ON et.id = e.type_id
                LEFT JOIN rooms r ON r.id = e.room_id
                WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (e.ma_thiet_bi LIKE :s1 OR e.ten_thiet_bi LIKE :s2)";
            $kw = '%' . $search . '%';
            $params['s1'] = $kw;
            $params['s2'] = $kw;
        }

        if ($typeId > 0) {
            $sql .= " AND e.type_id = :type_id";
            $params['type_id'] = $typeId;
        }

        $sql .= " ORDER BY e.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM equipment WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function maThietBiTonTai(string $ma, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM equipment WHERE ma_thiet_bi = :ma";
        $params = ['ma' => $ma];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function insert(array $d): string
    {
        $sql = "INSERT INTO equipment
                    (ma_thiet_bi, ten_thiet_bi, type_id, room_id, so_luong, gia_tri, trang_thai, ngay_mua, mo_ta)
                VALUES
                    (:ma_thiet_bi, :ten_thiet_bi, :type_id, :room_id, :so_luong, :gia_tri, :trang_thai, :ngay_mua, :mo_ta)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'ma_thiet_bi'  => $d['ma_thiet_bi'],
            'ten_thiet_bi' => $d['ten_thiet_bi'],
            'type_id'      => $d['type_id'],
            'room_id'      => $d['room_id'] ?: null,
            'so_luong'     => $d['so_luong'],
            'gia_tri'      => $d['gia_tri'] !== '' ? $d['gia_tri'] : null,
            'trang_thai'   => $d['trang_thai'],
            'ngay_mua'     => $d['ngay_mua'] !== '' ? $d['ngay_mua'] : null,
            'mo_ta'        => $d['mo_ta'] !== '' ? $d['mo_ta'] : null,
        ]);

        return $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $sql = "UPDATE equipment SET
                    ma_thiet_bi  = :ma_thiet_bi,
                    ten_thiet_bi = :ten_thiet_bi,
                    type_id      = :type_id,
                    room_id      = :room_id,
                    so_luong     = :so_luong,
                    gia_tri      = :gia_tri,
                    trang_thai   = :trang_thai,
                    ngay_mua     = :ngay_mua,
                    mo_ta        = :mo_ta
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'ma_thiet_bi'  => $d['ma_thiet_bi'],
            'ten_thiet_bi' => $d['ten_thiet_bi'],
            'type_id'      => $d['type_id'],
            'room_id'      => $d['room_id'] ?: null,
            'so_luong'     => $d['so_luong'],
            'gia_tri'      => $d['gia_tri'] !== '' ? $d['gia_tri'] : null,
            'trang_thai'   => $d['trang_thai'],
            'ngay_mua'     => $d['ngay_mua'] !== '' ? $d['ngay_mua'] : null,
            'mo_ta'        => $d['mo_ta'] !== '' ? $d['mo_ta'] : null,
            'id'           => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM equipment WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /** Danh sách loại thiết bị (dùng cho tab lọc + dropdown form) */
    public function danhSachLoai(): array
    {
        return $this->pdo->query("SELECT * FROM equipment_types ORDER BY ten_loai")->fetchAll();
    }

    /** Danh sách phòng (dùng cho dropdown chọn phòng đặt thiết bị) */
    public function danhSachPhong(): array
    {
        return $this->pdo->query("SELECT id, ma_phong, ten_phong FROM rooms ORDER BY ma_phong")->fetchAll();
    }

    public function demTong(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
    }
}