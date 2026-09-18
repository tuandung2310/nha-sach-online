# Nha Sach Online

Website ban sach online duoc xay dung bang PHP thuan va MySQL. Du an mo phong mot cua hang sach co day du cac chuc nang co ban cho nguoi dung va khu vuc quan tri rieng cho admin.

## Demo

- Website: https://nhasachtuandung.42web.io/
- Admin login: `/admin/dang-nhap.php`

> Luu y: demo dang chay tren hosting mien phi InfinityFree nen toc do va kha nang truy cap co the khong on dinh nhu hosting tra phi.

## Chuc Nang Chinh

Nguoi dung:

- Xem sach moi va sach theo tung the loai
- Tim kiem sach theo ten sach hoac tac gia
- Xem chi tiet sach, gia ban, mo ta va anh bia
- Them sach vao gio hang, cap nhat so luong va dat hang
- Dang ky, dang nhap tai khoan
- Xac thuc email bang ma OTP gui qua Gmail SMTP
- Danh gia sach sau khi dang nhap
- Xem thong tin tai khoan va lich su don hang

Quan tri vien:

- Dang nhap bang tai khoan admin rieng
- Xem tong quan so sach, don hang, nguoi dung va doanh thu
- Them, sua, xoa sach
- Upload anh bia sach hoac dung link anh online
- Quan ly the loai sach
- Quan ly don hang va cap nhat trang thai don

## Cong Nghe Su Dung

- PHP
- MySQL / MariaDB
- HTML, CSS, JavaScript
- PDO de ket noi database
- Gmail SMTP de gui ma xac thuc email
- WAMP/XAMPP cho moi truong local
- InfinityFree cho moi truong deploy demo

## Cau Truc Thu Muc

```text
admin/      Cac trang quan tri
config/     File cau hinh database va email
css/        Giao dien nguoi dung va admin
includes/   Header, footer va cac ham dung chung
js/         JavaScript
uploads/    Anh bia sach upload
```

## Cai Dat Local

1. Clone du an ve may:

```bash
git clone https://github.com/tuandung2310/nha-sach-online.git
```

2. Dat source vao thu muc web server, vi du voi WAMP:

```text
C:/wamp64/www/danh-sach-sach
```

3. Tao database MySQL:

```text
bookdb
```

4. Import file SQL du lieu cua du an vao database `bookdb`.

5. Copy file cau hinh mau:

```text
config/ket_noi.example.php -> config/ket_noi.php
config/email.example.php -> config/email.php
```

6. Dien thong tin database va Gmail App Password vao cac file cau hinh vua copy.

7. Chay website:

```text
http://localhost/danh-sach-sach/
```

## Cau Hinh Email SMTP

De dung chuc nang gui ma xac thuc email, can tao Gmail App Password va dien vao:

```text
config/email.php
```

File cau hinh that khong duoc commit len GitHub. Repo chi cung cap file mau:

```text
config/email.example.php
```

## Bao Mat

Repo da bo qua cac file chua thong tin rieng tu:

```text
config/ket_noi.php
config/email.php
*.zip
file debug/test
```

Khi deploy sang moi truong moi, hay copy tu cac file `.example.php` va dien cau hinh rieng.

## Ghi Chu

Du an duoc thuc hien nham thuc hanh xay dung website ban hang bang PHP/MySQL, bao gom ca luong nguoi dung, gio hang, dat hang va quan tri noi dung.
