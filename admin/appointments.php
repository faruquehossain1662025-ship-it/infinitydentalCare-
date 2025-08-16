<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$appointments = loadJsonData(APPOINTMENTS_FILE);
$patients = loadJsonData(PATIENTS_FILE);
$services = loadJsonData(SERVICES_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($appointments, 'id'));
        
        if ($key !== false) {
            $oldStatus = $appointments[$key]['status'] ?? 'pending';
            $newStatus = sanitizeInput($_POST['status']);
            $adminNote = sanitizeInput($_POST['notes'] ?? '');
            
            $appointments[$key]['status'] = $newStatus;
            $appointments[$key]['notes'] = $adminNote;
            $appointments[$key]['updated_at'] = date('Y-m-d H:i:s');
            
            if (saveJsonData(APPOINTMENTS_FILE, $appointments)) {
                $appointment = $appointments[$key];
                $emailSent = false;
                $smsSent = false;
                
                // ইমেইল পাঠানো (শুধু status change হলে)
                if (!empty($appointment['email']) && $oldStatus !== $newStatus) {
                    $emailSent = sendAppointmentStatusEmail($appointment, $oldStatus, $newStatus, $adminNote);
                    
                    if ($emailSent) {
                        $appointments[$key]['email_sent'] = true;
                        $appointments[$key]['email_sent_date'] = date('Y-m-d H:i:s');
                    }
                }
                
                // SMS পাঠানো (বিদ্যমান কোড)
                if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                    if (sendAppointmentApprovalSMS($appointment, $adminNote)) {
                        $smsSent = true;
                        $appointments[$key]['sms_sent'] = true;
                        $appointments[$key]['sms_sent_date'] = date('Y-m-d H:i:s');
                    }
                } elseif ($newStatus === 'rejected' && $oldStatus !== 'rejected') {
                    $rejectionReason = $adminNote;
                    if (sendAppointmentRejectionSMS($appointment, $rejectionReason)) {
                        $smsSent = true;
                        $appointments[$key]['sms_sent'] = true;
                        $appointments[$key]['sms_sent_date'] = date('Y-m-d H:i:s');
                    }
                }
                
                // ডাটা সেভ করা
                saveJsonData(APPOINTMENTS_FILE, $appointments);
                
                // সাকসেস মেসেজ তৈরি করা
                $statusText = $newStatus === 'approved' ? 'অনুমোদিত' : 
                             ($newStatus === 'rejected' ? 'বাতিল' : 'আপডেট');
                
                $message = 'অ্যাপয়েন্টমেন্ট ' . $statusText . ' করা হয়েছে!';
                
                // Notification details
                $notifications = [];
                if ($emailSent) $notifications[] = 'ইমেইল';
                if ($smsSent) $notifications[] = 'SMS';
                
                if (!empty($notifications)) {
                    $message .= ' রোগীকে ' . implode(' এবং ', $notifications) . ' পাঠানো হয়েছে।';
                } elseif (!empty($appointment['email']) || !empty($appointment['phone'])) {
                    $failedNotifications = [];
                    if (!empty($appointment['email']) && !$emailSent && $oldStatus !== $newStatus) $failedNotifications[] = 'ইমেইল';
                    if (!empty($appointment['phone']) && !$smsSent && (
                        ($newStatus === 'approved' && $oldStatus !== 'approved') || 
                        ($newStatus === 'rejected' && $oldStatus !== 'rejected')
                    )) $failedNotifications[] = 'SMS';
                    
                    if (!empty($failedNotifications)) {
                        $message .= ' তবে ' . implode(' এবং ', $failedNotifications) . ' পাঠানোতে সমস্যা হয়েছে।';
                    }
                }
                
                $messageType = 'success';
            } else {
                $message = 'অ্যাপয়েন্টমেন্ট আপডেট করতে সমস্যা হয়েছে!';
                $messageType = 'danger';
            }
        } else {
            $message = 'অ্যাপয়েন্টমেন্ট খুঁজে পাওয়া যায়নি!';
            $messageType = 'warning';
        }
    }
    
    elseif ($action === 'add') {
        $appointmentNumber = generateAppointmentNumber();
        
        $appointment = [
            'id' => generateId(),
            'appointment_number' => $appointmentNumber,
            'name' => sanitizeInput($_POST['name']),
            'phone' => sanitizeInput($_POST['phone']),
            'email' => sanitizeInput($_POST['email']),
            'age' => (int)($_POST['age'] ?? 0),
            'gender' => sanitizeInput($_POST['gender']),
            'service' => sanitizeInput($_POST['service']),
            'preferred_date' => sanitizeInput($_POST['preferred_date']),
            'preferred_time' => sanitizeInput($_POST['preferred_time']),
            'problem_description' => sanitizeInput($_POST['problem_description']),
            'emergency_contact' => sanitizeInput($_POST['emergency_contact']),
            'status' => 'approved', // Admin created appointments are auto-approved
            'notes' => sanitizeInput($_POST['notes'] ?? ''),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $appointments[] = $appointment;
        
        if (saveJsonData(APPOINTMENTS_FILE, $appointments)) {
            $message = 'অ্যাপয়েন্টমেন্ট সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
            
            // ইমেইল পাঠানো নতুন অ্যাপয়েন্টমেন্টের জন্য
            if (!empty($appointment['email'])) {
                sendAppointmentStatusEmail($appointment, 'pending', 'approved', 'আপনার অ্যাপয়েন্টমেন্ট সফলভাবে তৈরি এবং অনুমোদিত হয়েছে।');
            }
            
            // Add to patients if not exists
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
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($appointments, 'id'));
        
        if ($key !== false) {
            unset($appointments[$key]);
            $appointments = array_values($appointments);
            
            if (saveJsonData(APPOINTMENTS_FILE, $appointments)) {
                $message = 'অ্যাপয়েন্টমেন্ট সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Filter and search
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$filteredAppointments = $appointments;

if ($filter !== 'all') {
    $filteredAppointments = array_filter($filteredAppointments, fn($a) => ($a['status'] ?? 'pending') === $filter);
}

if ($search) {
    $filteredAppointments = array_filter($filteredAppointments, function($a) use ($search) {
        return stripos($a['name'], $search) !== false || 
               stripos($a['phone'], $search) !== false ||
               stripos($a['appointment_number'], $search) !== false;
    });
}

// Sort by date
usort($filteredAppointments, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$pageTitle = 'অ্যাপয়েন্টমেন্ট ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">অ্যাপয়েন্টমেন্ট ম্যানেজমেন্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                        <i class="bi bi-plus-circle me-2"></i>নতুন অ্যাপয়েন্টমেন্ট
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5>মোট অ্যাপয়েন্টমেন্ট</h5>
                                <h3><?php echo count($appointments); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5>অপেক্ষমাণ</h5>
                                <h3><?php echo count(array_filter($appointments, fn($a) => ($a['status'] ?? 'pending') === 'pending')); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>অনুমোদিত</h5>
                                <h3><?php echo count(array_filter($appointments, fn($a) => ($a['status'] ?? 'pending') === 'approved')); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5>বাতিল</h5>
                                <h3><?php echo count(array_filter($appointments, fn($a) => ($a['status'] ?? 'pending') === 'rejected')); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">স্ট্যাটাস ফিল্টার</label>
                                <select class="form-select" name="filter" onchange="this.form.submit()">
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>সব</option>
                                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>অপেক্ষমাণ</option>
                                    <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>অনুমোদিত</option>
                                    <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>বাতিল</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">খুঁজুন</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="নাম, ফোন বা অ্যাপয়েন্টমেন্ট নম্বর">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">খুঁজুন</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>অ্যাপয়েন্টমেন্ট নং</th>
                                        <th>রোগীর নাম</th>
                                        <th>ফোন</th>
                                        <th>ইমেইল</th>
                                        <th>সেবা</th>
                                        <th>তারিখ ও সময়</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>নোটিফিকেশন</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($filteredAppointments)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">কোন অ্যাপয়েন্টমেন্ট পাওয়া যায়নি</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($filteredAppointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['appointment_number']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['service']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($appointment['preferred_date']); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($appointment['preferred_time']); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $appointment['status'] ?? 'pending';
                                                $statusClass = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning');
                                                $statusText = $status === 'approved' ? 'অনুমোদিত' : ($status === 'rejected' ? 'বাতিল' : 'অপেক্ষমাণ');
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <?php if (isset($appointment['email_sent']) && $appointment['email_sent']): ?>
                                                        <small class="text-success">
                                                            <i class="bi bi-envelope-check"></i> ইমেইল পাঠানো হয়েছে
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if (isset($appointment['sms_sent']) && $appointment['sms_sent']): ?>
                                                        <small class="text-success">
                                                            <i class="bi bi-chat-dots-fill"></i> SMS পাঠানো হয়েছে
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewAppointmentModal<?php echo $appointment['id']; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#updateStatusModal<?php echo $appointment['id']; ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteAppointment('<?php echo $appointment['id']; ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Appointment Modal -->
    <?php foreach ($filteredAppointments as $appointment): ?>
    <div class="modal fade" id="viewAppointmentModal<?php echo $appointment['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">অ্যাপয়েন্টমেন্ট বিস্তারিত</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>অ্যাপয়েন্টমেন্ট নং:</strong> <?php echo htmlspecialchars($appointment['appointment_number']); ?></p>
                            <p><strong>নাম:</strong> <?php echo htmlspecialchars($appointment['name']); ?></p>
                            <p><strong>ফোন:</strong> <?php echo htmlspecialchars($appointment['phone']); ?></p>
                            <p><strong>ইমেইল:</strong> <?php echo htmlspecialchars($appointment['email'] ?? 'N/A'); ?></p>
                            <p><strong>বয়স:</strong> <?php echo htmlspecialchars($appointment['age'] ?? 'N/A'); ?></p>
                            <p><strong>লিঙ্গ:</strong> <?php echo htmlspecialchars($appointment['gender'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>সেবা:</strong> <?php echo htmlspecialchars($appointment['service']); ?></p>
                            <p><strong>পছন্দের তারিখ:</strong> <?php echo htmlspecialchars($appointment['preferred_date']); ?></p>
                            <p><strong>পছন্দের সময়:</strong> <?php echo htmlspecialchars($appointment['preferred_time']); ?></p>
                            <p><strong>জরুরি যোগাযোগ:</strong> <?php echo htmlspecialchars($appointment['emergency_contact'] ?? 'N/A'); ?></p>
                            <p><strong>তৈরি:</strong> <?php echo formatDateBengali($appointment['created_at']); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($appointment['problem_description'])): ?>
                    <div class="mt-3">
                        <strong>সমস্যার বিবরণ:</strong>
                        <p class="bg-light p-2 rounded"><?php echo nl2br(htmlspecialchars($appointment['problem_description'])); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($appointment['notes'])): ?>
                    <div class="mt-3">
                        <strong>অ্যাডমিন নোট:</strong>
                        <p class="bg-warning bg-opacity-25 p-2 rounded"><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal<?php echo $appointment['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">স্ট্যাটাস আপডেট</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="<?php echo $appointment['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">স্ট্যাটাস</label>
                            <select class="form-select" name="status" required>
                                <option value="pending" <?php echo ($appointment['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>অপেক্ষমাণ</option>
                                <option value="approved" <?php echo ($appointment['status'] ?? 'pending') === 'approved' ? 'selected' : ''; ?>>অনুমোদিত</option>
                                <option value="rejected" <?php echo ($appointment['status'] ?? 'pending') === 'rejected' ? 'selected' : ''; ?>>বাতিল</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">নোট (ঐচ্ছিক)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="রোগীর জন্য বিশেষ নির্দেশনা..."><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>নোটিফিকেশন:</strong> স্ট্যাটাস পরিবর্তন করলে রোগীর কাছে SMS এবং ইমেইল (যদি থাকে) পাঠানো হবে।
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
    <?php endforeach; ?>

    <!-- Add Appointment Modal -->
    <div class="modal fade" id="addAppointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন অ্যাপয়েন্টমেন্ট যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">রোগীর নাম *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">ফোন নম্বর *</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">ইমেইল</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">বয়স</label>
                                    <input type="number" class="form-control" name="age" min="1" max="120">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">লিঙ্গ</label>
                                    <select class="form-select" name="gender">
                                        <option value="">নির্বাচন করুন</option>
                                        <option value="male">পুরুষ</option>
                                        <option value="female">মহিলা</option>
                                        <option value="other">অন্যান্য</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">সেবা *</label>
                                    <select class="form-select" name="service" required>
                                        <option value="">নির্বাচন করুন</option>
                                        <?php foreach ($services as $service): ?>
                                        <option value="<?php echo htmlspecialchars($service['name']); ?>">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">পছন্দের তারিখ *</label>
                                    <input type="date" class="form-control" name="preferred_date" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">পছন্দের সময় *</label>
                                    <input type="time" class="form-control" name="preferred_time" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">জরুরি যোগাযোগ</label>
                                    <input type="tel" class="form-control" name="emergency_contact">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">সমস্যার বিবরণ</label>
                            <textarea class="form-control" name="problem_description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">অ্যাডমিন নোট</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">যোগ করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteForm">
                    <div class="modal-header">
                        <h5 class="modal-title">নিশ্চিতকরণ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <p>আপনি কি নিশ্চিত যে এই অ্যাপয়েন্টমেন্ট ডিলিট করতে চান?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">না</button>
                        <button type="submit" class="btn btn-danger">হ্যাঁ, ডিলিট করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js"></script>
    
    <script>
    function deleteAppointment(id) {
        document.getElementById('deleteId').value = id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        });
    });
    </script>
</body>
</html>