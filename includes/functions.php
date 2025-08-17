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
            'contact_email' => 'info@infinitydentalcare.top',
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
            'seo_title' => 'ইনফিনিটি ডেন্টাল কেয়ার - সর্বোচ্চ মানের দন্ত চিকিৎসা সেবা',
            'seo_description' => 'আধুনিক যন্ত্রপাতি ও অভিজ্ঞ ডাক্তারের মাধ্যমে দাঁত ও মুখের সর্বোচ্চ মানের চিকিৎসা সেবা',
            'seo_keywords' => 'দন্ত চিকিৎসা, ডেন্টাল ক্লিনিক, দাঁতের ডাক্তার, ঢাকা',
            'admin_username' => 'admin',
            'admin_password' => 'admin123'
        ];
        saveJsonData(SETTINGS_FILE, $defaultSettings);
    }

    // All possible files that might be used in the system
    $files = [
        // Core files
        BANNERS_FILE => [],
        SERVICES_FILE => [],
        APPOINTMENTS_FILE => [],
        PATIENTS_FILE => [],
        REVIEWS_FILE => [],
        NEWS_FILE => [],
        OFFERS_FILE => [],
        GALLERY_FILE => [],
        INCOME_FILE => [],
        EXPENSES_FILE => [],
        COUPONS_FILE => [],
        POPUPS_FILE => [],
        SOCIAL_FILE => [],
        VISITORS_FILE => [],
        CONTACTS_FILE => [],
        INQUIRIES_FILE => [], // Added missing file
        EMAIL_LOG_FILE => [],
        
        // Extended files
        DOCTORS_FILE => [],
        TESTIMONIALS_FILE => [],
        BLOG_FILE => [],
        FAQ_FILE => [],
        CATEGORIES_FILE => [],
        NOTIFICATIONS_FILE => [],
        BACKUP_FILE => [],
        ANALYTICS_FILE => [],
        SMS_LOG_FILE => [],
        SUBSCRIBERS_FILE => [],
        FEEDBACK_FILE => [],
        TREATMENTS_FILE => [],
        SCHEDULES_FILE => [],
        PRESCRIPTIONS_FILE => [],
        INVOICES_FILE => [],
        PAYMENTS_FILE => [],
        REPORTS_FILE => [],
        REMINDERS_FILE => [],
        STAFF_FILE => [],
        DEPARTMENTS_FILE => [],
        EQUIPMENT_FILE => [],
        INVENTORY_FILE => [],
        SUPPLIERS_FILE => [],
        PURCHASES_FILE => [],
        SALES_FILE => [],
        DISCOUNTS_FILE => [],
        PROMOTIONS_FILE => [],
        CAMPAIGNS_FILE => [],
        EVENTS_FILE => [],
        BOOKINGS_FILE => [],
        RESERVATIONS_FILE => [],
        WAITING_LIST_FILE => [],
        EMERGENCY_CONTACTS_FILE => [],
        MEDICAL_HISTORY_FILE => [],
        ALLERGIES_FILE => [],
        MEDICATIONS_FILE => [],
        INSURANCE_FILE => [],
        CLAIMS_FILE => [],
        REFERRALS_FILE => [],
        FOLLOWUPS_FILE => [],
        SURVEYS_FILE => [],
        FORMS_FILE => [],
        TEMPLATES_FILE => [],
        WORKFLOWS_FILE => [],
        TASKS_FILE => [],
        NOTES_FILE => [],
        ATTACHMENTS_FILE => [],
        LOGS_FILE => [],
        AUDIT_TRAIL_FILE => [],
        PERMISSIONS_FILE => [],
        ROLES_FILE => [],
        USERS_FILE => [],
        SESSIONS_FILE => [],
        TOKENS_FILE => [],
        API_KEYS_FILE => [],
        WEBHOOKS_FILE => [],
        INTEGRATIONS_FILE => [],
        CONFIGURATIONS_FILE => [],
        PREFERENCES_FILE => [],
        THEMES_FILE => [],
        LANGUAGES_FILE => [],
        TRANSLATIONS_FILE => [],
        CACHE_FILE => [],
        TEMP_FILE => []
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

// ==================== EMAIL FUNCTIONS ====================

// Email log function
function logEmailSent($to, $subject, $body, $status, $error = '', $appointmentData = null) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject,
        'status' => $status,
        'error' => $error,
        'appointment_data' => $appointmentData,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $logs = loadJsonData(EMAIL_LOG_FILE);
    $logs[] = $logEntry;
    
    // Keep only last 1000 logs
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    
    saveJsonData(EMAIL_LOG_FILE, $logs);
}

