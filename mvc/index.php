<?php
declare(strict_types=1);

/**
 * mvc/index.php — Front Controller cho phần đã migrate sang MVC chuẩn.
 * ---------------------------------------------------------------------
 * Đây là điểm vào DUY NHẤT cho các route /mvc/... . Toàn bộ hệ thống cũ
 * (auth/login.php, rooms/index.php, index.php, ...) hoàn toàn KHÔNG bị
 * đụng tới và tiếp tục chạy độc lập như trước — 2 cơ chế song song nhau
 * trong lúc migrate dần từng module (xem README_MVC.md ở thư mục gốc).
 * ---------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';     // $pdo + session an toàn
require_once __DIR__ . '/../includes/auth_check.php';  // current_user(), require_login()...
require_once __DIR__ . '/../includes/csrf.php';        // csrf_field(), csrf_verify()...
require_once __DIR__ . '/../includes/flash.php';       // flash_set(), flash_get()

require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/RoomController.php';
require_once __DIR__ . '/../app/Controllers/EquipmentController.php';
require_once __DIR__ . '/../app/Controllers/BookingController.php';
require_once __DIR__ . '/../app/Controllers/ReportController.php';
require_once __DIR__ . '/../app/Controllers/UserController.php';
require_once __DIR__ . '/../app/Controllers/SettingsController.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';

$router = new Router();

// ---- Route đã migrate: Auth (Phase 1) ----
$router->get('auth/login', [AuthController::class, 'showLogin']);
$router->post('auth/login', [AuthController::class, 'login']);
$router->get('auth/register', [AuthController::class, 'showRegister']);
$router->post('auth/register', [AuthController::class, 'register']);
$router->get('auth/logout', [AuthController::class, 'logout']);
$router->post('auth/logout', [AuthController::class, 'logout']);

// ---- Route đã migrate: Rooms (Phase 2) ----
$router->get('rooms', [RoomController::class, 'index']);
$router->get('rooms/form', [RoomController::class, 'form']);
$router->post('rooms/save', [RoomController::class, 'save']);
$router->post('rooms/delete', [RoomController::class, 'delete']);
$router->get('rooms/export', [RoomController::class, 'export']);
$router->get('rooms/import', [RoomController::class, 'showImport']);
$router->post('rooms/import', [RoomController::class, 'import']);

// ---- Route đã migrate: Equipment + Danh mục thiết bị (Phase 2) ----
// (equipment/handover.php thuộc nghiệp vụ Bookings, để dành Phase 3)
$router->get('equipment', [EquipmentController::class, 'index']);
$router->get('equipment/form', [EquipmentController::class, 'form']);
$router->post('equipment/save', [EquipmentController::class, 'save']);
$router->post('equipment/delete', [EquipmentController::class, 'delete']);
$router->get('equipment/export', [EquipmentController::class, 'export']);
$router->get('equipment/import', [EquipmentController::class, 'showImport']);
$router->post('equipment/import', [EquipmentController::class, 'import']);
$router->get('equipment-types', [EquipmentController::class, 'types']);
$router->get('equipment/handover', [EquipmentController::class, 'showHandover']);
$router->post('equipment/handover', [EquipmentController::class, 'handover']);

// ---- Route đã migrate: Bookings (Phase 3) ----
// /mvc/bookings tương đương bookings/my_requests.php (đích cho người dùng thường).
$router->get('bookings', [BookingController::class, 'myRequests']);
$router->get('bookings/form', [BookingController::class, 'form']);
$router->post('bookings/store', [BookingController::class, 'store']);
$router->post('bookings/cancel', [BookingController::class, 'cancel']);
$router->get('bookings/detail', [BookingController::class, 'detail']);
$router->get('bookings/history', [BookingController::class, 'history']);
// /mvc/bookings/pending tương đương bookings/pending.php và bookings/manage.php (alias cũ).
$router->get('bookings/pending', [BookingController::class, 'pending']);
$router->post('bookings/approve', [BookingController::class, 'approve']);
$router->post('bookings/reject', [BookingController::class, 'reject']);

// ---- Route đã migrate: Báo cáo (Phase 4) ----
$router->get('reports', [ReportController::class, 'index']);
$router->get('reports/create', [ReportController::class, 'create']);
$router->post('reports/store', [ReportController::class, 'store']);
$router->post('reports/update-status', [ReportController::class, 'updateStatus']);

// ---- Route đã migrate: Người dùng (Phase 4) ----
$router->get('users', [UserController::class, 'index']);
$router->post('users/toggle-status', [UserController::class, 'toggleStatus']);
$router->post('users/update-role', [UserController::class, 'updateRole']);

// ---- Route đã migrate: Cài đặt (Phase 4) ----
$router->get('settings', [SettingsController::class, 'index']);
$router->post('settings/update-info', [SettingsController::class, 'updateInfo']);
$router->post('settings/change-password', [SettingsController::class, 'changePassword']);

// ---- Route đã migrate: Dashboard / Tổng quan (Phase 5) ----
// "" (tức /mvc chính nó) là alias của "dashboard" để giống hành vi
// index.php gốc là trang chủ của toàn site.
$router->get('dashboard', [DashboardController::class, 'index']);
$router->get('', [DashboardController::class, 'index']);

// ---- Danh mục (danhmuc/*.php) và Bảo trì (maintenance/*.php): xem mục
//      "Ghi chú Phase 4" trong README_MVC.md — không migrate ở phase này
//      (danhmuc/* là giao diện mồ côi dùng model lệch schema, đã được
//      equipment-types thay thế từ Phase 2; maintenance/* chưa có logic
//      thật, chỉ là file rỗng chưa xây dựng). ----

// ---- Phase 6 (cắt chuyển cuối cùng): không thêm route mới, chỉ cập
//      nhật includes/navbar.php + includes/app_head.php để trỏ sang
//      /mvc/... rồi xoá file thủ tục cũ — xem README_MVC.md. ----

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$base = '/mvc';
$path = $requestPath;
if (strncmp($path, $base, strlen($base)) === 0) {
    $path = substr($path, strlen($base));
}
$path = trim($path, '/');

$router->dispatch($path, $_SERVER['REQUEST_METHOD'] ?? 'GET');
