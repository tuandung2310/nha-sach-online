<?php
// Copy file nay thanh config/email.php neu muon gui mail bang Brevo SMTP.
// Lay SMTP login va SMTP key trong Brevo: Settings > SMTP & API > SMTP.
return [
    'smtp_host' => 'smtp-relay.brevo.com',
    'smtp_port' => 587,
    'smtp_user' => 'YOUR_BREVO_SMTP_LOGIN',
    'smtp_pass' => 'YOUR_BREVO_SMTP_KEY',
    'from_email' => 'YOUR_VERIFIED_SENDER_EMAIL',
    'from_name' => 'Nha Sach Online',
];
