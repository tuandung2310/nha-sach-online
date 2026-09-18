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
// TÌM KIẾM NHANH THEO TÊN SÁCH (không bắt buộc)
// ============================================
$tu_khoa = isset($_GET['tu_khoa']) ? trim($_GET['tu_khoa']) : '';

if ($tu_khoa !== '') {
    $cau_lenh = $ket_noi->prepare("
        SELECT sach.*, dm_the_loai.ten_the_loai
        FROM sach
        LEFT JOIN dm_the_loai ON sach.the_loai = dm_the_loai.id
        WHERE sach.tieu_de LIKE :tu_khoa
        ORDER BY sach.id DESC
    ");
    $cau_lenh->execute(['tu_khoa' => '%' . $tu_khoa . '%']);
} else {
    $cau_lenh = $ket_noi->query("
        SELECT sach.*, dm_the_loai.ten_the_loai
        FROM sach
        LEFT JOIN dm_the_loai ON sach.the_loai = dm_the_loai.id
        ORDER BY sach.id DESC
    ");
}
$danh_sach_sach = $cau_lenh->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sách</title>
    <link rel="stylesheet" href="<?= URL_GOC ?>/css/style.css">
    <link rel="stylesheet" href="<?= URL_GOC ?>/css/admin.css">
</head>
<body>

<div class="khung-admin">
    <aside class="menu-admin">
        <h2>📚 Quản trị</h2>
        <ul>
            <li><a href="index.php">Tổng quan</a></li>
            <li><a href="sach.php" class="dang-chon">Quản lý sách</a></li>
            <li><a href="the-loai.php">Quản lý thể loại</a></li>
            <li><a href="don-hang.php">Quản lý đơn hàng</a></li>
        </ul>
        <p class="thong-tin-admin">
            Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung']) ?><br>
            <a href="<?= URL_GOC ?>/dang-xuat.php">Đăng xuất</a>
        </p>
    </aside>

    <main class="noi-dung-admin">
        <div class="tieu-de-trang-admin">
            <h1>Quản lý sách (<?= count($danh_sach_sach) ?>)</h1>
            <a href="sach-them.php" class="nut-thanh-toan">+ Thêm sách mới</a>
        </div>

        <form method="GET" class="form-tim-kiem-admin">
            <input type="text" name="tu_khoa" placeholder="Tìm theo tên sách..."
                   value="<?= htmlspecialchars($tu_khoa) ?>">
            <button type="submit">Tìm</button>
        </form>

        <?php if (empty($danh_sach_sach)): ?>
            <p>Không tìm thấy sách nào.</p>
        <?php else: ?>
            <table class="bang-admin">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Tiêu đề</th>
                        <th>Tác giả</th>
                        <th>Thể loại</th>
                        <th>Giá bán</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($danh_sach_sach as $sach): ?>
                        <tr>
                            <td><img src="<?= lay_anh_bia($sach) ?>" alt="" class="anh-nho-admin"></td>
                            <td><?= htmlspecialchars($sach['tieu_de']) ?></td>
                            <td><?= htmlspecialchars($sach['tac_gia']) ?></td>
                            <td><?= htmlspecialchars($sach['ten_the_loai'] ?? 'Chưa phân loại') ?></td>
                            <td><?= dinh_dang_gia($sach['gia_ban']) ?></td>
                            <td class="cot-hanh-dong">
                                <a href="sach-sua.php?id=<?= $sach['id'] ?>">Sửa</a>
                                <a href="sach-xoa.php?id=<?= $sach['id'] ?>"
                                   onclick="return confirm('Xoá sách này? Không thể hoàn tác!');">Xoá</a>
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
