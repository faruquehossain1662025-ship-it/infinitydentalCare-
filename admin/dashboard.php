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
$expenses = loadJsonData(EXPENSES_FILE);
$inquiries = loadJsonData(INQUIRIES_FILE);
$visitors = getVisitorStats();

// SMS Balance check
$smsBalance = checkSMSBalance();

// Basic statistics
$totalAppointments = count($appointments);
$today = date('d-m-Y');
$todayAppointments = count(array_filter($appointments, fn($a) => ($a['date'] ?? null) === $today));
$pendingAppointments = count(array_filter($appointments, fn($a) => ($a['status'] ?? 'pending') === 'pending'));
$totalPatients = count($patients);
$totalReviews = count($reviews);
$pendingReviews = count(array_filter($reviews, fn($r) => !($r['approved'] ?? false)));
$totalInquiries = count($inquiries);

// New appointments indicator (last 24 hours)
$yesterday = date('d-m-Y', strtotime('-1 day'));
$newAppointments = count(array_filter($appointments, function($a) use ($today, $yesterday) {
    $appointmentDate = $a['created_at'] ?? $a['date'] ?? '';
    return strpos($appointmentDate, $today) !== false || strpos($appointmentDate, $yesterday) !== false;
}));

// Calculate total income and expenses
$totalIncome = array_sum(array_column($income, 'amount'));
$totalExpenses = array_sum(array_column($expenses, 'amount'));
$thisMonthIncome = array_sum(array_column(
    array_filter($income, fn($i) => strpos($i['date'], date('Y-m')) === 0),
    'amount'
));
$thisMonthExpenses = array_sum(array_column(
    array_filter($expenses, fn($e) => strpos($e['date'], date('Y-m')) === 0),
    'amount'
));

// Profit/Loss calculations
$netProfit = $totalIncome - $totalExpenses;
$thisMonthProfit = $thisMonthIncome - $thisMonthExpenses;

// Percentage calculations
$incomePercentage = $totalIncome > 0 ? (($totalIncome / ($totalIncome + $totalExpenses)) * 100) : 0;
$expensePercentage = ($totalIncome + $totalExpenses) > 0 ? (($totalExpenses / ($totalIncome + $totalExpenses)) * 100) : 0;

// Advanced Analytics Calculations
// 1. Service wise pie chart data
$serviceStats = [];
foreach ($appointments as $appointment) {
    $service = $appointment['service'] ?? 'অজানা';
    $serviceStats[$service] = ($serviceStats[$service] ?? 0) + 1;
}

// 2. Monthly income trend (last 12 months)
$monthlyIncome = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));
    $monthlyAmount = array_sum(array_column(
        array_filter($income, fn($item) => strpos($item['date'], $month) === 0),
        'amount'
    ));
    $monthlyIncome[] = ['month' => $monthName, 'amount' => $monthlyAmount];
}

// 3. Daily income vs expense (last 30 days)
$dailyFinance = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayIncome = array_sum(array_column(
        array_filter($income, fn($item) => $item['date'] === $date),
        'amount'
    ));
    $dayExpense = array_sum(array_column(
        array_filter($expenses, fn($item) => $item['date'] === $date),
        'amount'
    ));
    
    $dailyFinance[] = [
        'date' => date('d M', strtotime($date)),
        'income' => $dayIncome,
        'expense' => $dayExpense
    ];
}

// 4. Patient age groups
$ageGroups = ['18-25' => 0, '26-35' => 0, '36-45' => 0, '46-55' => 0, '55+' => 0];
foreach ($patients as $patient) {
    $age = $patient['age'] ?? 0;
    if ($age >= 18 && $age <= 25) $ageGroups['18-25']++;
    elseif ($age >= 26 && $age <= 35) $ageGroups['26-35']++;
    elseif ($age >= 36 && $age <= 45) $ageGroups['36-45']++;
    elseif ($age >= 46 && $age <= 55) $ageGroups['46-55']++;
    elseif ($age > 55) $ageGroups['55+']++;
}

// 5. Popular time slots
$timeSlots = [];
foreach ($appointments as $appointment) {
    $time = $appointment['preferred_time'] ?? '';
    $timeSlots[$time] = ($timeSlots[$time] ?? 0) + 1;
}
arsort($timeSlots);

// 6. Patient satisfaction rate (from reviews)
$totalRating = 0;
$ratedReviews = 0;
foreach ($reviews as $review) {
    if (isset($review['rating']) && $review['rating'] > 0) {
        $totalRating += $review['rating'];
        $ratedReviews++;
    }
}
$satisfactionRate = $ratedReviews > 0 ? ($totalRating / $ratedReviews) * 20 : 0;

// 7. Average waiting time calculation
$completedAppointments = array_filter($appointments, fn($a) => ($a['status'] ?? '') === 'completed');
$totalWaitingTime = 0;
$waitingTimeCount = 0;

foreach ($completedAppointments as $appointment) {
    if (isset($appointment['actual_time']) && isset($appointment['preferred_time'])) {
        $preferredTime = strtotime($appointment['preferred_time']);
        $actualTime = strtotime($appointment['actual_time']);
        $waitingMinutes = max(0, ($actualTime - $preferredTime) / 60);
        $totalWaitingTime += $waitingMinutes;
        $waitingTimeCount++;
    }
}
$avgWaitingTime = $waitingTimeCount > 0 ? round($totalWaitingTime / $waitingTimeCount) : 0;

