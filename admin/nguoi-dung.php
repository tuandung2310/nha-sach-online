<?php
require_once '../config/ket_noi.php';
require_once '../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id']) || !la_admin()) {
    header('Location: ' . URL_GOC . '/admin/dang-nhap.php');
    exit;
}

$thong_bao = '';
$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanh_dong = $_POST['hanh_dong'] ?? '';
    $nguoi_dung_id = (int) ($_POST['nguoi_dung_id'] ?? 0);

    if ($nguoi_dung_id <= 0) {
        $loi = 'Tài khoản không hợp lệ.';
    } elseif ($hanh_dong === 'xac_thuc_nguoi_dung') {
        $cau_lenh = $ket_noi->prepare('SELECT * FROM nguoi_dung WHERE id = :id');
        $cau_lenh->execute(['id' => $nguoi_dung_id]);
        $nguoi_dung = $cau_lenh->fetch();

        if (!$nguoi_dung) {
            $loi = 'Không tìm thấy tài khoản cần xác thực.';
        } elseif (str_contains($nguoi_dung['email'], '@deleted.local')) {
            $loi = 'Không thể xác thực tài khoản đã bị ẩn.';
        } else {
            $cap_nhat = $ket_noi->prepare("
                UPDATE nguoi_dung
                SET email_da_xac_thuc = 1,
                    ma_xac_thuc = NULL,
                    ma_xac_thuc_het_han = NULL,
                    ngay_xac_thuc = NOW()
                WHERE id = :id
            ");
            $cap_nhat->execute(['id' => $nguoi_dung_id]);
            $thong_bao = 'Đã xác thực thủ công tài khoản. Người dùng có thể đăng nhập ngay.';
        }
    } elseif ($hanh_dong === 'xoa_nguoi_dung') {
        $cau_lenh = $ket_noi->prepare('SELECT * FROM nguoi_dung WHERE id = :id');
        $cau_lenh->execute(['id' => $nguoi_dung_id]);
        $nguoi_dung = $cau_lenh->fetch();

        if (!$nguoi_dung) {
            $loi = 'Không tìm thấy tài khoản cần xóa.';
        } else {
            $dem_don = $ket_noi->prepare('SELECT COUNT(*) FROM don_hang WHERE nguoi_dung_id = :id');
            $dem_don->execute(['id' => $nguoi_dung_id]);
            $so_don_hang = (int) $dem_don->fetchColumn();

            $dem_danh_gia = $ket_noi->prepare('SELECT COUNT(*) FROM danh_gia WHERE nguoi_dung_id = :id');
            $dem_danh_gia->execute(['id' => $nguoi_dung_id]);
            $so_danh_gia = (int) $dem_danh_gia->fetchColumn();

            if ($so_don_hang === 0 && $so_danh_gia === 0) {
                $xoa = $ket_noi->prepare('DELETE FROM nguoi_dung WHERE id = :id');
                $xoa->execute(['id' => $nguoi_dung_id]);
                $thong_bao = 'Đã xóa vĩnh viễn tài khoản chưa có đơn hàng/đánh giá.';
            } else {
                $email_moi = 'deleted_user_' . $nguoi_dung_id . '_' . time() . '@deleted.local';
                $mat_khau_ngau_nhien = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

                $cap_nhat = $ket_noi->prepare("
                    UPDATE nguoi_dung
                    SET ho_ten = :ho_ten,
                        email = :email,
                        mat_khau = :mat_khau,
                        so_dien_thoai = NULL,
                        dia_chi = NULL,
                        email_da_xac_thuc = 0,
                        ma_xac_thuc = NULL,
                        ma_xac_thuc_het_han = NULL
                    WHERE id = :id
                ");
                $cap_nhat->execute([
                    'ho_ten' => 'Tài khoản đã xóa',
                    'email' => $email_moi,
                    'mat_khau' => $mat_khau_ngau_nhien,
                    'id' => $nguoi_dung_id,
                ]);

                $thong_bao = 'Tài khoản đã có dữ liệu nên đã được ẩn và giải phóng email cũ để đăng ký lại.';
            }
        }
    }
}

$tu_khoa = trim($_GET['tu_khoa'] ?? '');

$sql = "
    SELECT
        nguoi_dung.*,
        COUNT(DISTINCT don_hang.id) AS so_don_hang,
        COUNT(DISTINCT danh_gia.id) AS so_danh_gia
    FROM nguoi_dung
    LEFT JOIN don_hang ON don_hang.nguoi_dung_id = nguoi_dung.id
    LEFT JOIN danh_gia ON danh_gia.nguoi_dung_id = nguoi_dung.id
";

$tham_so = [];
if ($tu_khoa !== '') {
    $sql .= " WHERE nguoi_dung.ho_ten LIKE :tu_khoa OR nguoi_dung.email LIKE :tu_khoa";
    $tham_so['tu_khoa'] = '%' . $tu_khoa . '%';
}

$sql .= " GROUP BY nguoi_dung.id ORDER BY nguoi_dung.id DESC";

$cau_lenh = $ket_noi->prepare($sql);
$cau_lenh->execute($tham_so);
$danh_sach_nguoi_dung = $cau_lenh->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="<?= URL_GOC ?>/css/style.css">
    <link rel="stylesheet" href="<?= URL_GOC ?>/css/admin.css">
</head>
<body>

<div class="khung-admin">
    <aside class="menu-admin">
        <h2>📚 Quản trị</h2>
        <ul>
            <li><a href="index.php">Tổng quan</a></li>
            <li><a href="sach.php">Quản lý sách</a></li>
            <li><a href="the-loai.php">Quản lý thể loại</a></li>
            <li><a href="don-hang.php">Quản lý đơn hàng</a></li>
            <li><a href="nguoi-dung.php" class="dang-chon">Quản lý người dùng</a></li>
        </ul>
        <p class="thong-tin-admin">
            Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung']) ?><br>
            <a href="<?= URL_GOC ?>/dang-xuat.php">Đăng xuất</a>
        </p>
    </aside>

    <main class="noi-dung-admin">
        <div class="tieu-de-trang-admin">
            <h1>Quản lý người dùng (<?= count($danh_sach_nguoi_dung) ?>)</h1>
        </div>

        <?php if ($thong_bao): ?>
            <div class="thong-bao-thanh-cong"><?= htmlspecialchars($thong_bao) ?></div>
        <?php endif; ?>

        <?php if ($loi): ?>
            <div class="thong-bao-loi"><?= htmlspecialchars($loi) ?></div>
        <?php endif; ?>

        <form method="GET" class="form-tim-kiem-admin">
            <input type="text" name="tu_khoa" placeholder="Tìm theo tên hoặc email..."
                   value="<?= htmlspecialchars($tu_khoa) ?>">
            <button type="submit">Tìm</button>
            <?php if ($tu_khoa !== ''): ?>
                <a href="nguoi-dung.php" class="nut-lam-moi-admin">Xóa lọc</a>
            <?php endif; ?>
        </form>

        <?php if (empty($danh_sach_nguoi_dung)): ?>
            <p>Không tìm thấy người dùng nào.</p>
        <?php else: ?>
            <table class="bang-admin bang-nguoi-dung-admin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Xác thực Gmail</th>
                        <th>Đơn hàng</th>
                        <th>Đánh giá</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($danh_sach_nguoi_dung as $nguoi_dung): ?>
                        <?php
                        $la_tai_khoan_da_xoa = str_contains($nguoi_dung['email'], '@deleted.local');
                        $da_xac_thuc = !empty($nguoi_dung['email_da_xac_thuc']);
                        ?>
                        <tr>
                            <td>#<?= $nguoi_dung['id'] ?></td>
                            <td><?= htmlspecialchars($nguoi_dung['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($nguoi_dung['email']) ?></td>
                            <td><?= htmlspecialchars($nguoi_dung['so_dien_thoai'] ?: 'Chưa có') ?></td>
                            <td>
                                <?php if ($da_xac_thuc): ?>
                                    <span class="nhan-admin nhan-admin--ok">Đã xác thực</span>
                                <?php else: ?>
                                    <span class="nhan-admin nhan-admin--cho">Chưa xác thực</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $nguoi_dung['so_don_hang'] ?></td>
                            <td><?= (int) $nguoi_dung['so_danh_gia'] ?></td>
                            <td><?= !empty($nguoi_dung['ngay_tao']) ? date('d/m/Y H:i', strtotime($nguoi_dung['ngay_tao'])) : 'Không rõ' ?></td>
                            <td class="cot-thao-tac-nguoi-dung">
                                <?php if ($la_tai_khoan_da_xoa): ?>
                                    <span class="nhan-admin nhan-admin--cho">Đã ẩn</span>
                                <?php else: ?>
                                    <?php if (!$da_xac_thuc): ?>
                                        <form method="POST" class="form-thao-tac-nguoi-dung-admin"
                                              onsubmit="return confirm('Xác thực thủ công tài khoản này? Người dùng sẽ đăng nhập được ngay mà không cần mã Gmail.');">
                                            <input type="hidden" name="hanh_dong" value="xac_thuc_nguoi_dung">
                                            <input type="hidden" name="nguoi_dung_id" value="<?= $nguoi_dung['id'] ?>">
                                            <button type="submit" class="nut-xac-thuc-nguoi-dung-admin">Xác thực</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" class="form-thao-tac-nguoi-dung-admin"
                                          onsubmit="return confirm('Xóa tài khoản này? Nếu đã có đơn hàng/đánh giá, hệ thống sẽ ẩn tài khoản và giải phóng email để đăng ký lại.');">
                                        <input type="hidden" name="hanh_dong" value="xoa_nguoi_dung">
                                        <input type="hidden" name="nguoi_dung_id" value="<?= $nguoi_dung['id'] ?>">
                                        <button type="submit" class="nut-xoa-nguoi-dung-admin">Xóa</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</div>

</body>
</html>
