<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$testResult = '';
if (isset($_POST['test_email'])) {
    $testEmail = $_POST['email'] ?? FROM_EMAIL;
    
    ob_start();
    testEmailConfiguration();
    $configTest = ob_get_clean();
    
    ob_start();
    $emailSent = sendSimpleTestEmail($testEmail);
    $emailTest = ob_get_clean();
    
    $testResult = $configTest . $emailTest;
    
    if ($emailSent) {
        $testResult .= "<div class='alert alert-success mt-3'>✅ <strong>Test email sent successfully!</strong></div>";
    } else {
        $testResult .= "<div class='alert alert-danger mt-3'>❌ <strong>Test email failed!</strong></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">📧 Email System Test</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label class="form-label">Test Email Address:</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars(FROM_EMAIL); ?>" required>
                                <div class="form-text">ইমেইল টেস্ট করার জন্য একটি valid email address দিন</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="test_email" class="btn btn-primary">
                                    🧪 Test Email System
                                </button>
                            </div>
                        </form>
                        
                        <?php if ($testResult): ?>
                            <div class="debug-output">
                                <?php echo $testResult; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <h5>📋 Current Configuration:</h5>
                            <table class="table table-sm">
                                <tr><td><strong>SMTP Host:</strong></td><td><?php echo SMTP_HOST; ?></td></tr>
                                <tr><td><strong>SMTP Port:</strong></td><td><?php echo SMTP_PORT; ?></td></tr>
                                <tr><td><strong>SMTP User:</strong></td><td><?php echo SMTP_USERNAME; ?></td></tr>
                                <tr><td><strong>From Email:</strong></td><td><?php echo FROM_EMAIL; ?></td></tr>
                                <tr><td><strong>Encryption:</strong></td><td><?php echo SMTP_ENCRYPTION; ?></td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        <a href="appointments.php" class="btn btn-info">View Appointments</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>