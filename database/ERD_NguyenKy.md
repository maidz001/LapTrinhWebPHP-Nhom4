# ERD cá nhân - Nguyễn Kỳ

Chức năng phụ trách: duyệt yêu cầu và quản lý booking.

```mermaid
erDiagram
    USERS ||--o{ BOOKINGS : gui
    USERS ||--o{ BOOKINGS : duyet
    ROOMS ||--o{ BOOKINGS : duoc_dat
    EQUIPMENT ||--o{ BOOKINGS : duoc_muon

    USERS {
        INT id PK
        VARCHAR ho_ten
        VARCHAR email UK
        ENUM vai_tro
        ENUM trang_thai
    }

    ROOMS {
        INT id PK
        VARCHAR ma_phong UK
        VARCHAR ten_phong
        ENUM trang_thai
    }

    EQUIPMENT {
        INT id PK
        VARCHAR ma_thiet_bi UK
        VARCHAR ten_thiet_bi
        BOOLEAN co_the_muon
        ENUM trang_thai
    }

    BOOKINGS {
        INT id PK
        INT user_id FK
        ENUM loai_yeu_cau
        INT room_id FK
        INT equipment_id FK
        DATETIME thoi_gian_bat_dau
        DATETIME thoi_gian_ket_thuc
        VARCHAR muc_dich
        ENUM trang_thai
        INT approved_by FK
        DATETIME approved_at
        VARCHAR ly_do_tu_choi
    }
```

Một người dùng có thể gửi nhiều yêu cầu. Mỗi yêu cầu chỉ đặt một phòng hoặc mượn một thiết bị. Người duyệt được lưu bằng `approved_by`, tham chiếu đến bảng `users`.

Không lưu tên người gửi, tên người duyệt hoặc tên tài nguyên trong `bookings` vì có thể lấy bằng khóa ngoại. Không lưu số thứ tự vì đã có `id`.
