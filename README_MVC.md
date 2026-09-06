# Kế hoạch chuyển dự án sang MVC chuẩn

## 0. Vì sao không thể làm "một lần xong hết"

Dự án hiện có **~8.200 dòng PHP** trải trên **~90 file**, viết theo kiểu thủ tục
(procedural), truy cập trực tiếp từng file qua URL (`/rooms/index.php`,
`/auth/login.php`, ...). Toàn bộ menu, navbar, redirect, link trong ~90 file đó
đều **hard-code** đường dẫn kiểu này (xem `includes/navbar.php`,
`includes/app_head.php`, và phần cuối mỗi file `xuly.php`/`store.php`).

Nếu đổi cấu trúc URL/routing cho *toàn bộ* dự án trong một lần mà không chạy
thử được trên PHP + MySQL thật (môi trường soạn thảo này không có PHP/MySQL để
chạy kiểm tra), rủi ro gãy một liên kết nào đó ở giữa chừng là rất cao và khó
phát hiện. Vì vậy cách an toàn nhất — và cũng là cách làm đúng chuẩn công
nghiệp khi "strangler pattern" hoá một hệ thống cũ — là **migrate dần theo
module, chạy song song 2 hệ thống, không xoá gì của bản cũ cho tới khi bản mới
được xác nhận chạy đúng**.

## 1. Đã làm trong lượt này (Phase 0 + Phase 1) — AN TOÀN 100%

**Không sửa, không xoá bất kỳ file nào đã có sẵn.** Toàn bộ là file MỚI, nằm
trong 2 thư mục mới:

```
app/
  Core/
    Database.php     — bọc lại $pdo có sẵn từ config/database.php (không tạo kết nối mới)
    Model.php         — lớp cha cho mọi Model
    Controller.php    — lớp cha cho mọi Controller (render view, redirect, input())
    Router.php        — router tối giản, chỉ phục vụ các route dưới /mvc/...
  Controllers/
    AuthController.php  — gộp logic từ auth/login.php + register.php + logout.php
  Models/
    User.php          — toàn bộ SQL của auth (users, login_attempts)
  Views/
    auth/login.php    — giao diện đăng nhập (tách khỏi logic)
    auth/register.php — giao diện đăng ký (tách khỏi logic)

mvc/
  index.php   — front controller, ĐIỂM VÀO DUY NHẤT cho các route /mvc/...
  .htaccess   — rewrite CHỈ trong thư mục mvc/, không đụng gì ở ngoài
```

Route mới hoạt động song song với route cũ:

| Chức năng | URL cũ (vẫn chạy y nguyên) | URL mới (MVC) |
|---|---|---|
| Đăng nhập | `/auth/login.php` | `/mvc/auth/login` |
| Đăng ký | `/auth/register.php` | `/mvc/auth/register` |
| Đăng xuất | `/auth/logout.php` | `/mvc/auth/logout` |

Toàn bộ quy tắc bảo mật gốc được giữ **nguyên vẹn 100%**: khoá 5 lần
sai/15 phút theo email, chặn brute-force theo IP, honeypot chống bot,
`session_regenerate_id()` chống fixation, CSRF token, validate họ tên/email/
mật khẩu/số điện thoại giống hệt bản cũ.

### Vì sao chắc chắn không ảnh hưởng phần đang chạy
- Không file nào trong `auth/`, `includes/`, `config/`, `rooms/`, `equipment/`,
  `bookings/`, ... bị sửa hay xoá.
- `.htaccess` chỉ đặt **bên trong** `mvc/`, Apache chỉ áp dụng rewrite cho
  request đi vào đúng thư mục đó — không có `.htaccess` ở thư mục gốc nên mọi
  URL khác tiếp tục được phục vụ như file vật lý, y hệt trước giờ.
- `Database::pdo()` không mở kết nối riêng, chỉ tái sử dụng biến `$pdo` đã có
  → không có nguy cơ lệch cấu hình hay mở 2 kết nối.

