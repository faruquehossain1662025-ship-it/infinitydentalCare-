<?php
// Common functions for Healthcare Website

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load JSON data from file
function loadJsonData($file) {
    if (!file_exists($file)) {
        return [];
    }
    $data = file_get_contents($file);
    return json_decode($data, true) ?: [];
}

// Save JSON data to file
function saveJsonData($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Initialize data files with default data
function initializeDataFiles() {
    // Settings
    if (!file_exists(SETTINGS_FILE)) {
        $defaultSettings = [
            'site_name' => 'ইনফিনিটি ডেন্টাল কেয়ার',
            'site_description' => 'আধুনিক যন্ত্রপাতি ও অভিজ্ঞ ডাক্তারের মাধ্যমে সর্বোচ্চ মানের দন্ত চিকিৎসা সেবা',
            'contact_phone' => '+880 1700-000000',
            'contact_email' => 'info@dentalclinic.com',
            'address' => 'ঢাকা, বাংলাদেশ',
            'working_hours' => 'সকাল ৯টা - রাত ৯টা',
            'facebook_url' => '',
            'twitter_url' => '',
            'instagram_url' => '',
            'youtube_url' => '',
            'whatsapp_number' => '+880 1700-000000',
            'whatsapp_enabled' => true,
            'breaking_news_enabled' => true,
            'google_analytics' => '',
            'app_download_url' => '',
            'app_popup_enabled' => true,
            'seo_title' => 'ডেন্টাল ক্লিনিক - সর্বোচ্চ মানের দন্ত চিকিৎসা সেবা',
            'seo_description' => 'আধুনিক যন্ত্রপাতি ও অভিজ্ঞ ডাক্তারের মাধ্যমে দাঁত ও মুখের সর্বোচ্চ মানের চিকিৎসা সেবা',
            'seo_keywords' => 'দন্ত চিকিৎসা, ডেন্টাল ক্লিনিক, দাঁতের ডাক্তার, ঢাকা',
            'admin_username' => 'admin',
            'admin_password' => 'admin123'
        ];
        saveJsonData(SETTINGS_FILE, $defaultSettings);
    }

    // Empty arrays for other files
    $files = [
        BANNERS_FILE => [],
        SERVICES_FILE => [],
        APPOINTMENTS_FILE => [],
        PATIENTS_FILE => [],
        REVIEWS_FILE => [],
        NEWS_FILE => [],
        OFFERS_FILE => [],
        GALLERY_FILE => [],
        INCOME_FILE => [],
        COUPONS_FILE => [],
        POPUPS_FILE => [],
        SOCIAL_FILE => [],
        VISITORS_FILE => [],
        CONTACTS_FILE => []
    ];

    foreach ($files as $file => $data) {
        if (!file_exists($file)) {
            saveJsonData($file, $data);
        }
    }
}

// Get settings
function getSettings() {
    return loadJsonData(SETTINGS_FILE);
}

// Update settings
function updateSettings($settings) {
    return saveJsonData(SETTINGS_FILE, $settings);
}

// Generate unique ID
function generateId() {
    return uniqid('', true);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone
function validatePhone($phone) {
    return preg_match('/^[+]?[0-9\s\-()]+$/', $phone);
}

// Upload file
function uploadFile($file, $allowedTypes = null) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'ফাইল আপলোড করা হয়নি'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'ফাইল সাইজ অনেক বড়'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($allowedTypes && !in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'এই ধরনের ফাইল সাপোর্ট করে না'];
    }

    $fileName = uniqid() . '.' . $extension;
    $uploadPath = UPLOADS_DIR . $fileName;

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'file' => $fileName, 'path' => $uploadPath];
    }

    return ['success' => false, 'message' => 'ফাইল আপলোড করতে ব্যর্থ'];
}

