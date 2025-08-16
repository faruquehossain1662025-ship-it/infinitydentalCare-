<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';
$emailStats = getEmailStats();

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_newsletter') {
        $title = sanitizeInput($_POST['title']);
        $intro = sanitizeInput($_POST['intro']);
        $content = $_POST['content']; // HTML content
        
        $sentCount = sendNewsletter($title, $content, $intro);
        
        if ($sentCount > 0) {
            $message = "Newsletter সফলভাবে {$sentCount} জন subscriber এ পাঠানো হয়েছে!";
            $messageType = 'success';
        } else {
            $message = 'Newsletter পাঠানোতে সমস্যা হয়েছে!';
            $messageType = 'danger';
        }
    }
    
    elseif ($action === 'add_subscriber') {
        $email = sanitizeInput($_POST['email']);
        $name = sanitizeInput($_POST['name']);
        
        if (addNewsletterSubscriber($email, $name)) {
            $message = 'নতুন subscriber যোগ করা হয়েছে!';
            $messageType = 'success';
        } else {
            $message = 'এই email দিয়ে ইতিমধ্যে subscribe করা আছে!';
            $messageType = 'warning';
        }
    }
}

$subscribers = loadJsonData(NEWSLETTER_FILE);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Management - Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Newsletter Management</h1>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Dashboard এ ফিরুন
                    </a>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Email Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $emailStats['active_subscribers']; ?></h5>
                                <p class="card-text">Active Subscribers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $emailStats['emails_today']; ?></h5>
                                <p class="card-text">Emails Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $emailStats['emails_this_month']; ?></h5>
                                <p class="card-text">Emails This Month</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $emailStats['total_emails_sent']; ?></h5>
                                <p class="card-text">Total Emails Sent</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Send Newsletter -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Newsletter পাঠান</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="send_newsletter">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Newsletter Title *</label>
                                        <input type="text" class="form-control" name="title" 
                                               placeholder="Newsletter এর শিরোনাম" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Introduction Text</label>
                                        <textarea class="form-control" name="intro" rows="2" 
                                                  placeholder="Newsletter এর প্রথম অংশ..."></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Newsletter Content *</label>
                                        <textarea class="form-control" name="content" rows="10" required
                                                  placeholder="Newsletter এর মূল বিষয়বস্তু (HTML supported)..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary" 
                                            onclick="return confirm('সব subscriber এ newsletter পাঠাতে চান?')">
                                        <i class="bi bi-send me-2"></i>Newsletter পাঠান
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Subscriber -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">নতুন Subscriber যোগ করুন</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="add_subscriber">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" 
                                               placeholder="নাম">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" 
                                               placeholder="email@example.com" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-plus me-2"></i>Subscriber যোগ করুন
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Subscribers List -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Subscribers</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($subscribers)): ?>
                                    <?php foreach (array_slice(array_reverse($subscribers), 0, 10) as $subscriber): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($subscriber['name'] ?: 'Unknown'); ?></strong><br>
                                                <small><?php echo htmlspecialchars($subscriber['email']); ?></small>
                                            </div>
                                            <span class="badge bg-<?php echo $subscriber['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $subscriber['status']; ?>
                                            </span>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>কোন subscriber নেই।</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>