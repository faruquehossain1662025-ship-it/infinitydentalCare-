<?php
require_once '../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'valid' => false,
        'message' => 'শুধুমাত্র POST request অনুমতি আছে'
    ]);
    exit;
}

// Get POST data
$couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
$serviceAmount = floatval($_POST['service_amount'] ?? 0);
$serviceName = trim($_POST['service_name'] ?? '');

// Validate input
if (empty($couponCode)) {
    echo json_encode([
        'valid' => false,
        'message' => 'কুপন কোড প্রবেশ করুন'
    ]);
    exit;
}

if ($serviceAmount <= 0) {
    echo json_encode([
        'valid' => false,
        'message' => 'সেবার মূল্য সঠিক নয়'
    ]);
    exit;
}

if (empty($serviceName)) {
    echo json_encode([
        'valid' => false,
        'message' => 'প্রথমে একটি সেবা নির্বাচন করুন'
    ]);
    exit;
}

try {
    // Validate coupon using the function from functions.php
    $result = validateCoupon($couponCode, $serviceAmount, $serviceName);
    
    // Return the result
    echo json_encode($result);
    
} catch (Exception $e) {
    // Handle any errors
    echo json_encode([
        'valid' => false,
        'message' => 'কুপন যাচাইকরণে ত্রুটি হয়েছে। পরে চেষ্টা করুন।',
        'error' => $e->getMessage()
    ]);
}
?>