// Delete file
function deleteFile($fileName) {
    $filePath = UPLOADS_DIR . $fileName;
    if (file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Track visitor
function trackVisitor() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    
    $visitors = loadJsonData(VISITORS_FILE);
    
    // Check if visitor already exists today
    $today = date('Y-m-d');
    $exists = false;
    foreach ($visitors as $visitor) {
        if (isset($visitor['ip']) && $visitor['ip'] === $ip && substr($visitor['timestamp'], 0, 10) === $today) {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        $visitors[] = [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'timestamp' => $timestamp,
            'date' => $today
        ];
        saveJsonData(VISITORS_FILE, $visitors);
    }
}

// Get visitor stats
function getVisitorStats() {
    $visitors = loadJsonData(VISITORS_FILE);
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    
    $todayCount = 0;
    $monthCount = 0;
    $totalCount = count($visitors);
    
    foreach ($visitors as $visitor) {
        if (isset($visitor['timestamp'])) {
            if (substr($visitor['timestamp'], 0, 10) === $today) {
                $todayCount++;
            }
            if (substr($visitor['timestamp'], 0, 7) === $thisMonth) {
                $monthCount++;
            }
        }
    }
    
    return [
        'today' => $todayCount,
        'month' => $monthCount,
        'total' => $totalCount
    ];
}

// Check if admin is logged in
function isAdminLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        return false;
    }
    
    // Check session timeout (24 hours)
    if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > (24 * 60 * 60)) {
        session_destroy();
        return false;
    }
    
    return true;
}

// Admin login
function adminLogin($username, $password) {
    $settings = getSettings();
    $adminUsername = $settings['admin_username'] ?? ADMIN_USERNAME;
    $adminPassword = $settings['admin_password'] ?? ADMIN_PASSWORD;
    
    if ($username === $adminUsername && $password === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        return true;
    }
    
    return false;
}

// Require admin login - redirects to login if not logged in
function requireLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Admin logout
function adminLogout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check admin session timeout
function checkAdminTimeout() {
    if (isAdminLoggedIn()) {
        if (time() - $_SESSION['admin_login_time'] > SESSION_TIMEOUT) {
            adminLogout();
            return false;
        }
        $_SESSION['admin_login_time'] = time(); // Refresh session
    }
    return isAdminLoggedIn();
}

// Format date in Bengali
function formatDateBengali($date) {
    $englishMonths = ['January', 'February', 'March', 'April', 'May', 'June', 
                     'July', 'August', 'September', 'October', 'November', 'December'];
    
    $bengaliMonths = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
                     'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
    
    $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bengaliNumerals = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    
    $formattedDate = date('d F Y, g:i A', strtotime($date));
    $formattedDate = str_replace($englishMonths, $bengaliMonths, $formattedDate);
    $formattedDate = str_replace($englishNumerals, $bengaliNumerals, $formattedDate);
    
    return $formattedDate;
}

// Generate appointment number
function generateAppointmentNumber() {
    return 'APT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// ==================== EMAIL FUNCTIONS (Without PHPMailer) ====================

// Built-in mail() function দিয়ে ইমেইল পাঠানো
function sendEmail($to, $subject, $body, $isHTML = true, $appointmentData = null) {
    if (!validateEmail($to)) {
        logEmailSent($to, $subject, $body, 'failed', 'Invalid email address', $appointmentData);
        return false;
    }
    
    // Email headers সেট করা
    $headers = [];
    
    if ($isHTML) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
    } else {
        $headers[] = 'Content-type: text/plain; charset=UTF-8';
    }
    
    $headers[] = 'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . FROM_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'X-Priority: 3';
    
    // Subject কে UTF-8 এ encode করা বাংলা text এর জন্য
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    
    // ইমেইল পাঠানো
    $result = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    
    // লগ রাখা
    $status = $result ? 'success' : 'failed';
    $error = $result ? '' : 'PHP mail() function failed - Check server mail configuration';
    logEmailSent($to, $subject, $body, $status, $error, $appointmentData);
    
    return $result;
}