### Cách kiểm thử phần vừa làm
1. Đảm bảo `mod_rewrite` đã bật trên Apache (XAMPP: bật trong `httpd.conf`,
   dòng `LoadModule rewrite_module`, và `AllowOverride All` cho thư mục dự án).
2. Truy cập `http://localhost/<duong-dan-du-an>/mvc/auth/login` — phải thấy
   đúng giao diện đăng nhập như bản cũ.
3. Thử đăng nhập sai 5 lần liên tiếp bằng 1 email — phải bị khoá 15 phút,
   giống hệt hành vi ở `/auth/login.php`.
4. Đăng ký tài khoản mới qua `/mvc/auth/register`, sau đó đăng nhập lại qua
   `/mvc/auth/login` để xác nhận session/CSRF hoạt động đúng.
5. Trong lúc đó, mở song song `/auth/login.php` (bản cũ) — phải vẫn chạy bình
   thường không có gì thay đổi.

## 2. Đã làm trong lượt này (Phase 2) — Rooms + Equipment — AN TOÀN 100%

Giống Phase 1: **không sửa, không xoá** `rooms/*.php`, `equipment/*.php`,
`equipment_types/*.php` — toàn bộ vẫn chạy y nguyên qua URL cũ. Chỉ thêm
file mới:

```
app/
  Models/
    Room.php           — toàn bộ SQL bảng `rooms`
    Equipment.php       — toàn bộ SQL bảng `equipment` (join equipment_types, rooms)
    EquipmentType.php   — SQL bảng `equipment_types` (dropdown + thống kê)
  Controllers/
    RoomController.php      — gộp logic rooms/list+form+save+delete+export+import.php
    EquipmentController.php — gộp logic equipment/list+form+save+delete+export+import.php
                               + equipment_types/list.php (action `types()`)
  Views/
    rooms/{index,form,import}.php
    equipment/{index,form,import,types}.php
```

Route mới hoạt động song song với route cũ:

| Chức năng | URL cũ (vẫn chạy y nguyên) | URL mới (MVC) |
|---|---|---|
| Danh sách phòng | `/rooms/list.php` | `/mvc/rooms` |
| Thêm/sửa phòng | `/rooms/form.php` | `/mvc/rooms/form` |
| Lưu phòng | `/rooms/save.php` | `/mvc/rooms/save` |
| Xoá phòng | `/rooms/delete.php` | `/mvc/rooms/delete` |
| Xuất CSV phòng | `/rooms/export.php` | `/mvc/rooms/export` |
| Import CSV phòng | `/rooms/import.php` | `/mvc/rooms/import` |
| Danh sách thiết bị | `/equipment/list.php` | `/mvc/equipment` |
| Thêm/sửa thiết bị | `/equipment/form.php` | `/mvc/equipment/form` |
| Lưu thiết bị | `/equipment/save.php` | `/mvc/equipment/save` |
| Xoá thiết bị | `/equipment/delete.php` | `/mvc/equipment/delete` |
| Xuất CSV thiết bị | `/equipment/export.php` | `/mvc/equipment/export` |
| Import CSV thiết bị | `/equipment/import.php` | `/mvc/equipment/import` |
| Danh mục thiết bị | `/equipment_types/list.php` | `/mvc/equipment-types` |

**Không** migrate `equipment/handover.php` (thuộc nghiệp vụ mượn/bàn giao —
sẽ làm chung với Bookings ở Phase 3, để tránh tách nửa vời một luồng
nghiệp vụ đang gắn chặt với `bookings`).

### ⚠️ Phát hiện quan trọng: `models/Phong.php` và `models/ThietBi.php` KHÔNG được tái sử dụng

Kế hoạch ban đầu (bản trước của tài liệu này) dự định tận dụng lại
`models/Phong.php` và `models/ThietBi.php`. Khi đọc kỹ lại, 2 file này
**không khớp schema hiện tại** của `database/database.sql`:
- `Phong::insert()/update()` ghi cột `loai_phong` — cột này **không tồn
  tại** trong bảng `rooms` hiện tại.
