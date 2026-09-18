<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/header.php';

// ============================================
// 1. LẤY ID SÁCH TỪ URL VÀ KIỂM TRA HỢP LỆ
// ============================================
$sach_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($sach_id <= 0) {
    echo '<p>Sách không tồn tại.</p>';
    require_once 'includes/footer.php';
    exit;
}

// ============================================
// 2. LẤY THÔNG TIN SÁCH (kèm tên thể loại)
// ============================================
$cau_lenh = $ket_noi->prepare("
    SELECT sach.*, dm_the_loai.ten_the_loai
    FROM sach
    LEFT JOIN dm_the_loai ON sach.the_loai = dm_the_loai.id
    WHERE sach.id = :id
");
$cau_lenh->execute(['id' => $sach_id]);
$sach = $cau_lenh->fetch();

// Nếu không tìm thấy sách (id sai, hoặc đã bị xoá)
if (!$sach) {
    echo '<p>Không tìm thấy sách này.</p>';
    require_once 'includes/footer.php';
    exit;
}

// ============================================
// 3. LẤY SÁCH LIÊN QUAN (cùng thể loại, khác id hiện tại)
// ============================================
$sach_lien_quan = [];
if (!empty($sach['the_loai'])) {
    $cau_lenh_lq = $ket_noi->prepare("
        SELECT * FROM sach
        WHERE the_loai = :the_loai AND id != :id
        ORDER BY id DESC
        LIMIT 4
    ");
    $cau_lenh_lq->execute([
        'the_loai' => $sach['the_loai'],
        'id'       => $sach_id,
    ]);
    $sach_lien_quan = $cau_lenh_lq->fetchAll();
}

// ============================================
// 4. LẤY DANH SÁCH ĐÁNH GIÁ CỦA SÁCH NÀY
// ============================================
$cau_lenh_dg = $ket_noi->prepare("
    SELECT danh_gia.*, nguoi_dung.ho_ten
    FROM danh_gia
    LEFT JOIN nguoi_dung ON danh_gia.nguoi_dung_id = nguoi_dung.id
    WHERE danh_gia.sach_id = :sach_id
    ORDER BY danh_gia.ngay_danh_gia DESC
");
$cau_lenh_dg->execute(['sach_id' => $sach_id]);
$danh_sach_danh_gia = $cau_lenh_dg->fetchAll();

// Tính điểm trung bình
$diem_trung_binh = 0;
$tong_so_danh_gia = count($danh_sach_danh_gia);
if ($tong_so_danh_gia > 0) {
    $tong_sao = 0;
    foreach ($danh_sach_danh_gia as $dg) {
        $tong_sao += $dg['so_sao'];
    }
    $diem_trung_binh = round($tong_sao / $tong_so_danh_gia, 1);
}

// Kiểm tra người đang đăng nhập đã đánh giá sách này chưa
$da_danh_gia_roi = false;
if (da_dang_nhap()) {
    $cau_lenh_kt_dg = $ket_noi->prepare("
        SELECT id FROM danh_gia
        WHERE sach_id = :sach_id AND nguoi_dung_id = :nguoi_dung_id
    ");
    $cau_lenh_kt_dg->execute([
        'sach_id'       => $sach_id,
        'nguoi_dung_id' => $_SESSION['nguoi_dung_id'],
    ]);
    $da_danh_gia_roi = (bool) $cau_lenh_kt_dg->fetch();
}
?>

<!-- Đường dẫn breadcrumb -->
<div class="duong-dan">
    <a href="<?= URL_GOC ?>/index.php">Trang chủ</a> /
    <?php if (!empty($sach['ten_the_loai'])): ?>
        <a href="<?= URL_GOC ?>/danh-sach.php?the_loai=<?= $sach['the_loai'] ?>">
            <?= htmlspecialchars($sach['ten_the_loai']) ?>
        </a> /
    <?php endif; ?>
    <span><?= htmlspecialchars($sach['tieu_de']) ?></span>
</div>

<div class="chi-tiet-sach">
    <!-- Cột trái: ảnh bìa -->
    <div class="chi-tiet-anh">
        <img src="<?= lay_anh_bia($sach) ?>" alt="<?= htmlspecialchars($sach['tieu_de']) ?>">
    </div>

    <!-- Cột phải: thông tin -->
    <div class="chi-tiet-thong-tin">
        <h1><?= htmlspecialchars($sach['tieu_de']) ?></h1>

        <table class="bang-thong-tin">
            <tr>
                <td>Tác giả</td>
                <td><?= htmlspecialchars($sach['tac_gia'] ?: 'Đang cập nhật') ?></td>
            </tr>
            <tr>
                <td>Nhà xuất bản</td>
                <td><?= htmlspecialchars($sach['nha_xuat_ban'] ?: 'Đang cập nhật') ?></td>
            </tr>
            <tr>
                <td>Nhà cung cấp</td>
                <td><?= htmlspecialchars($sach['nha_cung_cap'] ?: 'Đang cập nhật') ?></td>
            </tr>
            <tr>
                <td>Hình thức bìa</td>
                <td><?= htmlspecialchars($sach['hinh_thuc_bia'] ?: 'Đang cập nhật') ?></td>
            </tr>
        </table>

        <p class="chi-tiet-gia"><?= dinh_dang_gia($sach['gia_ban']) ?></p>

        <!-- Form thêm vào giỏ hàng -->
        <form action="<?= URL_GOC ?>/them-gio-hang.php" method="POST" class="form-them-gio-hang">
            <input type="hidden" name="sach_id" value="<?= $sach['id'] ?>">

            <label for="so_luong">Số lượng:</label>
            <input type="number" id="so_luong" name="so_luong" value="1" min="1" max="20">

            <button type="submit" class="nut-them-gio-hang">🛒 Thêm vào giỏ hàng</button>
        </form>
    </div>
</div>

<!-- Mô tả chi tiết -->
<div class="chi-tiet-mo-ta">
    <h2>Mô tả sản phẩm</h2>
    <div class="noi-dung-mo-ta">
        <?= nl2br(htmlspecialchars($sach['mo_ta'])) ?>
    </div>
</div>

<!-- Khối đánh giá -->
<div class="chi-tiet-danh-gia">
    <h2>Đánh giá sản phẩm</h2>

    <div class="tong-quan-danh-gia">
        <span class="diem-trung-binh"><?= $diem_trung_binh ?></span>
        <span class="so-sao-hien-thi">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <?= $i <= round($diem_trung_binh) ? '★' : '☆' ?>
            <?php endfor; ?>
        </span>
        <span>(<?= $tong_so_danh_gia ?> đánh giá)</span>
    </div>

    <!-- Thông báo kết quả gửi đánh giá -->
    <?php if (isset($_GET['danh_gia_thanh_cong'])): ?>
        <div class="thong-bao-thanh-cong">Cảm ơn bạn đã đánh giá sản phẩm!</div>
    <?php elseif (isset($_GET['loi_danh_gia']) && $_GET['loi_danh_gia'] === 'da_danh_gia'): ?>
        <div class="thong-bao-loi">Bạn đã đánh giá sách này rồi.</div>
    <?php elseif (isset($_GET['loi_danh_gia']) && $_GET['loi_danh_gia'] === 'so_sao'): ?>
        <div class="thong-bao-loi">Vui lòng chọn số sao hợp lệ (1-5).</div>
    <?php endif; ?>

    <!-- Form gửi đánh giá -->
    <?php if (!da_dang_nhap()): ?>
        <p><a href="<?= URL_GOC ?>/dang-nhap.php">Đăng nhập</a> để gửi đánh giá.</p>
    <?php elseif ($da_danh_gia_roi): ?>
        <p>Bạn đã đánh giá sách này rồi. Cảm ơn bạn!</p>
    <?php else: ?>
        <form action="<?= URL_GOC ?>/them-danh-gia.php" method="POST" class="form-danh-gia">
            <input type="hidden" name="sach_id" value="<?= $sach['id'] ?>">

            <label>Chọn số sao:</label>
            <div class="chon-so-sao">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="so_sao" id="sao<?= $i ?>" value="<?= $i ?>" <?= $i == 5 ? 'checked' : '' ?>>
                    <label for="sao<?= $i ?>"><?= $i ?> sao</label>
                <?php endfor; ?>
            </div>

            <textarea name="noi_dung" rows="3" placeholder="Nhận xét của bạn về sách này..."></textarea>

            <button type="submit" class="nut-thanh-toan">Gửi đánh giá</button>
        </form>
    <?php endif; ?>

    <!-- Danh sách các đánh giá -->
    <div class="danh-sach-danh-gia">
        <?php if (empty($danh_sach_danh_gia)): ?>
            <p>Chưa có đánh giá nào cho sách này.</p>
        <?php else: ?>
            <?php foreach ($danh_sach_danh_gia as $dg): ?>
                <div class="mot-danh-gia">
                    <div class="dau-danh-gia">
                        <strong><?= htmlspecialchars($dg['ho_ten'] ?? 'Người dùng ẩn danh') ?></strong>
                        <span class="so-sao-hien-thi">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?= $i <= $dg['so_sao'] ? '★' : '☆' ?>
                            <?php endfor; ?>
                        </span>
                        <span class="ngay-danh-gia"><?= date('d/m/Y', strtotime($dg['ngay_danh_gia'])) ?></span>
                    </div>
                    <?php if (!empty($dg['noi_dung'])): ?>
                        <p class="noi-dung-danh-gia"><?= nl2br(htmlspecialchars($dg['noi_dung'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Sách liên quan -->
<?php if (!empty($sach_lien_quan)): ?>
    <section class="khoi-sach">
        <div class="tieu-de-khoi">
            <h2>Sách cùng thể loại</h2>
        </div>
        <div class="luoi-sach">
            <?php foreach ($sach_lien_quan as $sp): ?>
                <div class="the-sach">
                    <a href="<?= URL_GOC ?>/chi-tiet.php?id=<?= $sp['id'] ?>">
                        <img src="<?= lay_anh_bia($sp) ?>" alt="<?= htmlspecialchars($sp['tieu_de']) ?>">
                        <h3 class="ten-sach"><?= htmlspecialchars($sp['tieu_de']) ?></h3>
                    </a>
                    <p class="tac-gia"><?= htmlspecialchars($sp['tac_gia']) ?></p>
                    <p class="gia"><?= dinh_dang_gia($sp['gia_ban']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>