<?php
require_once 'config/config.php';

// Handle AJAX track form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    try {
        // Validate required fields
        if (!isset($_POST['appointment_number']) || trim($_POST['appointment_number']) === '') {
            echo json_encode(['success' => false, 'message' => 'অ্যাপয়েন্টমেন্ট নম্বর দিন']);
            exit;
        }

        $appointmentNumber = sanitizeInput($_POST['appointment_number']);
        
        // Load appointments
        $appointments = loadJsonData(APPOINTMENTS_FILE);
        
        // Search for appointment
        $foundAppointment = null;
        foreach ($appointments as $appointment) {
            if ($appointment['appointment_number'] === $appointmentNumber) {
                $foundAppointment = $appointment;
                break;
            }
        }
        
        if ($foundAppointment) {
            // Format appointment data for display
            $appointmentData = [
                'appointment_number' => $foundAppointment['appointment_number'],
                'name' => $foundAppointment['name'],
                'phone' => $foundAppointment['phone'],
                'service' => $foundAppointment['service'],
                'date' => $foundAppointment['preferred_date'],
                'time' => $foundAppointment['preferred_time'],
                'status' => $foundAppointment['status'],
                'notes' => $foundAppointment['notes'] ?? '',
                'created_at' => $foundAppointment['created_at']
            ];
            
            echo json_encode([
                'success' => true,
                'appointment' => $appointmentData
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'এই নম্বর দিয়ে কোন অ্যাপয়েন্টমেন্ট পাওয়া যায়নি। অ্যাপয়েন্টমেন্ট নম্বর সঠিক কিনা পুনরায় দেখুন।'
            ]);
        }
        exit;

    } catch (Exception $e) {
        error_log('Track Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'সিস্টেম ত্রুটি হয়েছে']);
        exit;
    }
}

trackVisitor();
$settings = getSettings();

$pageTitle = 'অ্যাপয়েন্টমেন্ট ট্র্যাক করুন - ' . $settings['site_name'];
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="আপনার অ্যাপয়েন্টমেন্টের স্থিতি ট্র্যাক করুন">
    
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
                    <h1 class="display-4 fw-bold mb-3">অ্যাপয়েন্টমেন্ট ট্র্যাক করুন</h1>
                    <p class="lead">আপনার অ্যাপয়েন্টমেন্ট নম্বর দিয়ে বর্তমান স্থিতি জানুন</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Track Form -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="bi bi-search text-primary" style="font-size: 3rem;"></i>
                                <h3 class="mt-3">অ্যাপয়েন্টমেন্ট খুঁজুন</h3>
                            </div>
                            
                            <form id="trackForm">
                                <div class="mb-4">
                                    <label class="form-label">অ্যাপয়েন্টমেন্ট নম্বর <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" name="appointment_number" 
                                           placeholder="যেমন: APT-20250812-0001" required>
                                    <div class="form-text">অ্যাপয়েন্টমেন্ট বুক করার সময় যে নম্বর পেয়েছেন সেটি দিন</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">ফোন নম্বর (ঐচ্ছিক)</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           placeholder="অতিরিক্ত যাচাইয়ের জন্য">
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-search me-2"></i>অ্যাপয়েন্টমেন্ট খুঁজুন
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search Result -->
            <div class="row justify-content-center mt-4">
                <div class="col-lg-8">
                    <div id="trackResult"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Help Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h2 class="text-center mb-5">সাহায্য প্রয়োজন?</h2>
                    
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="bi bi-question-circle text-primary" style="font-size: 2.5rem;"></i>
                                <h5 class="mt-3">অ্যাপয়েন্টমেন্ট নম্বর খুঁজে পাচ্ছেন না?</h5>
                                <p class="text-muted small">আপনার SMS বা ইমেইল চেক করুন। অ্যাপয়েন্টমেন্ট বুক করার সময় আমরা নম্বর পাঠিয়েছি।</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="bi bi-telephone text-primary" style="font-size: 2.5rem;"></i>
                                <h5 class="mt-3">ফোনে যোগাযোগ করুন</h5>
                                <p class="text-muted small">
                                    <a href="tel:<?php echo $settings['contact_phone']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($settings['contact_phone']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="bi bi-clock text-primary" style="font-size: 2.5rem;"></i>
                                <h5 class="mt-3">কাজের সময়</h5>
                                <p class="text-muted small"><?php echo htmlspecialchars($settings['working_hours']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Appointment Status Info -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h3 class="text-center mb-4">অ্যাপয়েন্টমেন্ট স্ট্যাটাস বোঝার গাইড</h3>
                    
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm text-center">
                                <div class="card-body">
                                    <span class="badge bg-warning text-dark px-3 py-2">অপেক্ষমাণ</span>
                                    <h6 class="mt-3">অপেক্ষমাণ</h6>
                                    <p class="small text-muted">আপনার অ্যাপয়েন্টমেন্ট পর্যালোচনা করা হচ্ছে। শীঘ্রই নিশ্চিত করা হবে।</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm text-center">
                                <div class="card-body">
                                    <span class="badge bg-success px-3 py-2">অনুমোদিত</span>
                                    <h6 class="mt-3">অনুমোদিত</h6>
                                    <p class="small text-muted">আপনার অ্যাপয়েন্টমেন্ট নিশ্চিত হয়েছে। নির্ধারিত সময়ে উপস্থিত হন।</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm text-center">
                                <div class="card-body">
                                    <span class="badge bg-danger px-3 py-2">বাতিল</span>
                                    <h6 class="mt-3">বাতিল</h6>
                                    <p class="small text-muted">আপনার অ্যাপয়েন্টমেন্ট বাতিল হয়েছে। নতুন অ্যাপয়েন্টমেন্ট নিন।</p>
                                </div>
                            </div>
                        </div>
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
</body>
</html>