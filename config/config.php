<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Prime Holiday');
define('BASE_URL', '/prime_holiday');

define('DB_HOST', 'localhost');
define('DB_NAME', 'prime_holiday');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOADS_URL', BASE_URL . '/uploads');
define('ASSETS_URL', BASE_URL . '/assets');

define('SITE_URL', 'http://localhost/prime_holiday');

define('RAZORPAY_KEY_ID', 'rzp_test_SY1iUQFAa3KFgW');
define('RAZORPAY_KEY_SECRET', 'JpWMz5oH4jUjf5h4xtUFnV4x');

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 465);
define('MAIL_USERNAME', 'cloudwithrayyan@gmail.com');
define('MAIL_PASSWORD', 'linf uikn gzoq hbwt');
define('MAIL_FROM_ADDRESS', 'cloudwithrayyan@gmail.com');
define('MAIL_FROM_NAME', 'Prime Holiday');