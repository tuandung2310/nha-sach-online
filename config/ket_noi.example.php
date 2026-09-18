<?php
// Copy file nay thanh config/ket_noi.php va dien thong tin CSDL cua ban.
$ten_host_web = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dang_chay_local = str_contains($ten_host_web, 'localhost') || str_contains($ten_host_web, '127.0.0.1');

if ($dang_chay_local) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'bookdb');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('URL_GOC', '/danh-sach-sach');
} else {
    define('DB_HOST', 'YOUR_HOSTING_DB_HOST');
    define('DB_NAME', 'YOUR_HOSTING_DB_NAME');
    define('DB_USER', 'YOUR_HOSTING_DB_USER');
    define('DB_PASS', 'YOUR_HOSTING_DB_PASSWORD');
    define('URL_GOC', '');
}

define('DUONG_DAN_GOC', dirname(__DIR__));

try {
    $ket_noi = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $loi) {
    die('Khong ket noi duoc CSDL: ' . $loi->getMessage());
}
