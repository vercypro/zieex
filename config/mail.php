<?php
return [
    'driver'    => env('MAIL_DRIVER', 'mail'),
    'from'      => env('MAIL_FROM', 'noreply@example.com'),
    'from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Zieex')),
    'smtp'      => [
        'host'   => env('SMTP_HOST', '127.0.0.1'),
        'port'   => env('SMTP_PORT', 587),
        'user'   => env('SMTP_USER', ''),
        'pass'   => env('SMTP_PASS', ''),
        'secure' => env('SMTP_SECURE', 'tls'),
    ],
    'resend' => [
        'key' => env('RESEND_API_KEY', ''),
    ],
];
