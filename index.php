<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$duong_dan_hien_tai = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$la_link_goc = $duong_dan_hien_tai === rtrim(URL_GOC, '/') . '/';
$che_do_admin = isset($_GET['admin']) && $_GET['admin'] == '1';

if ($la_link_goc && !$che_do_admin) {
    unset(
        $_SESSION['nguoi_dung_id'],
        $_SESSION['admin_id'],
        $_SESSION['ten_nguoi_dung'],
        $_SESSION['la_admin']
    );
}

require_once 'includes/header.php';
require_once 'admin/_layout.php';

$sach_moi = $ket_noi->query("
    SELECT * FROM sach
    ORDER BY id DESC
    LIMIT 8
")->fetchAll();

$ds_the_loai = $ket_noi->query("SELECT * FROM dm_the_loai ORDER BY id")->fetchAll();
?>

<?php if (empty($_SESSION['admin_id']) && empty($_SESSION['nguoi_dung_id'])): ?>
    <section class="hop-tai-khoan-nhanh">
        <span>Ban muon mua hang va danh gia sach?</span>
        <div>
            <a href="<?= URL_GOC ?>/dang-nhap.php">Dang nhap</a>
            <a href="<?= URL_GOC ?>/dang-ky.php">Dang ky</a>
        </div>
    </section>
<?php endif; ?>

<?php if ($che_do_admin && !empty($_SESSION['admin_id']) && la_admin()): ?>
    <section class="loi-tat-quan-tri">
        <div>
            <p class="nhan-quan-tri">KHU VỰC QUẢN TRỊ</p>
            <h1>Xin chào, <?= htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? 'Quản trị viên') ?></h1>
            <p>Truy cập nhanh các chức năng quản lý cửa hàng.</p>
        </div>
        <div class="nut-loi-tat-admin">
            <a href="<?= URL_GOC ?>/admin/sach-them.php">Thêm sách</a>
            <a href="<?= URL_GOC ?>/admin/index.php">Tổng quan</a>
            <a href="<?= URL_GOC ?>/admin/sach.php">Quản lý sách</a>
            <a href="<?= URL_GOC ?>/admin/don-hang.php">Đơn hàng</a>
        </div>
    </section>
    
    <section class="them-sach-nhanh">
        <div class="them-sach-nhanh__head">
            <div>
                <p>THAO TÁC NHANH</p>
                <h2>Thêm sách mới</h2>
            </div>
            <a href="<?= URL_GOC ?>/admin/sach.php">Xem toàn bộ sách</a>
        </div>
        <form action="<?= URL_GOC ?>/admin/sach-them.php" method="post" enctype="multipart/form-data" class="form-them-nhanh">
            <input type="hidden" name="csrf" value="<?= admin_csrf() ?>">
            <div class="form-them-nhanh__grid">
                <label class="rong">Tiêu đề sách *<input required name="tieu_de" placeholder="Nhập tên sách"></label>
                <label>Tác giả<input name="tac_gia" placeholder="Tên tác giả"></label>
                <label>Thể loại<select name="the_loai"><option value="">-- Chọn thể loại --</option><?php foreach ($ds_the_loai as $tl): ?><option value="<?= $tl['id'] ?>"><?= htmlspecialchars($tl['ten_the_loai']) ?></option><?php endforeach; ?></select></label>
                <label>Giá bán (VNĐ) *<input required min="1" step="1000" type="number" name="gia_ban" placeholder="Ví dụ: 120000"></label>
                <label>Hình thức bìa<input name="hinh_thuc_bia" placeholder="Bìa mềm, bìa cứng..."></label>
                <label>Nhà xuất bản<input name="nha_xuat_ban"></label>
                <label>Nhà cung cấp<input name="nha_cung_cap"></label>
                <label>Ảnh bìa<input type="file" name="anh_bia" accept=".jpg,.jpeg,.png,.webp"></label>
                <label>Hoặc link ảnh<input type="url" name="link_anh_bia" placeholder="https://..."></label>
                <label class="rong">Mô tả<textarea name="mo_ta" rows="4" placeholder="Giới thiệu ngắn về sách..."></textarea></label>
            </div>
            <div class="form-them-nhanh__footer"><span>Ảnh: JPG, PNG, WEBP · tối đa 2MB</span><button type="submit">+ Thêm sách</button></div>
        </form>
    </section>