- `ThietBi` dùng cột `so_luong`, `gia_tri` — bảng `equipment` hiện tại
  dùng `co_the_muon`, `ngay_mua` thay thế, không có 2 cột đó.
- 2 model này cũng không phải thứ đang được dùng bởi hệ thống đang chạy:
  navbar/`app_head.php` trỏ tới `/rooms/list.php` + `/equipment/list.php`
  (dùng SQL thuần trực tiếp trong file, không qua model), còn
  `rooms/index.php` + `equipment/index.php` (nơi 2 model cũ này được
  dùng) là một bộ giao diện khác, **không được liên kết từ menu chính**.

Vì vậy `app/Models/Room.php` và `app/Models/Equipment.php` được viết
**mới hoàn toàn**, khớp đúng schema và đúng logic của bộ file đang thực
sự chạy (`rooms/list.php`, `equipment/list.php`, ...), không kế thừa gì
từ `models/Phong.php`/`models/ThietBi.php`. `models/Phong.php`,
`models/ThietBi.php`, `models/DanhMuc.php` giữ nguyên, không đụng tới.

### Cách kiểm thử phần vừa làm
1. Đăng nhập (qua `/auth/login.php` hoặc `/mvc/auth/login` đều được), rồi
   vào thẳng `/mvc/rooms` — phải thấy đúng danh sách phòng như
   `/rooms/list.php`.
2. Thử tìm kiếm theo tên (`?q=...`), thêm/sửa/xoá phòng qua
   `/mvc/rooms/form`, xuất CSV, rồi import lại CSV đó — hành vi (kể cả
   thông báo lỗi dòng nào, mã trùng...) phải giống hệt bản `/rooms/*.php`.
3. Lặp lại bước 1–2 cho `/mvc/equipment` (kèm bộ lọc loại + trạng thái)
   và `/mvc/equipment-types`.
4. Chỉ admin/lab_staff mới vào được `/mvc/rooms/form`,
   `/mvc/equipment/form`, `/mvc/equipment-types`, ... — user thường bị
   chặn 403 giống `require_role()` bản cũ.
5. Mở song song `/rooms/list.php`, `/equipment/list.php` (bản cũ) —
   phải vẫn chạy bình thường, dữ liệu đồng bộ 2 bên vì cùng 1 bảng CSDL.

## 3. Đã làm trong lượt trước (Phase 3) — Bookings — AN TOÀN 100%

(Bổ sung mô tả cho lượt đã hoàn thành trước đó, README chưa kịp cập nhật.)
Không sửa/xoá `bookings/*.php`, `equipment/handover.php`. File mới:
`app/Models/Booking.php`, `app/Controllers/BookingController.php`,
`app/Views/bookings/*`. Route song song: `/mvc/bookings`,
`/mvc/bookings/form`, `/mvc/bookings/store`, `/mvc/bookings/cancel`,
`/mvc/bookings/detail`, `/mvc/bookings/history`, `/mvc/bookings/pending`,
`/mvc/bookings/approve`, `/mvc/bookings/reject` — tương ứng
`bookings/my_requests.php`, `bookings/form.php`, `bookings/store.php`,
`bookings/cancel.php`, `bookings/detail.php`, `bookings/history.php`,
`bookings/pending.php` + `bookings/manage.php`, `bookings/approve.php`,
`bookings/reject.php`.

## 4. Đã làm trong lượt này (Phase 4) — Báo cáo, Người dùng, Cài đặt — AN TOÀN 100%

Giống các phase trước: **không sửa, không xoá** `reports/*.php`,
`users/*.php`, `settings/index.php` — toàn bộ vẫn chạy y nguyên qua URL
cũ. Chỉ thêm file mới:

