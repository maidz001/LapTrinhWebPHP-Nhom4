# Hệ thống quản lý phòng thực hành và thiết bị

## Mô tả

**Website Hệ thống quản lý phòng thực hành và thiết bị** — hỗ trợ đặt phòng, mượn thiết bị, báo hỏng và theo dõi bảo trì trong khoa/phòng thí nghiệm. Xây dựng bằng **PHP thuần** (không framework) và **MySQL** (kết nối qua PDO), thực hiện trong khuôn khổ học phần Lập trình Web.

Thiết kế CSDL, wireframe màn hình và quy tắc nghiệp vụ được thống nhất chi tiết trong [`SOBOHETHONG.md`](SOBOHETHONG.md) — nên đọc file đó trước khi sửa code liên quan tới database hoặc luồng nghiệp vụ.

## Danh sách thành viên và phân công

|      Họ tên      |         Vai trò phụ trách        |
|------------------|-----------------------------------|
| Nguyễn Hồng Mai  | Phân quyền, Đăng nhập/Đăng ký, Dashboard & Kiểm thử |
| Nguyễn Kỳ        | Duyệt yêu cầu & Quản lý booking  |
| Triệu Văn Phấn   | Đặt phòng                        |
| Đặng Quang Trung | Quản lý Phòng và Thiết bị        |
| Nguyễn Mạnh Hiếu | Báo hỏng & Bảo trì               |

## Vai trò người dùng

- **user** (sinh viên/giảng viên): xem lịch phòng/thiết bị, tạo yêu cầu đặt phòng/mượn thiết bị, huỷ yêu cầu của mình, báo hỏng.
- **lab_staff** (cán bộ phòng lab): tất cả quyền của `user` + duyệt/từ chối yêu cầu, cập nhật trạng thái thiết bị, xử lý bảo trì, xem báo hỏng toàn hệ thống.
- **admin**: tất cả quyền của `lab_staff` + CRUD phòng/loại thiết bị/thiết bị, quản lý tài khoản người dùng và phân quyền.

Chi tiết ma trận phân quyền theo từng chức năng xem tại mục 7 của `SOBOHETHONG.md`.

---

## Mô tả cơ sở dữ liệu

Database tên **`quanly_phongthuchanh`**, engine **InnoDB**, charset **utf8mb4** (hỗ trợ đầy đủ tiếng Việt có dấu). Toàn bộ script khởi tạo nằm ở [`database/database.sql`](database/database.sql).

Gồm **8 bảng**, chia làm 2 nhóm: dữ liệu nghiệp vụ (7 bảng) và bảng bảo mật hỗ trợ đăng nhập (1 bảng).

| # | Bảng | Mô tả | Bảng tham chiếu tới |
|---|---|---|---|
| 1 | `users` | Tài khoản người dùng, phân quyền (`user` / `lab_staff` / `admin`), trạng thái khoá tài khoản | — |
| 2 | `rooms` | Danh mục phòng thực hành: mã phòng, tên, vị trí, sức chứa, trạng thái | — |
| 3 | `equipment_types` | Danh mục loại thiết bị (máy tính, máy chiếu, laptop, thiết bị mạng, thiết bị đo...) | — |
| 4 | `equipment` | Thiết bị cụ thể, gắn với 1 loại và (tuỳ chọn) 1 phòng; đánh dấu thiết bị có thể cho mượn mang đi | `equipment_types`, `rooms` |
| 5 | `bookings` | Yêu cầu sử dụng tài nguyên — **hợp nhất** đặt phòng và mượn thiết bị, phân biệt bằng cột `loai_yeu_cau` | `users`, `rooms`, `equipment` |
| 6 | `reports` | Báo hỏng thiết bị do người dùng gửi lên | `equipment`, `users` |
| 7 | `maintenance_logs` | Lịch sử xử lý/bảo trì thiết bị, có thể liên kết tới báo hỏng gốc | `equipment`, `reports`, `users` |
| 8 | `login_attempts` | Nhật ký các lần đăng nhập (thành công/thất bại) theo email + IP, dùng để tạm khoá tài khoản khi đăng nhập sai nhiều lần | — |

### Chi tiết từng bảng

