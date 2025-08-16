<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$income = loadJsonData(INCOME_FILE);
$services = loadJsonData(SERVICES_FILE);
$appointments = loadJsonData(APPOINTMENTS_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $incomeEntry = [
            'id' => generateId(),
            'date' => sanitizeInput($_POST['date']),
            'source' => sanitizeInput($_POST['source']),
            'service' => sanitizeInput($_POST['service']),
            'patient_name' => sanitizeInput($_POST['patient_name']),
            'amount' => (float)$_POST['amount'],
            'payment_method' => sanitizeInput($_POST['payment_method']),
            'appointment_id' => sanitizeInput($_POST['appointment_id'] ?? ''),
            'notes' => sanitizeInput($_POST['notes']),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $income[] = $incomeEntry;
        
        if (saveJsonData(INCOME_FILE, $income)) {
            $message = 'আয় সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($income, 'id'));
        
        if ($key !== false) {
            $income[$key]['date'] = sanitizeInput($_POST['date']);
            $income[$key]['source'] = sanitizeInput($_POST['source']);
            $income[$key]['service'] = sanitizeInput($_POST['service']);
            $income[$key]['patient_name'] = sanitizeInput($_POST['patient_name']);
            $income[$key]['amount'] = (float)$_POST['amount'];
            $income[$key]['payment_method'] = sanitizeInput($_POST['payment_method']);
            $income[$key]['appointment_id'] = sanitizeInput($_POST['appointment_id'] ?? '');
            $income[$key]['notes'] = sanitizeInput($_POST['notes']);
            $income[$key]['updated_at'] = date('Y-m-d H:i:s');
            
            if (saveJsonData(INCOME_FILE, $income)) {
                $message = 'আয় সফলভাবে আপডেট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($income, 'id'));
        
        if ($key !== false) {
            unset($income[$key]);
            $income = array_values($income);
            
            if (saveJsonData(INCOME_FILE, $income)) {
                $message = 'আয় সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Filter and calculate statistics
$filter = $_GET['filter'] ?? 'all';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

$filteredIncome = $income;

if ($filter !== 'all') {
    $filteredIncome = array_filter($filteredIncome, fn($i) => $i['source'] === $filter);
}

if ($fromDate) {
    $filteredIncome = array_filter($filteredIncome, fn($i) => $i['date'] >= $fromDate);
}

if ($toDate) {
    $filteredIncome = array_filter($filteredIncome, fn($i) => $i['date'] <= $toDate);
}

// Calculate statistics
$totalIncome = array_sum(array_column($filteredIncome, 'amount'));
$todayIncome = array_sum(array_column(
    array_filter($income, fn($i) => $i['date'] === date('Y-m-d')),
    'amount'
));
$thisMonthIncome = array_sum(array_column(
    array_filter($income, fn($i) => strpos($i['date'], date('Y-m')) === 0),
    'amount'
));

// Sort by date
usort($filteredIncome, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

$pageTitle = 'আয় ব্যবস্থাপনা - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">আয় ব্যবস্থাপনা</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
                        <i class="bi bi-plus-circle me-2"></i>নতুন আয় যোগ করুন
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Income Statistics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-day text-primary" style="font-size: 2rem;"></i>
                                <h3 class="mt-2">৳<?php echo number_format($todayIncome); ?></h3>
                                <p class="text-muted mb-0">আজকের আয়</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-month text-success" style="font-size: 2rem;"></i>
                                <h3 class="mt-2">৳<?php echo number_format($thisMonthIncome); ?></h3>
                                <p class="text-muted mb-0">এই মাসের আয়</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-graph-up text-warning" style="font-size: 2rem;"></i>
                                <h3 class="mt-2">৳<?php echo number_format($totalIncome); ?></h3>
                                <p class="text-muted mb-0">ফিল্টার অনুযায়ী মোট</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">আয়ের উৎস</label>
                                <select class="form-select" name="filter">
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>সব</option>
                                    <option value="service" <?php echo $filter === 'service' ? 'selected' : ''; ?>>সেবা</option>
                                    <option value="consultation" <?php echo $filter === 'consultation' ? 'selected' : ''; ?>>পরামর্শ</option>
                                    <option value="other" <?php echo $filter === 'other' ? 'selected' : ''; ?>>অন্যান্য</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">শুরুর তারিখ</label>
                                <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">শেষ তারিখ</label>
                                <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>">
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-funnel"></i> ফিল্টার
                                </button>
                                <a href="income.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Income List -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($filteredIncome)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>তারিখ</th>
                                        <th>রোগী/উৎস</th>
                                        <th>সেবা</th>
                                        <th>পেমেন্ট</th>
                                        <th>পরিমাণ</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filteredIncome as $entry): ?>
                                    <tr>
                                        <td><?php echo formatDateBengali($entry['date']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($entry['patient_name']); ?></strong>
                                            <br><small class="text-muted">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($entry['source']); ?></span>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($entry['service']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($entry['payment_method']); ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-success">৳<?php echo number_format($entry['amount']); ?></strong>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info me-1" 
                                                    onclick="viewIncome('<?php echo $entry['id']; ?>')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editIncome('<?php echo $entry['id']; ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteIncome('<?php echo $entry['id']; ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th colspan="4">মোট আয়:</th>
                                        <th>৳<?php echo number_format($totalIncome); ?></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-currency-dollar text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">কোনো আয় নেই</h5>
                            <p class="text-muted">প্রথম আয়ের এন্ট্রি যোগ করুন।</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Income Modal -->
    <div class="modal fade" id="addIncomeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন আয় যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">তারিখ *</label>
                                <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">আয়ের উৎস *</label>
                                <select class="form-select" name="source" required>
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="service">সেবা</option>
                                    <option value="consultation">পরামর্শ</option>
                                    <option value="other">অন্যান্য</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">রোগীর নাম *</label>
                                <input type="text" class="form-control" name="patient_name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">সেবা *</label>
                                <select class="form-select" name="service" required>
                                    <option value="">সেবা নির্বাচন করুন</option>
                                    <?php foreach ($services as $service): ?>
                                        <?php if ($service['active'] ?? true): ?>
                                        <option value="<?php echo htmlspecialchars($service['name']); ?>">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <option value="consultation">পরামর্শ</option>
                                    <option value="other">অন্যান্য</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পরিমাণ (৳) *</label>
                                <input type="number" class="form-control" name="amount" min="0" step="0.01" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পেমেন্ট পদ্ধতি *</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="cash">নগদ</option>
                                    <option value="card">কার্ড</option>
                                    <option value="mobile_banking">মোবাইল ব্যাংকিং</option>
                                    <option value="bank_transfer">ব্যাংক ট্রান্সফার</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">অ্যাপয়েন্টমেন্ট নম্বর (ঐচ্ছিক)</label>
                                <input type="text" class="form-control" name="appointment_id" placeholder="APT20240811001">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">নোট</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">আয় যোগ করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Income Modal -->
    <div class="modal fade" id="editIncomeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_income_id">
                    <div class="modal-header">
                        <h5 class="modal-title">আয় এডিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">তারিখ *</label>
                                <input type="date" class="form-control" name="date" id="edit_date" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">আয়ের উৎস *</label>
                                <select class="form-select" name="source" id="edit_source" required>
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="service">সেবা</option>
                                    <option value="consultation">পরামর্শ</option>
                                    <option value="other">অন্যান্য</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">রোগীর নাম *</label>
                                <input type="text" class="form-control" name="patient_name" id="edit_patient_name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">সেবা *</label>
                                <select class="form-select" name="service" id="edit_service" required>
                                    <option value="">সেবা নির্বাচন করুন</option>
                                    <?php foreach ($services as $service): ?>
                                        <?php if ($service['active'] ?? true): ?>
                                        <option value="<?php echo htmlspecialchars($service['name']); ?>">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <option value="consultation">পরামর্শ</option>
                                    <option value="other">অন্যান্য</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পরিমাণ (৳) *</label>
                                <input type="number" class="form-control" name="amount" id="edit_amount" min="0" step="0.01" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পেমেন্ট পদ্ধতি *</label>
                                <select class="form-select" name="payment_method" id="edit_payment_method" required>
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="cash">নগদ</option>
                                    <option value="card">কার্ড</option>
                                    <option value="mobile_banking">মোবাইল ব্যাংকিং</option>
                                    <option value="bank_transfer">ব্যাংক ট্রান্সফার</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">অ্যাপয়েন্টমেন্ট নম্বর (ঐচ্ছিক)</label>
                                <input type="text" class="form-control" name="appointment_id" id="edit_appointment_id">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">নোট</label>
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

    <!-- View Income Modal -->
    <div class="modal fade" id="viewIncomeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">আয়ের বিবরণ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="incomeDetails">
                    <!-- Income details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteIncomeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_income_id">
                    <div class="modal-header">
                        <h5 class="modal-title">আয় ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই আয়ের এন্ট্রি ডিলিট করতে চান?</p>
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
    const income = <?php echo json_encode($income); ?>;
    
    function viewIncome(id) {
        const entry = income.find(i => i.id === id);
        if (entry) {
            const details = document.getElementById('incomeDetails');
            details.innerHTML = `
                <div class="row">
                    <div class="col-6"><strong>তারিখ:</strong></div>
                    <div class="col-6">${entry.date}</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>রোগীর নাম:</strong></div>
                    <div class="col-6">${entry.patient_name}</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>আয়ের উৎস:</strong></div>
                    <div class="col-6"><span class="badge bg-secondary">${entry.source}</span></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>সেবা:</strong></div>
                    <div class="col-6">${entry.service}</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>পরিমাণ:</strong></div>
                    <div class="col-6"><strong class="text-success">৳${entry.amount.toLocaleString()}</strong></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>পেমেন্ট পদ্ধতি:</strong></div>
                    <div class="col-6"><span class="badge bg-info">${getPaymentMethodText(entry.payment_method)}</span></div>
                </div>
                ${entry.appointment_id ? `
                    <hr>
                    <div class="row">
                        <div class="col-6"><strong>অ্যাপয়েন্টমেন্ট:</strong></div>
                        <div class="col-6">${entry.appointment_id}</div>
                    </div>
                ` : ''}
                ${entry.notes ? `
                    <hr>
                    <div class="row">
                        <div class="col-12"><strong>নোট:</strong></div>
                        <div class="col-12">${entry.notes}</div>
                    </div>
                ` : ''}
                <hr>
                <small class="text-muted">তৈরি: ${entry.created_at}</small>
            `;
            
            new bootstrap.Modal(document.getElementById('viewIncomeModal')).show();
        }
    }
    
    function editIncome(id) {
        const entry = income.find(i => i.id === id);
        if (entry) {
            document.getElementById('edit_income_id').value = entry.id;
            document.getElementById('edit_date').value = entry.date;
            document.getElementById('edit_source').value = entry.source;
            document.getElementById('edit_patient_name').value = entry.patient_name;
            document.getElementById('edit_service').value = entry.service;
            document.getElementById('edit_amount').value = entry.amount;
            document.getElementById('edit_payment_method').value = entry.payment_method;
            document.getElementById('edit_appointment_id').value = entry.appointment_id || '';
            document.getElementById('edit_notes').value = entry.notes || '';
            
            new bootstrap.Modal(document.getElementById('editIncomeModal')).show();
        }
    }
    
    function deleteIncome(id) {
        document.getElementById('delete_income_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteIncomeModal')).show();
    }
    
    function getPaymentMethodText(method) {
        const methods = {
            'cash': 'নগদ',
            'card': 'কার্ড',
            'mobile_banking': 'মোবাইল ব্যাংকিং',
            'bank_transfer': 'ব্যাংক ট্রান্সফার'
        };
        return methods[method] || method;
    }
    </script>
</body>
</html>
