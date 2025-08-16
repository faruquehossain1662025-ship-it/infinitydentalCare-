<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$patients = loadJsonData(PATIENTS_FILE);
$smsHistory = getAllSMSHistory();
$smsTemplates = getSMSTemplates();
$message = '';
$messageType = '';

// Handle SMS sending
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_individual') {
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
                $message = 'SMS সফলভাবে পাঠানো হয়েছে!';
                $messageType = 'success';
            } else {
                $message = 'SMS পাঠাতে ব্যর্থ হয়েছে।';
                $messageType = 'error';
            }
        }
    }
    
    elseif ($action === 'send_bulk') {
        $selectedPatients = $_POST['selected_patients'] ?? [];
        $messageText = $_POST['bulk_message'];
        $successCount = 0;
        $totalCount = count($selectedPatients);
        
        foreach ($selectedPatients as $patientId) {
            // Find patient
            $patient = null;
            foreach ($patients as $p) {
                if ($p['id'] === $patientId) {
                    $patient = $p;
                    break;
                }
            }
            
            if ($patient && sendSMS($patient['phone'], $messageText, $patient)) {
                $successCount++;
            }
        }
        
        $message = "$successCount/$totalCount SMS সফলভাবে পাঠানো হয়েছে।";
        $messageType = $successCount > 0 ? 'success' : 'error';
    }
    
    // Reload SMS history after sending
    $smsHistory = getAllSMSHistory();
}

// Sort SMS history by timestamp (newest first)
usort($smsHistory, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

$pageTitle = 'এসএমএস ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">এসএমএস ম্যানেজমেন্ট</h1>
                    <div class="btn-group">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendIndividualSMSModal">
                            <i class="bi bi-chat-text me-2"></i>ইন্ডিভিজুয়াল এসএমএস
                        </button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sendBulkSMSModal">
                            <i class="bi bi-broadcast me-2"></i>বাল্ক এসএমএস
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- SMS Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-chat-dots-fill fs-1 me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0">মোট এসএমএস</h5>
                                        <h3 class="mb-0"><?php echo count($smsHistory); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill fs-1 me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0">সফল</h5>
                                        <h3 class="mb-0"><?php echo count(array_filter($smsHistory, fn($sms) => $sms['status'] === 'Success')); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-x-circle-fill fs-1 me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0">ব্যর্থ</h5>
                                        <h3 class="mb-0"><?php echo count(array_filter($smsHistory, fn($sms) => $sms['status'] === 'Failed')); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-people-fill fs-1 me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0">মোট রোগী</h5>
                                        <h3 class="mb-0"><?php echo count($patients); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SMS History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">এসএমএস ইতিহাস</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="smsHistoryTable">
                                <thead>
                                    <tr>
                                        <th>সময়</th>
                                        <th>রোগী</th>
                                        <th>ফোন নম্বর</th>
                                        <th>বার্তা</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>রেসপন্স</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($smsHistory as $sms): ?>
                                        <tr>
                                            <td><?php echo formatDateBengali($sms['timestamp']); ?></td>
                                            <td><?php echo htmlspecialchars($sms['patient_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($sms['phone']); ?></td>
                                            <td>
                                                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo htmlspecialchars(substr($sms['message'], 0, 50)); ?>
                                                    <?php if (strlen($sms['message']) > 50): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($sms['status'] === 'Success'): ?>
                                                    <span class="badge bg-success">সফল</span>
                                                <?php elseif ($sms['status'] === 'Failed'): ?>
                                                    <span class="badge bg-danger">ব্যর্থ</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">অপেক্ষারত</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($sms['response']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Individual SMS Modal -->
    <div class="modal fade" id="sendIndividualSMSModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ইন্ডিভিজুয়াল এসএমএস পাঠান</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="send_individual">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">রোগী নির্বাচন করুন</label>
                            <select class="form-select" name="patient_id" required>
                                <option value="">রোগী নির্বাচন করুন</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>" 
                                            data-phone="<?php echo htmlspecialchars($patient['phone']); ?>">
                                        <?php echo htmlspecialchars($patient['name']); ?> - <?php echo htmlspecialchars($patient['phone']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">টেমপ্লেট নির্বাচন (ঐচ্ছিক)</label>
                            <select class="form-select" id="templateSelect">
                                <option value="">টেমপ্লেট নির্বাচন করুন</option>
                                <?php foreach ($smsTemplates as $template): ?>
                                    <option value="<?php echo htmlspecialchars($template['message']); ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">বার্তা</label>
                            <textarea class="form-control" name="message" rows="4" required id="messageTextarea" 
                                      placeholder="এখানে আপনার বার্তা লিখুন..."></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span>/160 অক্ষর
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">এসএমএস পাঠান</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk SMS Modal -->
    <div class="modal fade" id="sendBulkSMSModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">বাল্ক এসএমএস পাঠান</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="send_bulk">
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label">রোগী নির্বাচন করুন</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPatients()">
                                        সবাই নির্বাচন
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllPatients()">
                                        সব মুছে দিন
                                    </button>
                                </div>
                            </div>
                            
                            <div class="patient-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                <?php foreach ($patients as $patient): ?>
                                    <div class="form-check">
                                        <input class="form-check-input patient-checkbox" type="checkbox" 
                                               name="selected_patients[]" value="<?php echo $patient['id']; ?>">
                                        <label class="form-check-label">
                                            <?php echo htmlspecialchars($patient['name']); ?> - <?php echo htmlspecialchars($patient['phone']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">টেমপ্লেট নির্বাচন (ঐচ্ছিক)</label>
                            <select class="form-select" id="bulkTemplateSelect">
                                <option value="">টেমপ্লেট নির্বাচন করুন</option>
                                <?php foreach ($smsTemplates as $template): ?>
                                    <option value="<?php echo htmlspecialchars($template['message']); ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">বার্তা</label>
                            <textarea class="form-control" name="bulk_message" rows="4" required id="bulkMessageTextarea" 
                                      placeholder="এখানে আপনার বার্তা লিখুন..."></textarea>
                            <div class="form-text">
                                <span id="bulkCharCount">0</span>/160 অক্ষর | নির্বাচিত রোগী: <span id="selectedCount">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-success">বাল্ক এসএমএস পাঠান</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js"></script>
    <script src="js/sms.js"></script>
    
    <script>
        // Character counting
        document.getElementById('messageTextarea').addEventListener('input', function() {
            document.getElementById('charCount').textContent = this.value.length;
        });
        
        document.getElementById('bulkMessageTextarea').addEventListener('input', function() {
            document.getElementById('bulkCharCount').textContent = this.value.length;
        });
        
        // Template selection
        document.getElementById('templateSelect').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('messageTextarea').value = this.value;
                document.getElementById('charCount').textContent = this.value.length;
            }
        });
        
        document.getElementById('bulkTemplateSelect').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('bulkMessageTextarea').value = this.value;
                document.getElementById('bulkCharCount').textContent = this.value.length;
            }
        });
        
        // Count selected patients
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.patient-checkbox:checked');
            document.getElementById('selectedCount').textContent = checkboxes.length;
        }
        
        // Add event listeners to checkboxes
        document.querySelectorAll('.patient-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', updateSelectedCount);
        });
        
        function selectAllPatients() {
            document.querySelectorAll('.patient-checkbox').forEach(function(checkbox) {
                checkbox.checked = true;
            });
            updateSelectedCount();
        }
        
        function clearAllPatients() {
            document.querySelectorAll('.patient-checkbox').forEach(function(checkbox) {
                checkbox.checked = false;
            });
            updateSelectedCount();
        }
    </script>
</body>
</html>