// Gmail SMTP email sending function (improved)
function sendEmailSMTP($to, $subject, $body, $isHTML = true, $appointmentData = null) {
    try {
        if (!validateEmail($to)) {
            logEmailSent($to, $subject, $body, 'failed', 'Invalid email address', $appointmentData);
            return false;
        }

        $smtp_server = SMTP_HOST;
        $smtp_port = SMTP_PORT;
        $smtp_username = SMTP_USERNAME;
        $smtp_password = SMTP_PASSWORD;
        $smtp_encryption = SMTP_ENCRYPTION;
        $from_email = FROM_EMAIL;
        $from_name = FROM_NAME;

        if (EMAIL_DEBUG) {
            error_log("Attempting SMTP connection to: $smtp_server:$smtp_port");
        }

        // Create secure context for Gmail SSL
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'cafile' => '',
                'local_cert' => '',
                'passphrase' => ''
            ]
        ]);

        // Connect to Gmail SMTP server
        $socket = @stream_socket_client("ssl://{$smtp_server}:{$smtp_port}", $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            $error = "Gmail SMTP Connection failed: $errstr ($errno)";
            if (EMAIL_DEBUG) {
                error_log($error);
            }
            logEmailSent($to, $subject, $body, 'failed', $error, $appointmentData);
            return false;
        }

        // Helper function for SMTP responses
        $smtp_response = function($socket, $expected_code = 250) {
            $response = fgets($socket, 512);
            $code = intval(substr($response, 0, 3));
            if (EMAIL_DEBUG) {
                error_log("SMTP Response: $response");
            }
            return $code == $expected_code;
        };

        // Wait for Gmail greeting
        if (!$smtp_response($socket, 220)) {
            fclose($socket);
            $error = 'Gmail SMTP server greeting failed';
            logEmailSent($to, $subject, $body, 'failed', $error, $appointmentData);
            return false;
        }

        // SMTP conversation for Gmail
        $commands = [
            ["EHLO " . gethostname() . "\r\n", 250],
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
                $error = "Gmail SMTP command failed at step " . ($i + 1) . ": $command";
                if (EMAIL_DEBUG) {
                    error_log($error);
                }
                logEmailSent($to, $subject, $body, 'failed', $error, $appointmentData);
                return false;
            }
        }

        // Prepare email headers and body for Gmail
        $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encoded_from_name = '=?UTF-8?B?' . base64_encode($from_name) . '?=';
        
        $email_data = "From: $encoded_from_name <$from_email>\r\n";
        $email_data .= "To: <$to>\r\n";
        $email_data .= "Subject: $encoded_subject\r\n";
        $email_data .= "Date: " . date('r') . "\r\n";
        $email_data .= "Message-ID: <" . uniqid() . "@gmail.com>\r\n";
        
        if ($isHTML) {
            $email_data .= "MIME-Version: 1.0\r\n";
            $email_data .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email_data .= "Content-Transfer-Encoding: 8bit\r\n";
        } else {
            $email_data .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $email_data .= "Content-Transfer-Encoding: 8bit\r\n";
        }
        
        $email_data .= "\r\n";
        $email_data .= $body;
        $email_data .= "\r\n.\r\n";

        // Send email data to Gmail
        fwrite($socket, $email_data);
        
        if (!$smtp_response($socket, 250)) {
            fclose($socket);
            $error = 'Gmail email data sending failed';
            logEmailSent($to, $subject, $body, 'failed', $error, $appointmentData);
            return false;
        }

        // Quit
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        if (EMAIL_DEBUG) {
            error_log("Email sent successfully via Gmail SMTP to: $to");
        }

        logEmailSent($to, $subject, $body, 'success', '', $appointmentData);
        return true;

    } catch (Exception $e) {
        $error = 'Gmail SMTP Exception: ' . $e->getMessage();
        if (EMAIL_DEBUG) {
            error_log($error);
        }
        logEmailSent($to, $subject, $body, 'failed', $error, $appointmentData);
        return false;
    }
}

