# Hệ thống quản lý phòng thực hành và thiết bị

## Mô tả
Website quản lý phòng thực hành và thiết bị, được xây dựng bằng PHP và MySQL, thực hiện trong khuôn khổ học phần Lập trình Web.

## Yêu cầu môi trường
- PHP >= 8.0
- MySQL >= 5.7
- Web server: XAMPP / Laragon / WAMP

## Cấu trúc thư mục

LapTrinhWebPHP-Nhom4/
├── about.php
├── index.php
├── includes/
│ ├── header.php
│ └── footer.php
├── assets/
│ ├── css/
│ │ └── style.css
│ ├── js/
│ │ └── script.js
│ └── images/
├── config/
│ └── database.php
└── README.md


## Hướng dẫn cài đặt và chạy local

1. Clone repository về máy:
```bash
   git clone https://github.com/maidz001/LapTrinhWebPHP-Nhom4.git
```

2. Copy thư mục project vào thư mục web server, ví dụ với XAMPP:

C:/xampp/htdocs/LapTrinhWebPHP-Nhom4


3. Khởi động **Apache** và **MySQL** trong XAMPP Control Panel.

4. Tạo database:
   - Mở phpMyAdmin: `http://localhost/phpmyadmin`
   - Tạo database tên `quanly_phongthuchanh` (hoặc tên nhóm thống nhất)
   - Import file `database.sql` (nếu có) vào database vừa tạo
   - Cập nhật thông tin kết nối trong `config/database.php` cho khớp (host, username, password, tên database)

5. Truy cập project trên trình duyệt:

http://localhost/LapTrinhWebPHP-Nhom4/about.php


## Thành viên nhóm
| Họ tên | Vai trò |
|---|---|
| Nguyễn Hồng Mai | |
| Nguyễn Kỳ | |
| Triệu Văn Phấn | |
| Đặng Quang Trung | |
| Nguyễn Mạnh Hiếu | |