# Nha Sach Online

Website ban sach online viet bang PHP va MySQL. Du an co trang nguoi dung, gio hang, dat hang, danh gia sach va khu vuc quan tri rieng cho admin.

## Chuc nang chinh

- Xem danh sach sach theo the loai
- Tim kiem sach
- Xem chi tiet sach va danh gia
- Gio hang va dat hang
- Dang ky, dang nhap va xac thuc email bang ma OTP qua Gmail SMTP
- Admin quan ly sach: them, sua, xoa
- Admin quan ly the loai va don hang

## Cong nghe

- PHP
- MySQL / MariaDB
- HTML, CSS, JavaScript
- Gmail SMTP

## Cau hinh local

1. Tao database MySQL ten `bookdb`.
2. Import file SQL du lieu cua du an vao database.
3. Copy file cau hinh mau:

```text
config/ket_noi.example.php -> config/ket_noi.php
config/email.example.php -> config/email.php
```

4. Dien thong tin database va Gmail App Password vao 2 file cau hinh vua copy.
5. Chay tren WAMP/XAMPP:

```text
http://localhost/danh-sach-sach/
```

## Tai khoan admin

Tai khoan admin duoc luu trong bang `tai_khoan_admin`. Khi deploy, can tao admin trong database theo cau truc cua du an.

## Bao mat

Repo khong commit cac file chua mat khau that:

```text
config/ket_noi.php
config/email.php
```

Hay dung cac file `.example.php` de cau hinh moi truong rieng.
