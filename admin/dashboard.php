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

// Basic statistics
$totalAppointments = count($appointments);
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
    $dailyFinance[] = [
        'date' => date('d M', strtotime($date)),
        'income' => $dayIncome,
        'expense' => rand(500, 2000) // Mock expense data
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
$satisfactionRate = $ratedReviews > 0 ? ($totalRating / $ratedReviews) * 20 : 0; // Convert to percentage

// 7. Average waiting time (mock calculation)
$avgWaitingTime = 25; // minutes (mock data)

// 8. Appointment conversion rate
$totalInquiries = count($appointments) + rand(10, 50); // Mock total inquiries
$conversionRate = count($appointments) / $totalInquiries * 100;

// 9. Patient retention rate (returning patients)
$returningPatients = 0;
$patientAppointments = [];
foreach ($appointments as $appointment) {
    $phone = $appointment['phone'];
    $patientAppointments[$phone] = ($patientAppointments[$phone] ?? 0) + 1;
}
foreach ($patientAppointments as $count) {
    if ($count > 1) $returningPatients++;
}
$retentionRate = count($patientAppointments) > 0 ? ($returningPatients / count($patientAppointments)) * 100 : 0;

// 10. Review score trend (last 6 months)
$reviewTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));
    $monthReviews = array_filter($reviews, fn($r) => strpos($r['created_at'] ?? '', $month) === 0);
    $monthRating = 0;
    $monthCount = 0;
    foreach ($monthReviews as $review) {
        if (isset($review['rating'])) {
            $monthRating += $review['rating'];
            $monthCount++;
        }
    }
    $avgRating = $monthCount > 0 ? $monthRating / $monthCount : 0;
    $reviewTrend[] = ['month' => $monthName, 'rating' => $avgRating];
}

