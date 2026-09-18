<?php
require_once __DIR__ . '/_layout.php';

// ============================================
// 1. CHỈ ADMIN MỚI ĐƯỢC VÀO TRANG NÀY
// ============================================
admin_bat_buoc_dang_nhap();

$sach_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
admin_header('Sửa sách');
$loi = [];
$thanh_cong = false;

// ============================================
// 2. NẾU CHƯA CHỌN SÁCH -> HIỆN DANH SÁCH ĐỂ CHỌN
// ============================================
if ($sach_id <= 0) {
    $ds_sach = $ket_noi->query("SELECT id, tieu_de FROM sach ORDER BY tieu_de")->fetchAll();
    ?>
    <h1>Chọn sách cần sửa</h1>
    <ul class="danh-sach-chon-sach">
        <?php foreach ($ds_sach as $s): ?>
            <li>
                <a href="?id=<?= $s['id'] ?>"><?= htmlspecialchars($s['tieu_de']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    admin_footer();
    exit;
}

// ============================================
// 3. LẤY THÔNG TIN SÁCH ĐANG SỬA
// ============================================
$cau_lenh = $ket_noi->prepare("SELECT * FROM sach WHERE id = :id");
$cau_lenh->execute(['id' => $sach_id]);
$sach = $cau_lenh->fetch();

if (!$sach) {
    echo '<p>Không tìm thấy sách này.</p>';
    admin_footer();
    exit;
}

// ============================================
// 4. XỬ LÝ KHI SUBMIT FORM SỬA
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_kiem_tra_csrf()) {
        $loi[] = 'Yêu cầu không hợp lệ, vui lòng thử lại.';
    }
    $tieu_de       = trim($_POST['tieu_de'] ?? '');
    $tac_gia       = trim($_POST['tac_gia'] ?? '');
    $nha_xuat_ban  = trim($_POST['nha_xuat_ban'] ?? '');
    $nha_cung_cap  = trim($_POST['nha_cung_cap'] ?? '');
    $hinh_thuc_bia = trim($_POST['hinh_thuc_bia'] ?? '');
    $mo_ta         = trim($_POST['mo_ta'] ?? '');
    $gia_ban       = (float) ($_POST['gia_ban'] ?? 0);
    $the_loai      = (int) ($_POST['the_loai'] ?? 0);

    if ($tieu_de === '') {
        $loi[] = 'Vui lòng nhập tiêu đề sách.';
    }
    if ($gia_ban <= 0) {
        $loi[] = 'Giá bán phải lớn hơn 0.';
    }

    // Tên file ảnh hiện tại (mặc định giữ nguyên nếu không upload ảnh mới)
    $ten_file_anh = $sach['file_anh_bia'];

    // ----- Xử lý upload ảnh (nếu có chọn ảnh mới) -----
    if (empty($loi) && !empty($_FILES['anh_bia']['name'])) {
        $file = $_FILES['anh_bia'];

        // Kiểm tra lỗi upload cơ bản (dung lượng vượt giới hạn php.ini, upload dở dang...)
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $loi[] = 'Upload ảnh thất bại, vui lòng thử lại.';
        } else {
            // Chỉ cho phép đúng các đuôi ảnh này
            $duoi_cho_phep = ['jpg', 'jpeg', 'png', 'webp'];
            $duoi_file = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Kiểm tra dung lượng, tối đa 2MB
            $dung_luong_toi_da = 2 * 1024 * 1024;

            // Kiểm tra file có THẬT SỰ là ảnh không (không chỉ dựa vào đuôi file,
            // vì đuôi file có thể giả mạo, VD: đổi tên virus.php thành virus.jpg)
            $thong_tin_anh = @getimagesize($file['tmp_name']);

            if (!in_array($duoi_file, $duoi_cho_phep)) {
                $loi[] = 'Chỉ chấp nhận ảnh định dạng JPG, PNG hoặc WEBP.';
            } elseif ($file['size'] > $dung_luong_toi_da) {
                $loi[] = 'Ảnh không được vượt quá 2MB.';
            } elseif ($thong_tin_anh === false) {
                $loi[] = 'File tải lên không phải là ảnh hợp lệ.';
            } else {
                // Đặt tên file mới, tránh trùng tên gây ghi đè ảnh của sách khác
                // Ví dụ: sach-12-1736900000.jpg
                $ten_file_moi = 'sach-' . $sach_id . '-' . time() . '.' . $duoi_file;
                $duong_dan_luu = DUONG_DAN_GOC . '/uploads/' . $ten_file_moi;

                if (move_uploaded_file($file['tmp_name'], $duong_dan_luu)) {
                    // Xoá ảnh cũ (nếu có) để đỡ rác thư mục uploads/
                    if (!empty($sach['file_anh_bia'])) {
                        $duong_dan_anh_cu = DUONG_DAN_GOC . '/uploads/' . $sach['file_anh_bia'];
                        if (file_exists($duong_dan_anh_cu)) {
                            unlink($duong_dan_anh_cu);
                        }
                    }
                    $ten_file_anh = $ten_file_moi;
                } else {
                    $loi[] = 'Không thể lưu ảnh lên máy chủ.';
                }
            }
        }
    }

    // ----- Nếu không có lỗi -> cập nhật vào DB -----
    if (empty($loi)) {
        $cau_lenh_sua = $ket_noi->prepare("
            UPDATE sach SET
                tieu_de = :tieu_de,
                tac_gia = :tac_gia,
                nha_xuat_ban = :nha_xuat_ban,
                nha_cung_cap = :nha_cung_cap,
                hinh_thuc_bia = :hinh_thuc_bia,
                mo_ta = :mo_ta,
                gia_ban = :gia_ban,
                the_loai = :the_loai,
                file_anh_bia = :file_anh_bia
            WHERE id = :id
        ");
        $cau_lenh_sua->execute([
            'tieu_de'       => $tieu_de,
            'tac_gia'       => $tac_gia,
            'nha_xuat_ban'  => $nha_xuat_ban,
            'nha_cung_cap'  => $nha_cung_cap,
            'hinh_thuc_bia' => $hinh_thuc_bia,
            'mo_ta'         => $mo_ta,
            'gia_ban'       => $gia_ban,
            'the_loai'      => $the_loai ?: null,
            'file_anh_bia'  => $ten_file_anh,
            'id'            => $sach_id,
        ]);

        $thanh_cong = true;

        // Lấy lại dữ liệu mới nhất để hiện đúng trên form
        $cau_lenh->execute(['id' => $sach_id]);
        $sach = $cau_lenh->fetch();
    }
}

