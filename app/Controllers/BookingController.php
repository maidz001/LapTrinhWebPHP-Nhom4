<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Booking.php';

/**
 * app/Controllers/BookingController.php
 * ---------------------------------------------------------------------
 * Phiên bản MVC của bookings/form.php, store.php, my_requests.php,
 * pending.php (+ manage.php, chỉ là alias redirect), detail.php,
 * approve.php, reject.php, cancel.php, history.php.
 *
 * Các file bookings/*.php gốc KHÔNG bị xoá hay sửa và vẫn hoạt động
 * bình thường; đây là bản song song để migrate dần (xem README_MVC.md).
 * Toàn bộ quy tắc nghiệp vụ được giữ NGUYÊN VẸN như bản gốc: validate
 * thời gian/mục đích, kiểm tra trùng lịch, không tự duyệt/từ chối yêu
 * cầu của chính mình, chỉ chủ yêu cầu mới sửa/hủy được khi còn pending...
 * ---------------------------------------------------------------------
 */
final class BookingController extends Controller
{
    private const PER_PAGE = 3;

    /** GET /mvc/bookings — "Yêu cầu của tôi", tương đương bookings/my_requests.php. */
    public function myRequests(): void
    {
        require_login();
        $user = current_user();

        [$keyword, $status, $type, $page] = $this->readListFilters(['pending', 'approved', 'rejected', 'cancelled']);

        $bookingModel = new Booking();
        $filters = [
            'owner_id' => (int) $user['id'],
            'keyword' => $keyword,
            'status' => $status,
            'type' => $type,
        ];

        $total = $bookingModel->count($filters);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);
        $bookings = $bookingModel->paginate($filters, $page, self::PER_PAGE);

