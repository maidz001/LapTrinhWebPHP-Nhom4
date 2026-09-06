-- =====================================================================
-- HỆ THỐNG QUẢN LÝ PHÒNG THỰC HÀNH & THIẾT BỊ — Nhóm 4
-- File:   database/database.sql
-- Mô tả:  Script khởi tạo database + toàn bộ bảng + dữ liệu mẫu
--         Thiết kế theo tài liệu thống nhất SOBOHETHONG.md (mục 2)
-- Engine: MySQL 8.0+ / MariaDB 10.4+  (InnoDB, utf8mb4)
-- Cách dùng:
--   mysql -u root -p < database.sql
--   hoặc import trực tiếp file này qua phpMyAdmin
-- =====================================================================

-- ---------------------------------------------------------------------
-- 0. TẠO DATABASE
-- ---------------------------------------------------------------------
DROP DATABASE IF EXISTS quanly_phongthuchanh;

CREATE DATABASE quanly_phongthuchanh
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE quanly_phongthuchanh;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. BẢNG users — Tài khoản người dùng & phân quyền
-- =====================================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ho_ten`         VARCHAR(100)  NOT NULL,
    `email`          VARCHAR(150)  NOT NULL,
    `mat_khau`       VARCHAR(255)  NOT NULL COMMENT 'Lưu hash bằng password_hash()',
    `so_dien_thoai`  VARCHAR(15)   NULL,
    `vai_tro`        ENUM('user','lab_staff','admin') NOT NULL DEFAULT 'user',
    `trang_thai`     ENUM('active','locked') NOT NULL DEFAULT 'active',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_vai_tro` (`vai_tro`),
    KEY `idx_users_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tài khoản người dùng: sinh viên/giảng viên, cán bộ lab, admin';

-- =====================================================================
-- 2. BẢNG rooms — Phòng thực hành
-- =====================================================================
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ma_phong`    VARCHAR(20)  NOT NULL COMMENT 'VD: TH01',
    `ten_phong`   VARCHAR(100) NOT NULL,
    `vi_tri`      VARCHAR(150) NOT NULL COMMENT 'Toà nhà, tầng',
    `suc_chua`    INT UNSIGNED NOT NULL,
    `trang_thai`  ENUM('available','maintenance','closed') NOT NULL DEFAULT 'available',
    `mo_ta`       TEXT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_rooms_ma_phong` (`ma_phong`),
    KEY `idx_rooms_trang_thai` (`trang_thai`),
    CONSTRAINT `chk_rooms_suc_chua` CHECK (`suc_chua` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Danh mục phòng thực hành';

-- =====================================================================
-- 3. BẢNG equipment_types — Loại thiết bị
-- =====================================================================
DROP TABLE IF EXISTS `equipment_types`;
CREATE TABLE `equipment_types` (
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ten_loai`  VARCHAR(100) NOT NULL,
    `mo_ta`     TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_equipment_types_ten_loai` (`ten_loai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Danh mục loại thiết bị (máy tính, máy chiếu, ...)';

-- =====================================================================
-- 4. BẢNG equipment — Thiết bị
-- =====================================================================
DROP TABLE IF EXISTS `equipment`;
CREATE TABLE `equipment` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ma_thiet_bi`    VARCHAR(30)  NOT NULL,
    `ten_thiet_bi`   VARCHAR(150) NOT NULL,
    `type_id`        INT UNSIGNED NOT NULL,
    `room_id`        INT UNSIGNED NULL COMMENT 'NULL = thiết bị lưu động, có thể mang đi mượn',
    `co_the_muon`    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Đánh dấu thiết bị dùng cho luồng mượn thiết bị',
    `trang_thai`     ENUM('active','broken','maintenance','borrowed') NOT NULL DEFAULT 'active',
    `ngay_mua`       DATE NULL,
    `mo_ta`          TEXT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_equipment_ma_thiet_bi` (`ma_thiet_bi`),
    KEY `idx_equipment_type_id` (`type_id`),
    KEY `idx_equipment_room_id` (`room_id`),
    KEY `idx_equipment_trang_thai` (`trang_thai`),
    CONSTRAINT `fk_equipment_type`
        FOREIGN KEY (`type_id`) REFERENCES `equipment_types` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_equipment_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Thiết bị thực hành, gắn với loại và (tuỳ chọn) phòng';

