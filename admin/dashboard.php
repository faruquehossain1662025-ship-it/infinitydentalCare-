<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$settings = getSettings();

// Get statistics
$appointments = loadJsonData(APPOINTMENTS_FILE);
$patients = loadJsonData(PATIENTS_FILE);
$reviews = loadJsonData(REVIEWS_FILE);
$income = loadJsonData(INCOME_FILE);
$visitors = getVisitorStats();


// SMS Balance check
$smsBalance = checkSMSBalance();

$totalAppointments = count($appointments);
$today = date('d-m-Y');
$todayAppointments = count(array_filter($appointments, fn($a) => ($a['date'] ?? null) === $today));
$pendingAppointments = count(array_filter($appointments, fn($a) => ($a['status'] ?? 'pending') === 'pending'));



$totalAppointments = count($appointments);
//$todayAppointments = count(array_filter($appointments, fn($a) => $a['date'] === date('d-m-Y')));
$today = date('d-m-Y');
$todayAppointments = count(array_filter($appointments, fn($a) => ($a['date'] ?? null) === $today));

$pendingAppointments = count(array_filter($appointments, fn($a) => ($a['status'] ?? 'pending') === 'pending'));

$totalPatients = count($patients);
$totalReviews = count($reviews);
$pendingReviews = count(array_filter($reviews, fn($r) => !($r['approved'] ?? false)));

// Calculate total income
$totalIncome = array_sum(array_column($income, 'amount'));
$thisMonthIncome = array_sum(array_column(
    array_filter($income, fn($i) => strpos($i['date'], date('Y-m')) === 0),
    'amount'
));

$pageTitle = 'ড্যাশবোর্ড - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">ড্যাশবোর্ড</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar3"></i> আজ
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar-week"></i> এই সপ্তাহ
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar-month"></i> এই মাস
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="stat-value"><?php echo $totalAppointments; ?></div>
                                        <div class="stat-label">মোট অ্যাপয়েন্টমেন্ট</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="stat-value"><?php echo $totalPatients; ?></div>
                                        <div class="stat-label">মোট রোগী</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-people"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SMS Balance Card -->
<div class="col-xl-3 col-md-6">
    <div class="stat-card bg-secondary">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stat-value">
                        <?php 
                        if ($smsBalance !== false) {
                            echo '৳' . number_format($smsBalance, 2);
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div class="stat-label">SMS ব্যালেন্স</div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
            </div>
        </div>
    </div>
</div>
                    
                    
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="stat-value">৳<?php echo number_format($totalIncome); ?></div>
                                        <div class="stat-label">মোট আয়</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-currency-dollar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="stat-value"><?php echo $visitors['today']; ?></div>
                                        <div class="stat-label">আজকের ভিজিটর</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?php echo $pendingAppointments; ?></div>
                                <p class="text-muted mb-0">অপেক্ষমাণ অ্যাপয়েন্টমেন্ট</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-day text-primary" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?php echo $todayAppointments; ?></h3>
                                <p class="text-muted mb-0">আজকের অ্যাপয়েন্টমেন্ট</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-chat-square-text text-info" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?php echo $pendingReviews; ?></h3>
                                <p class="text-muted mb-0">অপেক্ষমাণ রিভিউ</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-currency-dollar text-success" style="font-size: 2rem;"></i>
                                <h3 class="mt-2">৳<?php echo number_format($thisMonthIncome); ?></h3>
                                <p class="text-muted mb-0">এই মাসের আয়</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">সাম্প্রতিক অ্যাপয়েন্টমেন্ট</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $recentAppointments = array_slice($appointments, -5);
                                if (!empty($recentAppointments)): 
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>রোগীর নাম</th>
                                                <th>সেবা</th>
                                                <th>তারিখ</th>
                                                <th>স্ট্যাটাস</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentAppointments as $appointment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($appointment['name']); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['service']); ?></td>
                                                <td><?php echo formatDateBengali($appointment['preferred_date']); ?></td>
                                                <td>
                                                    <?php
                                                    $status = $appointment['status'] ?? 'pending';
                                                    $statusClass = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning');
                                                    $statusText = $status === 'approved' ? 'অনুমোদিত' : ($status === 'rejected' ? 'বাতিল' : 'অপেক্ষমাণ');
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">কোনো অ্যাপয়েন্টমেন্ট নেই</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">দ্রুত কাজ</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <a href="appointments.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-calendar-plus text-primary me-2"></i>
                                        নতুন অ্যাপয়েন্টমেন্ট যোগ করুন
                                    </a>
                                    <a href="patients.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-person-plus text-success me-2"></i>
                                        নতুন রোগী যোগ করুন
                                    </a>
                                    <a href="services.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-plus-circle text-info me-2"></i>
                                        নতুন সেবা যোগ করুন
                                    </a>
                                    <a href="income.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-currency-dollar text-warning me-2"></i>
                                        আয় রেকর্ড করুন
                                    </a>
                                    <a href="settings.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-gear text-secondary me-2"></i>
                                        সেটিংস পরিবর্তন করুন
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
