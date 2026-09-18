<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/header.php';

// ============================================
// 1. LẤY DANH SÁCH GIỎ HÀNG (giống gio-hang.php)
// ============================================
$danh_sach_gio_hang = [];
$tong_tien = 0;

if (!empty($_SESSION['gio_hang'])) {
    $danh_sach_id = array_keys($_SESSION['gio_hang']);

    $placeholder = [];
    $tham_so = [];
    foreach ($danh_sach_id as $vi_tri => $id) {
        $ten_tham_so = ':id' . $vi_tri;
        $placeholder[] = $ten_tham_so;
        $tham_so[$ten_tham_so] = $id;
    }
    $chuoi_placeholder = implode(',', $placeholder);

    $cau_lenh = $ket_noi->prepare("SELECT * FROM sach WHERE id IN ($chuoi_placeholder)");
    $cau_lenh->execute($tham_so);
    $ket_qua = $cau_lenh->fetchAll();

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

// Nếu giỏ hàng trống thì không cho đặt hàng, đá về trang giỏ hàng
if (empty($danh_sach_gio_hang)) {
    header('Location: ' . URL_GOC . '/gio-hang.php');
    exit;
}

// ============================================
// 2. XỬ LÝ KHI NGƯỜI DÙNG SUBMIT FORM ĐẶT HÀNG
// ============================================
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_nguoi_nhan = trim($_POST['ten_nguoi_nhan'] ?? '');
    $so_dien_thoai  = trim($_POST['so_dien_thoai'] ?? '');
    $dia_chi        = trim($_POST['dia_chi'] ?? '');
    $ghi_chu        = trim($_POST['ghi_chu'] ?? '');

    // Kiểm tra dữ liệu bắt buộc
    if ($ten_nguoi_nhan === '') {
        $loi[] = 'Vui lòng nhập họ tên người nhận.';
    }
    if ($so_dien_thoai === '' || !preg_match('/^[0-9]{9,11}$/', $so_dien_thoai)) {
        $loi[] = 'Số điện thoại không hợp lệ (chỉ gồm 9-11 chữ số).';
    }
    if ($dia_chi === '') {
        $loi[] = 'Vui lòng nhập địa chỉ giao hàng.';
    }

    // Nếu không có lỗi thì tiến hành lưu đơn hàng
    if (empty($loi)) {
        try {
            // Bắt đầu transaction: đảm bảo lưu đơn hàng + chi tiết đơn hàng
            // hoặc thành công cả hai, hoặc không lưu gì cả (tránh dữ liệu nửa vời)
            $ket_noi->beginTransaction();

            // Lưu bảng don_hang trước
            $cau_lenh_don = $ket_noi->prepare("
    INSERT INTO don_hang (nguoi_dung_id, ten_nguoi_nhan, so_dien_thoai, dia_chi, ghi_chu, tong_tien)
    VALUES (:nguoi_dung_id, :ten, :dt, :dia_chi, :ghi_chu, :tong_tien)
");
$cau_lenh_don->execute([
    'nguoi_dung_id' => $_SESSION['nguoi_dung_id'] ?? null, // null nếu khách chưa đăng nhập
    'ten'           => $ten_nguoi_nhan,
    'dt'            => $so_dien_thoai,
    'dia_chi'       => $dia_chi,
    'ghi_chu'       => $ghi_chu,
    'tong_tien'     => $tong_tien,
]);

            // Lấy id đơn hàng vừa tạo, để gắn vào từng dòng chi tiết
            $don_hang_id = $ket_noi->lastInsertId();

            // Lưu từng sách trong giỏ vào bảng chi_tiet_don_hang
            $cau_lenh_ct = $ket_noi->prepare("
                INSERT INTO chi_tiet_don_hang (don_hang_id, sach_id, so_luong, don_gia)
                VALUES (:don_hang_id, :sach_id, :so_luong, :don_gia)
            ");
            foreach ($danh_sach_gio_hang as $dong) {
                $cau_lenh_ct->execute([
                    'don_hang_id' => $don_hang_id,
                    'sach_id'     => $dong['sach']['id'],
                    'so_luong'    => $dong['so_luong'],
                    'don_gia'     => $dong['sach']['gia_ban'],
                ]);
            }

            // Mọi thứ ổn -> ghi thật vào DB
            $ket_noi->commit();

            // Xoá giỏ hàng sau khi đặt thành công
            unset($_SESSION['gio_hang']);

            // Chuyển sang trang thông báo thành công, kèm mã đơn hàng
            header('Location: ' . URL_GOC . '/dat-hang-thanh-cong.php?ma_don=' . $don_hang_id);
            exit;

        } catch (Exception $e) {
            // Có lỗi ở bất kỳ bước nào -> huỷ hết, không lưu gì cả
            $ket_noi->rollBack();
            $loi[] = 'Có lỗi xảy ra khi đặt hàng, vui lòng thử lại.';
        }
    }
}
?>

<h1>Thanh toán</h1>

<div class="trang-thanh-toan">
    <!-- Cột trái: form thông tin giao hàng -->
    <div class="cot-form-thanh-toan">
        <h2>Thông tin giao hàng</h2>

        <?php if (!empty($loi)): ?>
            <div class="thong-bao-loi">
                <ul>
                    <?php foreach ($loi as $thong_bao): ?>
                        <li><?= htmlspecialchars($thong_bao) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= URL_GOC ?>/thanh-toan.php">
            <label>Họ tên người nhận</label>
            <input type="text" name="ten_nguoi_nhan"
                   value="<?= htmlspecialchars($_POST['ten_nguoi_nhan'] ?? '') ?>">

            <label>Số điện thoại</label>
            <input type="text" name="so_dien_thoai"
                   value="<?= htmlspecialchars($_POST['so_dien_thoai'] ?? '') ?>">

            <label>Địa chỉ giao hàng</label>
            <input type="text" name="dia_chi"
                   value="<?= htmlspecialchars($_POST['dia_chi'] ?? '') ?>">

            <label>Ghi chú (không bắt buộc)</label>
            <textarea name="ghi_chu" rows="3"><?= htmlspecialchars($_POST['ghi_chu'] ?? '') ?></textarea>

            <button type="submit" class="nut-thanh-toan">Đặt hàng</button>
        </form>
    </div>

    <!-- Cột phải: tóm tắt đơn hàng -->
    <div class="cot-tom-tat-don-hang">
        <h2>Đơn hàng của bạn</h2>
        <table class="bang-tom-tat">
            <?php foreach ($danh_sach_gio_hang as $dong): ?>
                <tr>
                    <td><?= htmlspecialchars($dong['sach']['tieu_de']) ?> x <?= $dong['so_luong'] ?></td>
                    <td><?= dinh_dang_gia($dong['thanh_tien']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="tong-tien">Tổng cộng: <span><?= dinh_dang_gia($tong_tien) ?></span></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>