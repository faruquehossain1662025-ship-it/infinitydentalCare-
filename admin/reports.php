<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$patients = loadJsonData(PATIENTS_FILE);
$appointments = loadJsonData(APPOINTMENTS_FILE);
$reports = loadJsonData(DATA_DIR . 'reports.json') ?: [];
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $report = [
            'id' => generateId(),
            'patient_id' => sanitizeInput($_POST['patient_id']),
            'patient_name' => sanitizeInput($_POST['patient_name']),
            'appointment_id' => sanitizeInput($_POST['appointment_id'] ?? ''),
            'report_type' => sanitizeInput($_POST['report_type']),
            'diagnosis' => sanitizeInput($_POST['diagnosis']),
            'treatment' => sanitizeInput($_POST['treatment']),
            'prescription' => sanitizeInput($_POST['prescription']),
            'follow_up_date' => sanitizeInput($_POST['follow_up_date']),
            'notes' => sanitizeInput($_POST['notes']),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $reports[] = $report;
        
        if (saveJsonData(DATA_DIR . 'reports.json', $reports)) {
            $message = 'রিপোর্ট সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($reports, 'id'));
        
        if ($key !== false) {
            $reports[$key]['patient_id'] = sanitizeInput($_POST['patient_id']);
            $reports[$key]['patient_name'] = sanitizeInput($_POST['patient_name']);
            $reports[$key]['appointment_id'] = sanitizeInput($_POST['appointment_id'] ?? '');
            $reports[$key]['report_type'] = sanitizeInput($_POST['report_type']);
            $reports[$key]['diagnosis'] = sanitizeInput($_POST['diagnosis']);
            $reports[$key]['treatment'] = sanitizeInput($_POST['treatment']);
            $reports[$key]['prescription'] = sanitizeInput($_POST['prescription']);
            $reports[$key]['follow_up_date'] = sanitizeInput($_POST['follow_up_date']);
            $reports[$key]['notes'] = sanitizeInput($_POST['notes']);
            $reports[$key]['updated_at'] = date('Y-m-d H:i:s');
            
            if (saveJsonData(DATA_DIR . 'reports.json', $reports)) {
                $message = 'রিপোর্ট সফলভাবে আপডেট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($reports, 'id'));
        
        if ($key !== false) {
            unset($reports[$key]);
            $reports = array_values($reports);
            
            if (saveJsonData(DATA_DIR . 'reports.json', $reports)) {
                $message = 'রিপোর্ট সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Filter and search
$search = $_GET['search'] ?? '';
$filteredReports = $reports;

if ($search) {
    $filteredReports = array_filter($filteredReports, function($r) use ($search) {
        return stripos($r['patient_name'], $search) !== false || 
               stripos($r['diagnosis'], $search) !== false ||
               stripos($r['report_type'], $search) !== false;
    });
}

// Sort by creation date
usort($filteredReports, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$pageTitle = 'মেডিকেল রিপোর্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">মেডিকেল রিপোর্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReportModal">
                        <i class="bi bi-file-earmark-medical-fill me-2"></i>নতুন রিপোর্ট যোগ করুন
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
                                <label class="form-label">রিপোর্ট খুঁজুন</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="রোগীর নাম, ডায়াগনোসিস বা রিপোর্টের ধরন">
                            </div>
                            
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-search"></i> খুঁজুন
                                </button>
                                <a href="reports.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reports List -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($filteredReports)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>রোগীর তথ্য</th>
                                        <th>রিপোর্টের ধরন</th>
                                        <th>ডায়াগনোসিস</th>
                                        <th>ফলো-আপ</th>
                                        <th>তৈরি</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filteredReports as $report): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($report['patient_name']); ?></strong>
                                            <?php if (!empty($report['appointment_id'])): ?>
                                                <br><small class="text-muted">অ্যাপয়েন্টমেন্ট: <?php echo htmlspecialchars($report['appointment_id']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($report['report_type']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($report['diagnosis'], 0, 50)) . '...'; ?></td>
                                        <td>
                                            <?php if (!empty($report['follow_up_date'])): ?>
                                                <?php echo formatDateBengali($report['follow_up_date']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">নেই</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDateBengali($report['created_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info me-1" 
                                                    onclick="viewReport('<?php echo $report['id']; ?>')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editReport('<?php echo $report['id']; ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success me-1" 
                                                    onclick="printReport('<?php echo $report['id']; ?>')">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteReport('<?php echo $report['id']; ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-file-earmark-medical text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">কোনো রিপোর্ট নেই</h5>
                            <p class="text-muted">প্রথম মেডিকেল রিপোর্ট তৈরি করুন।</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Report Modal -->
    <div class="modal fade" id="addReportModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন মেডিকেল রিপোর্ট</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">রোগী নির্বাচন করুন *</label>
                                <select class="form-select" name="patient_id" id="patient_select" required onchange="updatePatientName()">
                                    <option value="">রোগী নির্বাচন করুন</option>
                                    <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" data-name="<?php echo htmlspecialchars($patient['name']); ?>">
                                        <?php echo htmlspecialchars($patient['name'] . ' - ' . $patient['phone']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="patient_name" id="patient_name">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">অ্যাপয়েন্টমেন্ট (ঐচ্ছিক)</label>
                                <select class="form-select" name="appointment_id" id="appointment_select">
                                    <option value="">অ্যাপয়েন্টমেন্ট নির্বাচন করুন</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">রিপোর্টের ধরন *</label>
                                <select class="form-select" name="report_type" required>
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="চেকআপ">চেকআপ</option>
                                    <option value="চিকিৎসা">চিকিৎসা</option>
                                    <option value="সার্জারি">সার্জারি</option>
                                    <option value="ফলো-আপ">ফলো-আপ</option>
                                    <option value="জরুরি">জরুরি</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ফলো-আপের তারিখ</label>
                                <input type="date" class="form-control" name="follow_up_date" min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">ডায়াগনোসিস *</label>
                                <textarea class="form-control" name="diagnosis" rows="4" required placeholder="রোগ নির্ণয় ও পর্যবেক্ষণ"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">চিকিৎসা *</label>
                                <textarea class="form-control" name="treatment" rows="4" required placeholder="প্রদত্ত চিকিৎসা ও পদ্ধতি"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">প্রেসক্রিপশন</label>
                                <textarea class="form-control" name="prescription" rows="4" placeholder="ওষুধ ও ডোজ"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">অতিরিক্ত নোট</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="অন্যান্য তথ্য ও পরামর্শ"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">রিপোর্ট সেভ করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Report Modal -->
    <div class="modal fade" id="viewReportModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">মেডিকেল রিপোর্ট</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reportDetails">
                    <!-- Report details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>প্রিন্ট করুন
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Report Modal -->
    <div class="modal fade" id="editReportModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_report_id">
                    <div class="modal-header">
                        <h5 class="modal-title">রিপোর্ট এডিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">রোগী নির্বাচন করুন *</label>
                                <select class="form-select" name="patient_id" id="edit_patient_select" required>
                                    <option value="">রোগী নির্বাচন করুন</option>
                                    <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" data-name="<?php echo htmlspecialchars($patient['name']); ?>">
                                        <?php echo htmlspecialchars($patient['name'] . ' - ' . $patient['phone']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="patient_name" id="edit_patient_name">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">অ্যাপয়েন্টমেন্ট (ঐচ্ছিক)</label>
                                <input type="text" class="form-control" name="appointment_id" id="edit_appointment_id">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">রিপোর্টের ধরন *</label>
                                <select class="form-select" name="report_type" id="edit_report_type" required>
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="চেকআপ">চেকআপ</option>
                                    <option value="চিকিৎসা">চিকিৎসা</option>
                                    <option value="সার্জারি">সার্জারি</option>
                                    <option value="ফলো-আপ">ফলো-আপ</option>
                                    <option value="জরুরি">জরুরি</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ফলো-আপের তারিখ</label>
                                <input type="date" class="form-control" name="follow_up_date" id="edit_follow_up_date">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">ডায়াগনোসিস *</label>
                                <textarea class="form-control" name="diagnosis" id="edit_diagnosis" rows="4" required></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">চিকিৎসা *</label>
                                <textarea class="form-control" name="treatment" id="edit_treatment" rows="4" required></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">প্রেসক্রিপশন</label>
                                <textarea class="form-control" name="prescription" id="edit_prescription" rows="4"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">অতিরিক্ত নোট</label>
                                <textarea class="form-control" name="notes" id="edit_notes" rows="3"></textarea>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteReportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_report_id">
                    <div class="modal-header">
                        <h5 class="modal-title">রিপোর্ট ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই রিপোর্ট ডিলিট করতে চান?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            এই অ্যাকশন পূর্বাবস্থায় ফেরানো যাবে না।
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

    <?php include 'includes/footer.php'; ?>
    
    <script>
    const reports = <?php echo json_encode($reports); ?>;
    const patients = <?php echo json_encode($patients); ?>;
    const appointments = <?php echo json_encode($appointments); ?>;
    
    function updatePatientName() {
        const select = document.getElementById('patient_select');
        const hiddenInput = document.getElementById('patient_name');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.value) {
            hiddenInput.value = selectedOption.getAttribute('data-name');
            loadPatientAppointments(selectedOption.value);
        } else {
            hiddenInput.value = '';
            document.getElementById('appointment_select').innerHTML = '<option value="">অ্যাপয়েন্টমেন্ট নির্বাচন করুন</option>';
        }
    }
    
    function loadPatientAppointments(patientId) {
        const patient = patients.find(p => p.id === patientId);
        const appointmentSelect = document.getElementById('appointment_select');
        
        if (patient) {
            const patientAppointments = appointments.filter(a => a.phone === patient.phone);
            
            appointmentSelect.innerHTML = '<option value="">অ্যাপয়েন্টমেন্ট নির্বাচন করুন</option>';
            
            patientAppointments.forEach(appointment => {
                const option = document.createElement('option');
                option.value = appointment.appointment_number;
                option.textContent = `${appointment.appointment_number} - ${appointment.preferred_date} - ${appointment.service}`;
                appointmentSelect.appendChild(option);
            });
        }
    }
    
    function viewReport(id) {
        const report = reports.find(r => r.id === id);
        if (report) {
            const details = document.getElementById('reportDetails');
            details.innerHTML = `
                <div class="report-print">
                    <div class="text-center mb-4">
                        <h3>মেডিকেল রিপোর্ট</h3>
                        <p class="text-muted">রিপোর্ট নং: ${report.id}</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>রোগীর তথ্য</h6>
                            <p><strong>নাম:</strong> ${report.patient_name}</p>
                            ${report.appointment_id ? `<p><strong>অ্যাপয়েন্টমেন্ট:</strong> ${report.appointment_id}</p>` : ''}
                            <p><strong>রিপোর্টের ধরন:</strong> ${report.report_type}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>তারিখ</h6>
                            <p><strong>তৈরি:</strong> ${report.created_at}</p>
                            ${report.follow_up_date ? `<p><strong>ফলো-আপ:</strong> ${report.follow_up_date}</p>` : ''}
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6>ডায়াগনোসিস</h6>
                        <div class="border p-3 rounded">${report.diagnosis}</div>
                    </div>
                    
                    <div class="mb-4">
                        <h6>চিকিৎসা</h6>
                        <div class="border p-3 rounded">${report.treatment}</div>
                    </div>
                    
                    ${report.prescription ? `
                        <div class="mb-4">
                            <h6>প্রেসক্রিপশন</h6>
                            <div class="border p-3 rounded">${report.prescription}</div>
                        </div>
                    ` : ''}
                    
                    ${report.notes ? `
                        <div class="mb-4">
                            <h6>অতিরিক্ত নোট</h6>
                            <div class="border p-3 rounded">${report.notes}</div>
                        </div>
                    ` : ''}
                    
                    <div class="text-center mt-5">
                        <p class="text-muted">ডাক্তারের স্বাক্ষর</p>
                        <div style="height: 50px; border-bottom: 1px solid #ccc; width: 200px; margin: 0 auto;"></div>
                    </div>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('viewReportModal')).show();
        }
    }
    
    function editReport(id) {
        const report = reports.find(r => r.id === id);
        if (report) {
            document.getElementById('edit_report_id').value = report.id;
            document.getElementById('edit_patient_select').value = report.patient_id;
            document.getElementById('edit_patient_name').value = report.patient_name;
            document.getElementById('edit_appointment_id').value = report.appointment_id || '';
            document.getElementById('edit_report_type').value = report.report_type;
            document.getElementById('edit_follow_up_date').value = report.follow_up_date || '';
            document.getElementById('edit_diagnosis').value = report.diagnosis;
            document.getElementById('edit_treatment').value = report.treatment;
            document.getElementById('edit_prescription').value = report.prescription || '';
            document.getElementById('edit_notes').value = report.notes || '';
            
            new bootstrap.Modal(document.getElementById('editReportModal')).show();
        }
    }
    
    function deleteReport(id) {
        document.getElementById('delete_report_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteReportModal')).show();
    }
    
    function printReport(id) {
        viewReport(id);
        setTimeout(() => {
            window.print();
        }, 500);
    }
    </script>
    
    <style>
    @media print {
        .modal-header, .modal-footer, .btn {
            display: none !important;
        }
        
        .report-print {
            margin: 0;
            padding: 20px;
        }
        
        .border {
            border: 1px solid #000 !important;
        }
    }
    </style>
</body>
</html>
