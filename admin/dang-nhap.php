<?php
require_once __DIR__ . '/../config/ket_noi.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['admin_id']) && la_admin()) {
    header('Location: ' . URL_GOC . '/index.php?admin=1');
    exit;
}

$loi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';

    $cau_lenh = $ket_noi->prepare(
        'SELECT * FROM tai_khoan_admin WHERE email = :email'
    );
    $cau_lenh->execute(['email' => $email]);
    $admin = $cau_lenh->fetch();

    if (!$admin || !password_verify($mat_khau, $admin['mat_khau'])) {
        $loi = 'Email hoặc mật khẩu quản trị không đúng.';
    } else {
        session_regenerate_id(true);
        unset($_SESSION['nguoi_dung_id']);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['ten_nguoi_dung'] = $admin['ho_ten'];
        $_SESSION['la_admin'] = 1;

        header('Location: ' . URL_GOC . '/index.php?admin=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Đăng nhập quản trị</title>
    <style>
        body { font:15px Arial; background:#f5f6fa; }
        .box { max-width:390px; margin:10vh auto; padding:28px; background:#fff; border-radius:8px; box-shadow:0 2px 8px #ddd; }
        label,input,button { display:block; width:100%; box-sizing:border-box; }
        label { margin-top:15px; }
        input { margin-top:5px; padding:10px; border:1px solid #ccc; border-radius:4px; }
        button { margin-top:20px; padding:11px; border:0; border-radius:4px; background:#b42318; color:#fff; cursor:pointer; }
        .error { margin-top:12px; padding:10px; background:#fee2e2; color:#991b1b; }
    </style>
</head>
<body>
    <main class="box">
        <h1>Quản trị</h1>
        <p>Đăng nhập bằng tài khoản quản trị riêng.</p>
        <?php if ($loi): ?><p class="error"><?= htmlspecialchars($loi) ?></p><?php endif; ?>
        <form method="post">
            <label>Email<input required type="email" name="email"></label>
            <label>Mật khẩu<input required type="password" name="mat_khau"></label>
            <button type="submit">Đăng nhập</button>
        </form>
    </main>
</body>
</html>
