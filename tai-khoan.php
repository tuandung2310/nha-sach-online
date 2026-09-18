<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/header.php';

// ============================================
// 1. CHẶN NGƯỜI CHƯA ĐĂNG NHẬP
// ============================================
if (!da_dang_nhap()) {
    header('Location: ' . URL_GOC . '/dang-nhap.php');
    exit;
}

// ============================================
// 2. LẤY THÔNG TIN NGƯỜI DÙNG
// ============================================
$cau_lenh = $ket_noi->prepare("SELECT * FROM nguoi_dung WHERE id = :id");
$cau_lenh->execute(['id' => $_SESSION['nguoi_dung_id']]);
$nguoi_dung = $cau_lenh->fetch();

// Phòng trường hợp hy hữu: tài khoản đã bị xoá khỏi DB nhưng session vẫn còn
if (!$nguoi_dung) {
    header('Location: ' . URL_GOC . '/dang-xuat.php');
    exit;
}

// ============================================
// 3. LẤY LỊCH SỬ ĐƠN HÀNG CỦA NGƯỜI DÙNG NÀY
// ============================================
$cau_lenh_dh = $ket_noi->prepare("
    SELECT * FROM don_hang
    WHERE nguoi_dung_id = :nguoi_dung_id
    ORDER BY ngay_dat DESC
");
$cau_lenh_dh->execute(['nguoi_dung_id' => $nguoi_dung['id']]);
$danh_sach_don_hang = $cau_lenh_dh->fetchAll();
?>

<h1>Tài khoản của tôi</h1>

<div class="trang-tai-khoan">
    <!-- Thông tin cá nhân -->
    <div class="khoi-thong-tin-ca-nhan">
        <h2>Thông tin cá nhân</h2>
        <p><strong>Họ tên:</strong> <?= htmlspecialchars($nguoi_dung['ho_ten']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($nguoi_dung['email']) ?></p>
        <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($nguoi_dung['so_dien_thoai'] ?: 'Chưa cập nhật') ?></p>
        <p><strong>Ngày tham gia:</strong> <?= date('d/m/Y', strtotime($nguoi_dung['ngay_tao'])) ?></p>
    </div>

    <!-- Lịch sử đơn hàng -->
    <div class="khoi-lich-su-don-hang">
        <h2>Lịch sử đơn hàng</h2>

        <?php if (empty($danh_sach_don_hang)): ?>
            <p>Bạn chưa có đơn hàng nào.</p>
            <a href="<?= URL_GOC ?>/danh-sach.php" class="nut-thanh-toan">Mua sắm ngay</a>
        <?php else: ?>
            <table class="bang-don-hang">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($danh_sach_don_hang as $don): ?>
                        <tr>
                            <td>#<?= $don['id'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($don['ngay_dat'])) ?></td>
                            <td><?= dinh_dang_gia($don['tong_tien']) ?></td>
                            <td>
                                <span class="nhan-trang-thai nhan-<?= str_replace(' ', '-', strtolower($don['trang_thai'])) ?>">
                                    <?= htmlspecialchars($don['trang_thai']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= URL_GOC ?>/dat-hang-thanh-cong.php?ma_don=<?= $don['id'] ?>">Xem chi tiết</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>