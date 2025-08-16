<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$settings = getSettings();
$message = '';
$messageType = '';

if ($_POST) {
    $newSettings = [
        'site_name' => sanitizeInput($_POST['site_name']),
        'site_description' => sanitizeInput($_POST['site_description']),
        'contact_phone' => sanitizeInput($_POST['contact_phone']),
        'contact_email' => sanitizeInput($_POST['contact_email']),
        'address' => sanitizeInput($_POST['address']),
        'working_hours' => sanitizeInput($_POST['working_hours']),
        'facebook_url' => sanitizeInput($_POST['facebook_url']),
        'twitter_url' => sanitizeInput($_POST['twitter_url']),
        'instagram_url' => sanitizeInput($_POST['instagram_url']),
        'youtube_url' => sanitizeInput($_POST['youtube_url']),
        'whatsapp_number' => sanitizeInput($_POST['whatsapp_number']),
        'whatsapp_enabled' => isset($_POST['whatsapp_enabled']),
        'breaking_news_enabled' => isset($_POST['breaking_news_enabled']),
        'google_analytics' => sanitizeInput($_POST['google_analytics']),
        'app_download_url' => sanitizeInput($_POST['app_download_url']),
        'app_popup_enabled' => isset($_POST['app_popup_enabled']),
        'seo_title' => sanitizeInput($_POST['seo_title']),
        'seo_description' => sanitizeInput($_POST['seo_description']),
        'seo_keywords' => sanitizeInput($_POST['seo_keywords'])
    ];
    
    if (updateSettings($newSettings)) {
        $message = 'সেটিংস সফলভাবে আপডেট হয়েছে!';
        $messageType = 'success';
        $settings = $newSettings;
    } else {
        $message = 'সেটিংস আপডেট করতে সমস্যা হয়েছে!';
        $messageType = 'danger';
    }
}

$pageTitle = 'সেটিংস - অ্যাডমিন প্যানেল';
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">সেটিংস</h1>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- General Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">সাধারণ সেটিংস</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">সাইটের নাম</label>
                                            <input type="text" class="form-control" name="site_name" 
                                                   value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">ফোন নম্বর</label>
                                            <input type="tel" class="form-control" name="contact_phone" 
                                                   value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">ইমেইল</label>
                                            <input type="email" class="form-control" name="contact_email" 
                                                   value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">কাজের সময়</label>
                                            <input type="text" class="form-control" name="working_hours" 
                                                   value="<?php echo htmlspecialchars($settings['working_hours']); ?>">
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">সাইটের বিবরণ</label>
                                            <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">ঠিকানা</label>
                                            <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Media Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">সোশ্যাল মিডিয়া</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Facebook URL</label>
                                            <input type="url" class="form-control" name="facebook_url" 
                                                   value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Instagram URL</label>
                                            <input type="url" class="form-control" name="instagram_url" 
                                                   value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Twitter URL</label>
                                            <input type="url" class="form-control" name="twitter_url" 
                                                   value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">YouTube URL</label>
                                            <input type="url" class="form-control" name="youtube_url" 
                                                   value="<?php echo htmlspecialchars($settings['youtube_url'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- WhatsApp Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">WhatsApp সেটিংস</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">WhatsApp নম্বর</label>
                                            <input type="tel" class="form-control" name="whatsapp_number" 
                                                   value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="whatsapp_enabled" id="whatsapp_enabled"
                                                       <?php echo ($settings['whatsapp_enabled'] ?? true) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="whatsapp_enabled">
                                                    WhatsApp বাটন চালু করুন
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- App Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">মোবাইল অ্যাপ সেটিংস</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">অ্যাপ ডাউনলোড URL</label>
                                            <input type="url" class="form-control" name="app_download_url" 
                                                   value="<?php echo htmlspecialchars($settings['app_download_url'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="app_popup_enabled" id="app_popup_enabled"
                                                       <?php echo ($settings['app_popup_enabled'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="app_popup_enabled">
                                                    অ্যাপ ব্যানার দেখান
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SEO Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">SEO সেটিংস</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">SEO Title</label>
                                            <input type="text" class="form-control" name="seo_title" 
                                                   value="<?php echo htmlspecialchars($settings['seo_title'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">SEO Description</label>
                                            <textarea class="form-control" name="seo_description" rows="3"><?php echo htmlspecialchars($settings['seo_description'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">SEO Keywords</label>
                                            <input type="text" class="form-control" name="seo_keywords" 
                                                   value="<?php echo htmlspecialchars($settings['seo_keywords'] ?? ''); ?>"
                                                   placeholder="কমা দিয়ে আলাদা করুন">
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">Google Analytics ID</label>
                                            <input type="text" class="form-control" name="google_analytics" 
                                                   value="<?php echo htmlspecialchars($settings['google_analytics'] ?? ''); ?>"
                                                   placeholder="GA-XXXXXXXXX">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Feature Toggles -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">ফিচার টগল</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="breaking_news_enabled" id="breaking_news_enabled"
                                               <?php echo ($settings['breaking_news_enabled'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="breaking_news_enabled">
                                            ব্রেকিং নিউজ চালু করুন
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Save Button -->
                            <div class="card">
                                <div class="card-body">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-check-circle me-2"></i>সেটিংস সেভ করুন
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
