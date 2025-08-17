<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$expenses = loadJsonData(EXPENSES_FILE);
$message = '';

// Handle Export Requests
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $expenses = loadJsonData(EXPENSES_FILE);
    
    if ($exportType === 'excel') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="expenses_' . date('Y-m-d') . '.csv"');
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['শিরোনাম', 'বিবরণ', 'পরিমাণ (৳)', 'ক্যাটেগরি', 'তারিখ']);
        
        // Data
        foreach ($expenses as $expense) {
            fputcsv($output, [
                $expense['title'] ?? '',
                $expense['description'] ?? '',
                $expense['amount'] ?? 0,
                $expense['category'] ?? '',
                $expense['date'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    if ($exportType === 'pdf') {
        // PDF Export
        $totalExpense = array_sum(array_column($expenses, 'amount'));
        
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>খরচের রিপোর্ট</title>
            <style>
                body { font-family: 'SutonnyMJ', Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .company-name { font-size: 24px; font-weight: bold; color: #2c3e50; }
                .report-title { font-size: 18px; color: #34495e; margin-top: 10px; }
                .summary { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background-color: #3498db; color: white; }
                tr:nth-child(even) { background-color: #f2f2f2; }
                .total-row { background-color: #e8f4fd !important; font-weight: bold; }
                .print-btn { margin: 20px 0; text-align: center; }
                @media print { .print-btn { display: none; } }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-name">ইনফিনিটি ডেন্টাল কেয়ার</div>
                <div class="report-title">খরচের রিপোর্ট</div>
                <div>তারিখ: <?php echo date('d-m-Y'); ?></div>
            </div>
            
            <div class="summary">
                <h4>সারসংক্ষেপ</h4>
                <p><strong>মোট খরচ:</strong> ৳<?php echo number_format($totalExpense, 2); ?></p>
                <p><strong>মোট এন্ট্রি:</strong> <?php echo count($expenses); ?></p>
                <p><strong>গড় খরচ:</strong> ৳<?php echo count($expenses) > 0 ? number_format($totalExpense / count($expenses), 2) : '0.00'; ?></p>
            </div>
            
            <div class="print-btn">
                <button onclick="window.print()" class="btn btn-primary">প্রিন্ট করুন</button>
                <button onclick="window.close()" class="btn btn-secondary">বন্ধ করুন</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ক্রম</th>
                        <th>শিরোনাম</th>
                        <th>বিবরণ</th>
                        <th>পরিমাণ (৳)</th>
                        <th>ক্যাটেগরি</th>
                        <th>তারিখ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $serial = 1;
                    foreach ($expenses as $expense): 
                    ?>
                    <tr>
                        <td><?php echo $serial++; ?></td>
                        <td><?php echo htmlspecialchars($expense['title'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($expense['description'] ?? ''); ?></td>
                        <td>৳<?php echo number_format($expense['amount'] ?? 0, 2); ?></td>
                        <td><?php echo htmlspecialchars($expense['category'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($expense['date'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3"><strong>মোট</strong></td>
                        <td><strong>৳<?php echo number_format($totalExpense, 2); ?></strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
            
            <script>
                // Auto print when page loads
                setTimeout(() => {
                    window.print();
                }, 1000);
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_expense'])) {
        $newExpense = [
            'id' => uniqid(),
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'amount' => (float)$_POST['amount'],
            'category' => $_POST['category'],
            'date' => $_POST['date'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $expenses[] = $newExpense;
        saveJsonData(EXPENSES_FILE, $expenses);
        $message = 'খরচ সফলভাবে যোগ করা হয়েছে।';
    }
    
    if (isset($_POST['delete_expense'])) {
        $expenseId = $_POST['expense_id'];
        $expenses = array_filter($expenses, fn($e) => $e['id'] !== $expenseId);
        $expenses = array_values($expenses);
        saveJsonData(EXPENSES_FILE, $expenses);
        $message = 'খরচ সফলভাবে মুছে ফেলা হয়েছে।';
    }
}

// Sort expenses by date (newest first)
usort($expenses, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Calculate statistics
$totalExpense = array_sum(array_column($expenses, 'amount'));
$categoryStats = [];
foreach ($expenses as $expense) {
    $cat = $expense['category'] ?? 'অন্যান্য';
    $categoryStats[$cat] = ($categoryStats[$cat] ?? 0) + $expense['amount'];
}

$pageTitle = 'খরচ ব্যবস্থাপনা - অ্যাডমিন প্যানেল';
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
        .expense-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">খরচ ব্যবস্থাপনা</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-download"></i> এক্সপোর্ট
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?export=excel"><i class="bi bi-file-earmark-excel me-2"></i>Excel (.csv)</a></li>
                                <li><a class="dropdown-item" href="?export=pdf" target="_blank"><i class="bi bi-file-earmark-pdf me-2"></i>PDF</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportChart()"><i class="bi bi-image me-2"></i>Chart (PNG)</a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                            <i class="bi bi-plus"></i> নতুন খরচ যোগ করুন
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="expense-card text-center">
                            <h5 class="text-danger">মোট খরচ</h5>
                            <h3 class="text-danger">৳<?php echo number_format($totalExpense); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="expense-card text-center">
                            <h5 class="text-info">মোট এন্ট্রি</h5>
                            <h3 class="text-info"><?php echo count($expenses); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="expense-card text-center">
                            <h5 class="text-warning">গড় খরচ</h5>
                            <h3 class="text-warning">৳<?php echo count($expenses) > 0 ? number_format($totalExpense / count($expenses)) : '0'; ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="expense-card">
                            <h5>ক্যাটেগরি অনুযায়ী খরচ</h5>
                            <div class="chart-container">
                                <canvas id="expenseChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expenses Table -->
                <div class="expense-card">
                    <h5 class="mb-3">খরচের তালিকা</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>শিরোনাম</th>
                                    <th>বিবরণ</th>
                                    <th>পরিমাণ</th>
                                    <th>ক্যাটেগরি</th>
                                    <th>তারিখ</th>
                                    <th>অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">কোনো খরচের তথ্য পাওয়া যায়নি।</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($expenses as $expense): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($expense['title'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($expense['description'] ?? ''); ?></td>
                                            <td class="text-danger fw-bold">৳<?php echo number_format($expense['amount'] ?? 0); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($expense['category'] ?? ''); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($expense['date'] ?? ''); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('আপনি কি নিশ্চিত?')">
                                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                                    <button type="submit" name="delete_expense" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">নতুন খরচ যোগ করুন</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">শিরোনাম *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">বিবরণ</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">পরিমাণ (৳) *</label>
                            <input type="number" class="form-control" name="amount" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ক্যাটেগরি *</label>
                            <select class="form-control" name="category" required>
                                <option value="">ক্যাটেগরি নির্বাচন করুন</option>
                                <option value="অফিস ভাড়া">অফিস ভাড়া</option>
                                <option value="কর্মচারী বেতন">কর্মচারী বেতন</option>
                                <option value="ওষুধ ক্রয়">ওষুধ ক্রয়</option>
                                <option value="যন্ত্রপাতি">যন্ত্রপাতি</option>
                                <option value="বিদ্যুৎ বিল">বিদ্যুৎ বিল</option>
                                <option value="ইন্টারনেট বিল">ইন্টারনেট বিল</option>
                                <option value="ট্রান্সপোর্ট">ট্রান্সপোর্ট</option>
                                <option value="মার্কেটিং">মার্কেটিং</option>
                                <option value="অন্যান্য">অন্যান্য</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">তারিখ *</label>
                            <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" name="add_expense" class="btn btn-primary">সংরক্ষণ করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Expense Chart
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($categoryStats)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($categoryStats)); ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                        '#45B7D1', '#96CEB4'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    title: {
                        display: true,
                        text: 'ক্যাটেগরি অনুযায়ী খরচ বিতরণ'
                    }
                }
            }
        });

        // Export Chart as PNG
        function exportChart() {
            const canvas = document.getElementById('expenseChart');
            const url = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.download = 'expense_chart_' + new Date().toISOString().split('T')[0] + '.png';
            a.href = url;
            a.click();
        }
    </script>
</body>
</html>