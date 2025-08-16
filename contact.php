<?php
require_once 'config/config.php';

// Handle AJAX contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    try {
        // Validate required fields
        $required_fields = ['name', 'phone', 'subject', 'message'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                echo json_encode(['success' => false, 'message' => 'সব প্রয়োজনীয় তথ্য দিন']);
                exit;
            }
        }

        // Validate email if provided
        if (!empty($_POST['email']) && !validateEmail($_POST['email'])) {
            echo json_encode(['success' => false, 'message' => 'সঠিক ইমেইল দিন']);
            exit;
        }

        // Load existing contacts
        $contacts = loadJsonData(CONTACTS_FILE);

        // Create contact entry
        $contact = [
            'id' => generateId(),
            'name' => sanitizeInput($_POST['name']),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'phone' => sanitizeInput($_POST['phone']),
            'subject' => sanitizeInput($_POST['subject']),
            'message' => sanitizeInput($_POST['message']),
            'newsletter' => isset($_POST['newsletter']) ? 'yes' : 'no',
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Save contact
        $contacts[] = $contact;
        if (!saveJsonData(CONTACTS_FILE, $contacts)) {
            echo json_encode(['success' => false, 'message' => 'ডেটা সেভ করতে সমস্যা হয়েছে']);
            exit;
        }

        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'আপনার বার্তা সফলভাবে পাঠানো হয়েছে। শীঘ্রই আমরা আপনার সাথে যোগাযোগ করব।'
        ]);
        exit;

    } catch (Exception $e) {
        error_log('Contact Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'সিস্টেম ত্রুটি হয়েছে']);
        exit;
    }
}

trackVisitor();
$settings = getSettings();

$pageTitle = 'যোগাযোগ - ' . $settings['site_name'];
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="আমাদের সাথে যোগাযোগ করুন - ফোন, ইমেইল বা সরাসরি বার্তা পাঠান">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="py-5 bg-gradient-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">যোগাযোগ করুন</h1>
                    <p class="lead">আমাদের সাথে যোগাযোগ করুন যেকোনো প্রয়োজনে</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <div class="contact-icon mb-3">
                                <i class="bi bi-telephone text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5>ফোন</h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($settings['contact_phone']); ?></p>
                            <a href="tel:<?php echo $settings['contact_phone']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-telephone me-1"></i> কল করুন
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <div class="contact-icon mb-3">
                                <i class="bi bi-envelope text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5>ইমেইল</h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($settings['contact_email']); ?></p>
                            <a href="mailto:<?php echo $settings['contact_email']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-envelope me-1"></i> মেইল পাঠান
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <div class="contact-icon mb-3">
                                <i class="bi bi-geo-alt text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5>ঠিকানা</h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($settings['address']); ?></p>
                            <button class="btn btn-outline-primary btn-sm" onclick="openMap()">
                                <i class="bi bi-map me-1"></i> ম্যাপে দেখুন
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 text-center">
                        <div class="card-body p-4">
                            <div class="contact-icon mb-3">
                                <i class="bi bi-clock text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5>কাজের সময়</h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($settings['working_hours']); ?></p>
                            <span class="badge bg-success">এখন খোলা</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form & Map -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6">
                    <div class="card border-0 shadow">
                        <div class="card-body p-5">
                            <h3 class="mb-4">আমাদের বার্তা পাঠান</h3>
                            
                            <form id="contactForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">আপনার নাম <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">ফোন নম্বর <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" name="phone" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">ইমেইল</label>
                                        <input type="email" class="form-control" name="email">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">বিষয় <span class="text-danger">*</span></label>
                                        <select class="form-select" name="subject" required>
                                            <option value="">বিষয় নির্বাচন করুন</option>
                                            <option value="appointment">অ্যাপয়েন্টমেন্ট</option>
                                            <option value="general_inquiry">সাধারণ জিজ্ঞাসা</option>
                                            <option value="service_info">সেবা সম্পর্কে জানতে</option>
                                            <option value="complaint">অভিযোগ</option>
                                            <option value="feedback">মতামত</option>
                                            <option value="emergency">জরুরি</option>
                                            <option value="other">অন্যান্য</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">আপনার বার্তা <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="message" rows="5" 
                                                placeholder="আপনার বার্তা বিস্তারিত লিখুন" required></textarea>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="newsletter" id="newsletter">
                                            <label class="form-check-label" for="newsletter">
                                                আমি নিউজলেটার ও আপডেট পেতে চাই
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg w-100">
                                            <i class="bi bi-send me-2"></i>বার্তা পাঠান
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body p-0">
                            <div class="map-container">
                                <iframe 
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3651.9083482153295!2d90.39743831543487!3d23.750895194616264!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b8b087026b81%3A0x8fa563bbdd5904c2!2sDhaka%2C%20Bangladesh!5e0!3m2!1sen!2s!4v1629000000000!5m2!1sen!2s"
                                    width="100%" 
                                    height="400" 
                                    style="border:0;" 
                                    allowfullscreen="" 
                                    loading="lazy">
                                </iframe>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow mt-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-info-circle text-primary me-2"></i>গুরুত্বপূর্ণ তথ্য
                            </h5>
                            
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    আমরা ২৪ ঘন্টার মধ্যে জবাব দেওয়ার চেষ্টা করি
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    জরুরি বিষয়ে সরাসরি ফোন করুন
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    অ্যাপয়েন্টমেন্টের জন্য আগে থেকে বুকিং দিন
                                </li>
                                <li class="mb-0">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    আমাদের সোশ্যাল মিডিয়ায় ফলো করুন
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Media & Additional Contact -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="display-6 fw-bold text-primary mb-4">আমাদের সাথে যুক্ত হন</h2>
                    <p class="lead text-muted mb-4">সোশ্যাল মিডিয়ায় আমাদের ফলো করুন এবং সর্বশেষ আপডেট পান</p>
                    
                    <div class="social-links d-flex justify-content-center gap-3">
                        <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['facebook_url']); ?>" target="_blank" 
                           class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-facebook me-2"></i>Facebook
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($settings['instagram_url'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['instagram_url']); ?>" target="_blank" 
                           class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-instagram me-2"></i>Instagram
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['youtube_url']); ?>" target="_blank" 
                           class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-youtube me-2"></i>YouTube
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($settings['whatsapp_number'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['whatsapp_number']); ?>" 
                           target="_blank" class="btn btn-success btn-lg">
                            <i class="bi bi-whatsapp me-2"></i>WhatsApp
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- WhatsApp Float Button -->
    <?php if ($settings['whatsapp_enabled'] && !empty($settings['whatsapp_number'])): ?>
    <a href="#" class="whatsapp-float" data-phone="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>">
        <i class="bi bi-whatsapp"></i>
    </a>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    
    <script>
    function openMap() {
        const address = "<?php echo addslashes($settings['address']); ?>";
        const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
        window.open(url, '_blank');
    }
    </script>
</body>
</html>