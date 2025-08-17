<?php
require_once '../config/config.php';
requireLogin();

// Handle email settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        if ($_POST['action'] === 'test_email') {
            // Test email functionality
            $testEmail = sanitizeInput($_POST['test_email']);
            
            if (!validateEmail($testEmail)) {
                echo json_encode(['success' => false, 'message' => 'অবৈধ ইমেইল ঠিকানা']);
                exit;
            }
            
            $subject = 'Test Email from ' . SITE_NAME;
            $body = '
            <html>
            <body>
                <h2>Email Test Successful!</h2>
                <p>This is a test email from your dental care website.</p>
                <p><strong>Site:</strong> ' . SITE_NAME . '</p>
                <p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p>If you received this email, your email configuration is working correctly.</p>
            </body>
            </html>';
            
            if (sendEmail($testEmail, $subject, $body, true)) {
                echo json_encode(['success' => true, 'message' => 'টেস্ট ইমেইল সফলভাবে পাঠানো হয়েছে']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ইমেইল পাঠাতে ব্যর্থ। কনফিগারেশন চেক করুন']);
            }
            exit;
        }
        
        if ($_POST['action'] === 'update_settings') {
            // Update email settings in config file
            $configFile = '../config/config.php';
            $configContent = file_get_contents($configFile);
            
            // Update SMTP settings
            $configContent = preg_replace(
                "/define\('SMTP_HOST',\s*'[^']*'\);/",
                "define('SMTP_HOST', '" . sanitizeInput($_POST['smtp_host']) . "');",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('SMTP_PORT',\s*\d+\);/",
                "define('SMTP_PORT', " . intval($_POST['smtp_port']) . ");",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('SMTP_USERNAME',\s*'[^']*'\);/",
                "define('SMTP_USERNAME', '" . sanitizeInput($_POST['smtp_username']) . "');",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('SMTP_PASSWORD',\s*'[^']*'\);/",
                "define('SMTP_PASSWORD', '" . sanitizeInput($_POST['smtp_password']) . "');",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('SMTP_ENCRYPTION',\s*'[^']*'\);/",
                "define('SMTP_ENCRYPTION', '" . sanitizeInput($_POST['smtp_encryption']) . "');",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('FROM_EMAIL',\s*'[^']*'\);/",
                "define('FROM_EMAIL', '" . sanitizeInput($_POST['from_email']) . "');",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('FROM_NAME',\s*'[^']*'\);/",
                "define('FROM_NAME', '" . sanitizeInput($_POST['from_name']) . "');",
                $configContent
            );
            
            if (file_put_contents($configFile, $configContent)) {
                echo json_encode(['success' => true, 'message' => 'ইমেইল সেটিংস আপডেট হয়েছে']);
            } else {
                echo json_encode(['success' => false, 'message' => 'সেটিংস আপডেট করতে ব্যর্থ']);
            }
            exit;
        }
    }
}

$pageTitle = 'ইমেইল সেটিংস';
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-envelope-gear me-2"></i><?php echo $pageTitle; ?>
                    </h1>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-gear me-2"></i>SMTP Configuration
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="emailSettingsForm">
                                    <input type="hidden" name="action" value="update_settings">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Host <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="smtp_host" 
                                                   value="<?php echo SMTP_HOST; ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Port <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="smtp_port" 
                                                   value="<?php echo SMTP_PORT; ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Username <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="smtp_username" 
                                                   value="<?php echo SMTP_USERNAME; ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="smtp_password" 
                                                   value="<?php echo SMTP_PASSWORD; ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Encryption</label>
                                            <select class="form-select" name="smtp_encryption">
                                                <option value="ssl" <?php echo SMTP_ENCRYPTION === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                <option value="tls" <?php echo SMTP_ENCRYPTION === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="" <?php echo SMTP_ENCRYPTION === '' ? 'selected' : ''; ?>>None</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">From Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="from_email" 
                                                   value="<?php echo FROM_EMAIL; ?>" required>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">From Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="from_name" 
                                                   value="<?php echo FROM_NAME; ?>" required>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-1"></i>সেটিংস আপডেট করুন
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-envelope-check me-2"></i>Email Test
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Test your email configuration by sending a test email.</p>
                                
                                <form id="testEmailForm">
                                    <input type="hidden" name="action" value="test_email">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Test Email Address</label>
                                        <input type="email" class="form-control" name="test_email" 
                                               placeholder="test@example.com" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-send me-1"></i>Test Email পাঠান
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-info-circle me-2"></i>সাহায্য
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6>Common SMTP Ports:</h6>
                                    <ul class="list-unstyled small">
                                        <li>• <strong>587</strong> - STARTTLS</li>
                                        <li>• <strong>465</strong> - SSL/TLS</li>
                                        <li>• <strong>25</strong> - Plain (not recommended)</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h6>Popular Email Providers:</h6>
                                    <ul class="list-unstyled small">
                                        <li>• <strong>Gmail:</strong> smtp.gmail.com:587</li>
                                        <li>• <strong>Outlook:</strong> smtp-mail.outlook.com:587</li>
                                        <li>• <strong>Yahoo:</strong> smtp.mail.yahoo.com:587</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailSettingsForm = document.getElementById('emailSettingsForm');
        const testEmailForm = document.getElementById('testEmailForm');

        // Email settings form submission
        emailSettingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>আপডেট হচ্ছে...';
            
            fetch('email-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('একটি ত্রুটি হয়েছে', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });

        // Test email form submission
        testEmailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>পাঠানো হচ্ছে...';
            
            fetch('email-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('একটি ত্রুটি হয়েছে', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });

        function showAlert(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertIcon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="bi bi-${alertIcon} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            const container = document.querySelector('main');
            container.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    });
    </script>
</body>
</html>