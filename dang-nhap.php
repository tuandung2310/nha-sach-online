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
    $email = trim($_POST['email'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';

    if ($email === '' || $mat_khau === '') {
        $loi[] = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $cau_lenh = $ket_noi->prepare('SELECT * FROM nguoi_dung WHERE email = :email');
        $cau_lenh->execute(['email' => $email]);
        $nguoi_dung = $cau_lenh->fetch();

        if (!$nguoi_dung || !password_verify($mat_khau, $nguoi_dung['mat_khau'])) {
            $loi[] = 'Email hoặc mật khẩu không đúng.';
        } elseif (empty($nguoi_dung['email_da_xac_thuc'])) {
            $_SESSION['email_can_xac_thuc'] = $email;
            header('Location: ' . URL_GOC . '/xac-thuc-email.php?email=' . urlencode($email));
            exit;
        } else {
            $_SESSION['nguoi_dung_id'] = $nguoi_dung['id'];
            $_SESSION['ten_nguoi_dung'] = $nguoi_dung['ho_ten'];
            $_SESSION['la_admin'] = $nguoi_dung['la_admin'];

            header('Location: ' . URL_GOC . '/index.php');
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="trang-dang-nhap">
    <h1>Đăng nhập</h1>

    <?php if (!empty($loi)): ?>
        <div class="thong-bao-loi">
            <ul>
                <?php foreach ($loi as $thong_bao): ?>
                    <li><?= htmlspecialchars($thong_bao) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= URL_GOC ?>/dang-nhap.php">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>Mật khẩu</label>
        <input type="password" name="mat_khau">

        <button type="submit" class="nut-thanh-toan">Đăng nhập</button>
    </form>

    <p>Chưa có tài khoản? <a href="<?= URL_GOC ?>/dang-ky.php">Đăng ký ngay</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
