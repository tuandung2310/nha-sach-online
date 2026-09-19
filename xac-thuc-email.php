<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/email.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

dam_bao_bang_xac_thuc_email($ket_noi);

$email = trim($_GET['email'] ?? $_POST['email'] ?? $_SESSION['email_can_xac_thuc'] ?? '');
$loi = [];
$thong_bao = '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $loi[] = 'Không tìm thấy email cần xác thực.';
}

if (isset($_GET['kiem_tra']) && $_GET['kiem_tra'] === '1') {
    header('Content-Type: application/json; charset=UTF-8');

    if (!empty($loi)) {
        echo json_encode(['ok' => false, 'da_xac_thuc' => false]);
        exit;
    }

    $cau_lenh = $ket_noi->prepare('SELECT email_da_xac_thuc FROM nguoi_dung WHERE email = :email');
    $cau_lenh->execute(['email' => $email]);
    $nguoi_dung_kiem_tra = $cau_lenh->fetch();

    echo json_encode([
        'ok' => (bool) $nguoi_dung_kiem_tra,
        'da_xac_thuc' => !empty($nguoi_dung_kiem_tra['email_da_xac_thuc']),
        'login_url' => URL_GOC . '/dang-nhap.php',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($loi)) {
    $hanh_dong = $_POST['hanh_dong'] ?? 'xac_thuc';

    $cau_lenh = $ket_noi->prepare('SELECT * FROM nguoi_dung WHERE email = :email');
    $cau_lenh->execute(['email' => $email]);
    $nguoi_dung = $cau_lenh->fetch();

    if (!$nguoi_dung) {
        $loi[] = 'Không tìm thấy tài khoản với email này.';
    } elseif (!empty($nguoi_dung['email_da_xac_thuc'])) {
        $thong_bao = 'Email này đã được xác thực, bạn có thể đăng nhập.';
    } elseif ($hanh_dong === 'gui_lai') {
        $ma_moi = tao_ma_xac_thuc_email();
        $het_han = date('Y-m-d H:i:s', time() + 15 * 60);

        $cap_nhat = $ket_noi->prepare("
            UPDATE nguoi_dung
            SET ma_xac_thuc = :ma_xac_thuc,
                ma_xac_thuc_het_han = :ma_xac_thuc_het_han
            WHERE id = :id
        ");
        $cap_nhat->execute([
            'ma_xac_thuc' => $ma_moi,
            'ma_xac_thuc_het_han' => $het_han,
            'id' => $nguoi_dung['id'],
        ]);

        $loi_gui = '';
        if (gui_ma_xac_thuc_email($email, $nguoi_dung['ho_ten'], $ma_moi, $loi_gui)) {
            $thong_bao = 'Đã gửi lại mã xác thực về Gmail của bạn.';
        } else {
            $loi[] = 'Chưa gửi lại được mã: ' . $loi_gui;
        }
    } else {
        $ma_nhap = trim($_POST['ma_xac_thuc'] ?? '');

        if ($ma_nhap === '') {
            $loi[] = 'Vui lòng nhập mã xác thực.';
        } elseif ($ma_nhap !== $nguoi_dung['ma_xac_thuc']) {
            $loi[] = 'Mã xác thực không đúng.';
        } elseif (strtotime($nguoi_dung['ma_xac_thuc_het_han']) < time()) {
            $loi[] = 'Mã xác thực đã hết hạn, vui lòng bấm gửi lại mã.';
        } else {
            $cap_nhat = $ket_noi->prepare("
                UPDATE nguoi_dung
                SET email_da_xac_thuc = 1,
                    ma_xac_thuc = NULL,
                    ma_xac_thuc_het_han = NULL,
                    ngay_xac_thuc = NOW()
                WHERE id = :id
            ");
            $cap_nhat->execute(['id' => $nguoi_dung['id']]);

            unset($_SESSION['email_can_xac_thuc']);
            $_SESSION['nguoi_dung_id'] = $nguoi_dung['id'];
            $_SESSION['ten_nguoi_dung'] = $nguoi_dung['ho_ten'];
            $_SESSION['la_admin'] = 0;

            header('Location: ' . URL_GOC . '/index.php');
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="trang-dang-nhap">
    <h1>Xác thực Gmail</h1>
    <p>Nhập mã 6 số đã gửi tới <?= htmlspecialchars($email) ?>.</p>

    <div id="thong-bao-admin-xac-thuc" class="thong-bao-thanh-cong" style="display:none">
        Tài khoản đã được admin xác thực. Bạn sẽ được chuyển về trang đăng nhập sau vài giây.
    </div>

    <?php if ($thong_bao): ?>
        <div class="thong-bao-thanh-cong"><?= htmlspecialchars($thong_bao) ?></div>
    <?php endif; ?>

    <?php if (!empty($loi)): ?>
        <div class="thong-bao-loi">
            <ul>
                <?php foreach ($loi as $thong_bao_loi): ?>
                    <li><?= htmlspecialchars($thong_bao_loi) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= URL_GOC ?>/xac-thuc-email.php">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

        <label>Mã xác thực</label>
        <input type="text" name="ma_xac_thuc" maxlength="6" inputmode="numeric" placeholder="Nhập mã 6 số">

        <button type="submit" name="hanh_dong" value="xac_thuc" class="nut-thanh-toan">Xác thực tài khoản</button>
        <button type="submit" name="hanh_dong" value="gui_lai" class="nut-phu">Gửi lại mã</button>
    </form>

    <p><a href="<?= URL_GOC ?>/dang-nhap.php">Quay lại đăng nhập</a></p>
</div>

<?php if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)): ?>
<script>
(function () {
    const email = <?= json_encode($email) ?>;
    const hopThongBao = document.getElementById('thong-bao-admin-xac-thuc');
    const urlDangNhap = <?= json_encode(URL_GOC . '/dang-nhap.php') ?>;
    const urlKiemTra = <?= json_encode(URL_GOC . '/xac-thuc-email.php') ?>;

    async function kiemTraXacThuc() {
        try {
            const phanHoi = await fetch(
                urlKiemTra + '?kiem_tra=1&email=' + encodeURIComponent(email) + '&t=' + Date.now(),
                { cache: 'no-store' }
            );
            const duLieu = await phanHoi.json();

            if (duLieu.ok && duLieu.da_xac_thuc) {
                hopThongBao.style.display = 'block';
                setTimeout(function () {
                    window.location.href = urlDangNhap;
                }, 2500);
                return;
            }

            setTimeout(kiemTraXacThuc, 5000);
        } catch (e) {
            setTimeout(kiemTraXacThuc, 8000);
        }
    }

    setTimeout(kiemTraXacThuc, 3000);
})();
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
