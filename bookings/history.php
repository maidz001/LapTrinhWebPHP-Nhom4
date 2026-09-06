<?php
/**
 * bookings/history.php
 * ---------------------------------------------------------------------
 * Lịch sử các yêu cầu của người dùng.
 * Người dùng thường chỉ xem yêu cầu của chính mình.
 * Admin / lab_staff có thể xem lịch sử toàn hệ thống.
 * Có thể lọc theo từ khóa, loại và trạng thái.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/repository.php';

require_login();

$user = current_user();

$isStaff = in_array($user['role'], ['admin', 'lab_staff'], true);

$keyword = trim((string) ($_GET['q'] ?? ''));

$type = (string) ($_GET['type'] ?? 'all');

if (!in_array($type, ['all', 'room', 'equipment'], true)) {
    $type = 'all';
}

$status = (string) ($_GET['trang_thai'] ?? 'all');

$allowedStatus = [
    'all',
    'pending',
    'approved',
    'rejected',
    'cancelled'
];

if (!in_array($status, $allowedStatus, true)) {
    $status = 'all';
}

$page = max(1, (int) ($_GET['page'] ?? 1));

$perPage = 10;

/*
 * Repository của nguyenky-booking hỗ trợ tìm kiếm,
 * loại yêu cầu và trạng thái processed.
 *
 * Nếu chọn "Tất cả" trạng thái thì truy vấn trực tiếp
 * để giữ đầy đủ các trạng thái như bản main.
 */

if ($status === 'all') {

    $sql = "
        SELECT
            b.*,
            r.ma_phong,
            r.ten_phong,
            e.ma_thiet_bi,
            e.ten_thiet_bi,
            u.ho_ten AS nguoi_gui
        FROM bookings b
        LEFT JOIN rooms r ON r.id = b.room_id
        LEFT JOIN equipment e ON e.id = b.equipment_id
        LEFT JOIN users u ON u.id = b.user_id
        WHERE 1 = 1
    ";

    $params = [];

    if (!$isStaff) {
        $sql .= " AND b.user_id = :uid";
        $params['uid'] = (int) $user['id'];
    }

    if ($keyword !== '') {
        $sql .= "
            AND (
                b.muc_dich LIKE :keyword
                OR r.ma_phong LIKE :keyword
                OR r.ten_phong LIKE :keyword
                OR e.ma_thiet_bi LIKE :keyword
                OR e.ten_thiet_bi LIKE :keyword
                OR u.ho_ten LIKE :keyword
            )
        ";

        $params['keyword'] = '%' . $keyword . '%';
    }

    if ($type !== 'all') {
        $sql .= " AND b.loai_yeu_cau = :type";
        $params['type'] = $type;
    }

    $sql .= " ORDER BY b.thoi_gian_bat_dau DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $bookings = $stmt->fetchAll();

    $total = count($bookings);
    $totalPages = 1;

} else {

    $filters = [
        'owner_id' => $isStaff ? 0 : (int) $user['id'],
        'keyword' => $keyword,
        'status' => $status,
        'type' => $type === 'all' ? '' : $type,
    ];

    $total = countBookings($pdo, $filters);

    $totalPages = max(
        1,
        (int) ceil($total / $perPage)
    );

    $page = min($page, $totalPages);

    $bookings = findBookings(
        $pdo,
        $filters,
        $page,
        $perPage
    );
}

$page_title = 'Lịch sử sử dụng';
$active_menu = 'history';

require_once __DIR__ . '/../includes/app_head.php';
?>

<div class="page-heading">
    <div>
        <h2>Lịch sử sử dụng</h2>

        <p>
            Các yêu cầu đã được duyệt, từ chối, hủy hoặc đang chờ xử lý.
        </p>
    </div>
</div>

<section class="content-card">

    <form method="get" class="booking-filter history-filter">

        <div class="form-group">

            <label for="q">
                Tìm kiếm
            </label>

            <input
                type="text"
                id="q"
                name="q"
                value="<?php echo htmlspecialchars($keyword); ?>"
                placeholder="Mục đích hoặc tài nguyên"
            >

        </div>

        <div class="form-group">

            <label for="type">
                Loại yêu cầu
            </label>

            <select id="type" name="type">

                <option
                    value="all"
                    <?php echo $type === 'all' ? 'selected' : ''; ?>
                >
                    Tất cả
                </option>

                <option
                    value="room"
                    <?php echo $type === 'room' ? 'selected' : ''; ?>
                >
                    Đặt phòng
                </option>

                <option
                    value="equipment"
                    <?php echo $type === 'equipment' ? 'selected' : ''; ?>
                >
                    Mượn thiết bị
                </option>

            </select>

        </div>

        <div class="form-group">

            <label for="trang_thai">
                Trạng thái
            </label>

            <select id="trang_thai" name="trang_thai">

                <option
                    value="all"
                    <?php echo $status === 'all' ? 'selected' : ''; ?>
                >
                    Tất cả
                </option>

                <option
                    value="pending"
                    <?php echo $status === 'pending' ? 'selected' : ''; ?>
                >
                    Chờ duyệt
                </option>

                <option
                    value="approved"
                    <?php echo $status === 'approved' ? 'selected' : ''; ?>
                >
                    Đã duyệt
                </option>

                <option
                    value="rejected"
                    <?php echo $status === 'rejected' ? 'selected' : ''; ?>
                >
                    Từ chối
                </option>

                <option
                    value="cancelled"
                    <?php echo $status === 'cancelled' ? 'selected' : ''; ?>
                >
                    Đã huỷ
                </option>

            </select>

        </div>

        <div class="filter-actions">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Tìm kiếm
            </button>

            <a
                href="/bookings/history.php"
                class="btn btn-secondary"
            >
                Đặt lại
            </a>

        </div>

    </form>

