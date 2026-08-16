# Hệ thống quản lý phòng thực hành và thiết bị

## Tên đề tài
**Website Hệ thống quản lý phòng thực hành và thiết bị** — hỗ trợ đặt phòng, mượn thiết bị, báo hỏng và theo dõi bảo trì trong khoa/phòng thí nghiệm.

## Mô tả
Website được xây dựng bằng PHP thuần và MySQL (sử dụng PDO), thực hiện trong khuôn khổ học phần Lập trình Web.

## Danh sách thành viên và phân công

| Họ tên | Vai trò phụ trách |
|---|---|
| Nguyễn Hồng Mai | Phân quyền, Dashboard & Kiểm thử |
| Nguyễn Kỳ | Duyệt yêu cầu & Quản lý booking |
| Triệu Văn Phấn | Đặt phòng |
| Đặng Quang Trung | Quản lý Phòng và Thiết bị  |
| Nguyễn Mạnh Hiếu | Báo hỏng & Bảo trì |

## Các đối tượng dữ liệu chính

| Bảng | Mô tả | Phụ trách |
|---|---|---|
| `users` | Tài khoản người dùng, phân quyền (user / lab_staff / admin) | Nguyễn Hồng Mai |
| `rooms` | Thông tin phòng thực hành (tên, vị trí, sức chứa, trạng thái) | Đặng Quang Trung |
| `equipment_types` | Loại thiết bị | Đặng Quang Trung |
| `equipment` | Thiết bị (gắn với loại và phòng, trạng thái hoạt động/hỏng/bảo trì) | Đặng Quang Trung |
| `bookings` | Yêu cầu đặt phòng (thời gian, trạng thái duyệt) | Triệu Văn Phấn / Nguyễn Kỳ |
| `reports` | Báo hỏng thiết bị | Nguyễn Mạnh Hiếu |
| `maintenance_logs` | Lịch sử bảo trì thiết bị | Nguyễn Mạnh Hiếu |

## Các chức năng dự kiến

- **Vai trò người dùng:** Sinh viên/giảng viên (xem lịch, đặt phòng, mượn thiết bị); Cán bộ phòng lab (duyệt yêu cầu, cập nhật thiết bị, xử lý báo hỏng); Admin (quản lý tài khoản, phòng, thiết bị, phân quyền).
- CRUD phòng, loại thiết bị, thiết bị.
- Đặt phòng theo khoảng thời gian; duyệt/từ chối yêu cầu; người dùng tự hủy yêu cầu khi chưa bắt đầu.
- Báo hỏng thiết bị, cập nhật trạng thái và lịch sử bảo trì.
- Lọc thiết bị/phòng theo loại, trạng thái.
- Endpoint JSON kiểm tra phòng/thiết bị còn trống.
- Dashboard thống kê số thiết bị hoạt động, hỏng, đang bảo trì.
- Ràng buộc nghiệp vụ: không cho 2 booking đã duyệt trùng giờ cùng phòng; thiết bị hỏng/đang bảo trì không cho mượn; giờ kết thúc phải lớn hơn giờ bắt đầu; cán bộ lab mới được cập nhật kết quả bảo trì.

## Các chức năng đã thực hiện đến hết Buổi 2

- [x] Thiết kế và khởi tạo database (`database/database.sql`): bảng `users`, `rooms`, `equipment_types`, `equipment`, `bookings`, `reports`, `maintenance_logs` kèm dữ liệu mẫu.
- [x] Kết nối CSDL bằng PDO (`config/database.php`).
- [x] CRUD Phòng: danh sách, thêm, sửa, xóa (`rooms/`).
- [x] CRUD Loại thiết bị: danh sách, thêm, sửa, xóa (`equipment_types/`).
- [x] CRUD Thiết bị + màn hình **Danh sách thiết bị** (lọc theo phòng/loại/trạng thái, thống kê nhanh) (`equipment/`).
- [x] Endpoint JSON kiểm tra phòng/thiết bị còn trống (`api/check_availability.php`).
- [x] Khung kiểm tra phân quyền cơ bản (`includes/auth_check.php`) dùng chung cho cả nhóm.
- [x] Layout dùng chung: header, footer, navbar (`includes/`).
- [ ] Đặt phòng, duyệt yêu cầu — đang thực hiện.
- [ ] Báo hỏng, lịch sử bảo trì — đang thực hiện.
- [ ] Đăng nhập/đăng ký, phân quyền đầy đủ, dashboard — đang thực hiện.

## Yêu cầu môi trường
- PHP >= 8.0
- MySQL >= 5.7
- Web server: XAMPP / Laragon / WAMP

## Cấu trúc thư mục

```
LapTrinhWebPHP-Nhom4/
├── about.php
├── index.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── navbar.php
│   └── auth_check.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
├── config/
│   └── database.php
├── database/
│   └── database.sql
├── rooms/
│   ├── list.php
│   ├── add.php
│   ├── edit.php
│   └── delete.php
├── equipment_types/
│   ├── list.php
│   ├── add.php
│   ├── edit.php
│   └── delete.php
├── equipment/
│   ├── list.php
│   ├── add.php
│   ├── edit.php
│   └── delete.php
├── bookings/            # (đang phát triển - Triệu Văn Phấn, Nguyễn Kỳ)
├── reports/              # (đang phát triển - Nguyễn Mạnh Hiếu)
├── maintenance/          # (đang phát triển - Nguyễn Mạnh Hiếu)
├── auth/                 # (đang phát triển - Nguyễn Hồng Mai)
├── dashboard/            # (đang phát triển - Nguyễn Hồng Mai)
├── api/
│   └── check_availability.php
└── README.md
```

## Hướng dẫn cài đặt và chạy local

1. Clone repository về máy:
```bash
git clone https://github.com/maidz001/LapTrinhWebPHP-Nhom4.git
```

2. Copy thư mục project vào thư mục web server, ví dụ với XAMPP:
```
C:/xampp/htdocs/LapTrinhWebPHP-Nhom4
```

3. Khởi động **Apache** và **MySQL** trong XAMPP Control Panel.

4. Tạo database:
   - Mở phpMyAdmin: `http://localhost/phpmyadmin`
   - Import trực tiếp file `database/database.sql` (file đã tự tạo database `quanly_phongthuchanh` và dữ liệu mẫu)
   - Kiểm tra lại thông tin kết nối trong `config/database.php` cho khớp (host, username, password, tên database)

5. Truy cập project trên trình duyệt:
```
http://localhost/LapTrinhWebPHP-Nhom4/index.php
```
