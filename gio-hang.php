<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/header.php';

// ============================================
// 1. XỬ LÝ XOÁ SÁCH KHỎI GIỎ (nếu có yêu cầu)
// ============================================
if (isset($_GET['xoa'])) {
    $id_can_xoa = (int) $_GET['xoa'];
    if (isset($_SESSION['gio_hang'][$id_can_xoa])) {
        unset($_SESSION['gio_hang'][$id_can_xoa]);
    }
    // Chuyển hướng lại chính trang này để tránh xoá lặp lại khi F5
    header('Location: ' . URL_GOC . '/gio-hang.php');
    exit;
}

// ============================================
// 2. XỬ LÝ CẬP NHẬT SỐ LƯỢNG (khi bấm nút "Cập nhật")
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cap_nhat'])) {
    foreach ($_POST['so_luong'] as $id => $so_luong) {
        $id = (int) $id;
        $so_luong = (int) $so_luong;

        if (!isset($_SESSION['gio_hang'][$id])) {
            continue; // bỏ qua nếu id không có thật trong giỏ
        }

        if ($so_luong < 1) {
            unset($_SESSION['gio_hang'][$id]); // số lượng 0 hoặc âm -> xoá luôn
        } elseif ($so_luong > 20) {
            $_SESSION['gio_hang'][$id]['so_luong'] = 20;
        } else {
            $_SESSION['gio_hang'][$id]['so_luong'] = $so_luong;
        }
    }
    header('Location: ' . URL_GOC . '/gio-hang.php');
    exit;
}

// ============================================
// 3. LẤY THÔNG TIN CHI TIẾT TỪNG SÁCH TRONG GIỎ (luôn lấy giá mới nhất từ DB)
// ============================================
$danh_sach_gio_hang = [];
$tong_tien = 0;

if (!empty($_SESSION['gio_hang'])) {
    $danh_sach_id = array_keys($_SESSION['gio_hang']);

    // Tạo chuỗi (:id0, :id1, :id2...) để dùng trong IN(...)
    $placeholder = [];
    $tham_so = [];
    foreach ($danh_sach_id as $vi_tri => $id) {
        $ten_tham_so = ':id' . $vi_tri;
        $placeholder[] = $ten_tham_so;
        $tham_so[$ten_tham_so] = $id;
    }
    $chuoi_placeholder = implode(',', $placeholder);

    $cau_lenh = $ket_noi->prepare("
        SELECT * FROM sach
        WHERE id IN ($chuoi_placeholder)
    ");
    $cau_lenh->execute($tham_so);
    $ket_qua = $cau_lenh->fetchAll();

    // Ghép thông tin sách với số lượng trong session, đồng thời tính tiền
    foreach ($ket_qua as $sach) {
        $so_luong = $_SESSION['gio_hang'][$sach['id']]['so_luong'];
        $thanh_tien = $sach['gia_ban'] * $so_luong;
        $tong_tien += $thanh_tien;

        $danh_sach_gio_hang[] = [
            'sach'       => $sach,
            'so_luong'   => $so_luong,
            'thanh_tien' => $thanh_tien,
        ];
    }
}
?>

<h1>Giỏ hàng của bạn</h1>

<?php if (empty($danh_sach_gio_hang)): ?>
    <p>Giỏ hàng đang trống.</p>
    <a href="<?= URL_GOC ?>/danh-sach.php" class="nut-them-gio-hang">Tiếp tục mua sắm</a>
<?php else: ?>
    <form method="POST" action="<?= URL_GOC ?>/gio-hang.php">
        <table class="bang-gio-hang">
            <thead>
                <tr>
                    <th>Sách</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($danh_sach_gio_hang as $dong): ?>
                    <?php $sach = $dong['sach']; ?>
                    <tr>
                        <td class="cot-sach">
                            <img src="<?= lay_anh_bia($sach) ?>" alt="<?= htmlspecialchars($sach['tieu_de']) ?>">
                            <a href="<?= URL_GOC ?>/chi-tiet.php?id=<?= $sach['id'] ?>">
                                <?= htmlspecialchars($sach['tieu_de']) ?>
                            </a>
                        </td>
                        <td><?= dinh_dang_gia($sach['gia_ban']) ?></td>
                        <td>
                            <input type="number"
                                   name="so_luong[<?= $sach['id'] ?>]"
                                   value="<?= $dong['so_luong'] ?>"
                                   min="1" max="20" class="input-so-luong">
                        </td>
                        <td><?= dinh_dang_gia($dong['thanh_tien']) ?></td>
                        <td>
                            <a href="<?= URL_GOC ?>/gio-hang.php?xoa=<?= $sach['id'] ?>"
                               class="nut-xoa"
                               onclick="return confirm('Xoá sách này khỏi giỏ hàng?');">Xoá</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="khu-vuc-tong-tien">
            <button type="submit" name="cap_nhat" value="1" class="nut-cap-nhat">Cập nhật giỏ hàng</button>
            <p class="tong-tien">Tổng cộng: <span><?= dinh_dang_gia($tong_tien) ?></span></p>
        </div>
    </form>

    <div class="khu-vuc-thanh-toan">
        <a href="<?= URL_GOC ?>/danh-sach.php">&laquo; Tiếp tục mua sắm</a>
        <a href="<?= URL_GOC ?>/thanh-toan.php" class="nut-thanh-toan">Tiến hành thanh toán &raquo;</a>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>