<?php
require_once __DIR__ . '/../config/ket_noi.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function admin_bat_buoc_dang_nhap() {
    if (!la_admin() || empty($_SESSION['admin_id'])) {
        header('Location: ' . URL_GOC . '/admin/dang-nhap.php');
        exit;
    }
}
function admin_csrf() {
    if (empty($_SESSION['admin_csrf'])) $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['admin_csrf'];
}
function admin_kiem_tra_csrf() {
    return !empty($_POST['csrf']) && !empty($_SESSION['admin_csrf']) && hash_equals($_SESSION['admin_csrf'], $_POST['csrf']);
}
function admin_header($tieu_de) { ?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($tieu_de) ?> | Quản trị</title>
<style>body{margin:0;background:#f5f6fa;color:#222;font:15px Arial,sans-serif}.top{background:#18243a;color:#fff;padding:14px 5%;display:flex;gap:20px;align-items:center;flex-wrap:wrap}.top strong{font-size:18px}.top a{color:#fff;text-decoration:none}.top nav{display:flex;gap:15px;margin-left:auto}.page{max-width:1150px;margin:28px auto;padding:0 20px}h1{margin-top:0}table{width:100%;border-collapse:collapse;background:#fff}th,td{padding:11px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}th{background:#f0f3f8}a{color:#b42318}.btn,button{display:inline-block;border:0;border-radius:5px;background:#b42318;color:#fff;padding:9px 13px;text-decoration:none;cursor:pointer}.btn.alt{background:#334155}.actions{display:flex;gap:8px;flex-wrap:wrap;margin:16px 0}form.card{background:#fff;padding:22px;max-width:700px;border-radius:7px;box-shadow:0 1px 3px #ddd}label{display:block;margin:13px 0 5px;font-weight:bold}input,select,textarea{width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:4px;box-sizing:border-box}textarea{resize:vertical}.notice{padding:12px;border-radius:5px;margin:15px 0}.ok{background:#dcfce7;color:#166534}.error{background:#fee2e2;color:#991b1b}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:15px}.stat{background:#fff;padding:20px;border-radius:7px;font-size:16px}.stat b{display:block;font-size:29px;color:#b42318;margin-top:8px}.muted{color:#64748b}@media(max-width:650px){.top nav{margin-left:0;width:100%}}</style></head><body>
<header class="top"><strong>📚 Quản trị Nhà Sách</strong><nav><a href="<?= URL_GOC ?>/admin/index.php">Tổng quan</a><a href="<?= URL_GOC ?>/admin/sach.php">Sách</a><a href="<?= URL_GOC ?>/admin/the-loai.php">Thể loại</a><a href="<?= URL_GOC ?>/admin/don-hang.php">Đơn hàng</a><a href="<?= URL_GOC ?>/index.php" target="_blank">Xem web</a><a href="<?= URL_GOC ?>/dang-xuat.php">Đăng xuất</a></nav></header><main class="page">
<?php }
function admin_footer() { echo '</main></body></html>'; }
