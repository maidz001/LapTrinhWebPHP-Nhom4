USE quanly_phongthuchanh;

SELECT b.id,
       u.ho_ten AS nguoi_gui,
       CASE
           WHEN b.loai_yeu_cau = 'room' THEN 'Đặt phòng'
           ELSE 'Mượn thiết bị'
       END AS loai_yeu_cau,
       COALESCE(r.ten_phong, e.ten_thiet_bi) AS tai_nguyen,
       b.thoi_gian_bat_dau,
       b.thoi_gian_ket_thuc,
       b.muc_dich
FROM bookings b
JOIN users u ON u.id = b.user_id
LEFT JOIN rooms r ON r.id = b.room_id
LEFT JOIN equipment e ON e.id = b.equipment_id
WHERE b.trang_thai = 'pending'
ORDER BY b.created_at;

SELECT b.id,
       u.ho_ten AS nguoi_gui,
       nguoi_duyet.ho_ten AS nguoi_duyet,
       COALESCE(r.ma_phong, e.ma_thiet_bi) AS ma_tai_nguyen,
       b.thoi_gian_bat_dau,
       b.thoi_gian_ket_thuc,
       b.approved_at
FROM bookings b
JOIN users u ON u.id = b.user_id
JOIN users nguoi_duyet ON nguoi_duyet.id = b.approved_by
LEFT JOIN rooms r ON r.id = b.room_id
LEFT JOIN equipment e ON e.id = b.equipment_id
WHERE b.trang_thai = 'approved'
ORDER BY b.approved_at DESC;

SELECT r.ma_phong,
       r.ten_phong,
       COUNT(b.id) AS so_luot_dat
FROM rooms r
LEFT JOIN bookings b
       ON b.room_id = r.id
      AND b.loai_yeu_cau = 'room'
WHERE r.trang_thai IN ('available', 'maintenance')
GROUP BY r.id, r.ma_phong, r.ten_phong
ORDER BY so_luot_dat DESC, r.ma_phong;
