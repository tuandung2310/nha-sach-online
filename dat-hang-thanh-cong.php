<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/header.php';

// ============================================
// 1. LẤY MÃ ĐƠN HÀNG TỪ URL
// ============================================
$ma_don = isset($_GET['ma_don']) ? (int) $_GET['ma_don'] : 0;

if ($ma_don <= 0) {
    header('Location: ' . URL_GOC . '/index.php');
    exit;
}

// ============================================
// 2. LẤY THÔNG TIN ĐƠN HÀNG
// ============================================
$cau_lenh = $ket_noi->prepare("SELECT * FROM don_hang WHERE id = :id");
$cau_lenh->execute(['id' => $ma_don]);
$don_hang = $cau_lenh->fetch();

// Nếu không tìm thấy đơn hàng (id sai, hoặc gõ bừa URL) thì đá về trang chủ
if (!$don_hang) {
    header('Location: ' . URL_GOC . '/index.php');
    exit;
}

// ============================================
// 3. LẤY CHI TIẾT CÁC SÁCH TRONG ĐƠN HÀNG NÀY
// ============================================
$cau_lenh_ct = $ket_noi->prepare("
    SELECT chi_tiet_don_hang.*, sach.tieu_de
    FROM chi_tiet_don_hang
    LEFT JOIN sach ON chi_tiet_don_hang.sach_id = sach.id
    WHERE chi_tiet_don_hang.don_hang_id = :don_hang_id
");
$cau_lenh_ct->execute(['don_hang_id' => $ma_don]);
$chi_tiet = $cau_lenh_ct->fetchAll();
?>

<div class="trang-thanh-cong">
    <h1>🎉 Đặt hàng thành công!</h1>
    <p>Cảm ơn bạn đã đặt hàng. Mã đơn hàng của bạn là <strong>#<?= $don_hang['id'] ?></strong>.</p>
    <p>Chúng tôi sẽ liên hệ qua số điện thoại <strong><?= htmlspecialchars($don_hang['so_dien_thoai']) ?></strong> để xác nhận đơn hàng.</p>

    <h2>Chi tiết đơn hàng</h2>
    <table class="bang-tom-tat">
        <thead>
            <tr>
                <th>Sách</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($chi_tiet as $dong): ?>
                <tr>
                    <td><?= htmlspecialchars($dong['tieu_de'] ?? 'Sách đã bị xoá') ?></td>
                    <td><?= $dong['so_luong'] ?></td>
                    <td><?= dinh_dang_gia($dong['don_gia']) ?></td>
                    <td><?= dinh_dang_gia($dong['don_gia'] * $dong['so_luong']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="tong-tien">Tổng cộng: <span><?= dinh_dang_gia($don_hang['tong_tien']) ?></span></p>

    <div class="thong-tin-giao-hang">
        <h2>Thông tin giao hàng</h2>
        <p><strong>Người nhận:</strong> <?= htmlspecialchars($don_hang['ten_nguoi_nhan']) ?></p>
        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($don_hang['dia_chi']) ?></p>
        <?php if (!empty($don_hang['ghi_chu'])): ?>
            <p><strong>Ghi chú:</strong> <?= htmlspecialchars($don_hang['ghi_chu']) ?></p>
        <?php endif; ?>
    </div>

    <a href="<?= URL_GOC ?>/danh-sach.php" class="nut-thanh-toan">Tiếp tục mua sắm</a>
</div>

<?php require_once 'includes/footer.php'; ?>