```
app/
  Models/
    Report.php   — toàn bộ SQL bảng `reports` (báo hỏng thiết bị + đồng bộ trạng thái thiết bị)
    User.php     — bổ sung thêm các phương thức quản lý người dùng (admin)
                    + cài đặt tài khoản cá nhân vào Model User đã có từ Phase 1
  Controllers/
    ReportController.php   — gộp logic reports/index+create+store+update_status.php
    UserController.php     — gộp logic users/list+toggle_status+update_role.php
    SettingsController.php — gộp logic settings/index.php (2 form: thông tin + đổi mật khẩu)
  Views/
    reports/{index,create}.php
    users/index.php
    settings/index.php
```

Route mới hoạt động song song với route cũ:

| Chức năng | URL cũ (vẫn chạy y nguyên) | URL mới (MVC) |
|---|---|---|
| Danh sách báo cáo | `/reports/index.php` | `/mvc/reports` |
| Báo hỏng thiết bị (form) | `/reports/create.php` | `/mvc/reports/create` |
| Lưu báo hỏng | `/reports/store.php` | `/mvc/reports/store` |
| Cập nhật trạng thái báo cáo | `/reports/update_status.php` | `/mvc/reports/update-status` |
| Danh sách người dùng | `/users/list.php` | `/mvc/users` |
| Khoá/mở khoá tài khoản | `/users/toggle_status.php` | `/mvc/users/toggle-status` |
| Nâng/hạ vai trò | `/users/update_role.php` | `/mvc/users/update-role` |
| Cài đặt tài khoản | `/settings/index.php` | `/mvc/settings` |
| Cập nhật thông tin liên hệ | `/settings/index.php` (action `update_info`) | `/mvc/settings/update-info` |
| Đổi mật khẩu | `/settings/index.php` (action `change_password`) | `/mvc/settings/change-password` |

`users/delete.php` gốc không có chức năng thật (chỉ `header('Location: ...')`
để không vỡ đường link cũ — tài khoản không có chức năng xoá theo đúng
yêu cầu nghiệp vụ), nên **không có route MVC tương ứng**, giống hệt lý do
gốc.

### ⚠️ Phát hiện quan trọng: KHÔNG migrate `danhmuc/*.php`, đúng như cảnh báo đã ghi ở Phase 2

README bản trước dự định `models/DanhMuc.php` "vẫn có thể tận dụng lại
cho phần Danh mục ở Phase 4". Kiểm tra lại trước khi migrate (đúng như
khuyến cáo) cho thấy:
- `DanhMuc::getAll()`/`thongKe()` JOIN với `equipment` qua cột `e.so_luong`
  và lọc theo `e.trang_thai`, nhưng bảng `equipment` hiện tại **không có
  cột `so_luong`** (`database/database.sql`) — mọi câu SQL trong model này
  sẽ lỗi nếu chạy trên schema hiện tại.
- Bảng thực chất mà `DanhMuc` thao tác là `equipment_types` — **đúng là
  bảng đã được migrate xong ở Phase 2** qua `EquipmentController::types()`
  (`/mvc/equipment-types`), không phải một bảng `danh_muc` riêng.
- `danhmuc/*.php` (index/add/edit/delete/xuly) cũng là **giao diện mồ côi**:
  không được liên kết từ `includes/navbar.php` lẫn `includes/app_head.php`
  (sidebar chính) — giống hệt tình huống của `rooms/index.php` và
  `equipment/index.php` đã phát hiện ở Phase 2.

Vì vậy **Danh mục không có route MVC riêng** ở phase này — chức năng
"danh mục thiết bị" thật sự đang dùng (được liên kết từ sidebar) đã có
bản MVC hoàn chỉnh từ Phase 2 tại `/mvc/equipment-types`. Các file
`danhmuc/*.php`, `models/DanhMuc.php` giữ nguyên, không đụng tới.

### Ghi chú: `maintenance/*.php` chưa migrate vì chưa có logic thật

