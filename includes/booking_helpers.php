<?php
/**
 * includes/booking_helpers.php
 */

declare(strict_types=1);

function booking_to_mysql_datetime(string $value): ?string
{
    $value = trim($value);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
        return null;
    }
    return str_replace('T', ' ', $value) . ':00';
}

function booking_has_conflict(
    PDO $pdo,
    string $loai,
    ?int $roomId,
    ?int $equipmentId,
    string $start,
    string $end
): bool {
    if ($loai === 'room') {
        $sql = "SELECT COUNT(*) FROM bookings
                WHERE room_id = :rid AND trang_thai IN ('pending','approved')
                AND thoi_gian_bat_dau < :end AND thoi_gian_ket_thuc > :start";
        $params = ['rid' => $roomId, 'start' => $start, 'end' => $end];
    } else {
        $sql = "SELECT COUNT(*) FROM bookings
                WHERE equipment_id = :eid AND trang_thai IN ('pending','approved')
                AND thoi_gian_bat_dau < :end AND thoi_gian_ket_thuc > :start";
        $params = ['eid' => $equipmentId, 'start' => $start, 'end' => $end];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

function booking_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'cancelled' => 'Đã huỷ',
        default => $status,
    };
}

function booking_status_style(string $status): string
{
    return match ($status) {
        'pending' => 'background:var(--color-warning-bg);color:#92400e;',
        'approved' => 'background:var(--color-success-bg);color:#166534;',
        'rejected' => 'background:var(--color-error-bg);color:#991b1b;',
        'cancelled' => 'background:#f1f5f9;color:var(--color-text-muted);',
        default => '',
    };
}