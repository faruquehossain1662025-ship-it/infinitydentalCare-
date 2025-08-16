<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

if ($_POST) {
    $phone = sanitizeInput($_POST['phone']);
    $testMessage = sanitizeInput($_POST['message']);
    
    if (sendTestSMS($phone, $testMessage)) {
        $message = 'SMS সফলভাবে পাঠানো হয়েছে!';
        $messageType = 'success';
    } else {
        $message = 'SMS পাঠানোতে সমস্যা হয়েছে! Debug log চেক করুন।';
        $messageType = 'danger';
    }
}

$balance = checkSMSBalance();
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS টেস্ট - অ্যাডমিন প্যানেল</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h2 class="mb-4">SMS টেস্ট</h2>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- SMS Balance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">SMS ব্যালেন্স</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($balance !== false): ?>
                            <div class="alert alert-success">
                                <h4>আপনার SMS ব্যালেন্স: ৳<?php echo number_format($balance, 2); ?></h4>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                ব্যালেন্স চেক করতে সমস্যা হয়েছে।
                            </div>
                        <?php endif; ?>
                        
                        <h6>API Configuration:</h6>
                        <p><strong>Provider:</strong> <?php echo SMS_PROVIDER; ?></p>
                        <p><strong>API Key:</strong> <?php echo substr(SMS_API_KEY, 0, 10) . '...'; ?></p>
                    </div>
                </div>

                <!-- SMS Test Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">SMS পাঠান</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">ফোন নম্বর *</label>
                                <input type="tel" class="form-control" name="phone" 
                                       placeholder="01XXXXXXXXX" required>
                                <div class="form-text">উদাহরণ: 01700000000</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">মেসেজ *</label>
                                <textarea class="form-control" name="message" rows="4" 
                                          placeholder="আপনার মেসেজ লিখুন..." required>Test message from Infinity Dental Care - পরীক্ষামূলক মেসেজ</textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>SMS পাঠান
                            </button>
                            
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>ড্যাশবোর্ডে ফিরুন
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Debug Info -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $debugFile = DATA_DIR . 'sms_debug.log';
                        if (file_exists($debugFile)) {
                            $debugContent = file_get_contents($debugFile);
                            $lines = explode("\n", $debugContent);
                            $lastLines = array_slice(array_filter($lines), -5);
                            
                            echo '<h6>শেষ 5টি SMS Debug Log:</h6>';
                            echo '<pre style="max-height: 300px; overflow-y: auto;">';
                            foreach ($lastLines as $line) {
                                if (!empty($line)) {
                                    $decoded = json_decode($line, true);
                                    if ($decoded) {
                                        echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "\n\n";
                                    }
                                }
                            }
                            echo '</pre>';
                        } else {
                            echo '<p>এখনো কোন debug log নেই।</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>