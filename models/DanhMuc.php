<?php
class DanhMuc
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Lấy tất cả danh mục kèm số liệu thống kê từ bảng equipment */
    public function getAll(): array
    {
        $sql = "SELECT
                    et.id,
                    et.ma_danh_muc,
                    et.ten_loai,
                    et.mo_ta,
                    COALESCE(SUM(e.so_luong), 0) AS tong_sl,
                    COALESCE(SUM(CASE WHEN e.trang_thai = 'active' THEN e.so_luong ELSE 0 END), 0) AS dang_dung,
                    COALESCE(SUM(CASE WHEN e.trang_thai IN ('broken','maintenance') THEN e.so_luong ELSE 0 END), 0) AS can_sua
                FROM equipment_types et
                LEFT JOIN equipment e ON e.type_id = et.id
                GROUP BY et.id
                ORDER BY et.id";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM equipment_types WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function maTonTai(string $ma, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM equipment_types WHERE ma_danh_muc = :ma";
        $params = ['ma' => $ma];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function tenTonTai(string $ten, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM equipment_types WHERE ten_loai = :ten";
        $params = ['ten' => $ten];

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
        $sql = "INSERT INTO equipment_types (ma_danh_muc, ten_loai, mo_ta)
                VALUES (:ma_danh_muc, :ten_loai, :mo_ta)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'ma_danh_muc' => $d['ma_danh_muc'],
            'ten_loai'    => $d['ten_loai'],
            'mo_ta'       => $d['mo_ta'] !== '' ? $d['mo_ta'] : null,
        ]);

        return $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $sql = "UPDATE equipment_types SET
                    ma_danh_muc = :ma_danh_muc,
                    ten_loai    = :ten_loai,
                    mo_ta       = :mo_ta
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'ma_danh_muc' => $d['ma_danh_muc'],
            'ten_loai'    => $d['ten_loai'],
            'mo_ta'       => $d['mo_ta'] !== '' ? $d['mo_ta'] : null,
            'id'          => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM equipment_types WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /** Thống kê cho 3 ô đầu trang */
    public function thongKe(): array
    {
        $tongDanhMuc = (int) $this->pdo->query("SELECT COUNT(*) FROM equipment_types")->fetchColumn();
        $tongThietBi = (int) $this->pdo->query("SELECT COALESCE(SUM(so_luong),0) FROM equipment")->fetchColumn();
        $canKiemTra  = (int) $this->pdo->query(
            "SELECT COUNT(DISTINCT type_id) FROM equipment WHERE trang_thai IN ('broken','maintenance')"
        )->fetchColumn();

        return [
            'tong_danh_muc' => $tongDanhMuc,
            'tong_thiet_bi' => $tongThietBi,
            'can_kiem_tra'  => $canKiemTra,
        ];
    }
}