`maintenance/history.php` và `maintenance/update.php` mỗi file chỉ có
đúng dòng `<?php` — chưa được xây dựng chức năng (không liên kết từ
sidebar/navbar). Có bảng `maintenance_logs` sẵn trong schema nhưng chưa
có code thủ tục nào dùng tới, nên chưa có "bản gốc" để giữ nguyên logic
khi migrate. Để dành cho khi module này được xây dựng thật.

### Cách kiểm thử phần vừa làm
1. Đăng nhập (`/auth/login.php` hoặc `/mvc/auth/login`), vào
   `/mvc/reports` — phải thấy đúng danh sách báo cáo như `/reports/index.php`
   (người dùng thường chỉ thấy báo cáo của mình; admin/lab_staff thấy hết
   kèm cột "Cập nhật").
2. Từ `/mvc/reports`, bấm "Báo hỏng thiết bị" → `/mvc/reports/create`,
   gửi báo cáo → thiết bị liên quan phải tự chuyển trạng thái "Hỏng",
   giống hệt `reports/store.php`.
3. Với tài khoản admin/lab_staff, đổi trạng thái báo cáo tại
   `/mvc/reports` (mới → đang xử lý → đã xử lý) — trạng thái thiết bị
   phải tự đồng bộ (Hỏng → Bảo trì → Hoạt động), trừ khi thiết bị đang
   "Đang mượn" thì không đổi.
4. Đăng nhập bằng tài khoản admin, vào `/mvc/users` — thử khoá/mở khoá 1
   tài khoản, thử nâng/hạ vai trò user ↔ lab_staff. Xác nhận không thể tự
   khoá/tự đổi vai trò chính mình, và không có nút đổi vai trò với tài
   khoản admin khác.
5. Vào `/mvc/settings` — cập nhật họ tên/số điện thoại, xác nhận tên hiển
   thị trên sidebar đổi theo ngay. Đổi mật khẩu (nhập sai mật khẩu hiện
   tại phải báo lỗi; mật khẩu mới không đủ mạnh phải báo lỗi), đăng xuất
   rồi đăng nhập lại bằng mật khẩu mới để xác nhận.
6. Mở song song `/reports/index.php`, `/users/list.php`,
   `/settings/index.php` (bản cũ) — phải vẫn chạy bình thường, dữ liệu
   đồng bộ 2 bên vì cùng 1 bảng CSDL.

## 5. Đã làm trong lượt này (Phase 5) — Dashboard / Tổng quan — AN TOÀN 100%

Giống các phase trước: **không sửa, không xoá** `index.php` gốc — vẫn là
trang chủ thật của site, chạy y nguyên ở `/`. Chỉ thêm file mới:

```
app/
  Models/
    Dashboard.php   — toàn bộ SQL của trang tổng quan (4 thẻ số liệu,
                       lượt sử dụng phòng 7 ngày, tình trạng thiết bị)
  Controllers/
    DashboardController.php — tính toán dữ liệu biểu đồ (cột 7 ngày,
                       donut chart) y hệt index.php gốc, không đổi
                       công thức
  Views/
    dashboard/index.php
```

Route mới hoạt động song song với route cũ:

| Chức năng | URL cũ (vẫn chạy y nguyên) | URL mới (MVC) |
|---|---|---|
| Trang chủ / Tổng quan | `/index.php` (hoặc `/`) | `/mvc/dashboard` (alias: `/mvc`) |

Toàn bộ số liệu vẫn lấy **trực tiếp từ CSDL** bằng truy vấn thật giống
hệt bản gốc (không có dữ liệu minh hoạ), kể cả trường hợp rỗng hiển thị
"Chưa có dữ liệu" thay vì bịa số. Công thức tính biểu đồ cột (scale
trục Y theo `ceil(max/4)*4`) và donut chart (dash/offset theo chu vi
`2πr`, r = 70) được giữ **nguyên vẹn 100%** như bản thủ tục cũ.