</section>

<section class="content-card table-card">

    <div class="table-summary">
        Có
        <strong><?php echo (int) $total; ?></strong>
        yêu cầu
    </div>

    <?php if (empty($bookings)): ?>

        <div class="empty-state">
            Không có yêu cầu nào phù hợp với bộ lọc.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table class="data-table">

                <thead>

                <tr>

                    <th>Mã</th>

                    <?php if ($isStaff): ?>
                        <th>Người gửi</th>
                    <?php endif; ?>

                    <th>Loại</th>

                    <th>Tài nguyên</th>

                    <th>Thời gian</th>

                    <th>Mục đích</th>

                    <th>Trạng thái</th>

                    <th>Ghi chú</th>

                    <th></th>

                </tr>

                </thead>

                <tbody>

                <?php foreach ($bookings as $item): ?>

                    <tr>

                        <td>
                            #<?php echo (int) $item['id']; ?>
                        </td>

                        <?php if ($isStaff): ?>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    (string) ($item['nguoi_gui'] ?? '-')
                                );
                                ?>
                            </td>

                        <?php endif; ?>

                        <td>

                            <?php
                            if (function_exists('bookingTypeLabel')) {
                                echo htmlspecialchars(
                                    bookingTypeLabel(
                                        $item['loai_yeu_cau']
                                    )
                                );
                            } else {
                                echo $item['loai_yeu_cau'] === 'room'
                                    ? 'Đặt phòng'
                                    : 'Mượn thiết bị';
                            }
                            ?>

                        </td>

                        <td>

                            <?php

                            if (!empty($item['tai_nguyen'])) {

                                echo htmlspecialchars(
                                    (string) $item['tai_nguyen']
                                );

                            } elseif ($item['loai_yeu_cau'] === 'room') {

                                echo htmlspecialchars(
                                    ($item['ma_phong'] ?? '')
                                    . ' - '
                                    . ($item['ten_phong'] ?? '')
                                );

                            } else {

                                echo htmlspecialchars(
                                    ($item['ma_thiet_bi'] ?? '')
                                    . ' - '
                                    . ($item['ten_thiet_bi'] ?? '')
                                );

                            }

                            ?>

                        </td>

                        <td>

                            <?php
                            echo date(
                                'd/m/Y H:i',
                                strtotime(
                                    $item['thoi_gian_bat_dau']
                                )
                            );
                            ?>

                            <br>

                            <small>
                                đến

                                <?php
                                echo date(
                                    'd/m/Y H:i',
                                    strtotime(
                                        $item['thoi_gian_ket_thuc']
                                    )
                                );
                                ?>
                            </small>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                (string) (
                                    $item['muc_dich'] ?? ''
                                )
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            $statusLabel = function_exists(
                                'bookingStatusLabel'
                            )
                                ? bookingStatusLabel(
                                    $item['trang_thai']
                                )
                                : $item['trang_thai'];
                            ?>

                            <span class="status-pill <?php
                                echo htmlspecialchars(
                                    $item['trang_thai']
                                );
                            ?>">
                                <?php
                                echo htmlspecialchars(
                                    $statusLabel
                                );
                                ?>
                            </span>

                        </td>

                        <td>

                            <?php
                            echo !empty($item['ly_do_tu_choi'])
                                ? htmlspecialchars(
                                    $item['ly_do_tu_choi']
                                )
                                : '&mdash;';
                            ?>

                        </td>

                        <td>

                            <a
                                href="/bookings/detail.php?id=<?php echo (int) $item['id']; ?>"
                                class="btn btn-secondary btn-small"
                            >
                                Chi tiết
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

    <?php if ($totalPages > 1): ?>

        <nav
            class="pagination"
            aria-label="Phân trang"
        >

            <?php for (
                $i = 1;
                $i <= $totalPages;
                $i++
            ): ?>

                <?php

                $query = http_build_query([
                    'q' => $keyword,
                    'type' => $type,
                    'trang_thai' => $status,
                    'page' => $i
                ]);

                ?>

                <a
                    href="?<?php echo htmlspecialchars($query); ?>"
                    class="<?php echo $i === $page ? 'active' : ''; ?>"
                >
                    <?php echo $i; ?>
                </a>

            <?php endfor; ?>

        </nav>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/app_foot.php'; ?>