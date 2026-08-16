# Checklist kiểm thử — Hệ thống quản lý phòng thực hành & thiết bị

Người phụ trách: Nguyễn Hồng Mai (Phân quyền, Dashboard & Kiểm thử)
Cập nhật lần cuối: (Hiện chưa test)

Đánh dấu ✅ khi test PASS, ❌ khi FAIL (ghi rõ lỗi ở cột Ghi chú).

---

## 1. Kiểm thử chức năng — CRUD Phòng (`rooms/`)

| STT | Tình huống test | Kết quả mong đợi | Kết quả thực tế | Ghi chú |
|-----|-----------------|------------------|-----------------|---------|
| 1 | Thêm phòng với đầy đủ thông tin hợp lệ | Phòng được thêm, hiển thị trong danh sách | | |
| 2 | Thêm phòng bỏ trống trường bắt buộc | Báo lỗi, không cho lưu | | |
| 3 | Sửa thông tin phòng đã có | Cập nhật đúng, hiển thị dữ liệu mới | | |
| 4 | Xóa phòng | Phòng biến mất khỏi danh sách | | |
| 5 | Xóa phòng đang có thiết bị/booking liên kết | Có cảnh báo hoặc chặn xóa (tránh lỗi dữ liệu mồ côi) | | |

## 2. Kiểm thử chức năng — CRUD Loại thiết bị & Thiết bị

| STT | Tình huống test | Kết quả mong đợi | Kết quả thực tế | Ghi chú |
|---|---|---|---|---|
| 1 | Thêm loại thiết bị mới | Lưu thành công | | |
| 2 | Thêm thiết bị, gắn đúng loại + phòng | Thiết bị hiển thị đúng thông tin liên kết | | |
| 3 | Lọc danh sách thiết bị theo trạng thái (hỏng/bảo trì/hoạt động) | Danh sách lọc đúng | | |
| 4 | Lọc thiết bị theo phòng | Danh sách lọc đúng | | |

## 3. Kiểm thử phân quyền (`auth/`) — module bạn phụ trách

| STT | Tình huống test | Kết quả mong đợi | Kết quả thực tế | Ghi chú |
|---|---|---|---|---|
| 1 | Đăng nhập với tài khoản Sinh viên/Giảng viên | Chỉ thấy chức năng xem lịch, đặt phòng, mượn thiết bị | | |
| 2 | Đăng nhập với tài khoản Cán bộ lab | Thấy thêm chức năng duyệt yêu cầu, cập nhật thiết bị | | |
| 3 | Đăng nhập với tài khoản Admin | Toàn quyền: quản lý tài khoản, phòng, thiết bị | | |
| 4 | Tài khoản Sinh viên cố truy cập URL trang Admin trực tiếp | Bị chặn / chuyển hướng, không xem được | | |
| 5 | Đăng nhập sai mật khẩu | Báo lỗi rõ ràng, không lộ thông tin tài khoản có tồn tại hay không | | |
| 6 | Chưa đăng nhập, truy cập thẳng URL chức năng nội bộ | Bị chuyển hướng về trang đăng nhập | | |

## 4. Kiểm thử Dashboard (`dashboard/`) — module bạn phụ trách

| STT | Tình huống test | Kết quả mong đợi | Kết quả thực tế | Ghi chú |
|---|---|---|---|---|
| 1 | Số liệu thiết bị hoạt động/hỏng/bảo trì trên dashboard | Khớp với dữ liệu thật trong CSDL | | |
| 2 | Thêm/xóa thiết bị rồi tải lại dashboard | Số liệu cập nhật đúng | | |
| 3 | Tài khoản không phải Admin truy cập dashboard | Bị chặn theo đúng phân quyền | | |

## 5. Kiểm thử bảo mật cơ bản

| STT | Tình huống test | Kết quả mong đợi | Kết quả thực tế | Ghi chú |
|---|---|---|---|---|
| 1 | Nhập `' OR 1=1 --` vào ô tìm kiếm/đăng nhập | Không đăng nhập được, không lộ dữ liệu (nhờ PDO prepared statement) | | |
| 2 | Nhập `<script>alert(1)</script>` vào ô tên phòng/thiết bị | Không thực thi script, hiển thị lại là text (nhờ `htmlspecialchars()`) | | |

## 6. Kiểm thử endpoint JSON (`api/check_availability.php`)

| STT | Tình huống test | Kết quả mong đợi | Kết quả thực tế | Ghi chú |
|---|---|---|---|---|
| 1 | Gọi endpoint với phòng/thiết bị còn trống | Trả về JSON đúng trạng thái "còn trống" | | |
| 2 | Gọi endpoint với phòng đã có booking trùng giờ | Trả về JSON đúng trạng thái "đã kín" | | |

---

## Cách thực hiện

1. Chạy lần lượt từng dòng test bằng tay trên trình duyệt (`http://localhost/LapTrinhWebPHP-Nhom4/`).
2. Điền kết quả thực tế + đánh dấu ✅/❌ vào bảng.
3. Nếu FAIL, tạo Issue trên GitHub mô tả lỗi, gắn tên người phụ trách module đó để fix.
4. File này commit định kỳ để nhóm theo dõi tiến độ kiểm thử.