**`users`** — tài khoản & phân quyền
| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `ho_ten` | VARCHAR(100) | |
| `email` | VARCHAR(150) | **UNIQUE**, dùng để đăng nhập |
| `mat_khau` | VARCHAR(255) | Hash bcrypt tạo bằng `password_hash()`, **không bao giờ lưu plain text** |
| `so_dien_thoai` | VARCHAR(15) | Không bắt buộc |
| `vai_tro` | ENUM('user','lab_staff','admin') | Mặc định `user`; khi tự đăng ký luôn ép cứng `user` phía server |
| `trang_thai` | ENUM('active','locked') | `locked` = admin khoá thủ công |
| `created_at`, `updated_at` | DATETIME | Tự động |

**`rooms`** — phòng thực hành: `ma_phong` (UNIQUE, vd `TH01`), `ten_phong`, `vi_tri`, `suc_chua` (CHECK > 0), `trang_thai` ENUM('available','maintenance','closed').

**`equipment_types`** — loại thiết bị: `ten_loai` (UNIQUE), `mo_ta`.

**`equipment`** — thiết bị: `ma_thiet_bi` (UNIQUE), `ten_thiet_bi`, `type_id` (FK → `equipment_types`), `room_id` (FK → `rooms`, NULL = thiết bị lưu động cho mượn mang đi), `co_the_muon` (0/1), `trang_thai` ENUM('active','broken','maintenance','borrowed'), `ngay_mua`.

**`bookings`** — yêu cầu đặt phòng / mượn thiết bị (hợp nhất 1 bảng): `loai_yeu_cau` ENUM('room','equipment') quyết định `room_id` hay `equipment_id` được dùng (trường còn lại NULL), `thoi_gian_bat_dau` / `thoi_gian_ket_thuc` (CHECK kết thúc > bắt đầu), `muc_dich`, `trang_thai` ENUM('pending','approved','rejected','cancelled'), `approved_by` (FK → `users`), `ly_do_tu_choi` (bắt buộc khi từ chối).

**`reports`** — báo hỏng: `equipment_id` (FK), `reported_by` (FK → `users`), `mo_ta_su_co`, `muc_do` ENUM('low','medium','high'), `trang_thai` ENUM('new','processing','resolved','cancelled').

**`maintenance_logs`** — lịch sử bảo trì: `equipment_id` (FK), `report_id` (FK → `reports`, tuỳ chọn), `performed_by` (FK → `users`, chỉ `lab_staff`/`admin`), `noi_dung_xu_ly`, `ngay_bat_dau`/`ngay_ket_thuc` (CHECK kết thúc ≥ bắt đầu), `ket_qua` ENUM('fixed','replaced','pending','unrepairable'), `chi_phi`.

**`login_attempts`** — chống brute-force đăng nhập: `email`, `ip_address` (IPv4/IPv6), `thanh_cong` (0/1), `created_at`. `auth/login.php` đếm số lần đăng nhập sai gần đây theo email/IP trong bảng này để tạm khoá đăng nhập, đúng nghiệp vụ mục 5.1 của `SOBOHETHONG.md`.

### Sơ đồ quan hệ (rút gọn)

```
users (1) ──< bookings (loai_yeu_cau='room')  >── (1) rooms
users (1) ──< bookings (loai_yeu_cau='equipment') >── (1) equipment ──> (1) equipment_types
users (1) ──< reports >── (1) equipment
equipment (1) ──< maintenance_logs >── (0..1) reports
users (1) ──< login_attempts  [theo email/ip, không có khoá ngoại cứng]
```

### Dữ liệu mẫu có sẵn

Script tạo sẵn 5 tài khoản mẫu (1 admin, 2 lab_staff, 2 user), 5 phòng, 5 loại thiết bị, 8 thiết bị, 4 booking, 2 báo hỏng, 2 bản ghi bảo trì. **Mật khẩu của cả 5 tài khoản mẫu: `Matkhau123`**

| Email | Vai trò |
|---|---|
| mai.admin@nhom4.edu.vn | admin |
| ky.labstaff@nhom4.edu.vn | lab_staff |
| hieu.labstaff@nhom4.edu.vn | lab_staff |
| phan.sv@nhom4.edu.vn | user |
| trung.gv@nhom4.edu.vn | user |

---

## Yêu cầu môi trường

