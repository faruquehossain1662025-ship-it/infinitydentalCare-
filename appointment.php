<?php
require_once 'config/config.php';

// Handle AJAX appointment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    try {
        // Load existing data
        $appointments = loadJsonData(APPOINTMENTS_FILE);
        $patients = loadJsonData(PATIENTS_FILE);
        
        // Validate required fields
        $required_fields = ['name', 'phone', 'service', 'preferred_date', 'preferred_time'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                echo json_encode(['success' => false, 'message' => 'সব প্রয়োজনীয় তথ্য দিন']);
                exit;
            }
        }

        // Validate phone number
        if (!validatePhone($_POST['phone'])) {
            echo json_encode(['success' => false, 'message' => 'সঠিক ফোন নম্বর দিন']);
            exit;
        }

        // Validate email if provided
        if (!empty($_POST['email']) && !validateEmail($_POST['email'])) {
            echo json_encode(['success' => false, 'message' => 'সঠিক ইমেইল দিন']);
            exit;
        }

        // Generate unique appointment number
        $appointmentNumber = generateAppointmentNumber();
        while (array_search($appointmentNumber, array_column($appointments, 'appointment_number')) !== false) {
            $appointmentNumber = generateAppointmentNumber();
        }

        // Create appointment
        $appointment = [
            'id' => generateId(),
            'appointment_number' => $appointmentNumber,
            'name' => sanitizeInput($_POST['name']),
            'phone' => sanitizeInput($_POST['phone']),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'age' => isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null,
            'gender' => sanitizeInput($_POST['gender'] ?? ''),
            'service' => sanitizeInput($_POST['service']),
            'preferred_date' => sanitizeInput($_POST['preferred_date']),
            'preferred_time' => sanitizeInput($_POST['preferred_time']),
            'problem_description' => sanitizeInput($_POST['problem_description'] ?? ''),
            'emergency_contact' => sanitizeInput($_POST['emergency_contact'] ?? ''),
            'status' => 'pending',
            'notes' => '',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Save appointment
        $appointments[] = $appointment;
        if (!saveJsonData(APPOINTMENTS_FILE, $appointments)) {
            echo json_encode(['success' => false, 'message' => 'ডেটা সেভ করতে সমস্যা হয়েছে']);
            exit;
        }

        // Add patient if doesn't exist
        $existingPatient = array_filter($patients, fn($p) => $p['phone'] === $appointment['phone']);
        if (empty($existingPatient)) {
            $patient = [
                'id' => generateId(),
                'name' => $appointment['name'],
                'phone' => $appointment['phone'],
                'email' => $appointment['email'],
                'age' => $appointment['age'],
                'gender' => $appointment['gender'],
                'emergency_contact' => $appointment['emergency_contact'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            $patients[] = $patient;
            saveJsonData(PATIENTS_FILE, $patients);
        }

        // Return success
        echo json_encode([
            'success' => true, 
            'message' => 'অ্যাপয়েন্টমেন্ট সফলভাবে বুক হয়েছে!',
            'appointmentNumber' => $appointmentNumber
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log('Appointment Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'সিস্টেম ত্রুটি হয়েছে']);
        exit;
    }
}

trackVisitor();
$settings = getSettings();
$services = loadJsonData(SERVICES_FILE);
$activeServices = array_filter($services, fn($s) => $s['active'] ?? true);

$pageTitle = 'অ্যাপয়েন্টমেন্ট - ' . $settings['site_name'];
$selectedService = $_GET['service'] ?? '';
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="অনলাইনে অ্যাপয়েন্টমেন্ট বুক করুন">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary fs-3" href="/">
                <i class="bi bi-heart-pulse me-2"></i>
                <?php echo htmlspecialchars($settings['site_name']); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">হোম</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">সেবাসমূহ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="appointment.php">অ্যাপয়েন্টমেন্ট</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reviews.php">রিভিউ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gallery.php">গ্যালারি</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="track.php">ট্র্যাক</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">যোগাযোগ</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="py-5 bg-gradient-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">অ্যাপয়েন্টমেন্ট বুক করুন</h1>
                    <p class="lead">সহজেই অনলাইনে আপনার অ্যাপয়েন্টমেন্ট নিশ্চিত করুন</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Appointment Form -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <h3 class="text-center mb-4">অ্যাপয়েন্টমেন্ট ফর্ম</h3>
                            
                            <form id="appointmentForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">রোগীর নাম <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">ফোন নম্বর <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" name="phone" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">ইমেইল</label>
                                        <input type="email" class="form-control" name="email">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">বয়স</label>
                                        <input type="number" class="form-control" name="age" min="1" max="120">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">লিঙ্গ</label>
                                        <select class="form-select" name="gender">
                                            <option value="">নির্বাচন করুন</option>
                                            <option value="male">পুরুষ</option>
                                            <option value="female">মহিলা</option>
                                            <option value="other">অন্যান্য</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">সেবা <span class="text-danger">*</span></label>
                                        <select class="form-select" name="service" id="service" required>
                                            <option value="">সেবা নির্বাচন করুন</option>
                                            <?php foreach ($activeServices as $service): ?>
                                            <option value="<?php echo htmlspecialchars($service['name']); ?>" 
                                                    data-price="<?php echo $service['price']; ?>"
                                                    <?php echo $selectedService === $service['name'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($service['name']); ?> - ৳<?php echo $service['price']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">পছন্দের তারিখ <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="preferred_date" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">পছন্দের সময় <span class="text-danger">*</span></label>
                                        <select class="form-select" name="preferred_time" required>
                                            <option value="">সময় নির্বাচন করুন</option>
                                            <option value="09:00">সকাল ৯:০০</option>
                                            <option value="10:00">সকাল ১০:০০</option>
                                            <option value="11:00">সকাল ১১:০০</option>
                                            <option value="12:00">দুপুর ১২:০০</option>
                                            <option value="14:00">দুপুর ২:০০</option>
                                            <option value="15:00">বিকেল ৩:০০</option>
                                            <option value="16:00">বিকেল ৪:০০</option>
                                            <option value="17:00">বিকেল ৫:০০</option>
                                            <option value="18:00">সন্ধ্যা ৬:০০</option>
                                            <option value="19:00">সন্ধ্যা ৭:০০</option>
                                            <option value="20:00">রাত ৮:০০</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">কুপন কোড</label>
                                        <input type="text" class="form-control" name="coupon_code" id="couponCode" placeholder="কুপন কোড থাকলে লিখুন">
                                        <div id="couponFeedback"></div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">সমস্যার বিবরণ</label>
                                        <textarea class="form-control" name="problem_description" rows="3" 
                                                placeholder="আপনার সমস্যা সম্পর্কে বিস্তারিত লিখুন"></textarea>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">জরুরি যোগাযোগের নাম ও নম্বর</label>
                                        <input type="text" class="form-control" name="emergency_contact" 
                                               placeholder="নাম - ফোন নম্বর">
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="terms_agreed" id="termsAgreed" required>
                                            <label class="form-check-label" for="termsAgreed">
                                                আমি শর্তাবলী মেনে নিয়েছি এবং আমার তথ্য সংরক্ষণে সম্মতি দিয়েছি
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5>মোট খরচ:</h5>
                                            <h5 class="text-primary" id="totalPrice" data-original="0">৳0</h5>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-lg w-100">
                                            <i class="bi bi-calendar-plus me-2"></i>অ্যাপয়েন্টমেন্ট বুক করুন
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card border-0 shadow">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-info-circle text-primary me-2"></i>গুরুত্বপূর্ণ তথ্য
                            </h5>
                            
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-clock text-success me-2"></i>
                                    <strong>কাজের সময়:</strong> <?php echo htmlspecialchars($settings['working_hours']); ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-telephone text-success me-2"></i>
                                    <strong>ফোন:</strong> <?php echo htmlspecialchars($settings['contact_phone']); ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-envelope text-success me-2"></i>
                                    <strong>ইমেইল:</strong> <?php echo htmlspecialchars($settings['contact_email']); ?>
                                </li>
                                <li class="mb-3">
                                    <i class="bi bi-geo-alt text-success me-2"></i>
                                    <strong>ঠিকানা:</strong> <?php echo htmlspecialchars($settings['address']); ?>
                                </li>
                            </ul>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-lightbulb me-2"></i>
                                <strong>মনে রাখবেন:</strong> অ্যাপয়েন্টমেন্ট নিশ্চিত করার জন্য আমরা আপনার সাথে ফোনে যোগাযোগ করব।
                            </div>
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow mt-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-shield-check text-primary me-2"></i>আমাদের বৈশিষ্ট্য
                            </h5>
                            
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    অভিজ্ঞ ও দক্ষ ডাক্তার
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    আধুনিক যন্ত্রপাতি
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    স্বাস্থ্যবিধি মেনে চলা
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    সাশ্রয়ী মূল্য
                                </li>
                                <li class="mb-0">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    ২৪/৭ জরুরি সেবা
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
    // Update price when service is selected
    document.getElementById('service').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.dataset.price || 0;
        const priceElement = document.getElementById('totalPrice');
        priceElement.textContent = '৳' + price;
        priceElement.dataset.original = price;
    });

    // Set initial price if service is pre-selected
    document.addEventListener('DOMContentLoaded', function() {
        const serviceSelect = document.getElementById('service');
        if (serviceSelect.value) {
            serviceSelect.dispatchEvent(new Event('change'));
        }
    });
    </script>
</body>
</html>