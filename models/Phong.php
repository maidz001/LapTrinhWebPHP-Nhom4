<?php
/**
 * Model Phong - xử lý toàn bộ truy vấn CSDL cho bảng `rooms`
 */
class Phong
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Lấy tất cả phòng, có thể tìm kiếm + lọc trạng thái */
    public function getAll(string $search = '', string $trangThai = ''): array
    {
        $sql = "SELECT * FROM rooms WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (ma_phong LIKE :search1 OR ten_phong LIKE :search2 OR vi_tri LIKE :search3)";
            $keyword = '%' . $search . '%';
            $params['search1'] = $keyword;
            $params['search2'] = $keyword;
            $params['search3'] = $keyword;
        }

        if ($trangThai !== '') {
            $sql .= " AND trang_thai = :trang_thai";
            $params['trang_thai'] = $trangThai;
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Lấy 1 phòng theo id */
    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /** Kiểm tra mã phòng đã tồn tại chưa (loại trừ 1 id nếu đang sửa) */
    public function maPhongTonTai(string $ma_phong, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM rooms WHERE ma_phong = :ma_phong";
        $params = ['ma_phong' => $ma_phong];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /** Thêm phòng mới, trả về id vừa tạo */
    public function insert(array $data): string
    {
        $sql = "INSERT INTO rooms (ma_phong, ten_phong, vi_tri, loai_phong, suc_chua, trang_thai, mo_ta)
            VALUES (:ma_phong, :ten_phong, :vi_tri, :loai_phong, :suc_chua, :trang_thai, :mo_ta)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'ma_phong'   => $data['ma_phong'],
            'ten_phong'  => $data['ten_phong'],
            'vi_tri'     => $data['vi_tri'],
            'loai_phong' => $data['loai_phong'],
            'suc_chua'   => $data['suc_chua'],
            'trang_thai' => $data['trang_thai'],
            'mo_ta'      => $data['mo_ta'] !== '' ? $data['mo_ta'] : null,
        ]);

        return $this->pdo->lastInsertId();
    }

    /** Cập nhật phòng theo id */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE rooms SET
                    ma_phong   = :ma_phong,
                    ten_phong  = :ten_phong,
                    vi_tri     = :vi_tri,
                    suc_chua   = :suc_chua,
                    trang_thai = :trang_thai,
                    mo_ta      = :mo_ta
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'ma_phong'   => $data['ma_phong'],
            'ten_phong'  => $data['ten_phong'],
            'vi_tri'     => $data['vi_tri'],
            'suc_chua'   => $data['suc_chua'],
            'trang_thai' => $data['trang_thai'],
            'mo_ta'      => $data['mo_ta'] !== '' ? $data['mo_ta'] : null,
            'id'         => $id,
        ]);
    }

    /** Xóa phòng theo id */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM rooms WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /** Thống kê tổng quan cho 4 ô ở đầu trang */
    public function thongKe(): array
    {
        $tong = $this->pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();

        $hoatDong = $this->pdo->query(
            "SELECT COUNT(*) FROM rooms WHERE trang_thai = 'available'"
        )->fetchColumn();

        $baoTri = $this->pdo->query(
            "SELECT COUNT(*) FROM rooms WHERE trang_thai = 'maintenance'"
        )->fetchColumn();

        $dong = $this->pdo->query(
            "SELECT COUNT(*) FROM rooms WHERE trang_thai = 'closed'"
        )->fetchColumn();

        $tongSucChua = $this->pdo->query(
            "SELECT COALESCE(SUM(suc_chua), 0) FROM rooms"
        )->fetchColumn();

        return [
            'tong_phong'    => (int) $tong,
            'hoat_dong'     => (int) $hoatDong,
            'bao_tri'       => (int) $baoTri,
            'dong'          => (int) $dong,
            'tong_suc_chua' => (int) $tongSucChua,
        ];
    }
}