// SMTP দিয়ে ইমেইল পাঠানো (Advanced - PHPMailer ছাড়াই)
function sendEmailSMTP($to, $subject, $body, $isHTML = true, $appointmentData = null) {
    $smtp_server = SMTP_HOST;
    $smtp_port = SMTP_PORT;
    $smtp_username = SMTP_USERNAME;
    $smtp_password = SMTP_PASSWORD;
    $from_email = FROM_EMAIL;
    $from_name = FROM_NAME;
    
    // SMTP connection
    $socket = @fsockopen($smtp_server, $smtp_port, $errno, $errstr, 10);
    
    if (!$socket) {
        logEmailSent($to, $subject, $body, 'failed', "SMTP Connection failed: $errstr ($errno)", $appointmentData);
        return false;
    }
    
    // SMTP communication function
    $smtp_response = function($socket, $expected_code = 250) {
        $response = fgets($socket, 512);
        $code = intval(substr($response, 0, 3));
        return $code == $expected_code;
    };
    
    // Wait for initial response
    if (!$smtp_response($socket, 220)) {
        fclose($socket);
        logEmailSent($to, $subject, $body, 'failed', 'SMTP server initial response failed', $appointmentData);
        return false;
    }
    
    // SMTP commands
    $commands = [
        ["EHLO " . $_SERVER['HTTP_HOST'] . "\r\n", 250],
        ["AUTH LOGIN\r\n", 334],
        [base64_encode($smtp_username) . "\r\n", 334],
        [base64_encode($smtp_password) . "\r\n", 235],
        ["MAIL FROM: <$from_email>\r\n", 250],
        ["RCPT TO: <$to>\r\n", 250],
        ["DATA\r\n", 354]
    ];
    
    foreach ($commands as $i => $command_data) {
        list($command, $expected) = $command_data;
        fwrite($socket, $command);
        
        if (!$smtp_response($socket, $expected)) {
            fclose($socket);
            logEmailSent($to, $subject, $body, 'failed', "SMTP command failed at step " . ($i + 1), $appointmentData);
            return false;
        }
    }
    
    // Email content
    $email_content = "";
    $email_content .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $email_content .= "From: $from_name <$from_email>\r\n";
    $email_content .= "To: $to\r\n";
    $email_content .= "MIME-Version: 1.0\r\n";
    
    if ($isHTML) {
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $email_content .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }
    
    $email_content .= "Content-Transfer-Encoding: 8bit\r\n";
    $email_content .= "\r\n";
    $email_content .= $body . "\r\n";
    $email_content .= ".\r\n";
    
    fwrite($socket, $email_content);
    
    if (!$smtp_response($socket, 250)) {
        fclose($socket);
        logEmailSent($to, $subject, $body, 'failed', 'Email content sending failed', $appointmentData);
        return false;
    }
    
    // End connection
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    
    logEmailSent($to, $subject, $body, 'success', '', $appointmentData);
    return true;
}

// অ্যাপয়েন্টমেন্ট স্ট্যাটাস আপডেট ইমেইল পাঠানো
function sendAppointmentStatusEmail($appointment, $oldStatus, $newStatus, $adminNote = '') {
    $email = $appointment['email'] ?? '';
    
    if (empty($email) || !validateEmail($email)) {
        return false;
    }

    // ইমেইল সাবজেক্ট এবং বডি তৈরি করা
    $subject = "অ্যাপয়েন্টমেন্ট স্ট্যাটাস আপডেট - " . SITE_NAME;
    
    // HTML ইমেইল টেমপ্লেট
    $emailBody = createAppointmentStatusEmailTemplate($appointment, $oldStatus, $newStatus, $adminNote);
    
    // প্রথমে built-in mail() try করা, ব্যর্থ হলে SMTP
    if (!sendEmail($email, $subject, $emailBody, true, $appointment)) {
        // Built-in mail failed, try SMTP
        return sendEmailSMTP($email, $subject, $emailBody, true, $appointment);
    }
    
    return true;
}

