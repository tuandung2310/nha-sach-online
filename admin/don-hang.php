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

// Danh sách các trạng thái hợp lệ, dùng chung cho cả lọc và form đổi trạng thái
$cac_trang_thai = ['Chờ xác nhận', 'Đang giao', 'Hoàn thành', 'Đã huỷ'];

// ============================================
// XỬ LÝ ĐỔI TRẠNG THÁI ĐƠN HÀNG (khi submit form ở trang chi tiết)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doi_trang_thai'])) {
    $don_hang_id    = (int) ($_POST['don_hang_id'] ?? 0);
    $trang_thai_moi = $_POST['trang_thai'] ?? '';

    // Chỉ chấp nhận đúng các trạng thái đã định nghĩa sẵn, chặn dữ liệu lạ
    if ($don_hang_id > 0 && in_array($trang_thai_moi, $cac_trang_thai)) {
        $cau_lenh_cap_nhat = $ket_noi->prepare("
            UPDATE don_hang SET trang_thai = :trang_thai WHERE id = :id
        ");
        $cau_lenh_cap_nhat->execute([
            'trang_thai' => $trang_thai_moi,
            'id'         => $don_hang_id,
        ]);
    }

    header('Location: ' . URL_GOC . '/admin/don-hang.php?id=' . $don_hang_id);
    exit;
}

$don_hang_id_xem = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ============================================
// XEM CHI TIẾT 1 ĐƠN HÀNG
// ============================================
if ($don_hang_id_xem > 0) {
    $cau_lenh = $ket_noi->prepare("SELECT * FROM don_hang WHERE id = :id");
    $cau_lenh->execute(['id' => $don_hang_id_xem]);
    $don_hang = $cau_lenh->fetch();

    if (!$don_hang) {
        header('Location: ' . URL_GOC . '/admin/don-hang.php');
        exit;
    }

    $cau_lenh_ct = $ket_noi->prepare("
        SELECT chi_tiet_don_hang.*, sach.tieu_de
        FROM chi_tiet_don_hang
        LEFT JOIN sach ON chi_tiet_don_hang.sach_id = sach.id
        WHERE chi_tiet_don_hang.don_hang_id = :id
    ");
    $cau_lenh_ct->execute(['id' => $don_hang_id_xem]);
    $chi_tiet = $cau_lenh_ct->fetchAll();
}
// ============================================
// DANH SÁCH TẤT CẢ ĐƠN HÀNG (có lọc theo trạng thái)
// ============================================
else {
    $loc_trang_thai = isset($_GET['trang_thai']) ? $_GET['trang_thai'] : '';

    if ($loc_trang_thai !== '' && in_array($loc_trang_thai, $cac_trang_thai)) {
        $cau_lenh_ds = $ket_noi->prepare("
            SELECT * FROM don_hang WHERE trang_thai = :trang_thai ORDER BY ngay_dat DESC
        ");
        $cau_lenh_ds->execute(['trang_thai' => $loc_trang_thai]);
    } else {
        $cau_lenh_ds = $ket_noi->query("SELECT * FROM don_hang ORDER BY ngay_dat DESC");
    }
    $danh_sach_don_hang = $cau_lenh_ds->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
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
            <li><a href="don-hang.php" class="dang-chon">Quản lý đơn hàng</a></li>
        </ul>
        <p class="thong-tin-admin">
            Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung']) ?><br>
            <a href="<?= URL_GOC ?>/dang-xuat.php">Đăng xuất</a>
        </p>
    </aside>

    <main class="noi-dung-admin">

        <?php if ($don_hang_id_xem > 0): ?>
            <!-- ============ GIAO DIỆN CHI TIẾT 1 ĐƠN HÀNG ============ -->
            <div class="tieu-de-trang-admin">
                <h1>Đơn hàng #<?= $don_hang['id'] ?></h1>
                <a href="don-hang.php">&laquo; Quay lại danh sách</a>
            </div>

            <div class="chi-tiet-don-hang-admin">
                <p><strong>Người nhận:</strong> <?= htmlspecialchars($don_hang['ten_nguoi_nhan']) ?></p>
                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($don_hang['so_dien_thoai']) ?></p>
                <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($don_hang['dia_chi']) ?></p>
                <?php if (!empty($don_hang['ghi_chu'])): ?>
                    <p><strong>Ghi chú:</strong> <?= htmlspecialchars($don_hang['ghi_chu']) ?></p>
                <?php endif; ?>
                <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($don_hang['ngay_dat'])) ?></p>

                <table class="bang-admin">
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

                <p class="tong-tien" style="margin-top:12px;">
                    Tổng cộng: <span><?= dinh_dang_gia($don_hang['tong_tien']) ?></span>
                </p>

                <!-- Form đổi trạng thái -->
                <form method="POST" action="don-hang.php" class="form-doi-trang-thai">
                    <input type="hidden" name="don_hang_id" value="<?= $don_hang['id'] ?>">
                    <label>Trạng thái đơn hàng</label>
                    <select name="trang_thai">
                        <?php foreach ($cac_trang_thai as $trang_thai): ?>
                            <option value="<?= $trang_thai ?>" <?= $don_hang['trang_thai'] === $trang_thai ? 'selected' : '' ?>>
                                <?= $trang_thai ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="doi_trang_thai" value="1" class="nut-thanh-toan">Cập nhật trạng thái</button>
                </form>
            </div>

        <?php else: ?>
            <!-- ============ GIAO DIỆN DANH SÁCH ĐƠN HÀNG ============ -->
            <div class="tieu-de-trang-admin">
                <h1>Quản lý đơn hàng (<?= count($danh_sach_don_hang) ?>)</h1>
            </div>

            <!-- Lọc theo trạng thái -->
            <div class="khu-vuc-sap-xep" style="margin-bottom:16px;">
                <a href="don-hang.php" class="<?= $loc_trang_thai === '' ? 'dang-chon' : '' ?>">Tất cả</a>
                <?php foreach ($cac_trang_thai as $trang_thai): ?>
                    <a href="?trang_thai=<?= urlencode($trang_thai) ?>"
                       class="<?= $loc_trang_thai === $trang_thai ? 'dang-chon' : '' ?>"><?= $trang_thai ?></a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($danh_sach_don_hang)): ?>
                <p>Không có đơn hàng nào.</p>
            <?php else: ?>
                <table class="bang-admin">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Người nhận</th>
                            <th>SĐT</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danh_sach_don_hang as $don): ?>
                            <tr>
                                <td>#<?= $don['id'] ?></td>
                                <td><?= htmlspecialchars($don['ten_nguoi_nhan']) ?></td>
                                <td><?= htmlspecialchars($don['so_dien_thoai']) ?></td>
                                <td><?= dinh_dang_gia($don['tong_tien']) ?></td>
                                <td>
                                    <span class="nhan-trang-thai"><?= htmlspecialchars($don['trang_thai']) ?></span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($don['ngay_dat'])) ?></td>
                                <td><a href="?id=<?= $don['id'] ?>">Xem / Sửa</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