- PHP >= 8.0 (có extension `pdo_mysql`)
- MySQL >= 8.0 hoặc MariaDB >= 10.4
- Web server: XAMPP / Laragon / WAMP (hoặc PHP built-in server để chạy nhanh)

## Cấu trúc thư mục

```
LapTrinhWebPHP-Nhom4/
├── about.php
├── index.php
├── README.md
├── SOBOHETHONG.md                # Tài liệu thiết kế thống nhất: CSDL, wireframe, nghiệp vụ, route
│
├── config/
│   ├── database.php              # Kết nối PDO tới MySQL, đọc cấu hình từ biến môi trường
│   └── session.php                # Khởi tạo session an toàn (cookie HttpOnly/SameSite, idle timeout, chống fixation)
│
├── database/
│   └── database.sql               # Script tạo database + 8 bảng + dữ liệu mẫu
│
├── includes/
│   ├── app_head.php / app_foot.php   # Layout khung trang dùng chung (bản đầy đủ)
│   ├── header.php / footer.php       # Layout khung trang (bản rút gọn)
│   ├── navbar.php                    # Thanh điều hướng theo vai trò đăng nhập
│   ├── auth_check.php                # require_login(), require_role(), redirect_if_logged_in()
│   ├── csrf.php                      # Sinh/kiểm tra CSRF token cho mọi form POST
│   ├── flash.php                     # Thông báo flash theo mẫu Post/Redirect/Get
│   ├── icons.php                     # Icon dùng chung trong giao diện
│   └── coming_soon.php               # Khung trang cho chức năng chưa hoàn thiện
│
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── images/
│
├── auth/
│   ├── login.php                  # Đăng nhập (khoá tạm sau nhiều lần sai, dựa vào bảng login_attempts)
│   ├── register.php               # Đăng ký (ép cứng vai_tro='user' phía server)
│   └── logout.php
│
├── dashboard/
│   └── index.php                  # Thống kê tổng quan — admin, lab_staff
│
├── rooms/                         # CRUD phòng + lịch sử dụng theo phòng (admin quản lý)
│   ├── list.php  ├── add.php  ├── edit.php  ├── delete.php  └── calendar.php
│
├── equipment_types/
│   └── list.php                   # Danh mục loại thiết bị
│
├── equipment/                     # CRUD thiết bị + bàn giao thiết bị mượn
│   ├── list.php  ├── form.php  ├── add.php  ├── edit.php  ├── delete.php  └── handover.php
│
├── bookings/                      # Đặt phòng / mượn thiết bị (form dùng chung) + duyệt yêu cầu
│   ├── form.php  ├── store.php  ├── pending.php  ├── approve.php  ├── reject.php
│   ├── manage.php  ├── my_requests.php  ├── history.php  └── cancel.php
│
├── reports/                       # Báo hỏng thiết bị
│   ├── index.php  ├── form.php  └── store.php
│
├── maintenance/                   # Cập nhật & lịch sử bảo trì (lab_staff/admin)
│   ├── update.php  └── history.php
│
├── users/                         # Quản lý tài khoản (admin)
│   ├── list.php  ├── add.php  ├── edit.php  └── delete.php
│
├── settings/
│   └── index.php                  # Cài đặt cá nhân
│
├── api/
│   └── check_availability.php     # Endpoint JSON kiểm tra phòng/thiết bị còn trống theo khung giờ
│
└── test/
    └── checklist.md               # Checklist kiểm thử thủ công (bảo mật, phân quyền, nghiệp vụ)
```

---

## Hướng dẫn cài đặt và chạy

### 1. Lấy source code

```bash
git clone https://github.com/maidz001/LapTrinhWebPHP-Nhom4.git
cd LapTrinhWebPHP-Nhom4
```

### 2. Chạy script tạo cơ sở dữ liệu (`database/database.sql`)

Script này tự **DROP** và **CREATE** lại database `quanly_phongthuchanh` từ đầu, tạo toàn bộ 8 bảng và nạp sẵn dữ liệu mẫu — chỉ cần chạy 1 lệnh duy nhất, không cần tạo database thủ công trước.

