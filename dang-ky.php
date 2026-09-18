<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/email.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

dam_bao_bang_xac_thuc_email($ket_noi);

if (da_dang_nhap()) {
    header('Location: ' . URL_GOC . '/index.php');
    exit;
}

$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';
    $nhap_lai_mk = $_POST['nhap_lai_mat_khau'] ?? '';
    $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');

    if ($ho_ten === '') {
        $loi[] = 'Vui lòng nhập họ tên.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi[] = 'Email không hợp lệ.';
    }

    if (strlen($mat_khau) < 6) {
        $loi[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if ($mat_khau !== $nhap_lai_mk) {
        $loi[] = 'Mật khẩu nhập lại không khớp.';
    }

    if (empty($loi)) {
        $cau_lenh = $ket_noi->prepare('SELECT id FROM nguoi_dung WHERE email = :email');
        $cau_lenh->execute(['email' => $email]);
        if ($cau_lenh->fetch()) {
            $loi[] = 'Email này đã được đăng ký, vui lòng dùng email khác.';
        }
    }

    if (empty($loi)) {
        $ma_xac_thuc = tao_ma_xac_thuc_email();
        $het_han = date('Y-m-d H:i:s', time() + 15 * 60);
        $mat_khau_ma_hoa = password_hash($mat_khau, PASSWORD_DEFAULT);

        $cau_lenh = $ket_noi->prepare("
            INSERT INTO nguoi_dung (
                ho_ten, email, mat_khau, so_dien_thoai,
                email_da_xac_thuc, ma_xac_thuc, ma_xac_thuc_het_han
            )
            VALUES (
                :ho_ten, :email, :mat_khau, :sdt,
                0, :ma_xac_thuc, :ma_xac_thuc_het_han
            )
        ");
        $cau_lenh->execute([
            'ho_ten' => $ho_ten,
            'email' => $email,
            'mat_khau' => $mat_khau_ma_hoa,
            'sdt' => $so_dien_thoai,
            'ma_xac_thuc' => $ma_xac_thuc,
            'ma_xac_thuc_het_han' => $het_han,
        ]);

        $loi_gui = '';
        if (!gui_ma_xac_thuc_email($email, $ho_ten, $ma_xac_thuc, $loi_gui)) {
            $loi[] = 'Tài khoản đã được tạo nhưng chưa gửi được mã xác thực: ' . $loi_gui;
            $loi[] = 'Kiểm tra lại app password Gmail trong config/email.php rồi bấm gửi lại mã ở trang xác thực.';
        } else {
            $_SESSION['email_can_xac_thuc'] = $email;
            header('Location: ' . URL_GOC . '/xac-thuc-email.php?email=' . urlencode($email));
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="trang-dang-nhap">
    <h1>Đăng ký tài khoản</h1>
    <p>Nhập Gmail thật để nhận mã xác thực tài khoản.</p>

    <?php if (!empty($loi)): ?>
        <div class="thong-bao-loi">
            <ul>
                <?php foreach ($loi as $thong_bao): ?>
                    <li><?= htmlspecialchars($thong_bao) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= URL_GOC ?>/dang-ky.php">
        <label>Họ tên</label>
        <input type="text" name="ho_ten" value="<?= htmlspecialchars($_POST['ho_ten'] ?? '') ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>Số điện thoại (không bắt buộc)</label>
        <input type="text" name="so_dien_thoai" value="<?= htmlspecialchars($_POST['so_dien_thoai'] ?? '') ?>">

        <label>Mật khẩu</label>
        <input type="password" name="mat_khau">

        <label>Nhập lại mật khẩu</label>
        <input type="password" name="nhap_lai_mat_khau">

        <button type="submit" class="nut-thanh-toan">Đăng ký và nhận mã</button>
    </form>

    <p>Đã có tài khoản? <a href="<?= URL_GOC ?>/dang-nhap.php">Đăng nhập ngay</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