// Built-in mail() function
function sendEmailBuiltin($to, $subject, $body, $isHTML = true, $appointmentData = null) {
    if (!validateEmail($to)) {
        logEmailSent($to, $subject, $body, 'failed', 'Invalid email address', $appointmentData);
        return false;
    }
    
    $headers = [];
    
    if ($isHTML) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
    } else {
        $headers[] = 'Content-type: text/plain; charset=UTF-8';
    }
    
    $encoded_from_name = '=?UTF-8?B?' . base64_encode(FROM_NAME) . '?=';
    $headers[] = 'From: ' . $encoded_from_name . ' <' . FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . FROM_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'X-Priority: 3';
    
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    
    $result = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    
    $status = $result ? 'success' : 'failed';
    $error = $result ? '' : 'PHP mail() function failed';
    logEmailSent($to, $subject, $body, $status, $error, $appointmentData);
    
    return $result;
}

// Main send email function
function sendEmail($to, $subject, $body, $isHTML = true, $appointmentData = null) {
    // Try SMTP first, then fallback to built-in mail()
    if (sendEmailSMTP($to, $subject, $body, $isHTML, $appointmentData)) {
        return true;
    }
    return sendEmailBuiltin($to, $subject, $body, $isHTML, $appointmentData);
}

// Create appointment status email template
function createAppointmentStatusEmailTemplate($appointment, $oldStatus, $newStatus, $adminNote = '') {
    $patientName = $appointment['name'] ?? $appointment['patient_name'] ?? 'প্রিয় রোগী';
    $appointmentNumber = $appointment['appointment_number'] ?? 'N/A';
    $appointmentDate = $appointment['preferred_date'] ?? $appointment['appointment_date'] ?? '';
    $appointmentTime = $appointment['preferred_time'] ?? $appointment['appointment_time'] ?? '';
    
    $statusTranslation = [
        'pending' => 'অপেক্ষমান',
        'approved' => 'অনুমোদিত',
        'confirmed' => 'নিশ্চিত',
        'rejected' => 'বাতিল',
        'cancelled' => 'বাতিল',
        'completed' => 'সম্পন্ন'
    ];
    
    $oldStatusBangla = $statusTranslation[$oldStatus] ?? $oldStatus;
    $newStatusBangla = $statusTranslation[$newStatus] ?? $newStatus;
    
    $html = '
    <!DOCTYPE html>
    <html lang="bn">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>অ্যাপয়েন্টমেন্ট স্ট্যাটাস আপডেট</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;">
                <h1 style="margin: 0; font-size: 28px; font-weight: bold;">' . SITE_NAME . '</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">অ্যাপয়েন্টমেন্ট স্ট্যাটাস আপডেট</p>
            </div>
            
            <div style="padding: 40px 30px;">
                <h2 style="color: #333; margin-bottom: 20px; font-size: 24px;">প্রিয় ' . htmlspecialchars($patientName) . ',</h2>
                
                <p style="font-size: 16px; margin-bottom: 25px;">আপনার অ্যাপয়েন্টমেন্টের স্ট্যাটাস আপডেট করা হয়েছে।</p>
                
                <div style="background-color: #f8f9fa; border-left: 4px solid #007bff; padding: 20px; margin: 25px 0; border-radius: 5px;">
                    <h3 style="color: #007bff; margin-top: 0; margin-bottom: 15px;">অ্যাপয়েন্টমেন্ট বিবরণ</h3>
                    <p style="margin: 8px 0;"><strong>অ্যাপয়েন্টমেন্ট নম্বর:</strong> ' . htmlspecialchars($appointmentNumber) . '</p>
                    <p style="margin: 8px 0;"><strong>তারিখ:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                    <p style="margin: 8px 0;"><strong>সময়:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
                </div>
                
                <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 25px 0; border-radius: 5px; text-align: center;">
                    <h3 style="color: #856404; margin-top: 0;">স্ট্যাটাস পরিবর্তন</h3>
                    <div style="display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 15px;">
                        <span style="background-color: #6c757d; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold;">' . $oldStatusBangla . '</span>
                        <span style="font-size: 20px; color: #28a745;">→</span>
                        <span style="background-color: #28a745; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold;">' . $newStatusBangla . '</span>
                    </div>
                </div>';
    
    if (!empty($adminNote)) {
        $html .= '
                <div style="background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 20px; margin: 25px 0; border-radius: 5px;">
                    <h3 style="color: #1976D2; margin-top: 0; margin-bottom: 10px;">অতিরিক্ত তথ্য</h3>
                    <p style="margin: 0; font-style: italic;">' . nl2br(htmlspecialchars($adminNote)) . '</p>
                </div>';
    }
    
    $html .= '
                <div style="text-align: center; margin: 30px 0;">
                    <p style="font-size: 16px; margin-bottom: 20px;">কোনো প্রশ্ন থাকলে আমাদের সাথে যোগাযোগ করুন।</p>
                    <a href="tel:+8801700000000" style="display: inline-block; background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;">📞 কল করুন</a>
                    <a href="' . SITE_URL . '" style="display: inline-block; background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;">🌐 ওয়েবসাইট দেখুন</a>
                </div>
                
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <p style="color: #666; font-size: 14px; text-align: center; margin: 20px 0;">
                    ধন্যবাদ,<br>
                    <strong>' . SITE_NAME . ' টিম</strong>
                </p>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;">
                <p style="margin: 0; color: #666; font-size: 12px;">
                    © ' . date('Y') . ' ' . SITE_NAME . ' - সকল অধিকার সংরক্ষিত<br>
                    এই ইমেইলটি স্বয়ংক্রিয়ভাবে পাঠানো হয়েছে। অনুগ্রহ করে এর উত্তর দেবেন না।
                </p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Send appointment status email
function sendAppointmentStatusEmail($appointment, $oldStatus, $newStatus, $adminNote = '') {
    $email = $appointment['email'] ?? '';

    if (empty($email) || !validateEmail($email)) {
        return false;
    }

    $subject = "অ্যাপয়েন্টমেন্ট স্ট্যাটাস আপডেট - " . SITE_NAME;
    $emailBody = createAppointmentStatusEmailTemplate($appointment, $oldStatus, $newStatus, $adminNote);

    return sendEmail($email, $subject, $emailBody, true, $appointment);
}

// Send appointment confirmation email
function sendAppointmentConfirmationEmail($appointment) {
    $email = $appointment['email'] ?? '';
    if (empty($email) || !validateEmail($email)) {
        return false;
    }

    $subject = "অ্যাপয়েন্টমেন্ট নিশ্চিতকরণ - " . SITE_NAME;
    
    $patientName = $appointment['name'] ?? $appointment['patient_name'] ?? 'প্রিয় রোগী';
    $appointmentNumber = $appointment['appointment_number'] ?? '';
    $appointmentDate = $appointment['preferred_date'] ?? $appointment['appointment_date'] ?? '';
    $appointmentTime = $appointment['preferred_time'] ?? $appointment['appointment_time'] ?? '';
    
    $body = '
    <!DOCTYPE html>
    <html lang="bn">
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
            <h2 style="color: #28a745; text-align: center;">অ্যাপয়েন্টমেন্ট নিশ্চিত করা হয়েছে!</h2>
            <p>প্রিয় ' . htmlspecialchars($patientName) . ',</p>
            <p>আপনার অ্যাপয়েন্টমেন্ট সফলভাবে বুক করা হয়েছে।</p>
            
            <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
                <h3 style="color: #007bff;">অ্যাপয়েন্টমেন্ট বিবরণ</h3>
                <p><strong>অ্যাপয়েন্টমেন্ট নম্বর:</strong> ' . htmlspecialchars($appointmentNumber) . '</p>
                <p><strong>তারিখ:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                <p><strong>সময়:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
            </div>
            
            <p>অনুগ্রহ করে সময়মতো উপস্থিত হন। ধন্যবাদ।</p>
            
            <p style="text-align: center; margin-top: 30px;">
                <strong>' . SITE_NAME . '</strong><br>
                <small style="color: #666;">এই ইমেইলটি স্বয়ংক্রিয়ভাবে পাঠানো হয়েছে।</small>
            </p>
        </div>
    </body>
    </html>';
    
    return sendEmail($email, $subject, $body, true, $appointment);
}

// Send contact email
function sendContactEmail($contactData) {
    $subject = "নতুন যোগাযোগ বার্তা - " . SITE_NAME;
    
    $body = '
    <!DOCTYPE html>
    <html lang="bn">
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
            <h2 style="color: #2c5aa0;">নতুন যোগাযোগ বার্তা</h2>
            <p><strong>নাম:</strong> ' . htmlspecialchars($contactData['name']) . '</p>
            <p><strong>ইমেইল:</strong> ' . htmlspecialchars($contactData['email']) . '</p>
            <p><strong>ফোন:</strong> ' . htmlspecialchars($contactData['phone']) . '</p>
            <p><strong>বিষয়:</strong> ' . htmlspecialchars($contactData['subject'] ?? 'N/A') . '</p>
            <p><strong>বার্তা:</strong></p>
            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px;">
                ' . nl2br(htmlspecialchars($contactData['message'])) . '
            </div>
            <p style="color: #666; font-size: 12px; margin-top: 20px;">
                পাঠানোর সময়: ' . date('Y-m-d H:i:s') . '
            </p>
        </div>
    </body>
    </html>';
    
    $settings = getSettings();
    $adminEmail = $settings['contact_email'] ?? 'admin@example.com';
    
    return sendEmail($adminEmail, $subject, $body, true, $contactData);
}

// Test SMTP connection
function testSMTPConnection() {
    try {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        if (SMTP_ENCRYPTION === 'ssl') {
            $socket = @stream_socket_client("ssl://" . SMTP_HOST . ":" . SMTP_PORT, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        } else {
            $socket = @stream_socket_client(SMTP_HOST . ":" . SMTP_PORT, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        }

        if (!$socket) {
            return ['success' => false, 'message' => "Connection failed: $errstr ($errno)"];
        }

        $response = fgets($socket, 512);
        $code = intval(substr($response, 0, 3));
        
        fclose($socket);
        
        if ($code == 220) {
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } else {
            return ['success' => false, 'message' => "Server responded with code: $code"];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

// Get email logs
function getEmailLogs($limit = 50) {
    $logs = loadJsonData(EMAIL_LOG_FILE);
    return array_slice(array_reverse($logs), 0, $limit);
}

// ==================== SMS FUNCTIONS ====================

// Check SMS balance
function checkSMSBalance() {
    try {
        if (!defined('SMS_API_KEY') || !defined('SMS_PROVIDER')) {
            return ['success' => false, 'balance' => 0, 'message' => 'SMS configuration not found'];
        }

        $provider = SMS_PROVIDER;
        $apiKey = SMS_API_KEY;

        if ($provider === 'alpha_net') {
            $url = "http://alphasms.biz/api/balance?api_key=" . urlencode($apiKey);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response !== false) {
                $data = json_decode($response, true);
                if (isset($data['balance'])) {
                    return [
                        'success' => true,
                        'balance' => floatval($data['balance']),
                        'message' => 'Balance retrieved successfully'
                    ];
                }
            }
        }

        return [
            'success' => false,
            'balance' => 0,
            'message' => 'Unable to retrieve SMS balance'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'balance' => 0,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

// Send SMS
function sendSMS($phone, $message) {
    try {
        if (!defined('SMS_API_KEY') || !defined('SMS_PROVIDER') || !defined('SMS_SENDER_ID')) {
            logSMSSent($phone, $message, 'failed', 'SMS configuration not found');
            return ['success' => false, 'message' => 'SMS configuration not found'];
        }

        $provider = SMS_PROVIDER;
        $apiKey = SMS_API_KEY;
        $senderId = SMS_SENDER_ID;

        // Clean phone number
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '88' . $phone;
        } elseif (substr($phone, 0, 2) !== '88') {
            $phone = '88' . $phone;
        }

        if ($provider === 'alpha_net') {
            $url = "http://alphasms.biz/api/send";
            
            $postData = [
                'api_key' => $apiKey,
                'sender' => $senderId,
                'number' => $phone,
                'message' => $message
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response !== false) {
                $data = json_decode($response, true);
                if (isset($data['status']) && $data['status'] === 'success') {
                    logSMSSent($phone, $message, 'success');
                    return [
                        'success' => true,
                        'message' => 'SMS sent successfully',
                        'message_id' => $data['message_id'] ?? null
                    ];
                }
            }
        }

        logSMSSent($phone, $message, 'failed', 'SMS provider API failed');
        return [
            'success' => false,
            'message' => 'Failed to send SMS'
        ];

    } catch (Exception $e) {
        $error = 'SMS Exception: ' . $e->getMessage();
        logSMSSent($phone, $message, 'failed', $error);
        return [
            'success' => false,
            'message' => $error
        ];
    }
}

// Log SMS sent
function logSMSSent($phone, $message, $status, $error = '', $appointmentData = null) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'phone' => $phone,
        'message' => $message,
        'status' => $status,
        'error' => $error,
        'appointment_data' => $appointmentData,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $logs = loadJsonData(SMS_LOG_FILE);
    $logs[] = $logEntry;
    
    // Keep only last 1000 logs
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    
    saveJsonData(SMS_LOG_FILE, $logs);
}

// Send appointment approval SMS
function sendAppointmentApprovalSMS($appointment, $adminNote = '') {
    $phone = $appointment['phone'] ?? '';
    $patientName = $appointment['name'] ?? $appointment['patient_name'] ?? '';
    $appointmentDate = $appointment['preferred_date'] ?? $appointment['appointment_date'] ?? '';
    $appointmentTime = $appointment['preferred_time'] ?? $appointment['appointment_time'] ?? '';
    $appointmentNumber = $appointment['appointment_number'] ?? '';
    
    if (empty($phone)) {
        return ['success' => false, 'message' => 'Phone number not found'];
    }
    
    $message = "প্রিয় {$patientName}, আপনার অ্যাপয়েন্টমেন্ট অনুমোদিত হয়েছে। নম্বর: {$appointmentNumber}, তারিখ: {$appointmentDate}, সময়: {$appointmentTime}। সময়মতো উপস্থিত হন। ধন্যবাদ - " . SITE_NAME;
    
    if (!empty($adminNote)) {
        $message .= " নোট: " . $adminNote;
    }
    
    return sendSMS($phone, $message);
}

// Send appointment rejection SMS
function sendAppointmentRejectionSMS($appointment, $reason = '') {
    $phone = $appointment['phone'] ?? '';
    $patientName = $appointment['name'] ?? $appointment['patient_name'] ?? '';
    $appointmentNumber = $appointment['appointment_number'] ?? '';
    
    if (empty($phone)) {
        return ['success' => false, 'message' => 'Phone number not found'];
    }
    
    $message = "প্রিয় {$patientName}, আপনার অ্যাপয়েন্টমেন্ট নম্বর {$appointmentNumber} বাতিল করা হয়েছে।";
    
    if (!empty($reason)) {
        $message .= " কারণ: {$reason}.";
    }
    
    $message .= " নতুন অ্যাপয়েন্টমেন্টের জন্য যোগাযোগ করুন। ধন্যবাদ - " . SITE_NAME;
    
    return sendSMS($phone, $message);
}

// Send appointment confirmation SMS
function sendAppointmentConfirmationSMS($appointment) {
    $phone = $appointment['phone'] ?? '';
    $patientName = $appointment['name'] ?? $appointment['patient_name'] ?? '';
    $appointmentDate = $appointment['preferred_date'] ?? $appointment['appointment_date'] ?? '';
    $appointmentTime = $appointment['preferred_time'] ?? $appointment['appointment_time'] ?? '';
    $appointmentNumber = $appointment['appointment_number'] ?? '';
    
    if (empty($phone)) {
        return ['success' => false, 'message' => 'Phone number not found'];
    }
    
    $message = "প্রিয় {$patientName}, আপনার অ্যাপয়েন্টমেন্ট নিশ্চিত করা হয়েছে। নম্বর: {$appointmentNumber}, তারিখ: {$appointmentDate}, সময়: {$appointmentTime}। ধন্যবাদ - " . SITE_NAME;
    
    return sendSMS($phone, $message);
}

// Send appointment reminder SMS
function sendAppointmentReminderSMS($appointment) {
    $phone = $appointment['phone'] ?? '';
    $patientName = $appointment['name'] ?? $appointment['patient_name'] ?? '';
    $appointmentDate = $appointment['preferred_date'] ?? $appointment['appointment_date'] ?? '';
    $appointmentTime = $appointment['preferred_time'] ?? $appointment['appointment_time'] ?? '';
    
    if (empty($phone)) {
        return ['success' => false, 'message' => 'Phone number not found'];
    }
    
    $message = "প্রিয় {$patientName}, আপনার অ্যাপয়েন্টমেন্ট আগামীকাল {$appointmentDate} তারিখে {$appointmentTime} সময়ে। ধন্যবাদ - " . SITE_NAME;
    
    return sendSMS($phone, $message);
}

// Get SMS logs
function getSMSLogs($limit = 50) {
    $logs = loadJsonData(SMS_LOG_FILE);
    return array_slice(array_reverse($logs), 0, $limit);
}

// ==================== STATISTICS FUNCTIONS ====================

// Calculate appointment statistics
function getAppointmentStats() {
    $appointments = loadJsonData(APPOINTMENTS_FILE);
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    
    $stats = [
        'total' => count($appointments),
        'today' => 0,
        'month' => 0,
        'pending' => 0,
        'approved' => 0,
        'confirmed' => 0,
        'completed' => 0,
        'rejected' => 0,
        'cancelled' => 0
    ];
    
    foreach ($appointments as $appointment) {
        // Count by date
        $appointmentDate = $appointment['preferred_date'] ?? $appointment['appointment_date'] ?? $appointment['created_at'] ?? '';
        if (!empty($appointmentDate)) {
            if (substr($appointmentDate, 0, 10) === $today) {
                $stats['today']++;
            }
            if (substr($appointmentDate, 0, 7) === $thisMonth) {
                $stats['month']++;
            }
        }
        
        // Count by status
        $status = $appointment['status'] ?? 'pending';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }
    
    return $stats;
}

// Calculate income statistics
function getIncomeStats() {
    $income = loadJsonData(INCOME_FILE);
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    
    $stats = [
        'total' => 0,
        'today' => 0,
        'month' => 0,
        'count' => count($income)
    ];
    
    foreach ($income as $entry) {
        $amount = floatval($entry['amount'] ?? 0);
        $stats['total'] += $amount;
        
        if (isset($entry['date'])) {
            if ($entry['date'] === $today) {
                $stats['today'] += $amount;
            }
            if (substr($entry['date'], 0, 7) === $thisMonth) {
                $stats['month'] += $amount;
            }
        }
    }
    
    return $stats;
}

// Calculate expense statistics
function getExpenseStats() {
    $expenses = loadJsonData(EXPENSES_FILE);
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    
    $stats = [
        'total' => 0,
        'today' => 0,
        'month' => 0,
        'count' => count($expenses)
    ];
    
    foreach ($expenses as $expense) {
        $amount = floatval($expense['amount'] ?? 0);
        $stats['total'] += $amount;
        
        if (isset($expense['date'])) {
            if ($expense['date'] === $today) {
                $stats['today'] += $amount;
            }
            if (substr($expense['date'], 0, 7) === $thisMonth) {
                $stats['month'] += $amount;
            }
        }
    }
    
    return $stats;
}

// ==================== UTILITY FUNCTIONS ====================

// Format phone number for display
function formatPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 11 && substr($phone, 0, 2) === '01') {
        return '+88 ' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 4) . ' ' . substr($phone, 6);
    } elseif (strlen($phone) === 13 && substr($phone, 0, 4) === '8801') {
        return '+88 ' . substr($phone, 2, 2) . ' ' . substr($phone, 4, 4) . ' ' . substr($phone, 8);
    }
    return $phone;
}

// Format currency
function formatCurrency($amount) {
    return '৳ ' . number_format($amount, 2);
}

// Get dashboard summary
function getDashboardSummary() {
    return [
        'appointments' => getAppointmentStats(),
        'income' => getIncomeStats(),
        'expenses' => getExpenseStats(),
        'visitors' => getVisitorStats(),
        'sms_balance' => checkSMSBalance()
    ];
}

// Create notification
function createNotification($title, $message, $type = 'info') {
    $notifications = loadJsonData(NOTIFICATIONS_FILE);
    
    $notification = [
        'id' => generateId(),
        'title' => $title,
        'message' => $message,
        'type' => $type, // success, warning, error, info
        'created_at' => date('Y-m-d H:i:s'),
        'read' => false
    ];
    
    $notifications[] = $notification;
    
    // Keep only last 100 notifications
    if (count($notifications) > 100) {
        $notifications = array_slice($notifications, -100);
    }
    
    saveJsonData(NOTIFICATIONS_FILE, $notifications);
    return $notification;
}

// Get unread notifications
function getUnreadNotifications() {
    $notifications = loadJsonData(NOTIFICATIONS_FILE);
    return array_filter($notifications, fn($n) => !$n['read']);
}

// Mark notification as read
function markNotificationRead($notificationId) {
    $notifications = loadJsonData(NOTIFICATIONS_FILE);
    
    foreach ($notifications as &$notification) {
        if ($notification['id'] === $notificationId) {
            $notification['read'] = true;
            break;
        }
    }
    
    saveJsonData(NOTIFICATIONS_FILE, $notifications);
}

// ==================== BACKUP & RESTORE FUNCTIONS ====================

// Create backup
function createBackup() {
    $backupData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'settings' => loadJsonData(SETTINGS_FILE),
        'appointments' => loadJsonData(APPOINTMENTS_FILE),
        'patients' => loadJsonData(PATIENTS_FILE),
        'services' => loadJsonData(SERVICES_FILE),
        'income' => loadJsonData(INCOME_FILE),
        'expenses' => loadJsonData(EXPENSES_FILE),
        'visitors' => loadJsonData(VISITORS_FILE),
        'contacts' => loadJsonData(CONTACTS_FILE)
    ];
    
    $backupFile = DATA_DIR . 'backup_' . date('Y-m-d_H-i-s') . '.json';
    
    if (saveJsonData($backupFile, $backupData)) {
        createNotification('ব্যাকআপ সফল', 'ডাটা ব্যাকআপ সফলভাবে তৈরি হয়েছে', 'success');
        return ['success' => true, 'file' => basename($backupFile)];
    }
    
    return ['success' => false, 'message' => 'ব্যাকআপ তৈরি করতে ব্যর্থ'];
}

// List available backups
function getBackupFiles() {
    $files = glob(DATA_DIR . 'backup_*.json');
    $backups = [];
    
    foreach ($files as $file) {
        $backups[] = [
            'file' => basename($file),
            'size' => filesize($file),
            'created' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    
    return array_reverse($backups); // Latest first
}

// ==================== END OF FUNCTIONS ====================
?>
