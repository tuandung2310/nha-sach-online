<?php

function email_doc_cau_hinh()
{
    $duong_dan = __DIR__ . '/../config/email.php';
    if (!file_exists($duong_dan)) {
        return null;
    }

    $cau_hinh = require $duong_dan;
    if (
        empty($cau_hinh['smtp_host']) ||
        empty($cau_hinh['smtp_port']) ||
        empty($cau_hinh['smtp_user']) ||
        empty($cau_hinh['smtp_pass']) ||
        $cau_hinh['smtp_pass'] === 'DIEN_APP_PASSWORD_GMAIL_O_DAY'
    ) {
        return null;
    }

    return $cau_hinh;
}

function smtp_doc_dong($ket_noi)
{
    $phan_hoi = '';
    while ($dong = fgets($ket_noi, 515)) {
        $phan_hoi .= $dong;
        if (isset($dong[3]) && $dong[3] === ' ') {
            break;
        }
    }
    return $phan_hoi;
}

function smtp_gui_lenh($ket_noi, $lenh, $ma_hop_le)
{
    if ($lenh !== null) {
        fwrite($ket_noi, $lenh . "\r\n");
    }

    $phan_hoi = smtp_doc_dong($ket_noi);
    $ma_phan_hoi = (int) substr($phan_hoi, 0, 3);

    if (!in_array($ma_phan_hoi, (array) $ma_hop_le, true)) {
        throw new Exception(trim($phan_hoi));
    }

    return $phan_hoi;
}

function email_ma_hoa_tieu_de($noi_dung)
{
    return '=?UTF-8?B?' . base64_encode($noi_dung) . '?=';
}

function gui_email_smtp($email_nhan, $ten_nhan, $tieu_de, $noi_dung_html, &$loi_gui = '')
{
    $cau_hinh = email_doc_cau_hinh();
    if (!$cau_hinh) {
        $loi_gui = 'Chưa cấu hình Gmail SMTP trong file config/email.php.';
        return false;
    }

    $host = $cau_hinh['smtp_host'];
    $port = (int) $cau_hinh['smtp_port'];
    $timeout = 20;

    try {
        $ket_noi = fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$ket_noi) {
            throw new Exception($errstr ?: 'Không kết nối được SMTP Gmail.');
        }

        smtp_gui_lenh($ket_noi, null, 220);
        smtp_gui_lenh($ket_noi, 'EHLO localhost', 250);
        smtp_gui_lenh($ket_noi, 'STARTTLS', 220);

        if (!stream_socket_enable_crypto($ket_noi, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception('Không bật được mã hóa TLS.');
        }

        smtp_gui_lenh($ket_noi, 'EHLO localhost', 250);
        smtp_gui_lenh($ket_noi, 'AUTH LOGIN', 334);
        smtp_gui_lenh($ket_noi, base64_encode($cau_hinh['smtp_user']), 334);
        smtp_gui_lenh($ket_noi, base64_encode($cau_hinh['smtp_pass']), 235);
        smtp_gui_lenh($ket_noi, 'MAIL FROM:<' . $cau_hinh['from_email'] . '>', 250);
        smtp_gui_lenh($ket_noi, 'RCPT TO:<' . $email_nhan . '>', [250, 251]);
        smtp_gui_lenh($ket_noi, 'DATA', 354);

        $from_name = email_ma_hoa_tieu_de($cau_hinh['from_name']);
        $subject = email_ma_hoa_tieu_de($tieu_de);
        $to_name = email_ma_hoa_tieu_de($ten_nhan ?: $email_nhan);

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $from_name . ' <' . $cau_hinh['from_email'] . '>',
            'To: ' . $to_name . ' <' . $email_nhan . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $noi_dung_html = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $noi_dung_html);
        fwrite($ket_noi, implode("\r\n", $headers) . "\r\n\r\n" . $noi_dung_html . "\r\n.\r\n");
        smtp_gui_lenh($ket_noi, null, 250);
        smtp_gui_lenh($ket_noi, 'QUIT', 221);
        fclose($ket_noi);

        return true;
    } catch (Exception $loi) {
        if (isset($ket_noi) && is_resource($ket_noi)) {
            fclose($ket_noi);
        }
        $loi_gui = $loi->getMessage();
        return false;
    }
}

function dam_bao_bang_xac_thuc_email($ket_noi)
{
    $cot_can_co = [
        'email_da_xac_thuc' => "ALTER TABLE nguoi_dung ADD email_da_xac_thuc TINYINT(1) NOT NULL DEFAULT 0",
        'ma_xac_thuc' => "ALTER TABLE nguoi_dung ADD ma_xac_thuc VARCHAR(10) NULL",
        'ma_xac_thuc_het_han' => "ALTER TABLE nguoi_dung ADD ma_xac_thuc_het_han DATETIME NULL",
        'ngay_xac_thuc' => "ALTER TABLE nguoi_dung ADD ngay_xac_thuc DATETIME NULL",
    ];

    $cac_cot_hien_co = [];
    $ket_qua = $ket_noi->query('SHOW COLUMNS FROM nguoi_dung');
    foreach ($ket_qua->fetchAll() as $cot) {
        $cac_cot_hien_co[] = $cot['Field'];
    }

    foreach ($cot_can_co as $ten_cot => $sql) {
        if (!in_array($ten_cot, $cac_cot_hien_co, true)) {
            $ket_noi->exec($sql);
        }
    }

    $ket_noi->exec("
        UPDATE nguoi_dung
        SET email_da_xac_thuc = 1, ngay_xac_thuc = COALESCE(ngay_xac_thuc, NOW())
        WHERE email_da_xac_thuc = 0
          AND ma_xac_thuc IS NULL
    ");
}

function tao_ma_xac_thuc_email()
{
    return (string) random_int(100000, 999999);
}

function gui_ma_xac_thuc_email($email, $ho_ten, $ma_xac_thuc, &$loi_gui = '')
{
    $tieu_de = 'Mã xác thực tài khoản Nhà Sách Online';
    $noi_dung = '
        <div style="font-family:Arial,sans-serif;line-height:1.6;color:#222">
            <h2>Xác thực tài khoản Nhà Sách Online</h2>
            <p>Xin chào ' . htmlspecialchars($ho_ten) . ',</p>
            <p>Mã xác thực tài khoản của bạn là:</p>
            <p style="font-size:28px;font-weight:bold;letter-spacing:4px;color:#d70018">' . htmlspecialchars($ma_xac_thuc) . '</p>
            <p>Mã có hiệu lực trong 15 phút. Nếu bạn không đăng ký tài khoản, hãy bỏ qua email này.</p>
        </div>
    ';

    return gui_email_smtp($email, $ho_ten, $tieu_de, $noi_dung, $loi_gui);
}
