<?php
require_once '../config/ket_noi.php';
require_once '../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHỈ ADMIN MỚI ĐƯỢC VÀO
// ============================================
if (empty($_SESSION['admin_id']) || !la_admin()) {
    header('Location: ' . URL_GOC . '/admin/dang-nhap.php');
    exit;
}

// ============================================
// LẤY SỐ LIỆU TỔNG QUAN
// ============================================
$tong_so_sach     = $ket_noi->query("SELECT COUNT(*) FROM sach")->fetchColumn();
$tong_so_don_hang = $ket_noi->query("SELECT COUNT(*) FROM don_hang")->fetchColumn();
$tong_so_nguoi_dung = $ket_noi->query("SELECT COUNT(*) FROM nguoi_dung")->fetchColumn();
$tong_doanh_thu   = $ket_noi->query("
    SELECT COALESCE(SUM(tong_tien), 0) FROM don_hang
    WHERE trang_thai != 'Đã huỷ'
")->fetchColumn();

// Lấy 5 đơn hàng mới nhất
$don_hang_moi = $ket_noi->query("
    SELECT * FROM don_hang
    ORDER BY ngay_dat DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quản trị</title>
    <link rel="stylesheet" href="<?= URL_GOC ?>/css/style.css">
    <link rel="stylesheet" href="<?= URL_GOC ?>/css/admin.css">
</head>
<body>

<div class="khung-admin">
    <!-- Menu bên trái -->
    <aside class="menu-admin">
        <h2>📚 Quản trị</h2>
        <ul>
            <li><a href="index.php" class="dang-chon">Tổng quan</a></li>
            <li><a href="sach.php">Quản lý sách</a></li>
            <li><a href="the-loai.php">Quản lý thể loại</a></li>
            <li><a href="don-hang.php">Quản lý đơn hàng</a></li>
            <li><a href="nguoi-dung.php">Quản lý người dùng</a></li>
        </ul>
        <p class="thong-tin-admin">
            Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung']) ?><br>
            <a href="<?= URL_GOC ?>/dang-xuat.php">Đăng xuất</a>
        </p>
    </aside>

    <!-- Nội dung chính -->
    <main class="noi-dung-admin">
        <h1>Tổng quan</h1>

        <div class="luoi-thong-ke">
            <div class="the-thong-ke">
                <span class="so-lieu"><?= $tong_so_sach ?></span>
                <span class="nhan">Đầu sách</span>
            </div>
            <div class="the-thong-ke">
                <span class="so-lieu"><?= $tong_so_don_hang ?></span>
                <span class="nhan">Đơn hàng</span>
            </div>
            <div class="the-thong-ke">
                <span class="so-lieu"><?= $tong_so_nguoi_dung ?></span>
                <span class="nhan">Người dùng</span>
            </div>
            <div class="the-thong-ke">
                <span class="so-lieu"><?= dinh_dang_gia($tong_doanh_thu) ?></span>
                <span class="nhan">Doanh thu</span>
            </div>
        </div>

        <h2>Đơn hàng mới nhất</h2>
        <?php if (empty($don_hang_moi)): ?>
            <p>Chưa có đơn hàng nào.</p>
        <?php else: ?>
            <table class="bang-admin">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Người nhận</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($don_hang_moi as $don): ?>
                        <tr>
                            <td>#<?= $don['id'] ?></td>
                            <td><?= htmlspecialchars($don['ten_nguoi_nhan']) ?></td>
                            <td><?= dinh_dang_gia($don['tong_tien']) ?></td>
                            <td><?= htmlspecialchars($don['trang_thai']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($don['ngay_dat'])) ?></td>
                            <td><a href="don-hang.php?id=<?= $don['id'] ?>">Xem</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="don-hang.php">Xem tất cả đơn hàng &raquo;</a>
        <?php endif; ?>
    </main>
</div>

</body>
</html>
