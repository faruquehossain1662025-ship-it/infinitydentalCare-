<?php
// Configuration file for Infinity Dental Care Website

// সাইট সেটিংস
define('SITE_NAME', 'ইনফিনিটি ডেন্টাল কেয়ার');

// SITE_URL তৈরি (HTTP বা HTTPS স্বয়ংক্রিয় সনাক্তকরণ)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// dirname() দিয়ে admin ফোল্ডার বাদ দিয়ে মূল ওয়েবসাইটের URL বের করা
$siteRoot = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

// SITE_URL একবারই define করা (ডুপ্লিকেশন এড়ানোর জন্য)
if (!defined('SITE_URL')) {
    define('SITE_URL', $protocol . $_SERVER['HTTP_HOST'] . $siteRoot);
}

// admin প্যানেলের URL
define('ADMIN_URL', SITE_URL . '/admin');

// ডাটা ফাইলের ডিরেক্টরি (প্রকল্পের রুট থেকে একটি ধাপ উপরে data/)
define('DATA_DIR', __DIR__ . '/../data/');

// আপলোড ফোল্ডার - admin ফোল্ডারের বাইরে প্রকৃত ওয়েব রুটের uploads/ ফোল্ডার
define('UPLOADS_DIR', dirname(__DIR__) . '/uploads/');

// আপলোডের ওয়েব ইউআরএল - অবশ্যই SITE_URL এর সাথে মিল রাখতে হবে
define('UPLOADS_URL', SITE_URL . '/uploads/');

// ==================== SMS CONFIGURATION ====================
define('SMS_PROVIDER', 'alpha_net'); // alpha_net বা ssl_wireless
define('SMS_API_KEY', 'YYlYUp1EZ86vA4uFwpJ183M01z6OmSx96FRNNOd2'); // আপনার আসল API Key
define('SMS_SENDER_ID', 'Infinity'); // আপনার Sender ID

// ==================== EMAIL CONFIGURATION ====================
define('EMAIL_PROVIDER', 'custom');

// SMTP Settings (Primary method)
//define('SMTP_HOST', 'mail.infinitydentalcare.top');
//define('SMTP_PORT', 465); // SSL port
//define('SMTP_USERNAME', 'no-reply@infinitydentalcare.top'); // Fixed email address
//define('SMTP_PASSWORD', '8-1o*oetYoSp'); 
//define('SMTP_ENCRYPTION', 'ssl');

// Email Configuration
define('SMTP_HOST', 'mail.infinitydentalcare.top');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'no-reply@infinitydentalcare.top');
define('SMTP_PASSWORD', 'KPazpGI~9Tgs'); // এই পাসওয়ার্ড ভুল
define('SMTP_ENCRYPTION', 'ssl');
define('FROM_EMAIL', 'no-reply@infinitydentalcare.top');
define('FROM_NAME', 'ইনফিনিটি ডেন্টাল কেয়ার');



// Email From Settings
define('FROM_EMAIL', 'no-reply@infinitydentalcare.top'); // Fixed email address
define('FROM_NAME', 'ইনফিনিটি ডেন্টাল কেয়ার');

// Email Debug and Settings
define('EMAIL_DEBUG', false); // Testing এর জন্য true, production এ false করুন
define('EMAIL_TIMEOUT', 30);
define('EMAIL_MAX_ATTEMPTS', 3);

// Email File Storage
define('EMAIL_TEMPLATES_DIR', DATA_DIR . 'email_templates/');
define('NEWSLETTER_FILE', DATA_DIR . 'newsletter_subscribers.json');
define('EMAIL_QUEUE_FILE', DATA_DIR . 'email_queue.json');
define('EMAIL_LOG_FILE', DATA_DIR . 'email_log.json');

// ==================== JSON DATA FILES ====================
define('SETTINGS_FILE', DATA_DIR . 'settings.json');
define('BANNERS_FILE', DATA_DIR . 'banners.json');
define('SERVICES_FILE', DATA_DIR . 'services.json');
define('APPOINTMENTS_FILE', DATA_DIR . 'appointments.json');
define('PATIENTS_FILE', DATA_DIR . 'patients.json');
define('REVIEWS_FILE', DATA_DIR . 'reviews.json');
define('NEWS_FILE', DATA_DIR . 'news.json');
define('OFFERS_FILE', DATA_DIR . 'offers.json');
define('GALLERY_FILE', DATA_DIR . 'gallery.json');
define('INCOME_FILE', DATA_DIR . 'income.json');
define('COUPONS_FILE', DATA_DIR . 'coupons.json');
define('POPUPS_FILE', DATA_DIR . 'popups.json');
define('SOCIAL_FILE', DATA_DIR . 'social.json');
define('VISITORS_FILE', DATA_DIR . 'visitors.json');
define('CONTACTS_FILE', DATA_DIR . 'contacts.json');

// ==================== ADMIN SETTINGS ====================
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // প্রোডাকশনে অবশ্যই পরিবর্তন করবেন
define('SESSION_TIMEOUT', 3600); // সেশন টাইমআউট (সেকেন্ডে) - 1 ঘণ্টা

// ==================== FILE UPLOAD SETTINGS ====================
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'ogg']);

// ==================== SECURITY SETTINGS ====================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
ini_set('session.use_strict_mode', 1);

// ==================== ERROR REPORTING ====================
error_reporting(E_ALL);
ini_set('display_errors', 1); // প্রোডাকশনে 0 করুন

// ==================== TIMEZONE & UTF-8 ====================
date_default_timezone_set('Asia/Dhaka');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// ==================== AUTO-CREATE DIRECTORIES ====================
$directories = [
    DATA_DIR,
    UPLOADS_DIR,
    EMAIL_TEMPLATES_DIR
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ==================== SESSION START ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== LOAD FUNCTIONS ====================
$functionsPath = dirname(__DIR__) . '/includes/functions.php';
if (file_exists($functionsPath)) {
    require_once $functionsPath;
} else {
    // Alternative path
    $altFunctionsPath = __DIR__ . '/functions.php';
    if (file_exists($altFunctionsPath)) {
        require_once $altFunctionsPath;
    }
}

// ==================== INITIALIZE DATA FILES ====================
if (function_exists('initializeDataFiles')) {
    initializeDataFiles();
}
?>