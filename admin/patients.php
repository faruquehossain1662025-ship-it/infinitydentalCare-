<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$patients = loadJsonData(PATIENTS_FILE);
$appointments = loadJsonData(APPOINTMENTS_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_sms') {
        $patientId = $_POST['patient_id'];
        $messageText = $_POST['message'];
        
        // Find patient
        $patient = null;
        foreach ($patients as $p) {
            if ($p['id'] === $patientId) {
                $patient = $p;
                break;
            }
        }
        
        if ($patient) {
            if (sendSMS($patient['phone'], $messageText, $patient)) {
                $message = 'SMS সফলভাবে পাঠানো হয়েছে ' . htmlspecialchars($patient['name']) . ' এর কাছে!';
                $messageType = 'success';
            } else {
                $message = 'SMS পাঠাতে ব্যর্থ হয়েছে।';
                $messageType = 'danger';
            }
        }
    }
    
    elseif ($action === 'add') {
        $patient = [
            'id' => generateId(),
            'name' => sanitizeInput($_POST['name']),
            'phone' => sanitizeInput($_POST['phone']),
            'email' => sanitizeInput($_POST['email']),
            'age' => (int)($_POST['age'] ?? 0),
            'gender' => sanitizeInput($_POST['gender']),
            'address' => sanitizeInput($_POST['address']),
            'blood_group' => sanitizeInput($_POST['blood_group']),
            'medical_history' => sanitizeInput($_POST['medical_history']),
            'allergies' => sanitizeInput($_POST['allergies']),
            'emergency_contact' => sanitizeInput($_POST['emergency_contact']),
            'notes' => sanitizeInput($_POST['notes']),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $patients[] = $patient;
        
        if (saveJsonData(PATIENTS_FILE, $patients)) {
            $message = 'রোগী সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($patients, 'id'));
        
        if ($key !== false) {
            $patients[$key]['name'] = sanitizeInput($_POST['name']);
            $patients[$key]['phone'] = sanitizeInput($_POST['phone']);
            $patients[$key]['email'] = sanitizeInput($_POST['email']);
            $patients[$key]['age'] = (int)($_POST['age'] ?? 0);
            $patients[$key]['gender'] = sanitizeInput($_POST['gender']);
            $patients[$key]['address'] = sanitizeInput($_POST['address']);
            $patients[$key]['blood_group'] = sanitizeInput($_POST['blood_group']);
            $patients[$key]['medical_history'] = sanitizeInput($_POST['medical_history']);
            $patients[$key]['allergies'] = sanitizeInput($_POST['allergies']);
            $patients[$key]['emergency_contact'] = sanitizeInput($_POST['emergency_contact']);
            $patients[$key]['notes'] = sanitizeInput($_POST['notes']);
            $patients[$key]['updated_at'] = date('Y-m-d H:i:s');
            
            if (saveJsonData(PATIENTS_FILE, $patients)) {
                $message = 'রোগীর তথ্য সফলভাবে আপডেট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($patients, 'id'));
        
        if ($key !== false) {
            unset($patients[$key]);
            $patients = array_values($patients);
            
            if (saveJsonData(PATIENTS_FILE, $patients)) {
                $message = 'রোগী সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$filteredPatients = $patients;

if ($search) {
    $filteredPatients = array_filter($filteredPatients, function($p) use ($search) {
        return stripos($p['name'], $search) !== false || 
               stripos($p['phone'], $search) !== false ||
               stripos($p['email'], $search) !== false;
    });
}

// Sort by creation date
usort($filteredPatients, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$pageTitle = 'রোগী ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">রোগী ম্যানেজমেন্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                        <i class="bi bi-person-plus me-2"></i>নতুন রোগী যোগ করুন
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">রোগী খুঁজুন</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="নাম, ফোন বা ইমেইল">
                            </div>
                            
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-search"></i> খুঁজুন
                                </button>
                                <a href="patients.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Patients List -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($filteredPatients)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>রোগীর তথ্য</th>
                                        <th>যোগাযোগ</th>
                                        <th>বয়স/লিঙ্গ</th>
                                        <th>অ্যাপয়েন্টমেন্ট</th>
                                        <th>যোগদান</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filteredPatients as $patient): ?>
                                    <?php 
                                    $patientAppointments = array_filter($appointments, fn($a) => $a['phone'] === $patient['phone']);
                                    $appointmentCount = count($patientAppointments);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($patient['name']); ?></strong>
                                            <?php if (!empty($patient['blood_group'])): ?>
                                                <br><span class="badge bg-danger"><?php echo htmlspecialchars($patient['blood_group']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($patient['phone']); ?>
                                            <?php if (!empty($patient['email'])): ?>
                                                <br><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($patient['email']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($patient['age']): ?>
                                                <?php echo $patient['age']; ?> বছর
                                            <?php endif; ?>
                                            <?php if (!empty($patient['gender'])): ?>
                                                <br><small class="text-muted">
                                                    <?php 
                                                    $genderText = $patient['gender'] === 'male' ? 'পুরুষ' : 
                                                                 ($patient['gender'] === 'female' ? 'মহিলা' : 'অন্যান্য');
                                                    echo $genderText;
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $appointmentCount; ?>টি</span>
                                            <?php if ($appointmentCount > 0): ?>
                                                <br><small class="text-muted">সর্বশেষ: 
                                                <?php 
                                                $lastAppointment = end($patientAppointments);
                                                echo formatDateBengali($lastAppointment['created_at']);
                                                ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDateBengali($patient['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="sendSMSToPatient('<?php echo $patient['id']; ?>', '<?php echo htmlspecialchars($patient['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($patient['phone'], ENT_QUOTES); ?>')"
                                                        title="এসএমএস পাঠান">
                                                    <i class="bi bi-chat-text"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="viewPatient('<?php echo $patient['id']; ?>')"
                                                        title="দেখুন">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editPatient('<?php echo $patient['id']; ?>')"
                                                        title="সম্পাদনা">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deletePatient('<?php echo $patient['id']; ?>')"
                                                        title="ডিলিট">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">কোনো রোগী নেই</h5>
                            <p class="text-muted">প্রথম রোগী যোগ করুন বা অনুসন্ধান পরিবর্তন করুন।</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div class="modal fade" id="addPatientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন রোগী যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">নাম *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ফোন নম্বর *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ইমেইল</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">বয়স</label>
                                <input type="number" class="form-control" name="age" min="1" max="120">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">লিঙ্গ</label>
                                <select class="form-select" name="gender">
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="male">পুরুষ</option>
                                    <option value="female">মহিলা</option>
                                    <option value="other">অন্যান্য</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">রক্তের গ্রুপ</label>
                                <select class="form-select" name="blood_group">
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">ঠিকানা</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">চিকিৎসার ইতিহাস</label>
                                <textarea class="form-control" name="medical_history" rows="2"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">অ্যালার্জি</label>
                                <textarea class="form-control" name="allergies" rows="2"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">জরুরি যোগাযোগ</label>
                                <input type="text" class="form-control" name="emergency_contact" placeholder="নাম - ফোন নম্বর - সম্পর্ক">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">নোট</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">রোগী যোগ করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div class="modal fade" id="editPatientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_patient_id">
                    <div class="modal-header">
                        <h5 class="modal-title">রোগীর তথ্য এডিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">নাম *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ফোন নম্বর *</label>
                                <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ইমেইল</label>
                                <input type="email" class="form-control" name="email" id="edit_email">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">বয়স</label>
                                <input type="number" class="form-control" name="age" id="edit_age" min="1" max="120">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">লিঙ্গ</label>
                                <select class="form-select" name="gender" id="edit_gender">
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="male">পুরুষ</option>
                                    <option value="female">মহিলা</option>
                                    <option value="other">অন্যান্য</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">রক্তের গ্রুপ</label>
                                <select class="form-select" name="blood_group" id="edit_blood_group">
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">ঠিকানা</label>
                                <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">চিকিৎসার ইতিহাস</label>
                                <textarea class="form-control" name="medical_history" id="edit_medical_history" rows="2"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">অ্যালার্জি</label>
                                <textarea class="form-control" name="allergies" id="edit_allergies" rows="2"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">জরুরি যোগাযোগ</label>
                                <input type="text" class="form-control" name="emergency_contact" id="edit_emergency_contact">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">নোট</label>
                                <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">আপডেট করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Patient Modal -->
    <div class="modal fade" id="viewPatientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">রোগীর বিস্তারিত তথ্য</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="patientDetails">
                    <!-- Patient details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePatientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_patient_id">
                    <div class="modal-header">
                        <h5 class="modal-title">রোগী ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই রোগীর তথ্য ডিলিট করতে চান?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            রোগীর সাথে সম্পর্কিত সকল অ্যাপয়েন্টমেন্ট প্রভাবিত হতে পারে।
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-danger">ডিলিট করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SMS Modal -->
    <div class="modal fade" id="sendSMSModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">এসএমএস পাঠান</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="send_sms">
                    <input type="hidden" name="patient_id" id="sms_patient_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">রোগী</label>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-2" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <strong id="sms_patient_name"></strong>
                                    <br><small class="text-muted" id="sms_patient_phone"></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">টেমপ্লেট নির্বাচন (ঐচ্ছিক)</label>
                            <select class="form-select" id="smsTemplateSelect">
                                <option value="">কাস্টম বার্তা লিখুন</option>
                                <option value="প্রিয় {name}, আপনার অ্যাপয়েন্টমেন্ট আগামীকাল। দয়া করে সময়মতো উপস্থিত হন। ধন্যবাদ - ইনফিনিটি ডেন্টাল কেয়ার">অ্যাপয়েন্টমেন্ট অনুস্মারক</option>
                                <option value="প্রিয় {name}, আপনার চিকিৎসা সফলভাবে সম্পূর্ণ হয়েছে। পরবর্তী নির্দেশনা অনুসরণ করুন। ধন্যবাদ - ইনফিনিটি ডেন্টাল কেয়ার">চিকিৎসা সম্পূর্ণ</option>
                                <option value="প্রিয় {name}, নিয়মিত দাঁত ব্রাশ করুন এবং ৬ মাস পর পর চেকআপ করান। সুস্থ থাকুন। ধন্যবাদ - ইনফিনিটি ডেন্টাল কেয়ার">স্বাস্থ্য পরামর্শ</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">বার্তা</label>
                            <textarea class="form-control" name="message" rows="4" required id="smsMessageTextarea" 
                                      placeholder="এখানে আপনার বার্তা লিখুন..."></textarea>
                            <div class="form-text">
                                <span id="smsCharCount">0</span>/160 অক্ষর
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                বার্তায় {name} ব্যবহার করলে রোগীর নাম স্বয়ংক্রিয়ভাবে যুক্ত হবে।
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-chat-text me-2"></i>এসএমএস পাঠান
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    const patients = <?php echo json_encode($patients); ?>;
    const appointments = <?php echo json_encode($appointments); ?>;
    
    function viewPatient(id) {
        const patient = patients.find(p => p.id === id);
        if (patient) {
            const patientAppointments = appointments.filter(a => a.phone === patient.phone);
            
            const details = document.getElementById('patientDetails');
            details.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>ব্যক্তিগত তথ্য</h6>
                        <p><strong>নাম:</strong> ${patient.name}</p>
                        <p><strong>ফোন:</strong> ${patient.phone}</p>
                        ${patient.email ? `<p><strong>ইমেইল:</strong> ${patient.email}</p>` : ''}
                        ${patient.age ? `<p><strong>বয়স:</strong> ${patient.age} বছর</p>` : ''}
                        ${patient.gender ? `<p><strong>লিঙ্গ:</strong> ${getGenderText(patient.gender)}</p>` : ''}
                        ${patient.blood_group ? `<p><strong>রক্তের গ্রুপ:</strong> <span class="badge bg-danger">${patient.blood_group}</span></p>` : ''}
                    </div>
                    <div class="col-md-6">
                        <h6>যোগাযোগের তথ্য</h6>
                        ${patient.address ? `<p><strong>ঠিকানা:</strong> ${patient.address}</p>` : ''}
                        ${patient.emergency_contact ? `<p><strong>জরুরি যোগাযোগ:</strong> ${patient.emergency_contact}</p>` : ''}
                        <p><strong>মোট অ্যাপয়েন্টমেন্ট:</strong> ${patientAppointments.length}টি</p>
                        <p><strong>যোগদান:</strong> ${patient.created_at}</p>
                    </div>
                </div>
                ${patient.medical_history ? `
                    <h6>চিকিৎসার ইতিহাস</h6>
                    <p>${patient.medical_history}</p>
                ` : ''}
                ${patient.allergies ? `
                    <h6>অ্যালার্জি</h6>
                    <p>${patient.allergies}</p>
                ` : ''}
                ${patient.notes ? `
                    <h6>নোট</h6>
                    <p>${patient.notes}</p>
                ` : ''}
                ${patientAppointments.length > 0 ? `
                    <h6>সাম্প্রতিক অ্যাপয়েন্টমেন্ট</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>তারিখ</th>
                                    <th>সেবা</th>
                                    <th>স্ট্যাটাস</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${patientAppointments.slice(-5).map(a => `
                                    <tr>
                                        <td>${a.preferred_date}</td>
                                        <td>${a.service}</td>
                                        <td><span class="badge bg-${getStatusClass(a.status)}">${getStatusText(a.status)}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                ` : ''}
            `;
            
            new bootstrap.Modal(document.getElementById('viewPatientModal')).show();
        }
    }
    
    function editPatient(id) {
        const patient = patients.find(p => p.id === id);
        if (patient) {
            document.getElementById('edit_patient_id').value = patient.id;
            document.getElementById('edit_name').value = patient.name;
            document.getElementById('edit_phone').value = patient.phone;
            document.getElementById('edit_email').value = patient.email || '';
            document.getElementById('edit_age').value = patient.age || '';
            document.getElementById('edit_gender').value = patient.gender || '';
            document.getElementById('edit_blood_group').value = patient.blood_group || '';
            document.getElementById('edit_address').value = patient.address || '';
            document.getElementById('edit_medical_history').value = patient.medical_history || '';
            document.getElementById('edit_allergies').value = patient.allergies || '';
            document.getElementById('edit_emergency_contact').value = patient.emergency_contact || '';
            document.getElementById('edit_notes').value = patient.notes || '';
            
            new bootstrap.Modal(document.getElementById('editPatientModal')).show();
        }
    }
    
    function deletePatient(id) {
        document.getElementById('delete_patient_id').value = id;
        new bootstrap.Modal(document.getElementById('deletePatientModal')).show();
    }
    
    function getGenderText(gender) {
        return gender === 'male' ? 'পুরুষ' : (gender === 'female' ? 'মহিলা' : 'অন্যান্য');
    }
    
    function getStatusClass(status) {
        return status === 'approved' ? 'success' : (status === 'rejected' ? 'danger' : 'warning');
    }
    
    function getStatusText(status) {
        return status === 'approved' ? 'অনুমোদিত' : (status === 'rejected' ? 'বাতিল' : 'অপেক্ষমাণ');
    }
    
    // SMS functionality
    function sendSMSToPatient(patientId, patientName, patientPhone) {
        document.getElementById('sms_patient_id').value = patientId;
        document.getElementById('sms_patient_name').textContent = patientName;
        document.getElementById('sms_patient_phone').textContent = patientPhone;
        document.getElementById('smsMessageTextarea').value = '';
        document.getElementById('smsCharCount').textContent = '0';
        document.getElementById('smsTemplateSelect').value = '';
        
        new bootstrap.Modal(document.getElementById('sendSMSModal')).show();
    }
    
    // Character counting for SMS
    document.addEventListener('DOMContentLoaded', function() {
        const smsTextarea = document.getElementById('smsMessageTextarea');
        const charCount = document.getElementById('smsCharCount');
        const templateSelect = document.getElementById('smsTemplateSelect');
        
        if (smsTextarea && charCount) {
            smsTextarea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
                if (this.value.length > 160) {
                    charCount.style.color = 'red';
                } else {
                    charCount.style.color = '';
                }
            });
        }
        
        if (templateSelect && smsTextarea) {
            templateSelect.addEventListener('change', function() {
                if (this.value) {
                    smsTextarea.value = this.value;
                    charCount.textContent = this.value.length;
                    if (this.value.length > 160) {
                        charCount.style.color = 'red';
                    } else {
                        charCount.style.color = '';
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