-- =====================================================================
-- 5. BẢNG bookings — Yêu cầu sử dụng tài nguyên
--    (hợp nhất Đặt phòng + Mượn thiết bị, phân biệt bằng loai_yeu_cau)
-- =====================================================================
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`             INT UNSIGNED NOT NULL,
    `loai_yeu_cau`        ENUM('room','equipment') NOT NULL,
    `room_id`             INT UNSIGNED NULL COMMENT 'Bắt buộc khi loai_yeu_cau = room',
    `equipment_id`        INT UNSIGNED NULL COMMENT 'Bắt buộc khi loai_yeu_cau = equipment',
    `thoi_gian_bat_dau`   DATETIME NOT NULL,
    `thoi_gian_ket_thuc`  DATETIME NOT NULL,
    `muc_dich`            VARCHAR(255) NOT NULL,
    `trang_thai`          ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `approved_by`         INT UNSIGNED NULL,
    `approved_at`         DATETIME NULL,
    `ly_do_tu_choi`       VARCHAR(255) NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_bookings_user_id` (`user_id`),
    KEY `idx_bookings_room_id` (`room_id`),
    KEY `idx_bookings_equipment_id` (`equipment_id`),
    KEY `idx_bookings_trang_thai` (`trang_thai`),
    KEY `idx_bookings_thoi_gian` (`thoi_gian_bat_dau`, `thoi_gian_ket_thuc`),
    CONSTRAINT `fk_bookings_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_equipment`
        FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_approved_by`
        FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_bookings_thoi_gian` CHECK (`thoi_gian_ket_thuc` > `thoi_gian_bat_dau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Yêu cầu đặt phòng / mượn thiết bị và quy trình duyệt';
-- Lưu ý nghiệp vụ (kiểm tra ở tầng ứng dụng khi INSERT/UPDATE, không đặt CHECK
-- đa cột ở DB để tương thích rộng rãi giữa MySQL/MariaDB):
--   - loai_yeu_cau = 'room'      => room_id      NOT NULL, equipment_id IS NULL
--   - loai_yeu_cau = 'equipment' => equipment_id NOT NULL, room_id      IS NULL

-- =====================================================================
-- 6. BẢNG reports — Báo hỏng thiết bị
-- =====================================================================
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `equipment_id`   INT UNSIGNED NOT NULL,
    `reported_by`    INT UNSIGNED NOT NULL,
    `mo_ta_su_co`    TEXT NOT NULL,
    `muc_do`         ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    `trang_thai`     ENUM('new','processing','resolved','cancelled') NOT NULL DEFAULT 'new',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_reports_equipment_id` (`equipment_id`),
    KEY `idx_reports_reported_by` (`reported_by`),
    KEY `idx_reports_trang_thai` (`trang_thai`),
    CONSTRAINT `fk_reports_equipment`
        FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_user`
        FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Báo hỏng thiết bị do người dùng gửi';

-- =====================================================================
-- 7. BẢNG maintenance_logs — Lịch sử bảo trì
-- =====================================================================
DROP TABLE IF EXISTS `maintenance_logs`;
CREATE TABLE `maintenance_logs` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `equipment_id`     INT UNSIGNED NOT NULL,
    `report_id`        INT UNSIGNED NULL COMMENT 'Liên kết báo hỏng gốc nếu có',
    `performed_by`     INT UNSIGNED NOT NULL COMMENT 'Chỉ lab_staff/admin',
    `noi_dung_xu_ly`   TEXT NOT NULL,
    `ngay_bat_dau`     DATE NOT NULL,
    `ngay_ket_thuc`    DATE NULL,
    `ket_qua`          ENUM('fixed','replaced','pending','unrepairable') NOT NULL DEFAULT 'pending',
    `chi_phi`          DECIMAL(12,2) NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_maintenance_equipment_id` (`equipment_id`),
    KEY `idx_maintenance_report_id` (`report_id`),
    KEY `idx_maintenance_performed_by` (`performed_by`),
    CONSTRAINT `fk_maintenance_equipment`
        FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_maintenance_report`
        FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_maintenance_user`
        FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_maintenance_ngay` CHECK (`ngay_ket_thuc` IS NULL OR `ngay_ket_thuc` >= `ngay_bat_dau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lịch sử xử lý / bảo trì thiết bị';

-- =====================================================================
-- 8. BẢNG login_attempts — Nhật ký đăng nhập (phục vụ chống brute-force)
--    Bổ sung so với bản thiết kế gốc để auth/login.php có thể khoá tạm
--    thời tài khoản sau nhiều lần đăng nhập sai, đúng mục 5.1 SOBOHETHONG.md.
-- =====================================================================
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email`       VARCHAR(150) NOT NULL,
    `ip_address`  VARCHAR(45)  NOT NULL COMMENT 'Hỗ trợ cả IPv4 và IPv6',
    `thanh_cong`  TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_login_attempts_email_time` (`email`, `created_at`),
    KEY `idx_login_attempts_ip_time` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lịch sử các lần đăng nhập (thành công/thất bại) dùng để tạm khoá tài khoản';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 9. DỮ LIỆU MẪU
-- =====================================================================

-- 9.1. users
-- Mật khẩu cho cả 5 tài khoản mẫu: "Matkhau123" (đủ chữ + số, >= 8 ký tự)
-- Hash BCrypt THẬT (cost 12) tương thích password_verify() của PHP —
-- có thể đăng nhập ngay bằng dữ liệu mẫu này để kiểm thử auth/login.php.
INSERT INTO `users` (`ho_ten`, `email`, `mat_khau`, `so_dien_thoai`, `vai_tro`, `trang_thai`) VALUES
('Nguyễn Hồng Mai',  'mai.admin@nhom4.edu.vn',    '$2b$12$BkrE9VQfiZ0N2JpMBasHieMb2ys61dR1wyxM4vHeMsu4sn/prVb36', '0900000001', 'admin',      'active'),
('Nguyễn Kỳ',        'ky.labstaff@nhom4.edu.vn',  '$2b$12$BkrE9VQfiZ0N2JpMBasHieMb2ys61dR1wyxM4vHeMsu4sn/prVb36', '0900000002', 'lab_staff',  'active'),
('Triệu Văn Phấn',   'phan.sv@nhom4.edu.vn',      '$2b$12$BkrE9VQfiZ0N2JpMBasHieMb2ys61dR1wyxM4vHeMsu4sn/prVb36', '0900000003', 'user',       'active'),
('Đặng Quang Trung', 'trung.gv@nhom4.edu.vn',     '$2b$12$BkrE9VQfiZ0N2JpMBasHieMb2ys61dR1wyxM4vHeMsu4sn/prVb36', '0900000004', 'user',       'active'),
('Nguyễn Mạnh Hiếu', 'hieu.labstaff@nhom4.edu.vn','$2b$12$BkrE9VQfiZ0N2JpMBasHieMb2ys61dR1wyxM4vHeMsu4sn/prVb36', '0900000005', 'lab_staff',  'active');

-- 9.2. rooms
INSERT INTO `rooms` (`ma_phong`, `ten_phong`, `vi_tri`, `suc_chua`, `trang_thai`, `mo_ta`) VALUES
('TH01', 'Phòng thực hành Tin học 1', 'Nhà A, Tầng 2', 40, 'available',   'Phòng máy tính dùng cho các học phần lập trình'),
('TH02', 'Phòng thực hành Tin học 2', 'Nhà A, Tầng 2', 40, 'available',   'Phòng máy tính dùng cho các học phần cơ sở dữ liệu'),
('TH03', 'Phòng thực hành Mạng',     'Nhà A, Tầng 3', 30, 'maintenance', 'Đang bảo trì hệ thống mạng LAN'),
('TH04', 'Phòng Lab Điện tử',        'Nhà B, Tầng 1', 25, 'available',   'Phòng thực hành điện tử - vi xử lý'),
('TH05', 'Phòng Hội thảo',           'Nhà B, Tầng 3', 60, 'closed',      'Tạm đóng để sửa chữa trần nhà');

-- 9.3. equipment_types
INSERT INTO `equipment_types` (`ten_loai`, `mo_ta`) VALUES
('Máy tính để bàn', 'Máy tính PC dùng trong phòng thực hành'),
('Máy chiếu',        'Máy chiếu dùng để trình chiếu bài giảng'),
('Laptop',           'Laptop cho mượn mang đi'),
('Thiết bị mạng',    'Switch, Router, Access Point'),
('Thiết bị đo',      'Đồng hồ đo điện, oscilloscope...');

-- 9.4. equipment
INSERT INTO `equipment` (`ma_thiet_bi`, `ten_thiet_bi`, `type_id`, `room_id`, `co_the_muon`, `trang_thai`, `ngay_mua`, `mo_ta`) VALUES
('PC-TH01-01', 'Máy tính Dell OptiPlex 01', 1, 1, 0, 'active',      '2023-08-01', 'Cấu hình i5/8GB/256GB SSD'),
('PC-TH01-02', 'Máy tính Dell OptiPlex 02', 1, 1, 0, 'broken',      '2023-08-01', 'Hỏng nguồn, chờ báo hỏng xử lý'),
('PC-TH02-01', 'Máy tính HP ProDesk 01',    1, 2, 0, 'active',      '2023-08-01', NULL),
('MC-TH02-01', 'Máy chiếu Epson EB-X05',    2, 2, 0, 'maintenance', '2022-05-10', 'Đang bảo trì bóng đèn'),
('LT-MUON-01', 'Laptop Dell Latitude 01',   3, NULL, 1, 'active',    '2024-01-15', 'Thiết bị lưu động, cho mượn mang đi'),
('LT-MUON-02', 'Laptop Dell Latitude 02',   3, NULL, 1, 'borrowed',  '2024-01-15', 'Đang được mượn'),
('SW-TH03-01', 'Switch Cisco 24-port',      4, 3, 0, 'active',      '2022-11-20', NULL),
('DM-TH04-01', 'Đồng hồ đo VOM Sanwa',      5, 4, 1, 'active',      '2023-03-05', 'Thiết bị cho mượn trong buổi thực hành');

-- 9.5. bookings (2 = Nguyễn Kỳ (lab_staff, duyệt), 3 = Phấn, 4 = Trung)
INSERT INTO `bookings`
    (`user_id`, `loai_yeu_cau`, `room_id`, `equipment_id`, `thoi_gian_bat_dau`, `thoi_gian_ket_thuc`, `muc_dich`, `trang_thai`, `approved_by`, `approved_at`, `ly_do_tu_choi`)
VALUES
(3, 'room',      1,    NULL, '2026-08-25 07:00:00', '2026-08-25 09:00:00', 'Học nhóm môn Lập trình Web', 'approved', 2, '2026-08-22 08:00:00', NULL),
(4, 'room',      2,    NULL, '2026-08-26 13:00:00', '2026-08-26 15:00:00', 'Thực hành CSDL',             'pending',  NULL, NULL, NULL),
(3, 'equipment', NULL, 5,    '2026-08-23 08:00:00', '2026-08-23 17:00:00', 'Mượn laptop làm đồ án',      'approved', 2, '2026-08-22 09:00:00', NULL),
(4, 'equipment', NULL, 8,    '2026-08-24 08:00:00', '2026-08-24 10:00:00', 'Mượn đồng hồ đo thực hành',  'rejected', 2, '2026-08-22 10:00:00', 'Thiết bị đã có người đặt trùng giờ');

-- 9.6. reports
INSERT INTO `reports` (`equipment_id`, `reported_by`, `mo_ta_su_co`, `muc_do`, `trang_thai`) VALUES
(2, 3, 'Máy tính không lên nguồn, đã kiểm tra dây điện vẫn không khởi động được', 'high',   'processing'),
(4, 4, 'Máy chiếu bị mờ, nghi ngờ hỏng bóng đèn',                                  'medium', 'new');

-- 9.7. maintenance_logs
INSERT INTO `maintenance_logs`
    (`equipment_id`, `report_id`, `performed_by`, `noi_dung_xu_ly`, `ngay_bat_dau`, `ngay_ket_thuc`, `ket_qua`, `chi_phi`)
VALUES
(2, 1, 5, 'Kiểm tra và thay nguồn máy tính mới', '2026-08-20', NULL,          'pending', 350000.00),
(4, 2, 5, 'Đặt lịch thay bóng đèn máy chiếu',     '2026-08-21', '2026-08-21', 'pending', NULL);

-- =====================================================================
-- HẾT SCRIPT
-- =====================================================================
