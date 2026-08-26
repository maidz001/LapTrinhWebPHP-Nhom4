# Test case Bài 5 - Nguyễn Kỳ

Chức năng kiểm thử: CRUD bảng `bookings`, tìm kiếm, phân trang và duyệt yêu cầu.

| STT | Dữ liệu nhập hoặc thao tác | Kết quả mong đợi |
|-----|----------------------------|------------------|
| 1 | Chọn phòng hợp lệ, thời gian kết thúc sau thời gian bắt đầu và nhập mục đích rõ ràng | Tạo yêu cầu thành công, trạng thái ban đầu là Chờ duyệt |
| 2 | Bỏ trống phòng hoặc thiết bị | Hiển thị lỗi yêu cầu chọn tài nguyên, không thêm dữ liệu |
| 3 | Nhập thời gian kết thúc trước thời gian bắt đầu | Hiển thị lỗi thời gian, không thêm dữ liệu |
| 4 | Chọn cùng tài nguyên và khoảng thời gian trùng với yêu cầu đang chờ hoặc đã duyệt | Hiển thị lỗi trùng thời gian, không thêm dữ liệu |
| 5 | Hủy một yêu cầu đang chờ bằng nút Hủy và xác nhận | Chuyển trạng thái thành Đã hủy, dữ liệu vẫn còn trong lịch sử |

Tài khoản người dùng để tạo, sửa và hủy: `trung.gv@nhom4.edu.vn`.

Tài khoản cán bộ lab để duyệt hoặc từ chối: `ky.labstaff@nhom4.edu.vn`.

Mật khẩu dữ liệu mẫu: `Matkhau123`.