// 8. Appointment conversion rate
$conversionRate = $totalInquiries > 0 ? ($totalAppointments / $totalInquiries) * 100 : 0;

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="css/admin.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            overflow: hidden;
            position: relative;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
            font-weight: 500;
        }
        .stat-icon {
            font-size: 2.5rem;
            color: rgba(255,255,255,0.8);
        }
        .notification-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; }
        .bg-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important; }
        .bg-warning { background: linear-gradient(135deg, #fcb045 0%, #fd1d1d 100%) !important; }
        .bg-info { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; }
        .bg-secondary { background: linear-gradient(135deg, #868f96 0%, #596164 100%) !important; }
        .bg-danger { background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%) !important; }
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border: none;
        }
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .chart-container {
            position: relative;
            width: 100%;
        }
        .chart-container-small { height: 280px !important; }
        .chart-container-medium { height: 320px !important; }
        .chart-container-large { height: 250px !important; }
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s ease;
        }
        .metric-card:hover { transform: translateY(-2px); }
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .profit-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
        }
        .profit-positive { color: #28a745; }
        .profit-negative { color: #dc3545; }
        .percentage-display {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        .percentage-item {
            text-align: center;
            flex: 1;
        }
        .percentage-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .percentage-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">ড্যাশবোর্ড</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download"></i> রিপোর্ট এক্সপোর্ট
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card bg-primary">
                            <?php if ($newAppointments > 0): ?>
                                <div class="notification-badge"><?php echo $newAppointments; ?></div>
                            <?php endif; ?>
                            <div class="card-body d-flex align-items-center">
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
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card bg-success">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-value"><?php echo $totalPatients; ?></div>
                                    <div class="stat-label">মোট পেশেন্ট</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card bg-warning">
                            <div class="card-body d-flex align-items-center">
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
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card bg-info">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-value"><?php echo $todayAppointments; ?></div>
                                    <div class="stat-label">আজকের অ্যাপয়েন্টমেন্ট</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-calendar-day"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="row mb-4">
                    <div class="col-xl-6">
                        <div class="profit-card">
                            <h5 class="mb-3">আর্থিক সারসংক্ষেপ</h5>
                            <div class="percentage-display">
                                <div class="percentage-item">
                                    <div class="percentage-value text-success"><?php echo round($incomePercentage, 1); ?>%</div>
                                    <div class="percentage-label">আয়ের হার</div>
                                </div>
                                <div class="percentage-item">
                                    <div class="percentage-value text-danger"><?php echo round($expensePercentage, 1); ?>%</div>
                                    <div class="percentage-label">খরচের হার</div>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="metric-value <?php echo $netProfit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                        ৳<?php echo number_format($netProfit); ?>
                                    </div>
                                    <div class="metric-label">সর্বমোট লাভ/ক্ষতি</div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-value <?php echo $thisMonthProfit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                        ৳<?php echo number_format($thisMonthProfit); ?>
                                    </div>
                                    <div class="metric-label">এই মাসের লাভ/ক্ষতি</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-6">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="metric-card">
                                    <div class="metric-value text-success"><?php echo round($satisfactionRate); ?>%</div>
                                    <div class="metric-label">সন্তুষ্টির হার</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="metric-card">
                                    <div class="metric-value text-warning"><?php echo $avgWaitingTime; ?> মিনিট</div>
                                    <div class="metric-label">গড় অপেক্ষার সময়</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="metric-card">
                                    <div class="metric-value text-info"><?php echo round($conversionRate); ?>%</div>
                                    <div class="metric-label">কনভার্সন রেট</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="metric-card">
                                    <div class="metric-value text-primary"><?php echo $totalInquiries; ?></div>
                                    <div class="metric-label">মোট ইনকোয়ারি</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row">
                    <!-- Income vs Expense Chart -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="bi bi-bar-chart text-primary"></i>
                                আয় বনাম খরচ (শেষ ৩০ দিন)
                            </div>
                            <div class="chart-container chart-container-medium">
                                <canvas id="incomeExpenseChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service Distribution -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="bi bi-pie-chart text-success"></i>
                                সেবা বিতরণ
                            </div>
                            <div class="chart-container chart-container-small">
                                <canvas id="serviceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Charts -->
                <div class="row">
                    <!-- Monthly Trends -->
                    <div class="col-xl-6">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="bi bi-graph-up text-info"></i>
                                মাসিক আয়ের ট্রেন্ড (১২ মাস)
                            </div>
                            <div class="chart-container chart-container-large">
                                <canvas id="monthlyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Age Distribution -->
                    <div class="col-xl-6">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="bi bi-person-circle text-warning"></i>
                                পেশেন্টদের বয়স বিতরণ
                            </div>
                            <div class="chart-container chart-container-large">
                                <canvas id="ageChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Income vs Expense Chart
        const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');
        new Chart(incomeExpenseCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($dailyFinance, 'date')); ?>,
                datasets: [{
                    label: 'আয়',
                    data: <?php echo json_encode(array_column($dailyFinance, 'income')); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'খরচ',
                    data: <?php echo json_encode(array_column($dailyFinance, 'expense')); ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Service Chart
        const serviceCtx = document.getElementById('serviceChart').getContext('2d');
        new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($serviceStats)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($serviceStats)); ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Trend Chart
        const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(monthlyTrendCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($monthlyIncome, 'month')); ?>,
                datasets: [{
                    label: 'আয়',
                    data: <?php echo json_encode(array_column($monthlyIncome, 'amount')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Age Chart
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($ageGroups)); ?>,
                datasets: [{
                    label: 'পেশেন্ট সংখ্যা',
                    data: <?php echo json_encode(array_values($ageGroups)); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