// Lấy danh sách thể loại cho ô chọn
$ds_the_loai = $ket_noi->query("SELECT * FROM dm_the_loai ORDER BY ten_the_loai")->fetchAll();
?>

<h1>Sửa sách: <?= htmlspecialchars($sach['tieu_de']) ?></h1>

<?php if ($thanh_cong): ?>
    <div class="thong-bao-thanh-cong">Đã lưu thay đổi thành công!</div>
<?php endif; ?>

<?php if (!empty($loi)): ?>
    <div class="thong-bao-loi">
        <ul>
            <?php foreach ($loi as $thong_bao): ?>
                <li><?= htmlspecialchars($thong_bao) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- QUAN TRỌNG: bắt buộc phải có enctype="multipart/form-data" mới upload được file -->
<form method="POST" action="?id=<?= $sach_id ?>" enctype="multipart/form-data" class="form-admin-sach">
    <input type="hidden" name="csrf" value="<?= admin_csrf() ?>">

    <label>Ảnh bìa hiện tại</label>
    <div class="anh-bia-hien-tai">
        <img src="<?= lay_anh_bia($sach) ?>" alt="Ảnh bìa hiện tại" style="width:150px;">
    </div>

    <label>Chọn ảnh bìa mới (bỏ trống nếu không đổi ảnh)</label>
    <input type="file" name="anh_bia" accept=".jpg,.jpeg,.png,.webp">

    <label>Tiêu đề</label>
    <input type="text" name="tieu_de" value="<?= htmlspecialchars($sach['tieu_de']) ?>">

    <label>Tác giả</label>
    <input type="text" name="tac_gia" value="<?= htmlspecialchars($sach['tac_gia'] ?? '') ?>">

    <label>Nhà xuất bản</label>
    <input type="text" name="nha_xuat_ban" value="<?= htmlspecialchars($sach['nha_xuat_ban'] ?? '') ?>">

    <label>Nhà cung cấp</label>
    <input type="text" name="nha_cung_cap" value="<?= htmlspecialchars($sach['nha_cung_cap'] ?? '') ?>">

    <label>Hình thức bìa</label>
    <input type="text" name="hinh_thuc_bia" value="<?= htmlspecialchars($sach['hinh_thuc_bia'] ?? '') ?>">

    <label>Thể loại</label>
    <select name="the_loai">
        <option value="">-- Chọn thể loại --</option>
        <?php foreach ($ds_the_loai as $tl): ?>
            <option value="<?= $tl['id'] ?>" <?= $sach['the_loai'] == $tl['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($tl['ten_the_loai']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Giá bán (VNĐ)</label>
    <input type="number" name="gia_ban" value="<?= $sach['gia_ban'] ?>" step="1000">

    <label>Mô tả</label>
    <textarea name="mo_ta" rows="8"><?= htmlspecialchars($sach['mo_ta'] ?? '') ?></textarea>

    <button type="submit" class="nut-thanh-toan">Lưu thay đổi</button>
    <a href="<?= URL_GOC ?>/chi-tiet.php?id=<?= $sach['id'] ?>" target="_blank">Xem trang sách</a>
</form>

<?php admin_footer(); ?>