// 11. Location-wise patient data
$locations = [];
foreach ($patients as $patient) {
    $address = $patient['address'] ?? 'অজানা';
    // Extract area from address (simplified)
    $area = explode(',', $address)[0];
    $locations[$area] = ($locations[$area] ?? 0) + 1;
}
arsort($locations);
$topLocations = array_slice($locations, 0, 5, true);

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
        
        /* Fixed Chart Container Heights */
        .chart-container {
            position: relative;
            width: 100%;
        }
        
        .chart-container-small {
            height: 280px !important;
        }
        
        .chart-container-medium {
            height: 320px !important;
        }
        
        .chart-container-large {
            height: 250px !important;
        }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
        }
        
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
        
        .progress-modern {
            height: 10px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .progress-bar-modern {
            height: 100%;
            border-radius: 10px;
            transition: width 0.6s ease;
        }
        
        .location-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .location-item:last-child {
            border-bottom: none;
        }
        
        .location-name {
            font-weight: 500;
            color: #495057;
        }
        
        .location-count {
            font-weight: 600;
            color: #007bff;
        }
        
        /* Performance Optimizations */
        .chart-card {
            will-change: transform;
        }
        
        canvas {
            max-width: 100%;
            height: auto !important;
        }
        
        @media (max-width: 768px) {
            .chart-container-small,
            .chart-container-medium,
            .chart-container-large {
                height: 240px !important;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-speedometer2 me-2"></i>
                        ড্যাশবোর্ড
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar3 me-1"></i> আজ
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar-week me-1"></i> এই সপ্তাহ
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-calendar-month me-1"></i> এই মাস
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Statistics Cards -->
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

                <!-- Performance Metrics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value text-success"><?php echo number_format($satisfactionRate, 1); ?>%</div>
                            <div class="metric-label">রোগী সন্তুষ্টির হার</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value text-warning"><?php echo $avgWaitingTime; ?> মিনিট</div>
                            <div class="metric-label">গড় অপেক্ষার সময়</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value text-info"><?php echo number_format($conversionRate, 1); ?>%</div>
                            <div class="metric-label">কনভার্শন রেট</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value text-primary"><?php echo number_format($retentionRate, 1); ?>%</div>
                            <div class="metric-label">রোগী রিটেনশন রেট</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="bi bi-pie-chart text-primary"></i>
                                সেবাভিত্তিক চাহিদা
                            </h5>
                            <div class="chart-container chart-container-small">
                                <canvas id="serviceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="bi bi-graph-up text-success"></i>
                                মাসিক আয়ের ট্রেন্ড
                            </h5>
                            <div class="chart-container chart-container-small">
                                <canvas id="incomeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="bi bi-bar-chart text-warning"></i>
                                দৈনিক আয়-ব্যয় (শেষ ৩০ দিন)
                            </h5>
                            <div class="chart-container chart-container-large">
                                <canvas id="dailyFinanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="bi bi-people text-info"></i>
                                রোগীর বয়স গ্রুপ
                            </h5>
                            <div class="chart-container chart-container-large">
                                <canvas id="ageChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 3 -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="bi bi-clock text-primary"></i>
                                জনপ্রিয় সময়স্লট
                            </h5>
                            <div class="chart-container chart-container-medium">
                                <canvas id="timeSlotChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="bi bi-star text-warning"></i>
                                রিভিউ স্কোর ট্রেন্ড
                            </h5>
                            <div class="chart-container chart-container-medium">
                                <canvas id="reviewTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Analytics -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="bi bi-geo-alt text-danger"></i>
                                এলাকাভিত্তিক রোগী বিতরণ
                            </h5>
                            <?php foreach ($topLocations as $location => $count): ?>
                            <div class="location-item">
                                <span class="location-name"><?php echo htmlspecialchars($location); ?></span>
                                <span class="location-count"><?php echo $count; ?> জন</span>
                            </div>
                            <div class="progress-modern mb-2">
                                <div class="progress-bar-modern bg-primary" style="width: <?php echo ($count / max($topLocations)) * 100; ?>%"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="bi bi-calendar-event text-success"></i>
                                সাম্প্রতিক অ্যাপয়েন্টমেন্ট
                            </h5>
                            <?php 
                            $recentAppointments = array_slice(array_reverse($appointments), 0, 5);
                            if (!empty($recentAppointments)): 
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>নাম</th>
                                            <th>সেবা</th>
                                            <th>স্ট্যাটাস</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentAppointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($appointment['name'], 0, 15)); ?></td>
                                            <td><?php echo htmlspecialchars(substr($appointment['service'], 0, 20)); ?></td>
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
                            <p class="text-muted text-center">কোনো অ্যাপয়েন্টমেন্ট নেই</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart configurations - Optimized for performance
        Chart.defaults.font.family = 'Segoe UI';
        Chart.defaults.font.size = 12;
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Disable animations for better performance
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 0 // Disable animations for faster rendering
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        };

        // 1. Service Pie Chart - Fixed Height
        const serviceData = <?php echo json_encode($serviceStats); ?>;
        const serviceLabels = Object.keys(serviceData);
        const serviceValues = Object.values(serviceData);
        
        new Chart(document.getElementById('serviceChart'), {
            type: 'doughnut',
            data: {
                labels: serviceLabels,
                datasets: [{
                    data: serviceValues,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                        '#9966FF', '#FF9F40', '#FF6384', '#36A2EB'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { 
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // 2. Monthly Income Chart
        const incomeData = <?php echo json_encode($monthlyIncome); ?>;
        
        new Chart(document.getElementById('incomeChart'), {
            type: 'line',
            data: {
                labels: incomeData.map(d => d.month),
                datasets: [{
                    label: 'মাসিক আয়',
                    data: incomeData.map(d => d.amount),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // 3. Daily Finance Chart
        const dailyData = <?php echo json_encode($dailyFinance); ?>;
        
        new Chart(document.getElementById('dailyFinanceChart'), {
            type: 'bar',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [{
                    label: 'আয়',
                    data: dailyData.map(d => d.income),
                    backgroundColor: '#28a745',
                    borderRadius: 4,
                    maxBarThickness: 30
                }, {
                    label: 'ব্যয়',
                    data: dailyData.map(d => d.expense),
                    backgroundColor: '#dc3545',
                    borderRadius: 4,
                    maxBarThickness: 30
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // 4. Age Group Chart
        const ageData = <?php echo json_encode($ageGroups); ?>;
        
        new Chart(document.getElementById('ageChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(ageData),
                datasets: [{
                    data: Object.values(ageData),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderWidth: 0
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { 
                            padding: 10,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // 5. Time Slot Chart
        const timeData = <?php echo json_encode($timeSlots); ?>;
        
        new Chart(document.getElementById('timeSlotChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(timeData),
                datasets: [{
                    label: 'অ্যাপয়েন্টমেন্ট সংখ্যা',
                    data: Object.values(timeData),
                    backgroundColor: '#007bff',
                    borderRadius: 4,
                    maxBarThickness: 40
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // 6. Review Trend Chart
        const reviewData = <?php echo json_encode($reviewTrend); ?>;
        
        new Chart(document.getElementById('reviewTrendChart'), {
            type: 'line',
            data: {
                labels: reviewData.map(d => d.month),
                datasets: [{
                    label: 'গড় রেটিং',
                    data: reviewData.map(d => d.rating),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Performance optimization: Lazy load charts on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '50px'
        };

        const chartObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                }
            });
        }, observerOptions);

        // Observe all chart containers
        document.querySelectorAll('.chart-container').forEach(container => {
            chartObserver.observe(container);
        });
    </script>
</body>
</html>
