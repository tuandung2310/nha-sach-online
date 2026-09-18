<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=UTF-8');

$ds_the_loai = $ket_noi->query("SELECT * FROM dm_the_loai ORDER BY ten_the_loai")->fetchAll();

$so_luong_gio_hang = 0;
if (!empty($_SESSION['gio_hang'])) {
    foreach ($_SESSION['gio_hang'] as $item) {
        $so_luong_gio_hang += $item['so_luong'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhà Sách Online</title>
    <link rel="stylesheet" href="<?= URL_GOC ?>/css/style.css">
</head>
<body>

<header class="header">
    <div class="header-top">
        <a href="<?= URL_GOC ?>/index.php" class="logo">NHÀ SÁCH ONLINE</a>

        <form action="<?= URL_GOC ?>/tim-kiem.php" method="GET" class="form-tim-kiem">
            <input type="text" name="tu_khoa" placeholder="Tìm tên sách, tác giả..."
                   value="<?= isset($_GET['tu_khoa']) ? htmlspecialchars($_GET['tu_khoa']) : '' ?>">
            <button type="submit">Tìm</button>
        </form>

        <a href="<?= URL_GOC ?>/gio-hang.php" class="gio-hang-icon">
            Giỏ hàng
            <span class="so-luong-badge"><?= $so_luong_gio_hang ?></span>
        </a>

        <?php if (!empty($_SESSION['admin_id']) && la_admin()): ?>
            <a href="<?= URL_GOC ?>/admin/index.php">
                Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung']) ?>
            </a>
            <a href="<?= URL_GOC ?>/admin/index.php">Quản trị</a>
            <a href="<?= URL_GOC ?>/dang-xuat.php">Đăng xuất</a>
        <?php elseif (!empty($_SESSION['nguoi_dung_id'])): ?>
            <a href="<?= URL_GOC ?>/tai-khoan.php">
                Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung']) ?>
            </a>
            <a href="<?= URL_GOC ?>/dang-xuat.php">Đăng xuất</a>
            <?php if (la_admin()): ?>
                <a href="<?= URL_GOC ?>/admin/index.php">Quản trị</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?= URL_GOC ?>/dang-nhap.php">Đăng nhập</a>
            <a href="<?= URL_GOC ?>/dang-ky.php">Đăng ký</a>
        <?php endif; ?>
    </div>

    <nav class="menu-the-loai">
        <ul>
            <li><a href="<?= URL_GOC ?>/danh-sach.php">Tất cả sách</a></li>
            <?php foreach ($ds_the_loai as $tl): ?>
                <li>
                    <a href="<?= URL_GOC ?>/danh-sach.php?the_loai=<?= $tl['id'] ?>">
                        <?= htmlspecialchars($tl['ten_the_loai']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</header>

<main class="noi-dung-chinh">