<?php endif; ?>

<section class="khoi-sach">
    <div class="tieu-de-khoi">
        <h2>Sách mới</h2>
        <a href="<?= URL_GOC ?>/danh-sach.php">Xem tất cả &raquo;</a>
    </div>

    <div class="luoi-sach">
        <?php foreach ($sach_moi as $sach): ?>
            <div class="the-sach">
                <a href="<?= URL_GOC ?>/chi-tiet.php?id=<?= $sach['id'] ?>">
                    <img src="<?= lay_anh_bia($sach) ?>" alt="<?= htmlspecialchars($sach['tieu_de']) ?>">
                    <h3 class="ten-sach"><?= htmlspecialchars($sach['tieu_de']) ?></h3>
                </a>
                <p class="tac-gia"><?= htmlspecialchars($sach['tac_gia']) ?></p>
                <p class="gia"><?= dinh_dang_gia($sach['gia_ban']) ?></p>
                <?php if ($che_do_admin && !empty($_SESSION['admin_id']) && la_admin()): ?>
                    <div class="thao-tac-sach-admin">
                        <a class="nut-sua-sach" href="<?= URL_GOC ?>/admin/sach-sua.php?id=<?= $sach['id'] ?>">Sửa</a>
                        <a class="nut-xoa-sach" href="<?= URL_GOC ?>/admin/sach-xoa.php?id=<?= $sach['id'] ?>">Xóa</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php foreach ($ds_the_loai as $the_loai): ?>
    <?php
    $cau_lenh = $ket_noi->prepare("
        SELECT * FROM sach
        WHERE the_loai = :the_loai
        ORDER BY id DESC
        LIMIT 8
    ");
    $cau_lenh->execute(['the_loai' => $the_loai['id']]);
    $sach_theo_the_loai = $cau_lenh->fetchAll();

    if (empty($sach_theo_the_loai)) {
        continue;
    }
    ?>
    <section class="khoi-sach">
        <div class="tieu-de-khoi">
            <h2><?= htmlspecialchars($the_loai['ten_the_loai']) ?></h2>
            <a href="<?= URL_GOC ?>/danh-sach.php?the_loai=<?= $the_loai['id'] ?>">Xem tất cả &raquo;</a>
        </div>

        <div class="luoi-sach">
            <?php foreach ($sach_theo_the_loai as $sach): ?>
                <div class="the-sach">
                    <a href="<?= URL_GOC ?>/chi-tiet.php?id=<?= $sach['id'] ?>">
                        <img src="<?= lay_anh_bia($sach) ?>" alt="<?= htmlspecialchars($sach['tieu_de']) ?>">
                        <h3 class="ten-sach"><?= htmlspecialchars($sach['tieu_de']) ?></h3>
                    </a>
                    <p class="tac-gia"><?= htmlspecialchars($sach['tac_gia']) ?></p>
                    <p class="gia"><?= dinh_dang_gia($sach['gia_ban']) ?></p>
                    <?php if ($che_do_admin && !empty($_SESSION['admin_id']) && la_admin()): ?>
                        <div class="thao-tac-sach-admin">
                            <a class="nut-sua-sach" href="<?= URL_GOC ?>/admin/sach-sua.php?id=<?= $sach['id'] ?>">Sửa</a>
                            <a class="nut-xoa-sach" href="<?= URL_GOC ?>/admin/sach-xoa.php?id=<?= $sach['id'] ?>">Xóa</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>
