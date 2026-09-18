<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/header.php';

// ============================================
// 1. LẤY CÁC THAM SỐ LỌC/SẮP XẾP TỪ URL
// ============================================
$the_loai_id = isset($_GET['the_loai']) ? (int) $_GET['the_loai'] : 0;
$gia_tu      = isset($_GET['gia_tu']) ? (float) $_GET['gia_tu'] : 0;
$gia_den     = isset($_GET['gia_den']) ? (float) $_GET['gia_den'] : 0;
$sap_xep     = isset($_GET['sap_xep']) ? $_GET['sap_xep'] : 'moi_nhat';

// Phân trang
$trang_hien_tai = isset($_GET['trang']) ? (int) $_GET['trang'] : 1;
if ($trang_hien_tai < 1) {
    $trang_hien_tai = 1;
}
$so_sach_moi_trang = 12;
$vi_tri_bat_dau = ($trang_hien_tai - 1) * $so_sach_moi_trang;

// ============================================
// 2. XÂY DỰNG CÂU SQL ĐỘNG THEO ĐIỀU KIỆN LỌC
// ============================================
$dieu_kien = [];   // chứa các mẩu điều kiện WHERE
$tham_so   = [];   // chứa giá trị tương ứng, tránh SQL injection

if ($the_loai_id > 0) {
    $dieu_kien[] = "the_loai = :the_loai";
    $tham_so['the_loai'] = $the_loai_id;
}
if ($gia_tu > 0) {
    $dieu_kien[] = "gia_ban >= :gia_tu";
    $tham_so['gia_tu'] = $gia_tu;
}
if ($gia_den > 0) {
    $dieu_kien[] = "gia_ban <= :gia_den";
    $tham_so['gia_den'] = $gia_den;
}

// Ghép các điều kiện lại bằng AND, nếu không có điều kiện nào thì để trống
$cau_where = '';
if (!empty($dieu_kien)) {
    $cau_where = 'WHERE ' . implode(' AND ', $dieu_kien);
}

// Xác định kiểu sắp xếp
switch ($sap_xep) {
    case 'gia_tang':
        $cau_order = 'ORDER BY gia_ban ASC';
        break;
    case 'gia_giam':
        $cau_order = 'ORDER BY gia_ban DESC';
        break;
    case 'ten_az':
        $cau_order = 'ORDER BY tieu_de ASC';
        break;
    default: // moi_nhat
        $cau_order = 'ORDER BY id DESC';
        break;
}

// ============================================
// 3. ĐẾM TỔNG SỐ SÁCH THOẢ ĐIỀU KIỆN (để tính số trang)
// ============================================
$cau_lenh_dem = $ket_noi->prepare("SELECT COUNT(*) FROM sach $cau_where");
$cau_lenh_dem->execute($tham_so);
$tong_so_sach = $cau_lenh_dem->fetchColumn();
$tong_so_trang = (int) ceil($tong_so_sach / $so_sach_moi_trang);

