<?php
declare(strict_types=1);

function bookingStatusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'cancelled' => 'Đã hủy',
        default => 'Không xác định',
    };
}

function bookingTypeLabel(string $type): string
{
    return $type === 'equipment' ? 'Mượn thiết bị' : 'Đặt phòng';
}

function bookingFilterSql(array $filters): array
{
    $where = [];
    $params = [];

    if (!empty($filters['owner_id'])) {
        $where[] = 'b.user_id = :owner_id';
        $params['owner_id'] = (int) $filters['owner_id'];
    }

    $status = (string) ($filters['status'] ?? '');
    if ($status === 'processed') {
        $where[] = "b.trang_thai <> 'pending'";
    } elseif (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
        $where[] = 'b.trang_thai = :status';
        $params['status'] = $status;
    }

    $type = (string) ($filters['type'] ?? '');
    if (in_array($type, ['room', 'equipment'], true)) {
        $where[] = 'b.loai_yeu_cau = :type';
        $params['type'] = $type;
    }

    $keyword = trim((string) ($filters['keyword'] ?? ''));
    if ($keyword !== '') {
        $where[] = "CONCAT_WS(' ', u.ho_ten, b.muc_dich, r.ma_phong, r.ten_phong, e.ma_thiet_bi, e.ten_thiet_bi) LIKE :keyword";
        $params['keyword'] = '%' . $keyword . '%';
    }

    return [empty($where) ? '' : ' WHERE ' . implode(' AND ', $where), $params];
}

function countBookings(PDO $pdo, array $filters = []): int
{
    [$where, $params] = bookingFilterSql($filters);
    $sql = "SELECT COUNT(*)
            FROM bookings b
            INNER JOIN users u ON u.id = b.user_id
            LEFT JOIN rooms r ON r.id = b.room_id
            LEFT JOIN equipment e ON e.id = b.equipment_id
            {$where}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function findBookings(PDO $pdo, array $filters, int $page, int $perPage): array
{
    [$where, $params] = bookingFilterSql($filters);
    $offset = ($page - 1) * $perPage;
    $sql = "SELECT b.id, b.loai_yeu_cau, b.thoi_gian_bat_dau,
                   b.thoi_gian_ket_thuc, b.muc_dich, b.trang_thai,
                   b.created_at, u.ho_ten AS nguoi_gui,
                   CASE
                       WHEN b.loai_yeu_cau = 'room' THEN CONCAT(r.ma_phong, ' - ', r.ten_phong)
                       ELSE CONCAT(e.ma_thiet_bi, ' - ', e.ten_thiet_bi)
                   END AS tai_nguyen
            FROM bookings b
            INNER JOIN users u ON u.id = b.user_id
            LEFT JOIN rooms r ON r.id = b.room_id
            LEFT JOIN equipment e ON e.id = b.equipment_id
            {$where}
            ORDER BY b.created_at DESC, b.id DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $name => $value) {
        $stmt->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function findBookingById(PDO $pdo, int $id): ?array
{
    $sql = "SELECT b.*, u.ho_ten AS nguoi_gui, u.email AS email_nguoi_gui,
                   approver.ho_ten AS nguoi_duyet,
                   r.ma_phong, r.ten_phong, r.vi_tri AS vi_tri_phong,
                   e.ma_thiet_bi, e.ten_thiet_bi
            FROM bookings b
            INNER JOIN users u ON u.id = b.user_id
            LEFT JOIN users approver ON approver.id = b.approved_by
            LEFT JOIN rooms r ON r.id = b.room_id
            LEFT JOIN equipment e ON e.id = b.equipment_id
            WHERE b.id = :id
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $booking = $stmt->fetch();
    return $booking ?: null;
}

function findAvailableRooms(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        "SELECT id, ma_phong, ten_phong, vi_tri
         FROM rooms
         WHERE trang_thai = :status
         ORDER BY ma_phong"
    );
    $stmt->execute(['status' => 'available']);
    return $stmt->fetchAll();
}

function findBorrowableEquipment(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        "SELECT id, ma_thiet_bi, ten_thiet_bi
         FROM equipment
         WHERE co_the_muon = :borrowable AND trang_thai = :status
         ORDER BY ma_thiet_bi"
    );
    $stmt->execute(['borrowable' => 1, 'status' => 'active']);
    return $stmt->fetchAll();
}

