<?php
require_once 'config/ket_noi.php';
require_once 'includes/ham_chung.php';
require_once 'includes/header.php';

// ============================================
// 1. LẤY TỪ KHOÁ TỪ URL
// ============================================
$tu_khoa = isset($_GET['tu_khoa']) ? trim($_GET['tu_khoa']) : '';

// Phân trang (giống danh-sach.php)
$trang_hien_tai = isset($_GET['trang']) ? (int) $_GET['trang'] : 1;
if ($trang_hien_tai < 1) {
    $trang_hien_tai = 1;
}
$so_sach_moi_trang = 12;
$vi_tri_bat_dau = ($trang_hien_tai - 1) * $so_sach_moi_trang;

$danh_sach_sach = [];
$tong_so_sach = 0;
$tong_so_trang = 0;

// Chỉ tìm khi có từ khoá, tránh query rỗng quét cả bảng không cần thiết
if ($tu_khoa !== '') {
    $tu_khoa_tim = '%' . $tu_khoa . '%'; // dùng cho LIKE

    // ============================================
    // 2. ĐẾM TỔNG SỐ KẾT QUẢ
    // ============================================
    $cau_lenh_dem = $ket_noi->prepare("
        SELECT COUNT(*) FROM sach
        WHERE tieu_de LIKE :tu_khoa
           OR tac_gia LIKE :tu_khoa2
    ");
    $cau_lenh_dem->execute([
        'tu_khoa'  => $tu_khoa_tim,
        'tu_khoa2' => $tu_khoa_tim,
    ]);
    $tong_so_sach = $cau_lenh_dem->fetchColumn();
    $tong_so_trang = (int) ceil($tong_so_sach / $so_sach_moi_trang);

    // ============================================
    // 3. LẤY KẾT QUẢ CỦA TRANG HIỆN TẠI
    // ============================================
    $cau_lenh = $ket_noi->prepare("
        SELECT * FROM sach
        WHERE tieu_de LIKE :tu_khoa
           OR tac_gia LIKE :tu_khoa2
        ORDER BY id DESC
        LIMIT :bat_dau, :so_luong
    ");
    $cau_lenh->bindValue(':tu_khoa', $tu_khoa_tim);
    $cau_lenh->bindValue(':tu_khoa2', $tu_khoa_tim);
    $cau_lenh->bindValue(':bat_dau', $vi_tri_bat_dau, PDO::PARAM_INT);
    $cau_lenh->bindValue(':so_luong', $so_sach_moi_trang, PDO::PARAM_INT);
    $cau_lenh->execute();
    $danh_sach_sach = $cau_lenh->fetchAll();
}

// Hàm giữ tham số khi đổi trang (giống danh-sach.php)
function tao_link_giu_tham_so($tham_so_moi)
{
    $tham_so_cu = $_GET;
    $ket_hop = array_merge($tham_so_cu, $tham_so_moi);
    return '?' . http_build_query($ket_hop);
}
?>

<h1>Kết quả tìm kiếm cho: "<?= htmlspecialchars($tu_khoa) ?>"</h1>
<p>Tìm thấy <?= $tong_so_sach ?> sản phẩm</p>

<?php if ($tu_khoa === ''): ?>
    <p>Vui lòng nhập từ khoá để tìm kiếm.</p>
<?php elseif (empty($danh_sach_sach)): ?>
    <p>Không tìm thấy sách nào phù hợp với "<?= htmlspecialchars($tu_khoa) ?>".</p>
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
    <?php if ($tong_so_trang > 1): ?>
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
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>