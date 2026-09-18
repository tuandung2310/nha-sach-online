<?php
require_once __DIR__ . '/_layout.php';
admin_bat_buoc_dang_nhap();

$loi = [];
$data = ['tieu_de'=>'', 'tac_gia'=>'', 'nha_xuat_ban'=>'', 'nha_cung_cap'=>'', 'hinh_thuc_bia'=>'', 'gia_ban'=>'', 'the_loai'=>'', 'mo_ta'=>'', 'link_anh_bia'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_kiem_tra_csrf()) {
        $loi[] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        foreach ($data as $key => $value) {
            $data[$key] = trim($_POST[$key] ?? '');
        }
        $data['the_loai'] = (int) $data['the_loai'];
        $data['gia_ban'] = (float) $data['gia_ban'];

        if ($data['tieu_de'] === '') $loi[] = 'Vui lòng nhập tiêu đề sách.';
        if ($data['gia_ban'] <= 0) $loi[] = 'Giá bán phải lớn hơn 0.';

        $ten_file_anh = '';
        if (empty($loi) && !empty($_FILES['anh_bia']['name'])) {
            $file = $_FILES['anh_bia'];
            $duoi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $la_anh = @getimagesize($file['tmp_name']);
            if ($file['error'] !== UPLOAD_ERR_OK || !in_array($duoi, ['jpg','jpeg','png','webp'], true) || $file['size'] > 2 * 1024 * 1024 || !$la_anh) {
                $loi[] = 'Ảnh phải là JPG, PNG hoặc WEBP và không quá 2MB.';
            } else {
                if (!is_dir(DUONG_DAN_GOC . '/uploads')) mkdir(DUONG_DAN_GOC . '/uploads', 0755, true);
                $ten_file_anh = 'sach-' . bin2hex(random_bytes(8)) . '.' . $duoi;
                if (!move_uploaded_file($file['tmp_name'], DUONG_DAN_GOC . '/uploads/' . $ten_file_anh)) {
                    $loi[] = 'Không thể lưu ảnh tải lên.';
                }
            }
        }

        if (empty($loi)) {
            $stmt = $ket_noi->prepare('INSERT INTO sach (tieu_de,tac_gia,nha_xuat_ban,nha_cung_cap,hinh_thuc_bia,gia_ban,the_loai,mo_ta,link_anh_bia,file_anh_bia) VALUES (:tieu_de,:tac_gia,:nxb,:ncc,:bia,:gia,:the_loai,:mo_ta,:link,:file)');
            $stmt->execute(['tieu_de'=>$data['tieu_de'], 'tac_gia'=>$data['tac_gia'], 'nxb'=>$data['nha_xuat_ban'], 'ncc'=>$data['nha_cung_cap'], 'bia'=>$data['hinh_thuc_bia'], 'gia'=>$data['gia_ban'], 'the_loai'=>$data['the_loai'] ?: null, 'mo_ta'=>$data['mo_ta'], 'link'=>$data['link_anh_bia'], 'file'=>$ten_file_anh]);
            header('Location: sach.php?ok=1');
            exit;
        }
    }
}

$ds_the_loai = $ket_noi->query('SELECT * FROM dm_the_loai ORDER BY ten_the_loai')->fetchAll();
admin_header('Thêm sách');
?>
<style>
    .form-them-sach { max-width:900px !important; padding:0 !important; overflow:hidden; }
    .form-them-sach__head { padding:24px 28px; color:#fff; background:linear-gradient(120deg,#8b1111,#d70018); }
    .form-them-sach__head h1 { margin:0 0 6px; font-size:25px; }
    .form-them-sach__head p { margin:0; opacity:.88; }
    .form-them-sach__body { padding:26px 28px 10px; }
    .nhom-form { margin-bottom:24px; }
    .nhom-form h2 { margin:0 0 16px; padding-bottom:10px; border-bottom:1px solid #e2e8f0; font-size:16px; color:#334155; }
    .luoi-form { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 18px; }
    .truong-rong { grid-column:1 / -1; }
    .ghi-chu-form { margin-top:5px; font-size:12px; color:#64748b; }
    .tai-anh { padding:14px; border:1px dashed #94a3b8; border-radius:6px; background:#f8fafc; }
    .form-them-sach__footer { display:flex; gap:10px; justify-content:flex-end; padding:18px 28px; background:#f8fafc; border-top:1px solid #e2e8f0; }
    @media(max-width:650px) { .luoi-form { grid-template-columns:1fr; } .form-them-sach__body { padding:20px; } }
</style>

<?php if ($loi): ?>
    <div class="notice error"><?= htmlspecialchars(implode(' ', $loi)) ?></div>
<?php endif; ?>

<form class="card form-them-sach" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= admin_csrf() ?>">
    <div class="form-them-sach__head">
        <h1>Thêm sách mới</h1>
        <p>Điền thông tin bên dưới để đưa sách lên cửa hàng.</p>
    </div>

    <div class="form-them-sach__body">
        <section class="nhom-form">
            <h2>Thông tin cơ bản</h2>
            <div class="luoi-form">
                <div class="truong-rong"><label>Tiêu đề sách *</label><input required name="tieu_de" value="<?= htmlspecialchars($data['tieu_de']) ?>" placeholder="Ví dụ: Nhà giả kim"></div>
                <div><label>Tác giả</label><input name="tac_gia" value="<?= htmlspecialchars($data['tac_gia']) ?>" placeholder="Tên tác giả"></div>
                <div><label>Thể loại</label><select name="the_loai"><option value="">-- Chọn thể loại --</option><?php foreach ($ds_the_loai as $tl): ?><option value="<?= $tl['id'] ?>" <?= $data['the_loai'] == $tl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tl['ten_the_loai']) ?></option><?php endforeach; ?></select></div>
                <div><label>Giá bán (VNĐ) *</label><input required min="1" step="1000" type="number" name="gia_ban" value="<?= htmlspecialchars($data['gia_ban']) ?>" placeholder="Ví dụ: 120000"></div>
                <div><label>Hình thức bìa</label><input name="hinh_thuc_bia" value="<?= htmlspecialchars($data['hinh_thuc_bia']) ?>" placeholder="Bìa mềm, bìa cứng..."></div>
                <div><label>Nhà xuất bản</label><input name="nha_xuat_ban" value="<?= htmlspecialchars($data['nha_xuat_ban']) ?>"></div>
                <div><label>Nhà cung cấp</label><input name="nha_cung_cap" value="<?= htmlspecialchars($data['nha_cung_cap']) ?>"></div>
            </div>
        </section>

        <section class="nhom-form">
            <h2>Ảnh bìa</h2>
            <div class="luoi-form">
                <div class="tai-anh"><label>Tải ảnh từ máy</label><input type="file" name="anh_bia" accept=".jpg,.jpeg,.png,.webp"><p class="ghi-chu-form">JPG, PNG, WEBP · tối đa 2MB</p></div>
                <div><label>Hoặc dùng liên kết ảnh</label><input type="url" name="link_anh_bia" value="<?= htmlspecialchars($data['link_anh_bia']) ?>" placeholder="https://..."></div>
            </div>
        </section>

        <section class="nhom-form">
            <h2>Mô tả</h2>
            <label>Mô tả nội dung sách</label>
            <textarea name="mo_ta" rows="7" placeholder="Nhập giới thiệu ngắn về sách..."><?= htmlspecialchars($data['mo_ta']) ?></textarea>
        </section>
    </div>

    <div class="form-them-sach__footer">
        <a class="btn alt" href="sach.php">Hủy</a>
        <button type="submit">Lưu sách</button>
    </div>
</form>
<?php admin_footer(); ?>