function bookingResourceExists(PDO $pdo, string $type, int $resourceId): bool
{
    if ($type === 'room') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM rooms WHERE id = :id AND trang_thai = :status"
        );
        $stmt->execute(['id' => $resourceId, 'status' => 'available']);
    } else {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM equipment
             WHERE id = :id AND co_the_muon = :borrowable AND trang_thai = :status"
        );
        $stmt->execute(['id' => $resourceId, 'borrowable' => 1, 'status' => 'active']);
    }
    return (int) $stmt->fetchColumn() > 0;
}

function bookingHasTimeConflict(
    PDO $pdo,
    string $type,
    int $resourceId,
    string $start,
    string $end,
    ?int $excludeId = null,
    bool $approvedOnly = false
): bool {
    $resourceColumn = $type === 'room' ? 'room_id' : 'equipment_id';
    $statusSql = $approvedOnly
        ? "b.trang_thai = 'approved'"
        : "b.trang_thai IN ('pending', 'approved')";
    $sql = "SELECT COUNT(*)
            FROM bookings b
            WHERE b.{$resourceColumn} = :resource_id
              AND {$statusSql}
              AND b.thoi_gian_bat_dau < :end_time
              AND b.thoi_gian_ket_thuc > :start_time";
    $params = [
        'resource_id' => $resourceId,
        'start_time' => $start,
        'end_time' => $end,
    ];
    if ($excludeId !== null) {
        $sql .= ' AND b.id <> :exclude_id';
        $params['exclude_id'] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

function createBooking(PDO $pdo, int $userId, array $data): int
{
    $sql = "INSERT INTO bookings
                (user_id, loai_yeu_cau, room_id, equipment_id,
                 thoi_gian_bat_dau, thoi_gian_ket_thuc, muc_dich, trang_thai)
            VALUES
                (:user_id, :type, :room_id, :equipment_id,
                 :start_time, :end_time, :purpose, 'pending')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id' => $userId,
        'type' => $data['type'],
        'room_id' => $data['room_id'],
        'equipment_id' => $data['equipment_id'],
        'start_time' => $data['start_time'],
        'end_time' => $data['end_time'],
        'purpose' => $data['purpose'],
    ]);
    return (int) $pdo->lastInsertId();
}

function updateBooking(PDO $pdo, int $id, int $userId, array $data): bool
{
    $sql = "UPDATE bookings
            SET loai_yeu_cau = :type,
                room_id = :room_id,
                equipment_id = :equipment_id,
                thoi_gian_bat_dau = :start_time,
                thoi_gian_ket_thuc = :end_time,
                muc_dich = :purpose
            WHERE id = :id AND user_id = :user_id AND trang_thai = 'pending'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'type' => $data['type'],
        'room_id' => $data['room_id'],
        'equipment_id' => $data['equipment_id'],
        'start_time' => $data['start_time'],
        'end_time' => $data['end_time'],
        'purpose' => $data['purpose'],
        'id' => $id,
        'user_id' => $userId,
    ]);
    if ($stmt->rowCount() > 0) {
        return true;
    }
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM bookings
         WHERE id = :id AND user_id = :user_id AND trang_thai = 'pending'"
    );
    $check->execute(['id' => $id, 'user_id' => $userId]);
    return (int) $check->fetchColumn() > 0;
}

function cancelBooking(PDO $pdo, int $id, int $userId): bool
{
    $stmt = $pdo->prepare(
        "UPDATE bookings
         SET trang_thai = 'cancelled'
         WHERE id = :id AND user_id = :user_id AND trang_thai = 'pending'"
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    return $stmt->rowCount() > 0;
}

function approveBooking(PDO $pdo, int $id, int $approverId): bool
{
    $stmt = $pdo->prepare(
        "UPDATE bookings
         SET trang_thai = 'approved', approved_by = :approver_id,
             approved_at = NOW(), ly_do_tu_choi = NULL
         WHERE id = :id AND trang_thai = 'pending'"
    );
    $stmt->execute(['id' => $id, 'approver_id' => $approverId]);
    return $stmt->rowCount() > 0;
}

function rejectBooking(PDO $pdo, int $id, int $approverId, string $reason): bool
{
    $stmt = $pdo->prepare(
        "UPDATE bookings
         SET trang_thai = 'rejected', approved_by = :approver_id,
             approved_at = NOW(), ly_do_tu_choi = :reason
         WHERE id = :id AND trang_thai = 'pending'"
    );
    $stmt->execute([
        'id' => $id,
        'approver_id' => $approverId,
        'reason' => $reason,
    ]);
    return $stmt->rowCount() > 0;
}