// ============================================
// 4. LẤY DANH SÁCH SÁCH CỦA TRANG HIỆN TẠI
// ============================================
$cau_lenh = $ket_noi->prepare("
    SELECT * FROM sach
    $cau_where
    $cau_order
    LIMIT :bat_dau, :so_luong
");
// Gán trước các tham số lọc (nếu có)
foreach ($tham_so as $ten => $gia_tri) {
    $cau_lenh->bindValue(':' . $ten, $gia_tri);
}
// LIMIT bắt buộc phải bindValue kiểu INT, không dùng execute([...]) chung được
$cau_lenh->bindValue(':bat_dau', $vi_tri_bat_dau, PDO::PARAM_INT);
$cau_lenh->bindValue(':so_luong', $so_sach_moi_trang, PDO::PARAM_INT);
$cau_lenh->execute();
$danh_sach_sach = $cau_lenh->fetchAll();

// Lấy tên thể loại đang chọn (để hiện tiêu đề trang)
$ten_the_loai_dang_chon = '';
if ($the_loai_id > 0) {
    $cau_lenh_tl = $ket_noi->prepare("SELECT ten_the_loai FROM dm_the_loai WHERE id = :id");
    $cau_lenh_tl->execute(['id' => $the_loai_id]);
    $ten_the_loai_dang_chon = $cau_lenh_tl->fetchColumn();
}

// Hàm nhỏ giúp giữ lại các tham số lọc khi đổi trang/sắp xếp (dùng ở link phân trang)
function tao_link_giu_tham_so($tham_so_moi)
{
    $tham_so_cu = $_GET;
    $ket_hop = array_merge($tham_so_cu, $tham_so_moi);
    return '?' . http_build_query($ket_hop);
}
?>

<h1><?= $ten_the_loai_dang_chon ? htmlspecialchars($ten_the_loai_dang_chon) : 'Tất cả sách' ?></h1>
<p>Tìm thấy <?= $tong_so_sach ?> sản phẩm</p>

<div class="khu-vuc-loc-sap-xep">
    <!-- Form lọc theo giá -->
    <form method="GET" class="form-loc-gia">
        <?php if ($the_loai_id > 0): ?>
            <input type="hidden" name="the_loai" value="<?= $the_loai_id ?>">
        <?php endif; ?>
        <input type="number" name="gia_tu" placeholder="Giá từ" value="<?= $gia_tu ?: '' ?>">
        <input type="number" name="gia_den" placeholder="Giá đến" value="<?= $gia_den ?: '' ?>">
        <button type="submit">Lọc</button>
    </form>

    <!-- Sắp xếp -->
    <div class="khu-vuc-sap-xep">
        Sắp xếp:
        <a href="<?= tao_link_giu_tham_so(['sap_xep' => 'moi_nhat']) ?>"
           class="<?= $sap_xep === 'moi_nhat' ? 'dang-chon' : '' ?>">Mới nhất</a>
        <a href="<?= tao_link_giu_tham_so(['sap_xep' => 'gia_tang']) ?>"
           class="<?= $sap_xep === 'gia_tang' ? 'dang-chon' : '' ?>">Giá tăng dần</a>
        <a href="<?= tao_link_giu_tham_so(['sap_xep' => 'gia_giam']) ?>"
           class="<?= $sap_xep === 'gia_giam' ? 'dang-chon' : '' ?>">Giá giảm dần</a>
        <a href="<?= tao_link_giu_tham_so(['sap_xep' => 'ten_az']) ?>"
           class="<?= $sap_xep === 'ten_az' ? 'dang-chon' : '' ?>">Tên A-Z</a>
    </div>
</div>

<?php if (empty($danh_sach_sach)): ?>
    <p>Không tìm thấy sách nào phù hợp.</p>
<?php else: ?>
    <div class="luoi-sach">
        <?php foreach ($danh_sach_sach as $sach): ?>
            <div class="the-sach">
                <a href="<?= URL_GOC ?>/chi-tiet.php?id=<?= $sach['id'] ?>">
                    <img src="<?= lay_anh_bia($sach) ?>" alt="<?= htmlspecialchars($sach['tieu_de']) ?>">
                    <h3 class="ten-sach"><?= htmlspecialchars($sach['tieu_de']) ?></h3>
                </a>
                <p class="tac-gia"><?= htmlspecialchars($sach['tac_gia']) ?></p>
                <p class="gia"><?= dinh_dang_gia($sach['gia_ban']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Phân trang -->
    <div class="phan-trang">
        <?php if ($trang_hien_tai > 1): ?>
            <a href="<?= tao_link_giu_tham_so(['trang' => $trang_hien_tai - 1]) ?>">&laquo; Trước</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $tong_so_trang; $i++): ?>
            <a href="<?= tao_link_giu_tham_so(['trang' => $i]) ?>"
               class="<?= $i === $trang_hien_tai ? 'dang-chon' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($trang_hien_tai < $tong_so_trang): ?>
            <a href="<?= tao_link_giu_tham_so(['trang' => $trang_hien_tai + 1]) ?>">Sau &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>