// ইমেইল টেমপ্লেট তৈরি করা
function createAppointmentStatusEmailTemplate($appointment, $oldStatus, $newStatus, $adminNote = '') {
    $statusText = '';
    $statusColor = '';
    $statusMessage = '';

    switch ($newStatus) {
        case 'approved':
            $statusText = 'অনুমোদিত';
            $statusColor = '#28a745';
            $statusMessage = 'আপনার অ্যাপয়েন্টমেন্ট অনুমোদিত হয়েছে। নির্দিষ্ট সময়ে আমাদের ক্লিনিকে উপস্থিত হন।';
            break;
        case 'rejected':
            $statusText = 'বাতিল';
            $statusColor = '#dc3545';
            $statusMessage = 'দুঃখিত, আপনার অ্যাপয়েন্টমেন্ট বাতিল করা হয়েছে।';
            break;
        case 'pending':
            $statusText = 'অপেক্ষমাণ';
            $statusColor = '#ffc107';
            $statusMessage = 'আপনার অ্যাপয়েন্টমেন্ট পর্যালোচনা করা হচ্ছে।';
            break;
        default:
            $statusText = $newStatus;
            $statusColor = '#6c757d';
            $statusMessage = 'আপনার অ্যাপয়েন্টমেন্ট স্ট্যাটাস আপডেট করা হয়েছে।';
    }

    // Settings load করা
    $settings = getSettings();
    $contactPhone = $settings['contact_phone'] ?? '+880 1700-000000';
    $address = $settings['address'] ?? 'ঢাকা, বাংলাদেশ';

    $template = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>অ্যাপয়েন্টমেন্ট স্ট্যাটাস আপডেট</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background-color: #007bff; color: white; text-align: center; padding: 30px 20px; }
            .header h1 { margin: 0 0 10px 0; font-size: 24px; }
            .header h2 { margin: 0; font-size: 18px; font-weight: normal; }
            .content { padding: 30px; }
            .greeting { font-size: 18px; color: #333; margin-bottom: 20px; }
            .status-badge { display: inline-block; padding: 12px 24px; border-radius: 25px; color: white; font-weight: bold; margin: 15px 0; font-size: 16px; text-transform: uppercase; }
            .appointment-details { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #007bff; }
            .appointment-details h4 { color: #007bff; margin-top: 0; }
            .appointment-details p { margin: 8px 0; }
            .note { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
            .note h4 { color: #856404; margin-top: 0; }
            .contact-info { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { background-color: #6c757d; color: white; text-align: center; padding: 20px; font-size: 14px; }
            .footer p { margin: 5px 0; }
            strong { color: #495057; }
            .message { font-size: 16px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . SITE_NAME . '</h1>
                <h2>অ্যাপয়েন্টমেন্ট স্ট্যাটাস আপডেট</h2>
            </div>
            
            <div class="content">
                <div class="greeting">প্রিয় ' . htmlspecialchars($appointment['name']) . ',</div>
                
                <div class="message">' . $statusMessage . '</div>
                
                <div style="text-align: center;">
                    <span class="status-badge" style="background-color: ' . $statusColor . ';">
                        স্ট্যাটাস: ' . $statusText . '
                    </span>
                </div>
                
                <div class="appointment-details">
                    <h4>অ্যাপয়েন্টমেন্টের বিস্তারিত:</h4>
                    <p><strong>অ্যাপয়েন্টমেন্ট নম্বর:</strong> ' . htmlspecialchars($appointment['appointment_number']) . '</p>
                    <p><strong>সেবা:</strong> ' . htmlspecialchars($appointment['service']) . '</p>
                    <p><strong>পছন্দের তারিখ:</strong> ' . htmlspecialchars($appointment['preferred_date']) . '</p>
                    <p><strong>পছন্দের সময়:</strong> ' . htmlspecialchars($appointment['preferred_time']) . '</p>
                    <p><strong>ফোন নম্বর:</strong> ' . htmlspecialchars($appointment['phone']) . '</p>';

    if (!empty($appointment['problem_description'])) {
        $template .= '
                    <p><strong>সমস্যার বিবরণ:</strong> ' . htmlspecialchars($appointment['problem_description']) . '</p>';
    }

    $template .= '
                </div>';

    if (!empty($adminNote)) {
        $template .= '
                <div class="note">
                    <h4>বিশেষ নোট:</h4>
                    <p>' . nl2br(htmlspecialchars($adminNote)) . '</p>
                </div>';
    }

    $template .= '
                <div class="contact-info">
                    <h4>যোগাযোগের তথ্য:</h4>
                    <p><strong>ফোন:</strong> ' . htmlspecialchars($contactPhone) . '</p>
                    <p><strong>ইমেইল:</strong> ' . FROM_EMAIL . '</p>
                    <p><strong>ঠিকানা:</strong> ' . htmlspecialchars($address) . '</p>
                </div>
                
                <p>আপনার সুস্বাস্থ্য কামনায়,<br>
                <strong>' . SITE_NAME . ' টিম</strong></p>
            </div>
            
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. সকল অধিকার সংরক্ষিত।</p>
                <p>এই ইমেইলটি স্বয়ংক্রিয়ভাবে পাঠানো হয়েছে। প্রয়োজনে আমাদের সাথে যোগাযোগ করুন।</p>
            </div>
        </div>
    </body>
    </html>';

    return $template;
}

// ইমেইল লগ রাখা
function logEmailSent($to, $subject, $body, $status, $error = '', $appointmentData = null) {
    $logData = [
        'id' => generateId(),
        'to' => $to,
        'subject' => $subject,
        'status' => $status,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s'),
        'appointment_id' => $appointmentData['id'] ?? null,
        'appointment_number' => $appointmentData['appointment_number'] ?? null
    ];

    $emailLogFile = defined('EMAIL_LOG_FILE') ? EMAIL_LOG_FILE : DATA_DIR . 'email_log.json';
    $logs = loadJsonData($emailLogFile);
    $logs[] = $logData;
    
    // শুধু শেষ 1000 লগ রাখা (পারফরমেন্সের জন্য)
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    
    saveJsonData($emailLogFile, $logs);
}

// ইমেইল লগ পড়া
function getEmailLogs($limit = 50) {
    $emailLogFile = defined('EMAIL_LOG_FILE') ? EMAIL_LOG_FILE : DATA_DIR . 'email_log.json';
    $logs = loadJsonData($emailLogFile);
    return array_slice(array_reverse($logs), 0, $limit);
}

// টেস্ট ইমেইল পাঠানো
function sendTestEmail($to = null) {
    $testEmail = $to ?? FROM_EMAIL;
    $subject = "টেস্ট ইমেইল - " . SITE_NAME;
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
            <h2 style='color: #007bff; text-align: center;'>টেস্ট ইমেইল</h2>
            <p>এই ইমেইলটি সফলভাবে পাঠানো হয়েছে!</p>
            <hr>
            <p><strong>সময়:</strong> " . date('d/m/Y H:i:s') . "</p>
            <p><strong>সার্ভার:</strong> " . $_SERVER['HTTP_HOST'] . "</p>
            <p><strong>ইমেইল সিস্টেম:</strong> PHP Built-in Mail</p>
            <p><strong>SMTP সেটিংস:</strong></p>
            <ul>
                <li>SMTP Host: " . SMTP_HOST . "</li>
                <li>SMTP Port: " . SMTP_PORT . "</li>
                <li>From Email: " . FROM_EMAIL . "</li>
            </ul>
            <hr>
            <p style='text-align: center; color: #666;'>
                <small>" . SITE_NAME . " - ইমেইল সিস্টেম টেস্ট</small>
            </p>
        </div>
    </body>
    </html>";
    
    return sendEmail($testEmail, $subject, $body, true);
}

// ==================== SMS FUNCTIONS ====================

// বাংলাদেশী মোবাইল নম্বর validate করা
function isValidBangladeshiNumber($phone) {
    // শুধু নম্বার রাখা
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // বিভিন্ন ফরম্যাট থেকে 01xxxxxxxxx বের করা
    $localNumber = '';
    
    if (substr($phone, 0, 2) == '01' && strlen($phone) == 11) {
        // 01xxxxxxxxx ফরম্যাট
        $localNumber = $phone;
    } elseif (substr($phone, 0, 4) == '8801' && strlen($phone) == 13) {
        // 8801xxxxxxxxx ফরম্যাট
        $localNumber = '0' . substr($phone, 3);
    } elseif (substr($phone, 0, 3) == '880' && strlen($phone) == 12) {
        // 8801xxxxxxxx ফরম্যাট (ভুল)
        $localNumber = '0' . substr($phone, 3);
    }
    
    // বাংলাদেশী অপারেটর চেক করা
    if (strlen($localNumber) == 11 && substr($localNumber, 0, 2) == '01') {
        $operatorCode = substr($localNumber, 2, 1);
        // বাংলাদেশী অপারেটর কোড: 3,4,5,6,7,8,9
        if (in_array($operatorCode, ['3', '4', '5', '6', '7', '8', '9'])) {
            return true;
        }
    }
    
    return false;
}

// Format phone number for Alpha Net SMS API (+8801xxxxxxxxx format)
function formatPhoneNumber($phone) {
    // প্রথমে validate করা
    if (!isValidBangladeshiNumber($phone)) {
        return false; // ভ্যালিড বাংলাদেশী নম্বর না হলে false return
    }
    
    // শুধু নম্বার রাখা
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // সব ফরম্যাট থেকে 01xxxxxxxxx বের করা
    $localNumber = '';
    
    if (substr($phone, 0, 2) == '01' && strlen($phone) == 11) {
        // 01xxxxxxxxx → 01xxxxxxxxx
        $localNumber = $phone;
    } elseif (substr($phone, 0, 4) == '8801' && strlen($phone) == 13) {
        // 8801xxxxxxxxx → 01xxxxxxxxx
        $localNumber = '0' . substr($phone, 3);
    } elseif (substr($phone, 0, 3) == '880' && strlen($phone) == 12) {
        // 8801xxxxxxxx → 01xxxxxxxxx (ভুল ফরম্যাট ঠিক করা)
        $localNumber = '0' . substr($phone, 3);
    } else {
        return false;
    }
    
    // Final validation এবং Alpha Net format এ convert (+8801xxxxxxxxx)
    if (strlen($localNumber) == 11 && substr($localNumber, 0, 2) == '01') {
        return '+880' . $localNumber; // +88001xxxxxxxxx
    }
    
    return false;
}

// Main SMS function with validation
function sendSMS($phone, $message, $appointmentData = null) {
    // ফোন নম্বার ফরম্যাট এবং validate করা
    $formattedPhone = formatPhoneNumber($phone);
    
    if ($formattedPhone === false) {
        // ভ্যালিড বাংলাদেশী নম্বর না হলে error log করা
        logSMSResponse($phone, $message, 'Invalid Bangladesh Number', 'Phone number is not a valid Bangladeshi mobile number');
        return false;
    }
    
    // Alpha Net SMS API দিয়ে পাঠানো
    return sendSMSViaAlphaNet($formattedPhone, $message);
}

// Alpha Net SMS API - Updated for +880 format
function sendSMSViaAlphaNet($phone, $message) {
    $apiKey = SMS_API_KEY;
    
    // GET method ব্যবহার করা (আপনার API +880 format চায়)
    $url = "https://api.sms.net.bd/sendsms";
    
    // Parameters
    $params = [
        'api_key' => $apiKey,
        'msg' => $message,
        'to' => $phone,
        'sender_id' => SMS_SENDER_ID
    ];
    
    $url .= '?' . http_build_query($params);
    
    // cURL দিয়ে API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Response লগ করা
    logSMSResponse($phone, $message, $response, $error, $httpCode);
    
    // Success check করা
    if ($httpCode == 200 && !empty($response)) {
        $responseData = json_decode($response, true);
        return isset($responseData['status']) && $responseData['status'] == 'success';
    }
    
    return false;
}

// SMS response লগ করা
function logSMSResponse($phone, $message, $response, $error = '', $httpCode = 0) {
    $logData = [
        'phone' => $phone,
        'message' => $message,
        'response' => $response,
        'error' => $error,
        'http_code' => $httpCode,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $logFile = DATA_DIR . 'sms_log.json';
    $logs = loadJsonData($logFile);
    $logs[] = $logData;
    
    // শুধু শেষ 500 লগ রাখা
    if (count($logs) > 500) {
        $logs = array_slice($logs, -500);
    }
    
    saveJsonData($logFile, $logs);
}

// SMS Balance চেক করা
function checkSMSBalance() {
    $apiKey = SMS_API_KEY;
    $url = "https://api.sms.net.bd/balance?api_key=" . $apiKey;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && !empty($response)) {
        $data = json_decode($response, true);
        return $data['balance'] ?? false;
    }
    
    return false;
}

// অ্যাপয়েন্টমেন্ট অনুমোদনের SMS
function sendAppointmentApprovalSMS($appointment, $adminNote = '') {
    $phone = $appointment['phone'];
    $name = $appointment['name'];
    $appointmentNumber = $appointment['appointment_number'];
    $date = $appointment['preferred_date'];
    $time = $appointment['preferred_time'];
    
    $message = "প্রিয় {$name}, আপনার অ্যাপয়েন্টমেন্ট ({$appointmentNumber}) অনুমোদিত হয়েছে। তারিখ: {$date}, সময়: {$time}।";
    
    if (!empty($adminNote)) {
        $message .= " বিশেষ নির্দেশনা: {$adminNote}।";
    }
    
    $message .= " - " . SITE_NAME;
    
    return sendSMS($phone, $message, $appointment);
}





// অ্যাপয়েন্টমেন্ট বাতিলের SMS
function sendAppointmentRejectionSMS($appointment, $reason = '') {
    $phone = $appointment['phone'];
    $name = $appointment['name'];
    $appointmentNumber = $appointment['appointment_number'];
    
    $message = "প্রিয় {$name}, দুঃখিত! আপনার অ্যাপয়েন্টমেন্ট ({$appointmentNumber}) বাতিল করা হয়েছে।";
    
    if (!empty($reason)) {
        $message .= " কারণ: {$reason}।";
    }
    
    $message .= " আরও তথ্যের জন্য যোগাযোগ করুন। - " . SITE_NAME;
    
    return sendSMS($phone, $message, $appointment);
}

?>