**Cách 1 — dòng lệnh (khuyên dùng, tránh lỗi copy/paste thiếu câu lệnh):**
```bash
mysql -u root -p < database/database.sql
```
Nhập mật khẩu root MySQL khi được hỏi (nếu root không có mật khẩu, bỏ trống và Enter).

**Cách 2 — qua phpMyAdmin (XAMPP/Laragon):**
1. Mở `http://localhost/phpmyadmin`
2. Chọn tab **Import** (không cần tạo database trước, script tự tạo)
3. Chọn file `database/database.sql`, bấm **Go**

**Cách 3 — MySQL Workbench / HeidiSQL:** mở file `database/database.sql`, bấm **Ctrl+A** để chọn toàn bộ nội dung rồi **Execute** — không chỉ chạy phần bôi đen, vì script gồm nhiều câu lệnh nối tiếp nhau (tạo bảng, ràng buộc khoá ngoại, rồi mới insert dữ liệu).

**Kiểm tra sau khi import:**
```sql
USE quanly_phongthuchanh;
SHOW TABLES;              -- phải thấy đủ 8 bảng
SELECT COUNT(*) FROM users; -- phải ra 5
```

> Nếu cần import lại từ đầu (ví dụ dữ liệu test bị lỗi), cứ chạy lại đúng lệnh ở Cách 1 — script tự `DROP DATABASE IF EXISTS` nên không bị lỗi trùng.

### 3. Cấu hình kết nối CSDL

Mở [`config/database.php`](config/database.php) và kiểm tra 4 giá trị mặc định có khớp với MySQL trên máy bạn không:

```php
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'quanly_phongthuchanh';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '12345678';
```

Có 2 cách sửa nếu không khớp:
- **Sửa trực tiếp** 4 dòng trên trong file (đơn giản nhất khi chạy local một mình).
- **Dùng biến môi trường** `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` (khuyên dùng khi nhiều người cùng chạy chung 1 máy chủ, tránh commit nhầm mật khẩu thật lên Git).

### 4. Chạy web server

**Với XAMPP/Laragon/WAMP:**
1. Copy toàn bộ thư mục project vào `htdocs` (XAMPP) hoặc `www` (Laragon), ví dụ: `C:/xampp/htdocs/LapTrinhWebPHP-Nhom4`
2. Khởi động **Apache** và **MySQL** trong control panel
3. Truy cập: `http://localhost/LapTrinhWebPHP-Nhom4/index.php`

**Hoặc dùng PHP built-in server (nhanh, không cần cài Apache):**
```bash
php -S localhost:8000
```
rồi truy cập `http://localhost:8000/index.php`

### 5. Đăng nhập thử

Dùng 1 trong 5 tài khoản mẫu ở bảng "Dữ liệu mẫu có sẵn" phía trên, mật khẩu chung là `Matkhau123`. Hoặc vào `/auth/register.php` để tạo tài khoản mới (mặc định vai trò `user`).

---

## Bảo mật đã áp dụng

- Toàn bộ truy vấn dùng **PDO prepared statement** (`PDO::ATTR_EMULATE_PREPARES => false`) — chống SQL Injection.
- Mật khẩu lưu bằng **`password_hash()` (bcrypt)**, xác thực bằng `password_verify()`, không bao giờ lưu/log plain text.
- **CSRF token** bắt buộc cho mọi form POST (`includes/csrf.php`).
- Mọi giá trị hiển thị lại ra HTML đều qua `htmlspecialchars()` — chống XSS.
- Session cookie **HttpOnly + SameSite=Lax**, tự **regenerate ID** định kỳ, **idle timeout** 30 phút (`config/session.php`).
- Đăng nhập sai nhiều lần liên tiếp → tạm khoá tài khoản (dựa vào bảng `login_attempts`), thông báo lỗi dùng chung 1 câu để không lộ email có tồn tại hay không.
- Vai trò khi tự đăng ký luôn ép cứng `'user'` phía server, không tin giá trị gửi từ client.
- Phân quyền kiểm tra lại ở **server** cho mọi route (`includes/auth_check.php`), không chỉ ẩn nút ở giao diện.

Chi tiết đầy đủ từng quy tắc validation/nghiệp vụ theo từng bảng: xem mục 5 của `SOBOHETHONG.md`. Checklist kiểm thử bảo mật/phân quyền: xem `test/checklist.md`.