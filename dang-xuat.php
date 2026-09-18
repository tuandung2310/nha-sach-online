<?php
// Bắt đầu session để có thể xoá nó
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xoá toàn bộ dữ liệu session (đăng nhập, giỏ hàng...)
$_SESSION = [];

// Xoá luôn cookie session ở trình duyệt (nếu có)
if (ini_get('session.use_cookies')) {
    $tham_so_cookie = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $tham_so_cookie['path'],
        $tham_so_cookie['domain'],
        $tham_so_cookie['secure'],
        $tham_so_cookie['httponly']
    );
}

// Huỷ session trên server
session_destroy();

// Về trang chủ
require_once 'config/ket_noi.php';
header('Location: ' . URL_GOC . '/index.php');
exit;