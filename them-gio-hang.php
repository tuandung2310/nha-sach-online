<?php
require_once 'config/ket_noi.php';

// Bắt đầu session (để lưu giỏ hàng), phòng khi file này được gọi trực tiếp
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 1. CHỈ CHO PHÉP GỬI BẰNG POST (đúng như form ở chi-tiet.php)
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_GOC . '/index.php');
    exit;
}

// ============================================
// 2. LẤY VÀ KIỂM TRA DỮ LIỆU GỬI LÊN
// ============================================
$sach_id  = isset($_POST['sach_id']) ? (int) $_POST['sach_id'] : 0;
$so_luong = isset($_POST['so_luong']) ? (int) $_POST['so_luong'] : 1;

// Chặn số lượng vô lý (âm, 0, hoặc quá nhiều)
if ($so_luong < 1) {
    $so_luong = 1;
}
if ($so_luong > 20) {
    $so_luong = 20;
}

// Kiểm tra sách có thật sự tồn tại trong CSDL không
// (tránh trường hợp ai đó tự sửa sach_id trong form bằng tay để nhét id không có thật)
$cau_lenh = $ket_noi->prepare("SELECT id, tieu_de, gia_ban FROM sach WHERE id = :id");
$cau_lenh->execute(['id' => $sach_id]);
$sach = $cau_lenh->fetch();

if (!$sach) {
    // Sách không tồn tại, quay lại trang chủ
    header('Location: ' . URL_GOC . '/index.php');
    exit;
}

// ============================================
// 3. THÊM VÀO GIỎ HÀNG (LƯU TRONG SESSION)
// ============================================
// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['gio_hang'])) {
    $_SESSION['gio_hang'] = [];
}

// Nếu sách đã có trong giỏ thì cộng dồn số lượng, chưa có thì thêm mới
if (isset($_SESSION['gio_hang'][$sach_id])) {
    $_SESSION['gio_hang'][$sach_id]['so_luong'] += $so_luong;
} else {
    $_SESSION['gio_hang'][$sach_id] = [
        'so_luong' => $so_luong,
    ];
}

// ============================================
// 4. QUAY LẠI TRANG CHI TIẾT SÁCH VỚI THÔNG BÁO THÀNH CÔNG
// ============================================
header('Location: ' . URL_GOC . '/chi-tiet.php?id=' . $sach_id . '&da_them=1');
exit;