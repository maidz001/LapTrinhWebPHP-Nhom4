# TÀI LIỆU PHÂN TÍCH & THIẾT KẾ CHI TIẾT
## Hệ thống Quản lý Phòng thực hành & Thiết bị — Nhóm 4

| | |
|---|---|
| **Học phần** | Lập trình Web |
| **Nhóm thực hiện** | Nhóm 4 |
| **Phạm vi tài liệu** | (1) Rà soát & thống nhất form chính · (2) Wireframe màn hình quan trọng · (3) Quy tắc validation & nghiệp vụ · (4) Danh sách route dự kiến |
| **Trạng thái** | Bản thiết kế thống nhất trước khi triển khai code (chốt trước khi các thành viên code tiếp) |

> Tài liệu này được xây dựng dựa trên việc rà soát toàn bộ mã nguồn hiện có của kho `LapTrinhWebPHP-Nhom4` (cấu trúc thư mục, các file đã tạo, `README.md`, `test/checklist.md`) và bảng phân công thành viên. Tại thời điểm rà soát, phần lớn các file chức năng (rooms, equipment, bookings, reports, maintenance, auth, dashboard...) mới chỉ là **file khung rỗng** (`<?php`), chưa có logic — đây chính là lý do bắt buộc phải thống nhất thiết kế **trước** khi cả 5 thành viên code song song, tránh làm sai lệch hướng và phải sửa lại nhiều lần.

---

## MỤC LỤC