Lưu ý: route `''` (tức gọi thẳng `/mvc` hoặc `/mvc/`, không có gì phía
sau) được đăng ký như alias của `dashboard` trong `mvc/index.php`, vì
`Router::dispatch()` đã `trim($path, '/')` nên cả hai cùng khớp route
rỗng — giống việc `index.php` là trang mặc định của thư mục gốc.

### Cách kiểm thử phần vừa làm
1. Đăng nhập (`/auth/login.php` hoặc `/mvc/auth/login`), vào
   `/mvc/dashboard` (hoặc chỉ `/mvc`) — phải thấy đúng 4 thẻ số liệu,
   biểu đồ cột 7 ngày và donut chart tình trạng thiết bị giống hệt
   `/index.php`.
2. So sánh số liệu 2 bên (`/index.php` vs `/mvc/dashboard`) — phải khớp
   100% vì cùng 1 CSDL, cùng công thức tính.
3. Thử với tài khoản chưa có booking/thiết bị nào (CSDL trống) để xác
   nhận cả 2 bản đều hiển thị đúng trạng thái rỗng ("Chưa có dữ liệu
   đặt phòng...", "Chưa có thiết bị nào...") thay vì lỗi chia cho 0.
4. Truy cập `/mvc/dashboard` khi chưa đăng nhập — phải bị `require_login()`
   chuyển hướng sang trang đăng nhập, giống `index.php` gốc.

## 6. Tổng kết: các module thật sự đang dùng đã có bản MVC song song đầy đủ

Sau Phase 5, mọi mục trong sidebar (`includes/app_head.php`) và navbar
(`includes/navbar.php`) đều đã có route MVC tương ứng: Tổng quan, Phòng
thực hành, Thiết bị, Danh mục thiết bị, Lịch sử dùng, Tạo yêu cầu, Yêu
cầu của tôi, Duyệt yêu cầu, Bàn giao thiết bị, Báo cáo, Người dùng, Cài
đặt, Đăng nhập/Đăng ký/Đăng xuất. Việc còn lại chỉ còn **Phase 6 — cắt
chuyển cuối cùng**.

## 7. Việc để lại cho lượt sau (không ảnh hưởng lượt này)

| Phase | Việc cần làm |
|---|---|
| 6 | **Cắt chuyển cuối cùng**: cập nhật `includes/navbar.php` + `includes/app_head.php` để trỏ toàn bộ menu sang `/mvc/...`, kiểm thử lại toàn bộ luồng nghiệp vụ trên route mới, rồi mới xoá các file thủ tục cũ (`auth/`, `rooms/`, `equipment/`, `equipment_types/`, `bookings/`, `reports/`, `users/`, `settings/`, `index.php`...). Đây là bước DUY NHẤT có thể ảnh hưởng người dùng đang dùng hệ thống, nên làm sau cùng, làm trong 1 lần triển khai riêng, và nên có backup/rollback plan trước khi xoá bất kỳ file nào. |

Ghi chú quan trọng cho Phase 6:
- Đổi từng dòng trong mảng `$__menu` (`includes/app_head.php`) và các
  `<a href>` trong `includes/navbar.php` sang tiền tố `/mvc/...` tương
  ứng theo các bảng URL cũ→mới đã liệt kê ở mục 1–5 phía trên.
- `danhmuc/*.php` và `maintenance/*.php` không có route MVC (xem lý do ở
  mục 4) — nếu 2 module này thật sự cần dùng, phải làm chức năng/migrate
  riêng **trước khi** xoá file cũ ở Phase 6, không được xoá cùng lúc.
- Sau khi trỏ menu sang `/mvc/...`, nên giữ file thủ tục cũ thêm một
  thời gian (không xoá ngay) để có đường lùi nếu phát hiện lỗi, rồi mới
  dọn dẹp hẳn.

## 8. Muốn mình làm tiếp phần nào?

Trả lời 1 trong các lựa chọn sau để mình làm tiếp trong lượt kế:
- "Làm Phase 6" (cắt chuyển menu + dọn file cũ)
- Hoặc nêu việc cụ thể bạn muốn ưu tiên trước.
