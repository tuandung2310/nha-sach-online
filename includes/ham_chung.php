<?php
// ============================================
// Các hàm dùng chung cho toàn bộ website
// ============================================

/**
 * Định dạng giá tiền kiểu Việt Nam: 85000 -> "85.000₫"
 */
function dinh_dang_gia($gia)
{
    return number_format($gia, 0, ',', '.') . '₫';
}

/**
 * Tính phần trăm giảm giá dựa trên giá gốc và giá bán
 * Ví dụ: giá gốc 100.000, giá bán 80.000 -> giảm 20%
 */
function tinh_phan_tram_giam($gia_goc, $gia_ban)
{
    if (empty($gia_goc) || $gia_goc <= $gia_ban) {
        return 0; // không giảm giá hoặc dữ liệu không hợp lệ
    }
    $phan_tram = (($gia_goc - $gia_ban) / $gia_goc) * 100;
    return round($phan_tram);
}

/**
 * Lấy đường dẫn ảnh bìa sách.
 * Ưu tiên ảnh upload (file_anh_bia) trong thư mục uploads/,
 * nếu không có thì dùng link ảnh ngoài (link_anh_bia),
 * nếu cả hai đều trống thì trả về ảnh mặc định.
 */
function lay_anh_bia($sach)
{
    // Ưu tiên file upload thật sự tồn tại trên server (dùng cho sách admin tự thêm/sửa sau này)
    if (!empty($sach['file_anh_bia'])) {
        $duong_dan_that = DUONG_DAN_GOC . '/uploads/' . $sach['file_anh_bia'];
        if (file_exists($duong_dan_that)) {
            return URL_GOC . '/uploads/' . $sach['file_anh_bia'];
        }
    }
    // Nếu không có file thật, dùng link ảnh online có sẵn (dữ liệu gốc từ Fahasa)
    if (!empty($sach['link_anh_bia'])) {
        return $sach['link_anh_bia'];
    }
    return URL_GOC . '/uploads/khong-co-anh.jpg';
}

/**
 * Cắt ngắn mô tả sách để hiện ở trang danh sách
 * (trang chi tiết thì hiện full, không dùng hàm này)
 */
function cat_ngan_mo_ta($noi_dung, $so_ky_tu = 100)
{
    $noi_dung = strip_tags($noi_dung); // bỏ thẻ HTML nếu có
    if (mb_strlen($noi_dung) <= $so_ky_tu) {
        return $noi_dung;
    }
    return mb_substr($noi_dung, 0, $so_ky_tu) . '...';
}

/**
 * Kiểm tra người dùng đã đăng nhập chưa
 */
function da_dang_nhap()
{
    return !empty($_SESSION['nguoi_dung_id']);
}

/**
 * Kiểm tra người dùng đăng nhập có phải admin không
 */
function la_admin()
{
    return !empty($_SESSION['la_admin']) && $_SESSION['la_admin'] == 1;
}
?>