        $this->view('bookings/my_requests', [
            'bookings' => $bookings,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'status' => $status,
            'type' => $type,
            'flashSuccess' => flash_get('success'),
            'flashError' => flash_get('error'),
        ]);
    }

    /** GET /mvc/bookings/form — tạo mới / sửa (?id=X). */
    public function form(): void
    {
        require_login();
        $user = current_user();
        $bookingModel = new Booking();

        $id = $this->idParam('id') ?? 0;
        $booking = null;

        if ($id > 0) {
            $booking = $bookingModel->find($id);
            if (!$booking || (int) $booking['user_id'] !== (int) $user['id'] || $booking['trang_thai'] !== 'pending') {
                flash_set('error', 'Yêu cầu không tồn tại hoặc không còn được phép sửa.');
                $this->redirect('/mvc/bookings');
            }
        }

        $formData = $_SESSION['mvc_booking_old'] ?? [];
        unset($_SESSION['mvc_booking_old']);
        $errors = $_SESSION['mvc_booking_errors'] ?? [];
        unset($_SESSION['mvc_booking_errors']);

        $type = (string) ($formData['type'] ?? ($booking['loai_yeu_cau'] ?? $this->input('loai', 'room')));
        if (!in_array($type, Booking::LOAI_HOP_LE, true)) {
            $type = 'room';
        }

        $roomId = (int) ($formData['room_id'] ?? ($booking['room_id'] ?? 0));
        $equipmentId = (int) ($formData['equipment_id'] ?? ($booking['equipment_id'] ?? 0));
        $startTime = (string) ($formData['start_time'] ?? ($booking['thoi_gian_bat_dau'] ?? ''));
        $endTime = (string) ($formData['end_time'] ?? ($booking['thoi_gian_ket_thuc'] ?? ''));
        $purpose = (string) ($formData['purpose'] ?? ($booking['muc_dich'] ?? ''));

        // datetime-local cần định dạng YYYY-MM-DDTHH:MM.
        if ($startTime !== '' && !str_contains($startTime, 'T')) {
            $ts = strtotime($startTime);
            if ($ts !== false) {
                $startTime = date('Y-m-d\TH:i', $ts);
            }
        }
        if ($endTime !== '' && !str_contains($endTime, 'T')) {
            $ts = strtotime($endTime);
            if ($ts !== false) {
                $endTime = date('Y-m-d\TH:i', $ts);
            }
        }

        $this->view('bookings/form', [
            'id' => $id,
            'type' => $type,
            'roomId' => $roomId,
            'equipmentId' => $equipmentId,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'purpose' => $purpose,
            'errors' => $errors,
            'rooms' => $bookingModel->availableRooms(),
            'equipment' => $bookingModel->borrowableEquipment(),
            'flashError' => flash_get('error'),
            'flashSuccess' => flash_get('success'),
        ]);
    }

    /** POST /mvc/bookings/store — xử lý tạo mới / cập nhật. */
    public function store(): void
    {
        require_login();

        if (!$this->isPost()) {
            $this->redirect('/mvc/bookings/form');
        }

        $id = max(0, (int) $this->input('id', 0));
        $formUrl = '/mvc/bookings/form' . ($id > 0 ? ('?id=' . $id) : '');

        if (!csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
            $this->redirect($formUrl);
        }

        $type = (string) $this->input('loai_yeu_cau', '');
        $roomId = max(0, (int) $this->input('room_id', 0));
        $equipmentId = max(0, (int) $this->input('equipment_id', 0));
        $startInput = trim((string) $this->input('thoi_gian_bat_dau', ''));
        $endInput = trim((string) $this->input('thoi_gian_ket_thuc', ''));
        $purpose = trim((string) $this->input('muc_dich', ''));

        $errors = [];
        $bookingModel = new Booking();

        if (!in_array($type, Booking::LOAI_HOP_LE, true)) {
            $errors[] = 'Loại yêu cầu không hợp lệ.';
        }

        $resourceId = $type === 'equipment' ? $equipmentId : $roomId;

        if ($resourceId <= 0) {
            $errors[] = $type === 'equipment' ? 'Vui lòng chọn thiết bị.' : 'Vui lòng chọn phòng.';
        } elseif (in_array($type, Booking::LOAI_HOP_LE, true) && !$bookingModel->resourceExists($type, $resourceId)) {
            $errors[] = 'Phòng hoặc thiết bị đã chọn hiện không thể sử dụng.';
        }

        $purposeLength = mb_strlen($purpose, 'UTF-8');
        if ($purposeLength < 5 || $purposeLength > 255) {
            $errors[] = 'Mục đích sử dụng phải có từ 5 đến 255 ký tự.';
        }

        $startDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $startInput);
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $endInput);

        if (!$startDate || $startDate->format('Y-m-d\TH:i') !== $startInput) {
            $errors[] = 'Thời gian bắt đầu không hợp lệ.';
        }
        if (!$endDate || $endDate->format('Y-m-d\TH:i') !== $endInput) {
            $errors[] = 'Thời gian kết thúc không hợp lệ.';
        }
        if ($startDate && $endDate && $endDate <= $startDate) {
            $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }
        if ($startDate && $startDate->format('Y-m-d H:i:00') < date('Y-m-d H:i:00')) {
            $errors[] = 'Thời gian bắt đầu không được ở trong quá khứ.';
        }

        $startSql = $startDate ? $startDate->format('Y-m-d H:i:s') : '';
        $endSql = $endDate ? $endDate->format('Y-m-d H:i:s') : '';

        if (empty($errors) && $bookingModel->hasTimeConflict($type, $resourceId, $startSql, $endSql, $id > 0 ? $id : null)) {
            $errors[] = 'Phòng hoặc thiết bị đã có yêu cầu trùng thời gian.';
        }

        if (!empty($errors)) {
            $_SESSION['mvc_booking_errors'] = $errors;
            $_SESSION['mvc_booking_old'] = [
                'type' => $type,
                'room_id' => $roomId,
                'equipment_id' => $equipmentId,
                'start_time' => $startInput,
                'end_time' => $endInput,
                'purpose' => $purpose,
            ];
            $this->redirect($formUrl);
        }

        $data = [
            'type' => $type,
            'room_id' => $type === 'room' ? $roomId : null,
            'equipment_id' => $type === 'equipment' ? $equipmentId : null,
            'start_time' => $startSql,
            'end_time' => $endSql,
            'purpose' => $purpose,
        ];

        $user = current_user();

        if ($id > 0) {
            $updated = $bookingModel->updateOwn($id, (int) $user['id'], $data);
            flash_set($updated ? 'success' : 'error', $updated
                ? 'Cập nhật yêu cầu thành công.'
                : 'Yêu cầu không còn được phép sửa.');
        } else {
            $bookingModel->create((int) $user['id'], $data);
            flash_set('success', 'Tạo yêu cầu thành công và đang chờ duyệt.');
        }

        $this->redirect('/mvc/bookings');
    }

    /** POST /mvc/bookings/cancel */
    public function cancel(): void
    {
        require_login();

        if (!$this->isPost() || !csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Yêu cầu hủy không hợp lệ.');
            $this->redirect('/mvc/bookings');
        }

        $id = max(0, (int) $this->input('id', 0));
        $user = current_user();
        $cancelled = (new Booking())->cancelOwn($id, (int) $user['id']);

        flash_set($cancelled ? 'success' : 'error', $cancelled
            ? 'Hủy yêu cầu thành công.'
            : 'Yêu cầu không tồn tại hoặc không còn được phép hủy.');

        $this->redirect('/mvc/bookings');
    }

    /** GET /mvc/bookings/pending — quản lý yêu cầu (admin/lab_staff). Cũng là đích của bookings/manage.php. */
    public function pending(): void
    {
        require_role(['admin', 'lab_staff']);

        [$keyword, $status, $type, $page] = $this->readListFilters(
            ['pending', 'approved', 'rejected', 'cancelled'],
            'pending'
        );

        $bookingModel = new Booking();
        $filters = ['keyword' => $keyword, 'status' => $status, 'type' => $type];

        $total = $bookingModel->count($filters);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);
        $bookings = $bookingModel->paginate($filters, $page, self::PER_PAGE);

        $this->view('bookings/pending', [
            'bookings' => $bookings,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'status' => $status,
            'type' => $type,
            'flashSuccess' => flash_get('success'),
            'flashError' => flash_get('error'),
        ]);
    }

    /** POST /mvc/bookings/approve */
    public function approve(): void
    {
        require_role(['admin', 'lab_staff']);

        $id = max(0, (int) $this->input('id', 0));
        $returnUrl = $this->input('return_to') === 'detail'
            ? '/mvc/bookings/detail?id=' . $id
            : '/mvc/bookings/pending';

        if (!$this->isPost() || !csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Yêu cầu duyệt không hợp lệ.');
            $this->redirect($returnUrl);
        }

        $bookingModel = new Booking();
        $booking = $bookingModel->find($id);

        if (!$booking || $booking['trang_thai'] !== 'pending') {
            flash_set('error', 'Yêu cầu không tồn tại hoặc đã được xử lý.');
            $this->redirect($returnUrl);
        }

        $user = current_user();
        if ((int) $booking['user_id'] === (int) $user['id']) {
            flash_set('error', 'Bạn không thể tự duyệt yêu cầu của chính mình.');
            $this->redirect($returnUrl);
        }

        $resourceId = $booking['loai_yeu_cau'] === 'room' ? (int) $booking['room_id'] : (int) $booking['equipment_id'];
        $conflict = $bookingModel->hasTimeConflict(
            $booking['loai_yeu_cau'],
            $resourceId,
            $booking['thoi_gian_bat_dau'],
            $booking['thoi_gian_ket_thuc'],
            $id,
            true
        );

        if ($conflict) {
            flash_set('error', 'Không thể duyệt vì đã có lịch được duyệt trùng thời gian.');
        } else {
            $approved = $bookingModel->approve($id, (int) $user['id']);
            flash_set($approved ? 'success' : 'error', $approved
                ? 'Duyệt yêu cầu thành công.'
                : 'Yêu cầu đã được người khác xử lý.');
        }

        $this->redirect($returnUrl);
    }

    /** POST /mvc/bookings/reject */
    public function reject(): void
    {
        require_role(['admin', 'lab_staff']);

        $id = max(0, (int) $this->input('id', 0));
        $returnUrl = $this->input('return_to') === 'detail'
            ? '/mvc/bookings/detail?id=' . $id
            : '/mvc/bookings/pending';

        if (!$this->isPost() || !csrf_verify($this->stringInput('csrf_token'))) {
            flash_set('error', 'Yêu cầu từ chối không hợp lệ.');
            $this->redirect($returnUrl);
        }

        $reason = trim((string) $this->input('ly_do_tu_choi', ''));
        if (mb_strlen($reason, 'UTF-8') < 5 || mb_strlen($reason, 'UTF-8') > 255) {
            flash_set('error', 'Lý do từ chối phải có từ 5 đến 255 ký tự.');
            $this->redirect($returnUrl);
        }

        $bookingModel = new Booking();
        $booking = $bookingModel->find($id);

        if (!$booking || $booking['trang_thai'] !== 'pending') {
            flash_set('error', 'Yêu cầu không tồn tại hoặc đã được xử lý.');
            $this->redirect($returnUrl);
        }

        $user = current_user();
        if ((int) $booking['user_id'] === (int) $user['id']) {
            flash_set('error', 'Bạn không thể tự xử lý yêu cầu của chính mình.');
            $this->redirect($returnUrl);
        }

        $rejected = $bookingModel->reject($id, (int) $user['id'], $reason);
        flash_set($rejected ? 'success' : 'error', $rejected
            ? 'Đã từ chối yêu cầu.'
            : 'Yêu cầu không tồn tại hoặc đã được xử lý.');

        $this->redirect($returnUrl);
    }

    /** GET /mvc/bookings/detail */
    public function detail(): void
    {
        require_login();

        $id = max(0, (int) $this->input('id', 0));
        $booking = (new Booking())->find($id);
        $user = current_user();
        $isStaff = in_array($user['role'] ?? '', ['admin', 'lab_staff'], true);
        $isOwner = $booking && (int) $booking['user_id'] === (int) $user['id'];

        if (!$booking || (!$isStaff && !$isOwner)) {
            http_response_code(404);
            $this->view('bookings/not_found', ['isStaff' => $isStaff]);
            return;
        }

        $resourceName = $booking['loai_yeu_cau'] === 'room'
            ? $booking['ma_phong'] . ' - ' . $booking['ten_phong']
            : $booking['ma_thiet_bi'] . ' - ' . $booking['ten_thiet_bi'];

        $this->view('bookings/detail', [
            'id' => $id,
            'booking' => $booking,
            'resourceName' => $resourceName,
            'isStaff' => $isStaff,
            'isOwner' => $isOwner,
            'flashSuccess' => flash_get('success'),
            'flashError' => flash_get('error'),
        ]);
    }

    /** GET /mvc/bookings/history */
    public function history(): void
    {
        require_login();

        $user = current_user();
        $isStaff = in_array($user['role'] ?? '', ['admin', 'lab_staff'], true);

        $keyword = trim((string) $this->input('q', ''));
        $type = (string) $this->input('type', 'all');
        if (!in_array($type, ['all', 'room', 'equipment'], true)) {
            $type = 'all';
        }
        $status = (string) $this->input('trang_thai', 'all');
        if (!in_array($status, array_merge(['all'], Booking::TRANG_THAI_HOP_LE), true)) {
            $status = 'all';
        }
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 10;

        $bookingModel = new Booking();

        if ($status === 'all') {
            $bookings = $bookingModel->allForHistory($isStaff, (int) $user['id'], $keyword, $type);
            $total = count($bookings);
            $totalPages = 1;
        } else {
            $filters = [
                'owner_id' => $isStaff ? 0 : (int) $user['id'],
                'keyword' => $keyword,
                'status' => $status,
                'type' => $type === 'all' ? '' : $type,
            ];
            $total = $bookingModel->count($filters);
            $totalPages = max(1, (int) ceil($total / $perPage));
            $page = min($page, $totalPages);
            $bookings = $bookingModel->paginate($filters, $page, $perPage);
        }

        $this->view('bookings/history', [
            'bookings' => $bookings,
            'isStaff' => $isStaff,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'type' => $type,
            'status' => $status,
        ]);
    }

    /**
     * Đọc bộ lọc chung cho các trang danh sách (my_requests/pending).
     * @return array{0: string, 1: string, 2: string, 3: int}
     */
    private function readListFilters(array $allowedStatus, string $defaultStatus = ''): array
    {
        $keyword = trim((string) $this->input('q', ''));

        $status = (string) $this->input('status', $defaultStatus);
        if (!in_array($status, array_merge([''], $allowedStatus), true)) {
            $status = $defaultStatus;
        }

        $type = (string) $this->input('type', '');
        if (!in_array($type, ['', 'room', 'equipment'], true)) {
            $type = '';
        }

        $page = max(1, (int) $this->input('page', 1));

        return [$keyword, $status, $type, $page];
    }

    private function idParam(string $key): ?int
    {
        $v = $this->input($key);
        return ($v !== null && $v !== '' && ctype_digit((string) $v)) ? (int) $v : null;
    }

    private function stringInput(string $key): ?string
    {
        $v = $this->input($key);
        return is_string($v) ? $v : null;
    }
}
