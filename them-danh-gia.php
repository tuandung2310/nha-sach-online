<?php
require_once 'config/ket_noi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 1. CHỈ CHO PHÉP GỬI BẰNG POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_GOC . '/index.php');
    exit;
}

// ============================================
// 2. BẮT BUỘC PHẢI ĐĂNG NHẬP
// ============================================
if (empty($_SESSION['nguoi_dung_id'])) {
    header('Location: ' . URL_GOC . '/dang-nhap.php');
    exit;
}

// ============================================
// 3. LẤY VÀ KIỂM TRA DỮ LIỆU GỬI LÊN
// ============================================
$sach_id       = isset($_POST['sach_id']) ? (int) $_POST['sach_id'] : 0;
$so_sao        = isset($_POST['so_sao']) ? (int) $_POST['so_sao'] : 0;
$noi_dung      = trim($_POST['noi_dung'] ?? '');
$nguoi_dung_id = $_SESSION['nguoi_dung_id'];

// Chặn số sao ngoài khoảng 1-5
if ($so_sao < 1 || $so_sao > 5) {
    header('Location: ' . URL_GOC . '/chi-tiet.php?id=' . $sach_id . '&loi_danh_gia=so_sao');
    exit;
}

// Kiểm tra sách có tồn tại thật không
$cau_lenh = $ket_noi->prepare("SELECT id FROM sach WHERE id = :id");
$cau_lenh->execute(['id' => $sach_id]);
if (!$cau_lenh->fetch()) {
    header('Location: ' . URL_GOC . '/index.php');
    exit;
}

// ============================================
// 4. KIỂM TRA NGƯỜI NÀY ĐÃ ĐÁNH GIÁ SÁCH NÀY CHƯA
// ============================================
$cau_lenh_kt = $ket_noi->prepare("
    SELECT id FROM danh_gia
    WHERE sach_id = :sach_id AND nguoi_dung_id = :nguoi_dung_id
");
$cau_lenh_kt->execute([
    'sach_id'       => $sach_id,
    'nguoi_dung_id' => $nguoi_dung_id,
]);

if ($cau_lenh_kt->fetch()) {
    // Đã đánh giá rồi -> không cho gửi thêm
    header('Location: ' . URL_GOC . '/chi-tiet.php?id=' . $sach_id . '&loi_danh_gia=da_danh_gia');
    exit;
}

// ============================================
// 5. LƯU ĐÁNH GIÁ MỚI
// ============================================
$cau_lenh_them = $ket_noi->prepare("
    INSERT INTO danh_gia (sach_id, nguoi_dung_id, so_sao, noi_dung)
    VALUES (:sach_id, :nguoi_dung_id, :so_sao, :noi_dung)
");
$cau_lenh_them->execute([
    'sach_id'       => $sach_id,
    'nguoi_dung_id' => $nguoi_dung_id,
    'so_sao'        => $so_sao,
    'noi_dung'      => $noi_dung,
]);

header('Location: ' . URL_GOC . '/chi-tiet.php?id=' . $sach_id . '&danh_gia_thanh_cong=1');
exit;