1. [Rà soát hiện trạng & các vấn đề cần thống nhất](#1-rà-soát-hiện-trạng--các-vấn-đề-cần-thống-nhất)
2. [Mô hình dữ liệu thống nhất](#2-mô-hình-dữ-liệu-thống-nhất)
3. [Danh sách form chính đã rà soát & thống nhất](#3-danh-sách-form-chính-đã-rà-soát--thống-nhất)
4. [Wireframe các màn hình / form quan trọng](#4-wireframe-các-màn-hình--form-quan-trọng)
5. [Quy tắc validation & nghiệp vụ theo từng trường dữ liệu](#5-quy-tắc-validation--nghiệp-vụ-theo-từng-trường-dữ-liệu)
6. [Danh sách route dự kiến](#6-danh-sách-route-dự-kiến)
7. [Ma trận phân quyền theo vai trò](#7-ma-trận-phân-quyền-theo-vai-trò)
8. [Khuyến nghị & việc cần làm tiếp theo](#8-khuyến-nghị--việc-cần-làm-tiếp-theo)

---

## 1. RÀ SOÁT HIỆN TRẠNG & CÁC VẤN ĐỀ CẦN THỐNG NHẤT

### 1.1. Đối chiếu cấu trúc thực tế trên đĩa với README

| Hạng mục | README mô tả | Thực tế trong mã nguồn | Kết luận rà soát |
|---|---|---|---|
| `database/database.sql` | Có, kèm dữ liệu mẫu | **Không tồn tại** trong repo (không có ở bất kỳ commit nào) | ⚠️ Chưa từng được tạo/commit. Mục 2 của tài liệu này chốt lại schema chính thức để bạn phụ trách CSDL tạo file `database/database.sql` theo đúng thiết kế thống nhất. |
| `equipment_types/` (CRUD loại thiết bị) | Có nhắc tới, đã "hoàn thành" | **Không tồn tại thư mục** trong repo | ⚠️ Module bị thiếu so với mô tả. Cần bổ sung 4 file `list.php / add.php / edit.php / delete.php` theo đúng khuôn mẫu của `rooms/`. |
| `config/database.php`, `includes/header.php`, `includes/footer.php`, `index.php` | Đã có kết nối CSDL, layout dùng chung | File **rỗng** (0 byte) | ⚠️ Đây là các file nền tảng dùng chung cho *toàn bộ* hệ thống — phải hoàn thiện **đầu tiên**, trước khi bất kỳ module nào khác chạy được. |
| Toàn bộ `auth/`, `bookings/`, `equipment/`, `maintenance/`, `reports/`, `users/`, `dashboard/`, `api/` | — | Toàn bộ chỉ là file khung `<?php` (7 byte), chưa có logic | Đúng như README đã ghi nhận "đang thực hiện" — đây là lý do tài liệu này cần chốt thiết kế trước khi các bạn viết code, để không phải sửa đi sửa lại. |
| `README.md` | — | Có dấu vết merge conflict còn sót lại (`<<<<<<< HEAD`, `=======`), nội dung bị lặp 2 lần | ⚠️ Đã dọn dẹp và viết lại hoàn toàn trong lần cập nhật này (xem file `README.md` ở thư mục gốc). |

### 1.2. Vấn đề thiết kế cần thống nhất giữa các module (quan trọng nhất)

**Vấn đề #1 — "Đặt phòng" và "Mượn thiết bị" đang được thiết kế như hai luồng nghiệp vụ tách biệt, trong khi bản chất là một.**

Trong cấu trúc hiện tại có `bookings/form.php` (đặt phòng) và `equipment/borrow.php` (mượn thiết bị) là hai file riêng, do hai người phụ trách khác nhau (Triệu Văn Phấn/Nguyễn Kỳ và Đặng Quang Trung). Nếu để tách rời, hệ thống sẽ phải xây **hai lần** các màn hình: gửi yêu cầu, danh sách chờ duyệt, duyệt/từ chối, hủy yêu cầu, lịch sử — trùng lặp code, trùng lặp bảng dữ liệu, và cán bộ lab phải vào 2 nơi khác nhau để duyệt yêu cầu, dễ sai sót.

**Quyết định thống nhất:** Gộp thành **một luồng nghiệp vụ "Yêu cầu sử dụng tài nguyên" (Request)** dùng chung 1 bảng `bookings` với cột `loai_yeu_cau` (`room` | `equipment`), một quy trình duyệt/từ chối/hủy duy nhất (`bookings/pending.php`, `approve.php`, `reject.php`, `cancel.php`, `my_requests.php`) áp dụng cho cả hai loại. `equipment/borrow.php` được giữ lại **chỉ như một điểm vào nhanh** (UI rút gọn dành riêng cho ngữ cảnh mượn thiết bị ngay tại trang danh sách thiết bị) nhưng **submit về cùng** `bookings/store.php` với `loai_yeu_cau = equipment`. Chi tiết tại mục 2 và mục 3.

**Vấn đề #2 — Thiếu màn hình quản trị báo hỏng cho cán bộ lab.**

`reports/` hiện chỉ có `form.php` (người dùng gửi báo hỏng) và `store.php` (lưu). Không có nơi để cán bộ lab **xem danh sách báo hỏng** và cập nhật trạng thái xử lý trước khi tạo `maintenance_logs`. → Bổ sung `reports/list.php` (xem, lọc theo trạng thái) và `reports/update_status.php`.

**Vấn đề #3 — Chuẩn hoá mẫu đặt tên route giữa 2 nhóm module.**

- Nhóm **dữ liệu chủ (master data)**: `rooms`, `equipment_types`, `equipment`, `users` → theo mẫu **`list / add / edit / delete`** (đã nhất quán, giữ nguyên).
- Nhóm **quy trình giao dịch (transaction/workflow)**: `bookings`, `reports`, `maintenance` → theo mẫu **`form (hiển thị) / store (lưu) / các hành động trạng thái (approve, reject, cancel, update_status)`** (đã nhất quán, giữ nguyên).

Hai mẫu trên **không mâu thuẫn** — đây là 2 loại chức năng khác bản chất (CRUD dữ liệu tĩnh vs. quy trình có trạng thái), nên cố tình thiết kế khác nhau là hợp lý và cần **giữ nguyên quy ước này xuyên suốt dự án**, không trộn lẫn (ví dụ không thêm `bookings/edit.php` — sửa một yêu cầu đã tồn tại là nghiệp vụ không hợp lệ, chỉ có huỷ rồi tạo lại).

**Vấn đề #4 — Ràng buộc xoá dữ liệu tham chiếu (mồ côi dữ liệu).**

`test/checklist.md` đã đặt câu hỏi "Xóa phòng đang có thiết bị/booking liên kết" nhưng chưa có quy tắc chính thức. → Chốt tại mục 5: áp dụng **xoá mềm (soft delete = đổi trạng thái `ngung_hoat_dong`)** cho `rooms`, `equipment`, `users`; **chặn xoá cứng** nếu còn dữ liệu tham chiếu (booking đang `pending`/`approved` trong tương lai, thiết bị đang gắn với phòng...).

**Vấn đề #5 — Giao diện dùng chung chưa hoàn thiện.**

`includes/header.php`, `footer.php` đang rỗng trong khi `navbar.php` cũng rỗng nhưng đã được các module khác `require`. Đây là **phụ thuộc chặn (blocking dependency)**: không module nào hiển thị đúng nếu 3 file này chưa xong. → Ưu tiên #1 tuyệt đối trong kế hoạch (mục 8).

---

## 2. MÔ HÌNH DỮ LIỆU THỐNG NHẤT

Sơ đồ quan hệ (dạng văn bản):

```
users (1) ──────< bookings >──────── (1) rooms
  │                  │  (loai_yeu_cau = room | equipment)
  │                  └──────────────── (1) equipment
  │
  ├────< reports  >──── (1) equipment
  │           │
  │           └────< maintenance_logs >──── (1) equipment
  │
  └────< maintenance_logs (performed_by) >

equipment_types (1) ──────< equipment
rooms (1) ──────< equipment   (thiết bị gắn cố định tại 1 phòng — có thể NULL nếu là thiết bị lưu động cho mượn)
```

### 2.1. Bảng `users`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | INT, AI | PK |
| `ho_ten` | VARCHAR(100) | NOT NULL |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE |
| `mat_khau` | VARCHAR(255) | NOT NULL (lưu hash, `password_hash()`) |
| `so_dien_thoai` | VARCHAR(15) | NULL |
| `vai_tro` | ENUM('user','lab_staff','admin') | NOT NULL, mặc định `'user'` |
| `trang_thai` | ENUM('active','locked') | NOT NULL, mặc định `'active'` |
| `created_at` | DATETIME | mặc định `CURRENT_TIMESTAMP` |
| `updated_at` | DATETIME | tự cập nhật |

### 2.2. Bảng `rooms`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | INT, AI | PK |
| `ma_phong` | VARCHAR(20) | NOT NULL, UNIQUE (VD: `TH01`) |
| `ten_phong` | VARCHAR(100) | NOT NULL |
| `vi_tri` | VARCHAR(150) | NOT NULL (toà nhà, tầng) |
| `suc_chua` | INT | NOT NULL, > 0 |
| `trang_thai` | ENUM('available','maintenance','closed') | NOT NULL, mặc định `'available'` |
| `mo_ta` | TEXT | NULL |
| `created_at` / `updated_at` | DATETIME | |

### 2.3. Bảng `equipment_types`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | INT, AI | PK |
| `ten_loai` | VARCHAR(100) | NOT NULL, UNIQUE |
| `mo_ta` | TEXT | NULL |

### 2.4. Bảng `equipment`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | INT, AI | PK |
| `ma_thiet_bi` | VARCHAR(30) | NOT NULL, UNIQUE |
| `ten_thiet_bi` | VARCHAR(150) | NOT NULL |
| `type_id` | INT | FK → `equipment_types.id`, NOT NULL |
| `room_id` | INT | FK → `rooms.id`, NULL (NULL = thiết bị lưu động, có thể cho mượn mang đi) |
| `co_the_muon` | BOOLEAN | NOT NULL, mặc định `0` (đánh dấu thiết bị dùng cho luồng "mượn thiết bị") |
| `trang_thai` | ENUM('active','broken','maintenance','borrowed') | NOT NULL, mặc định `'active'` |
| `ngay_mua` | DATE | NULL |
| `mo_ta` | TEXT | NULL |
| `created_at` / `updated_at` | DATETIME | |

### 2.5. Bảng `bookings` (Yêu cầu sử dụng tài nguyên — hợp nhất Đặt phòng + Mượn thiết bị)

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | INT, AI | PK |
| `user_id` | INT | FK → `users.id`, NOT NULL |
| `loai_yeu_cau` | ENUM('room','equipment') | NOT NULL |
| `room_id` | INT | FK → `rooms.id`, NULL — **bắt buộc khi `loai_yeu_cau = room`** |
| `equipment_id` | INT | FK → `equipment.id`, NULL — **bắt buộc khi `loai_yeu_cau = equipment`** |
| `thoi_gian_bat_dau` | DATETIME | NOT NULL |
| `thoi_gian_ket_thuc` | DATETIME | NOT NULL |
| `muc_dich` | VARCHAR(255) | NOT NULL |
| `trang_thai` | ENUM('pending','approved','rejected','cancelled') | NOT NULL, mặc định `'pending'` |
| `approved_by` | INT | FK → `users.id`, NULL |
| `approved_at` | DATETIME | NULL |
| `ly_do_tu_choi` | VARCHAR(255) | NULL |
| `created_at` / `updated_at` | DATETIME | |

### 2.6. Bảng `reports` (Báo hỏng thiết bị)

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | INT, AI | PK |
| `equipment_id` | INT | FK → `equipment.id`, NOT NULL |
| `reported_by` | INT | FK → `users.id`, NOT NULL |
| `mo_ta_su_co` | TEXT | NOT NULL |
| `muc_do` | ENUM('low','medium','high') | NOT NULL, mặc định `'medium'` |
| `trang_thai` | ENUM('new','processing','resolved','cancelled') | NOT NULL, mặc định `'new'` |
| `created_at` / `updated_at` | DATETIME | |

### 2.7. Bảng `maintenance_logs` (Lịch sử bảo trì)

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | INT, AI | PK |
| `equipment_id` | INT | FK → `equipment.id`, NOT NULL |
| `report_id` | INT | FK → `reports.id`, NULL (liên kết báo hỏng gốc nếu có) |
| `performed_by` | INT | FK → `users.id`, NOT NULL (chỉ `lab_staff`/`admin`) |
| `noi_dung_xu_ly` | TEXT | NOT NULL |
| `ngay_bat_dau` | DATE | NOT NULL |
| `ngay_ket_thuc` | DATE | NULL |
| `ket_qua` | ENUM('fixed','replaced','pending','unrepairable') | NOT NULL, mặc định `'pending'` |
| `chi_phi` | DECIMAL(12,2) | NULL |
| `created_at` | DATETIME | |

---

## 3. DANH SÁCH FORM CHÍNH ĐÃ RÀ SOÁT & THỐNG NHẤT

Quy ước chung áp dụng cho **mọi** form trong hệ thống (để 5 thành viên code đồng bộ):

- Method **GET** để hiển thị form, method **POST** để submit (không dùng GET cho hành động ghi dữ liệu).
- Mọi form POST đều có **CSRF token ẩn** (`<input type="hidden" name="csrf_token">`), kiểm tra tại file xử lý.
- Lỗi validate hiển thị **ngay trên từng trường** (inline) + giữ lại dữ liệu người dùng đã nhập (không bắt gõ lại).
- Thông báo thành công dùng **flash message** qua `$_SESSION['flash']` rồi `redirect` (mẫu Post/Redirect/Get — tránh resubmit khi F5).
- Các nút hành động nguy hiểm (xoá, từ chối) đều có **xác nhận** (`confirm()` JS hoặc modal).
- Đặt tên trường input **tiếng Việt không dấu, snake_case**, khớp 1-1 với tên cột CSDL ở mục 2 (VD: `ten_phong`, `suc_chua`) để tránh nhầm lẫn khi 5 người code song song.

| # | Tên form | File hiển thị | File xử lý | Phụ trách | Vai trò được dùng |
|---|---|---|---|---|---|
| 1 | Đăng nhập | `auth/login.php` | `auth/login.php` (tự xử lý POST) | Mai | Khách |
| 2 | Đăng ký | `auth/register.php` | `auth/register.php` | Mai | Khách |
| 3 | Danh sách phòng | `rooms/list.php` | — | Trung | Tất cả (hiển thị khác nhau theo quyền) |
| 4 | Thêm phòng | `rooms/add.php` | `rooms/add.php` | Trung | admin |
| 5 | Sửa phòng | `rooms/edit.php` | `rooms/edit.php` | Trung | admin |
| 6 | Lịch phòng (calendar) | `rooms/calendar.php` | — (đọc qua `api/check_availability.php`) | Trung | Tất cả |
| 7 | Danh sách loại thiết bị *(mới bổ sung)* | `equipment_types/list.php` | — | Trung | admin |
| 8 | Thêm / Sửa loại thiết bị *(mới bổ sung)* | `equipment_types/add.php`, `edit.php` | cùng file | Trung | admin |
| 9 | Danh sách thiết bị | `equipment/list.php` | — | Trung | Tất cả |
| 10 | Thêm thiết bị | `equipment/add.php` | `equipment/add.php` | Trung | admin |
| 11 | Sửa thiết bị | `equipment/edit.php` | `equipment/edit.php` | Trung | admin, lab_staff (chỉ trạng thái) |
| 12 | **Form yêu cầu sử dụng** (dùng chung Đặt phòng + Mượn thiết bị) | `bookings/form.php?loai=room` hoặc `?loai=equipment` | `bookings/store.php` | Phấn / Kỳ | user, lab_staff |
| 13 | Lối tắt Mượn thiết bị (UI rút gọn, submit vào form #12) | `equipment/borrow.php` (redirect có sẵn `equipment_id`) → `bookings/form.php` | `bookings/store.php` | Trung + Phấn | user, lab_staff |
| 14 | Yêu cầu chờ duyệt | `bookings/pending.php` | `bookings/approve.php`, `bookings/reject.php` | Kỳ | lab_staff, admin |
| 15 | Yêu cầu của tôi | `bookings/my_requests.php` | `bookings/cancel.php` | Phấn | user, lab_staff, admin |
| 16 | Báo hỏng thiết bị | `reports/form.php` | `reports/store.php` | Hiếu | user, lab_staff |
| 17 | Danh sách báo hỏng *(mới bổ sung)* | `reports/list.php` | `reports/update_status.php` | Hiếu | lab_staff, admin |
| 18 | Cập nhật bảo trì | `maintenance/update.php` | `maintenance/update.php` | Hiếu | lab_staff, admin |
| 19 | Lịch sử bảo trì | `maintenance/history.php` | — | Hiếu | Tất cả (lọc theo thiết bị) |
| 20 | Danh sách người dùng | `users/list.php` | — | Mai | admin |
| 21 | Thêm người dùng | `users/add.php` | `users/add.php` | Mai | admin |
| 22 | Sửa người dùng / phân quyền | `users/edit.php` | `users/edit.php` | Mai | admin |
| 23 | Dashboard thống kê | `dashboard/index.php` | — | Mai | admin, lab_staff (thu gọn) |

---

## 4. WIREFRAME CÁC MÀN HÌNH / FORM QUAN TRỌNG

> Wireframe mức thấp (low-fidelity), thể hiện bố cục & thành phần, chưa đi vào màu sắc/CSS. Mọi màn hình (trừ đăng nhập/đăng ký) đều dùng chung khung `includes/header.php` (thẻ `<head>`, CSS) + `includes/navbar.php` (menu điều hướng theo vai trò) + `includes/footer.php`.

### 4.1. Đăng nhập — `auth/login.php`

```
┌──────────────────────────────────────────┐
│               [ LOGO HỆ THỐNG ]           │
│      Quản lý Phòng thực hành & Thiết bị   │
├──────────────────────────────────────────┤
│                                            │
│   Email       [____________________]      │
│   Mật khẩu    [____________________] 👁    │
│                                            │
│   ( ! ) Thông báo lỗi nếu sai (nếu có)     │
│                                            │
│              [   ĐĂNG NHẬP   ]             │
│                                            │
│   Chưa có tài khoản? -> Đăng ký            │
└──────────────────────────────────────────┘
```

### 4.2. Đăng ký — `auth/register.php`

```
┌──────────────────────────────────────────┐
│                ĐĂNG KÝ TÀI KHOẢN          │
├──────────────────────────────────────────┤
│  Họ tên          [_______________________]│
│  Email           [_______________________]│
│  Số điện thoại   [_______________________]│
│  Mật khẩu        [_______________________]│
│  Xác nhận MK     [_______________________]│
│  (Vai trò mặc định: user — không cho chọn │
│   admin/lab_staff khi tự đăng ký)          │
│                                            │
│              [   ĐĂNG KÝ   ]               │
│   Đã có tài khoản? -> Đăng nhập            │
└──────────────────────────────────────────┘
```

### 4.3. Dashboard — `dashboard/index.php`

```
┌──────────────────────────────────────────────────────┐
│ [Navbar: Trang chủ | Phòng | Thiết bị | Yêu cầu | ...]│
├──────────────────────────────────────────────────────┤
│  DASHBOARD TỔNG QUAN                                  │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌────────┐│
│  │ Thiết bị  │ │ Thiết bị  │ │ Thiết bị  │ │ Yêu cầu││
│  │ hoạt động │ │   hỏng    │ │ bảo trì   │ │chờ duyệt││
│  │    128    │ │    5      │ │    3      │ │   7    ││
│  └───────────┘ └───────────┘ └───────────┘ └────────┘│
│                                                        │
│  [ Biểu đồ cột: số lượng thiết bị theo loại ]         │
│  [ Bảng: 5 yêu cầu gần nhất | 5 báo hỏng gần nhất ]   │
└──────────────────────────────────────────────────────┘
```

### 4.4. Danh sách phòng — `rooms/list.php`

```
┌──────────────────────────────────────────────────────┐
│ Danh sách phòng thực hành          [+ Thêm phòng]     │
│ (nút "Thêm phòng" chỉ hiện với admin)                 │
├──────────────────────────────────────────────────────┤
│ Lọc: Trạng thái [Tất cả ▾]   Tìm kiếm [___________] 🔍│
├────┬─────────┬────────┬──────────┬────────┬──────────┤
│ Mã │ Tên phòng│ Vị trí │ Sức chứa │ Trạng thái │ Thao tác│
├────┼─────────┼────────┼──────────┼────────┼──────────┤
│TH01│ Phòng Lý│ A1-101 │   40     │ ● Sẵn sàng │[Xem lịch][Sửa][Xoá]│
│TH02│ Phòng Hoá│ A1-102 │   35     │ ● Bảo trì  │[Xem lịch][Sửa][Xoá]│
├────┴─────────┴────────┴──────────┴────────┴──────────┤
│                     « 1 2 3 »                          │
└──────────────────────────────────────────────────────┘
```

### 4.5. Thêm / Sửa phòng — `rooms/add.php`, `rooms/edit.php`

```
┌──────────────────────────────────────────┐
│  THÊM PHÒNG THỰC HÀNH                     │
├──────────────────────────────────────────┤
│  Mã phòng *      [____________]           │
│  Tên phòng *     [____________________]   │
│  Vị trí *        [____________________]   │
│  Sức chứa *      [______] (số nguyên > 0)  │
│  Trạng thái *    ( ) Sẵn sàng              │
│                  ( ) Bảo trì               │
│                  ( ) Ngừng sử dụng         │
│  Mô tả           [____________________]   │
│                  [____________________]   │
│                                            │
│         [ Lưu ]      [ Huỷ ]               │
└──────────────────────────────────────────┘
```

### 4.6. Lịch phòng — `rooms/calendar.php`

```
┌──────────────────────────────────────────────────────┐
│ Lịch sử dụng phòng: [Chọn phòng ▾]   [< Tuần trước][Tuần sau >]│
├──────┬──────┬──────┬──────┬──────┬──────┬──────┬─────┤
│ Giờ  │  T2  │  T3  │  T4  │  T5  │  T6  │  T7  │ CN  │
├──────┼──────┼──────┼──────┼──────┼──────┼──────┼─────┤
│ 7-9  │[Đã đặt]│      │      │[Đã đặt]│      │     │     │
│ 9-11 │      │[Đã đặt]│      │      │      │     │     │
│ ...  │      │      │      │      │      │     │     │
├──────┴──────┴──────┴──────┴──────┴──────┴──────┴─────┤
│  Click vào ô trống -> mở `bookings/form.php` với      │
│  phòng + khung giờ đã điền sẵn                        │
└──────────────────────────────────────────────────────┘
```

### 4.7. Danh sách thiết bị — `equipment/list.php`

```
┌──────────────────────────────────────────────────────┐
│ Danh sách thiết bị                  [+ Thêm thiết bị] │
├──────────────────────────────────────────────────────┤
│ Lọc: Loại [Tất cả▾] Phòng [Tất cả▾] Trạng thái[Tất cả▾]│
├─────┬───────────┬───────┬──────┬──────────┬──────────┤
│ Mã  │ Tên TB     │ Loại  │ Phòng│Trạng thái│ Thao tác  │
├─────┼───────────┼───────┼──────┼──────────┼──────────┤
│TB001│Máy chiếu   │AV     │TH01  │●Hoạt động│[Sửa][Báo hỏng][Mượn]│
│TB002│Kính hiển vi│Sinh   │TH02  │●Hỏng     │[Sửa][Xem lịch sử]   │
├─────┴───────────┴───────┴──────┴──────────┴──────────┤
│ Thống kê nhanh: 128 hoạt động · 5 hỏng · 3 bảo trì    │
└──────────────────────────────────────────────────────┘
```
*Nút **[Mượn]** chỉ hiện với thiết bị có `co_the_muon = true` và `trang_thai = active`; bấm vào sẽ mở `bookings/form.php?loai=equipment&equipment_id=...` (đây chính là màn hình `equipment/borrow.php` — xem mục 1.2 Vấn đề #1).*

### 4.8. Form yêu cầu sử dụng (Đặt phòng / Mượn thiết bị hợp nhất) — `bookings/form.php`

```
┌──────────────────────────────────────────┐
│  TẠO YÊU CẦU SỬ DỤNG                      │
├──────────────────────────────────────────┤
│  Loại yêu cầu *  ( ) Đặt phòng            │
│                  (•) Mượn thiết bị        │
│                                            │
│  -- Nếu chọn "Đặt phòng" --                │
│  Phòng *         [Chọn phòng ▾]           │
│                                            │
│  -- Nếu chọn "Mượn thiết bị" --            │
│  Thiết bị *      [Chọn thiết bị ▾]        │
│                                            │
│  Thời gian bắt đầu * [ dd/mm/yyyy hh:mm ] │
│  Thời gian kết thúc * [ dd/mm/yyyy hh:mm ]│
│  Mục đích sử dụng *  [__________________] │
│                                            │
│  ⓘ Trạng thái còn trống: (kiểm tra realtime│
│    qua AJAX -> api/check_availability.php)│
│                                            │
│         [ Gửi yêu cầu ]   [ Huỷ ]          │
└──────────────────────────────────────────┘
```

### 4.9. Yêu cầu chờ duyệt — `bookings/pending.php`

```
┌──────────────────────────────────────────────────────────┐
│ Yêu cầu chờ duyệt                                         │
├────┬────────┬──────┬──────────┬────────────┬─────────────┤
│ ID │Người gửi│ Loại │Tài nguyên│  Thời gian  │  Thao tác   │
├────┼────────┼──────┼──────────┼────────────┼─────────────┤
│ 12 │Nguyễn A│Phòng │  TH01    │08:00-10:00 │[Duyệt][Từ chối]│
│ 13 │Trần B  │TB    │Máy chiếu │13:00-15:00 │[Duyệt][Từ chối]│
├────┴────────┴──────┴──────────┴────────────┴─────────────┤
│ Bấm [Từ chối] -> hiện ô nhập lý do (bắt buộc) trước khi lưu│
└──────────────────────────────────────────────────────────┘
```

### 4.10. Yêu cầu của tôi — `bookings/my_requests.php`

```
┌──────────────────────────────────────────────────────┐
│ Yêu cầu của tôi              Lọc: [Tất cả trạng thái▾]│
├────┬──────┬──────────┬────────────┬─────────┬────────┤
│ ID │ Loại │Tài nguyên│  Thời gian  │Trạng thái│Thao tác│
├────┼──────┼──────────┼────────────┼─────────┼────────┤
│ 10 │Phòng │  TH03    │08:00-10:00 │●Đã duyệt │  —     │
│ 11 │TB    │Kính HV   │14:00-16:00 │●Chờ duyệt│[Huỷ]   │
├────┴──────┴──────────┴────────────┴─────────┴────────┤
│ (Nút [Huỷ] chỉ hiện khi trạng thái = pending VÀ        │
│  thời gian bắt đầu chưa tới)                           │
└──────────────────────────────────────────────────────┘
```

### 4.11. Báo hỏng thiết bị — `reports/form.php`

```
┌──────────────────────────────────────────┐
│  BÁO HỎNG THIẾT BỊ                        │
├──────────────────────────────────────────┤
│  Thiết bị *       [Chọn thiết bị ▾]       │
│  Mức độ *         ( ) Nhẹ (•) Vừa ( ) Nặng │
│  Mô tả sự cố *    [_______________________]│
│                   [_______________________]│
│                                            │
│              [ Gửi báo hỏng ]              │
└──────────────────────────────────────────┘
```

### 4.12. Danh sách báo hỏng & Cập nhật bảo trì — `reports/list.php` + `maintenance/update.php`

```
┌────────────────────────────────────────────────────────────┐
│ Danh sách báo hỏng          Lọc: [Trạng thái ▾]             │
├────┬──────────┬─────────┬───────────┬──────────┬───────────┤
│ ID │ Thiết bị │Người báo│ Mức độ    │Trạng thái│  Thao tác  │
├────┼──────────┼─────────┼───────────┼──────────┼───────────┤
│ 5  │Máy chiếu │Nguyễn A │ Vừa       │●Mới      │[Xử lý]     │
├────┴──────────┴─────────┴───────────┴──────────┴───────────┤
│ Bấm [Xử lý] -> mở `maintenance/update.php?report_id=5`:      │
│  ┌────────────────────────────────────────┐                 │
│  │ Nội dung xử lý * [______________________]│                │
│  │ Ngày bắt đầu *   [dd/mm/yyyy]            │                │
│  │ Ngày kết thúc    [dd/mm/yyyy] (để trống  │                │
│  │                   nếu đang xử lý)         │                │
│  │ Kết quả *   [Đã sửa/Thay mới/Đang xử lý/ │                │
│  │              Không sửa được ▾]           │                │
│  │ Chi phí          [___________] VNĐ       │                │
│  │            [ Lưu ]                        │                │
│  └────────────────────────────────────────┘                 │
└────────────────────────────────────────────────────────────┘
```

### 4.13. Lịch sử bảo trì — `maintenance/history.php`

```
┌──────────────────────────────────────────────────────┐
│ Lịch sử bảo trì   Lọc theo thiết bị: [Chọn thiết bị▾] │
├────┬──────────┬────────────┬──────────┬──────┬───────┤
│ ID │Thiết bị  │Người xử lý │Ngày BT   │Kết quả│Chi phí│
├────┼──────────┼────────────┼──────────┼──────┼───────┤
│ 1  │Máy chiếu │Cán bộ Lê C │12/03/2026│Đã sửa│150.000│
└────┴──────────┴────────────┴──────────┴──────┴───────┘
```

### 4.14. Quản lý người dùng — `users/list.php`, `users/add.php`, `users/edit.php`

```
┌──────────────────────────────────────────────────────┐
│ Quản lý người dùng                 [+ Thêm người dùng]│
├────┬─────────┬───────────────┬──────────┬────────────┤
│ ID │ Họ tên  │     Email     │ Vai trò  │  Thao tác   │
├────┼─────────┼───────────────┼──────────┼────────────┤
│ 1  │Nguyễn A │a@example.com  │ user     │[Sửa][Khoá]  │
│ 2  │Lê C     │c@example.com  │lab_staff │[Sửa][Khoá]  │
├────┴─────────┴───────────────┴──────────┴────────────┤
│ Form Sửa: Họ tên, SĐT, Vai trò (dropdown), Trạng thái  │
│ (Không cho admin tự đổi vai trò của chính mình)        │
└──────────────────────────────────────────────────────┘
```

---

## 5. QUY TẮC VALIDATION & NGHIỆP VỤ THEO TỪNG TRƯỜNG DỮ LIỆU

> Nguyên tắc chung: **validate cả 2 lớp** — HTML5 (`required`, `type`, `pattern`, `min/max`) cho trải nghiệm nhanh, và **PHP phía server** cho bảo mật (không tin dữ liệu client gửi lên). Mọi input hiển thị lại ra trang phải qua `htmlspecialchars()` để chống XSS (đã có trong `test/checklist.md`). Mọi truy vấn dùng **PDO prepared statement**, không nối chuỗi SQL trực tiếp.

### 5.1. `users` (Tài khoản)

| Trường | Validation |
|---|---|
| `ho_ten` | Bắt buộc; 2–100 ký tự; chỉ chữ cái/khoảng trắng (hỗ trợ tiếng Việt có dấu) |
| `email` | Bắt buộc; đúng định dạng email; **duy nhất** trong bảng (kiểm tra tồn tại trước khi insert); tối đa 150 ký tự |
| `mat_khau` | Bắt buộc; tối thiểu 8 ký tự, có ít nhất 1 chữ và 1 số; lưu bằng `password_hash(PASSWORD_BCRYPT)`, không bao giờ lưu plain text |
| `xac_nhan_mat_khau` (chỉ ở form, không lưu DB) | Phải khớp với `mat_khau` |
| `so_dien_thoai` | Không bắt buộc; nếu nhập: đúng 10 số, bắt đầu bằng 0 |
| `vai_tro` | Khi tự đăng ký: luôn ép cứng = `'user'` phía server (**không tin giá trị select gửi từ client**, đề phòng người dùng sửa HTML để tự cấp quyền admin); chỉ admin mới đổi được vai trò người khác qua `users/edit.php` |
| **Nghiệp vụ** | Đăng nhập sai quá 5 lần trong 15 phút → tạm khoá đăng nhập 15 phút. Thông báo lỗi đăng nhập dùng chung 1 câu ("Email hoặc mật khẩu không đúng") — **không tiết lộ** email có tồn tại hay không (đúng yêu cầu test bảo mật). Admin không thể tự hạ quyền/khoá chính tài khoản mình đang dùng. |

### 5.2. `rooms` (Phòng)

| Trường | Validation |
|---|---|
| `ma_phong` | Bắt buộc; duy nhất; 2–20 ký tự, chữ+số, không khoảng trắng |
| `ten_phong` | Bắt buộc; 3–100 ký tự |
| `vi_tri` | Bắt buộc; tối đa 150 ký tự |
| `suc_chua` | Bắt buộc; số nguyên; **> 0** và ≤ 500 (chặn nhập số âm/ký tự chữ) |
| `trang_thai` | Bắt buộc; chỉ nhận 1 trong 3 giá trị enum (whitelist phía server, không tin giá trị lạ) |
| **Nghiệp vụ** | Không cho xoá cứng phòng nếu còn `equipment.room_id` trỏ tới, hoặc còn `bookings` ở trạng thái `pending`/`approved` với `thoi_gian_ket_thuc` > hiện tại → chỉ cho chuyển `trang_thai = closed` (xoá mềm). Đổi `trang_thai` sang `maintenance`/`closed` khi đang có booking `approved` sắp diễn ra → cảnh báo xác nhận trước khi lưu. |

### 5.3. `equipment_types` (Loại thiết bị)

| Trường | Validation |
|---|---|
| `ten_loai` | Bắt buộc; duy nhất; 2–100 ký tự |
| **Nghiệp vụ** | Không xoá được loại thiết bị nếu còn `equipment.type_id` tham chiếu tới. |

### 5.4. `equipment` (Thiết bị)

| Trường | Validation |
|---|---|
| `ma_thiet_bi` | Bắt buộc; duy nhất; 2–30 ký tự |
| `ten_thiet_bi` | Bắt buộc; 2–150 ký tự |
| `type_id` | Bắt buộc; phải tồn tại trong `equipment_types` (kiểm tra FK hợp lệ, không chỉ tin ID gửi lên) |
| `room_id` | Không bắt buộc (thiết bị lưu động); nếu có, phải tồn tại trong `rooms` |
| `ngay_mua` | Không bắt buộc; nếu nhập, không được là ngày trong tương lai |
| `trang_thai` | Bắt buộc; whitelist enum |
| **Nghiệp vụ** | Thiết bị `trang_thai IN ('broken','maintenance')` → **ẩn nút Mượn/Đặt** và bị **chặn tạo booking mới** cho thiết bị đó (kiểm tra lại ở server khi `bookings/store.php`, không chỉ ẩn ở giao diện). Khi có `reports` mới ở mức `high` cho thiết bị → hệ thống tự đề xuất chuyển `trang_thai = broken`. Xoá thiết bị: chặn nếu còn `bookings` chưa kết thúc hoặc `reports` chưa `resolved`. |

### 5.5. `bookings` (Yêu cầu sử dụng — Đặt phòng / Mượn thiết bị)

| Trường | Validation |
|---|---|
| `loai_yeu_cau` | Bắt buộc; chỉ `room` hoặc `equipment` |
| `room_id` / `equipment_id` | Đúng 1 trong 2 trường phải có giá trị tương ứng với `loai_yeu_cau`, trường còn lại phải NULL (validate chéo phía server) |
| `thoi_gian_bat_dau` | Bắt buộc; phải **≥ thời điểm hiện tại** (không cho đặt lịch trong quá khứ) |
| `thoi_gian_ket_thuc` | Bắt buộc; **phải lớn hơn** `thoi_gian_bat_dau` (đúng ràng buộc README đã nêu); thời lượng tối đa 1 lần đặt ≤ 4 giờ (tránh chiếm phòng cả ngày) |
| `muc_dich` | Bắt buộc; 5–255 ký tự |
| **Nghiệp vụ cốt lõi #1 — Chống trùng lịch** | Không cho 2 booking cùng `room_id` (hoặc cùng `equipment_id`) ở trạng thái `approved` có khung giờ **giao nhau** (`NOT (kết_thúc_mới ≤ bắt_đầu_cũ OR bắt_đầu_mới ≥ kết_thúc_cũ)`). Kiểm tra tại `api/check_availability.php` (gợi ý AJAX khi người dùng chọn giờ) **và bắt buộc kiểm tra lại** tại `bookings/approve.php` (vì có thể có 2 yêu cầu `pending` trùng giờ, chỉ 1 được duyệt) |
| **Nghiệp vụ #2** | Tài nguyên (phòng/thiết bị) đang `maintenance`/`closed`/`broken` → không tạo được yêu cầu mới |
| **Nghiệp vụ #3** | Chỉ `lab_staff`/`admin` được `approve`/`reject`; **không được tự duyệt yêu cầu của chính mình** |
| **Nghiệp vụ #4** | `reject` bắt buộc phải nhập `ly_do_tu_choi` (không cho để trống) |
| **Nghiệp vụ #5** | `cancel` chỉ được thực hiện bởi chính người tạo yêu cầu (hoặc admin), chỉ khi `trang_thai = pending` **hoặc** (`trang_thai = approved` và `thoi_gian_bat_dau` chưa tới) |

### 5.6. `reports` (Báo hỏng)

| Trường | Validation |
|---|---|
| `equipment_id` | Bắt buộc; phải tồn tại |
| `mo_ta_su_co` | Bắt buộc; 10–1000 ký tự |
| `muc_do` | Bắt buộc; whitelist enum, mặc định `medium` nếu không chọn |
| **Nghiệp vụ** | Một thiết bị có báo hỏng đang ở trạng thái `new`/`processing` → không tạo thêm báo hỏng trùng (gợi ý người dùng xem báo hỏng hiện có) để tránh trùng lặp dữ liệu xử lý. |

### 5.7. `maintenance_logs` (Bảo trì)

| Trường | Validation |
|---|---|
| `noi_dung_xu_ly` | Bắt buộc; tối thiểu 10 ký tự |
| `ngay_bat_dau` | Bắt buộc; hợp lệ định dạng ngày |
| `ngay_ket_thuc` | Không bắt buộc; nếu có, phải **≥** `ngay_bat_dau` |
| `ket_qua` | Bắt buộc; whitelist enum |
| `chi_phi` | Không bắt buộc; nếu nhập, phải là số ≥ 0 |
| **Nghiệp vụ** | Chỉ `lab_staff`/`admin` được tạo/sửa. Khi `ket_qua = fixed` hoặc `replaced` → tự động cập nhật `equipment.trang_thai = active`. Khi `ket_qua = unrepairable` → gợi ý chuyển `equipment.trang_thai` sang trạng thái ngừng dùng (thêm giá trị `retired` nếu nhóm thống nhất mở rộng enum). Khi tạo bản ghi bảo trì gắn với `report_id` → tự cập nhật `reports.trang_thai = resolved` (hoặc `processing` nếu `ngay_ket_thuc` còn trống). |

---

## 6. DANH SÁCH ROUTE DỰ KIẾN

> Dự án dùng PHP thuần (không framework routing), nên "route" tương ứng trực tiếp với đường dẫn file vật lý dưới host, ví dụ `http://localhost/LapTrinhWebPHP-Nhom4/rooms/list.php`. Cột **Method thực tế** ghi nhận GET để hiển thị trang, POST để xử lý submit trong cùng file (theo mẫu PHP thuần phổ biến) hoặc file `store.php` riêng theo bảng mục 3.

### 6.1. Auth

| Route (file) | Method | Chức năng | Quyền truy cập |
|---|---|---|---|
| `/auth/login.php` | GET / POST | Hiển thị & xử lý đăng nhập | Khách (đã đăng nhập → redirect dashboard) |
| `/auth/register.php` | GET / POST | Hiển thị & xử lý đăng ký | Khách |
| `/auth/logout.php` | GET | Huỷ session, chuyển về login | Đã đăng nhập |

### 6.2. Dashboard

| Route | Method | Chức năng | Quyền |
|---|---|---|---|
| `/dashboard/index.php` | GET | Thống kê tổng quan | admin, lab_staff |
| `/index.php` | GET | Trang chủ công khai / điều hướng theo trạng thái đăng nhập | Tất cả |
| `/about.php` | GET | Giới thiệu nhóm & đề tài | Tất cả |

### 6.3. Rooms

| Route | Method | Chức năng | Quyền |
|---|---|---|---|
| `/rooms/list.php` | GET | Danh sách phòng, lọc/tìm kiếm | Tất cả |
| `/rooms/add.php` | GET / POST | Thêm phòng | admin |
| `/rooms/edit.php?id=` | GET / POST | Sửa phòng | admin |
| `/rooms/delete.php?id=` | POST | Xoá (mềm) phòng | admin |
| `/rooms/calendar.php?id=` | GET | Xem lịch sử dụng theo phòng | Tất cả |

### 6.4. Equipment Types *(bổ sung mới)*

| Route | Method | Chức năng | Quyền |
|---|---|---|---|
| `/equipment_types/list.php` | GET | Danh sách loại thiết bị | admin |
| `/equipment_types/add.php` | GET / POST | Thêm loại thiết bị | admin |
| `/equipment_types/edit.php?id=` | GET / POST | Sửa loại thiết bị | admin |
| `/equipment_types/delete.php?id=` | POST | Xoá loại thiết bị (nếu không còn thiết bị tham chiếu) | admin |

### 6.5. Equipment

| Route | Method | Chức năng | Quyền |
|---|---|---|---|
| `/equipment/list.php` | GET | Danh sách thiết bị, lọc theo loại/phòng/trạng thái | Tất cả |
| `/equipment/add.php` | GET / POST | Thêm thiết bị | admin |
| `/equipment/edit.php?id=` | GET / POST | Sửa thiết bị | admin (toàn quyền), lab_staff (chỉ đổi `trang_thai`) |
| `/equipment/delete.php?id=` | POST | Xoá (mềm) thiết bị | admin |
| `/equipment/borrow.php?id=` | GET | Lối tắt mượn thiết bị → redirect `bookings/form.php?loai=equipment&equipment_id=` | user, lab_staff |

### 6.6. Bookings (Yêu cầu sử dụng — hợp nhất)

| Route | Method | Chức năng | Quyền |
|---|---|---|---|
| `/bookings/form.php?loai=room\|equipment` | GET | Hiển thị form tạo yêu cầu | user, lab_staff, admin |
| `/bookings/store.php` | POST | Lưu yêu cầu mới, kiểm tra trùng lịch | user, lab_staff, admin |
| `/bookings/my_requests.php` | GET | Danh sách yêu cầu của bản thân | user, lab_staff, admin |
| `/bookings/pending.php` | GET | Danh sách yêu cầu chờ duyệt | lab_staff, admin |
| `/bookings/approve.php?id=` | POST | Duyệt yêu cầu (kiểm tra lại trùng lịch trước khi duyệt) | lab_staff, admin |
| `/bookings/reject.php?id=` | POST | Từ chối yêu cầu (bắt buộc lý do) | lab_staff, admin |
| `/bookings/cancel.php?id=` | POST | Huỷ yêu cầu | Chủ yêu cầu, admin |

### 6.7. Reports & Maintenance

| Route | Method | Chức năng | Quyền |
|---|---|---|---|
| `/reports/form.php` | GET | Form báo hỏng | user, lab_staff |
| `/reports/store.php` | POST | Lưu báo hỏng | user, lab_staff |
| `/reports/list.php` *(bổ sung mới)* | GET | Danh sách báo hỏng, lọc theo trạng thái | lab_staff, admin |
| `/reports/update_status.php` *(bổ sung mới)* | POST | Đổi trạng thái báo hỏng nhanh (không cần tạo log) | lab_staff, admin |
| `/maintenance/update.php?report_id=` | GET / POST | Tạo/cập nhật bản ghi xử lý bảo trì | lab_staff, admin |
| `/maintenance/history.php?equipment_id=` | GET | Lịch sử bảo trì (lọc theo thiết bị) | Tất cả |

### 6.8. Users (Quản trị)

| Route | Method | Chức năng | Quyền |
|---|---|---|---|
| `/users/list.php` | GET | Danh sách người dùng | admin |
| `/users/add.php` | GET / POST | Thêm người dùng (tạo bởi admin) | admin |
| `/users/edit.php?id=` | GET / POST | Sửa thông tin / phân quyền / khoá tài khoản | admin |
| `/users/delete.php?id=` | POST | Xoá (mềm — chuyển `trang_thai = locked`) | admin |

### 6.9. API (JSON)

| Route | Method | Chức năng | Quyền |
|---|---|---|---|
| `/api/check_availability.php?loai=&id=&bat_dau=&ket_thuc=` | GET | Trả JSON `{ "trong": true/false }` kiểm tra phòng/thiết bị còn trống trong khung giờ | user, lab_staff, admin (yêu cầu đã đăng nhập) |

---

## 7. MA TRẬN PHÂN QUYỀN THEO VAI TRÒ

| Chức năng | user (SV/GV) | lab_staff (Cán bộ lab) | admin |
|---|:---:|:---:|:---:|
| Xem danh sách/lịch phòng, thiết bị | ✅ | ✅ | ✅ |
| Tạo yêu cầu đặt phòng / mượn thiết bị | ✅ | ✅ | ✅ |
| Huỷ yêu cầu của chính mình | ✅ | ✅ | ✅ |
| Báo hỏng thiết bị | ✅ | ✅ | ✅ |
| Duyệt / từ chối yêu cầu | ❌ | ✅ | ✅ |
| Cập nhật trạng thái thiết bị, xử lý bảo trì | ❌ | ✅ | ✅ |
| Xem/danh sách báo hỏng toàn hệ thống | ❌ | ✅ | ✅ |
| CRUD Phòng / Loại thiết bị / Thiết bị | ❌ | ❌ | ✅ |
| Quản lý tài khoản, phân quyền | ❌ | ❌ | ✅ |
| Xem Dashboard | ❌ | ✅ (thu gọn) | ✅ (đầy đủ) |

**Cơ chế thực thi:** `includes/auth_check.php` cung cấp hàm dùng chung, ví dụ `require_login()` và `require_role(['admin'])`, được `require_once` ở **đầu mỗi file route** cần bảo vệ (trước khi in bất kỳ HTML nào, để có thể `header('Location: ...')` redirect hợp lệ). Không kiểm tra quyền chỉ ở giao diện (ẩn nút) — **luôn kiểm tra lại ở server** cho mọi route ghi dữ liệu, đúng như tình huống test "Sinh viên cố truy cập URL trang Admin trực tiếp" trong `test/checklist.md`.

---

## 8. KHUYẾN NGHỊ & VIỆC CẦN LÀM TIẾP THEO

Thứ tự ưu tiên bắt buộc (module sau phụ thuộc module trước):

1. **[Chặn toàn nhóm]** Hoàn thiện `config/database.php`, `includes/header.php`, `includes/footer.php`, `includes/navbar.php`, `includes/auth_check.php` theo đúng mô hình dữ liệu ở mục 2 — hiện đang rỗng, không module nào chạy được nếu thiếu.
2. Tạo `database/database.sql` chính thức đúng theo mục 2 (kèm dữ liệu mẫu: ≥ 3 phòng, ≥ 2 loại thiết bị, ≥ 5 thiết bị, 3 tài khoản mẫu 3 vai trò).
3. Hoàn thiện `auth/` (Mai) — vì mọi module khác đều cần đăng nhập trước.
4. Bổ sung `equipment_types/` (Trung) — thiếu so với README, `equipment/add.php` cần dropdown loại thiết bị nên phụ thuộc module này.
5. Hoàn thiện `rooms/`, `equipment/` (Trung) theo wireframe mục 4.4–4.7 và validation mục 5.2, 5.4.
6. Hoàn thiện luồng hợp nhất `bookings/` (Phấn, Kỳ) — áp dụng đúng quyết định thống nhất ở mục 1.2 Vấn đề #1; `equipment/borrow.php` chỉ redirect sang `bookings/form.php`.
7. Hoàn thiện `reports/`, `maintenance/` (Hiếu), bổ sung `reports/list.php`, `reports/update_status.php` còn thiếu.
8. Hoàn thiện `dashboard/`, `users/`, và chạy toàn bộ `test/checklist.md` (Mai) sau khi các module trên xong.
9. Rà soát chéo (code review) giữa các thành viên trước khi merge vào nhánh chính, đối chiếu lại đúng tên trường/route trong tài liệu này để đảm bảo tính nhất quán toàn hệ thống.

**Việc cần cả nhóm thống nhất & chốt trong buổi họp gần nhất** (đánh dấu để không quên):
- [ ] Xác nhận đổi tên bảng/trường sang tiếng Việt không dấu như mục 2, hay giữ tiếng Anh (`name`, `capacity`...) — tài liệu này tạm chọn tiếng Việt không dấu cho dễ đọc, nhóm có thể đổi miễn **thống nhất toàn bộ**.
- [ ] Xác nhận quyết định hợp nhất "Đặt phòng" + "Mượn thiết bị" thành 1 bảng `bookings` (mục 1.2, Vấn đề #1) — đây là thay đổi thiết kế lớn nhất, cần Phấn/Kỳ/Trung đồng thuận trước khi code.
- [ ] Xác nhận giới hạn thời lượng tối đa 4 giờ/lượt đặt phòng (mục 5.5) có phù hợp thực tế môn học hay không, hoặc nhóm tự đặt ngưỡng khác.