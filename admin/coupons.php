<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$coupons = loadJsonData(COUPONS_FILE);
$services = loadJsonData(SERVICES_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $coupon = [
            'id' => generateId(),
            'code' => strtoupper(sanitizeInput($_POST['code'])),
            'description' => sanitizeInput($_POST['description']),
            'discount_type' => sanitizeInput($_POST['discount_type']),
            'discount_value' => (float)$_POST['discount_value'],
            'max_discount' => (float)($_POST['max_discount'] ?? 0),
            'min_amount' => (float)($_POST['min_amount'] ?? 0),
            'usage_limit' => (int)($_POST['usage_limit'] ?? 0),
            'used_count' => 0,
            'applicable_services' => $_POST['applicable_services'] ?? [],
            'start_date' => sanitizeInput($_POST['start_date']),
            'end_date' => sanitizeInput($_POST['end_date']),
            'active' => isset($_POST['active']),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $coupons[] = $coupon;
        
        if (saveJsonData(COUPONS_FILE, $coupons)) {
            $message = 'কুপন সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($coupons, 'id'));
        
        if ($key !== false) {
            $coupons[$key]['code'] = strtoupper(sanitizeInput($_POST['code']));
            $coupons[$key]['description'] = sanitizeInput($_POST['description']);
            $coupons[$key]['discount_type'] = sanitizeInput($_POST['discount_type']);
            $coupons[$key]['discount_value'] = (float)$_POST['discount_value'];
            $coupons[$key]['max_discount'] = (float)($_POST['max_discount'] ?? 0);
            $coupons[$key]['min_amount'] = (float)($_POST['min_amount'] ?? 0);
            $coupons[$key]['usage_limit'] = (int)($_POST['usage_limit'] ?? 0);
            $coupons[$key]['applicable_services'] = $_POST['applicable_services'] ?? [];
            $coupons[$key]['start_date'] = sanitizeInput($_POST['start_date']);
            $coupons[$key]['end_date'] = sanitizeInput($_POST['end_date']);
            $coupons[$key]['active'] = isset($_POST['active']);
            $coupons[$key]['updated_at'] = date('Y-m-d H:i:s');
            
            if (saveJsonData(COUPONS_FILE, $coupons)) {
                $message = 'কুপন সফলভাবে আপডেট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($coupons, 'id'));
        
        if ($key !== false) {
            unset($coupons[$key]);
            $coupons = array_values($coupons);
            
            if (saveJsonData(COUPONS_FILE, $coupons)) {
                $message = 'কুপন সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Sort by creation date
usort($coupons, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$pageTitle = 'কুপন ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">কুপন ম্যানেজমেন্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCouponModal">
                        <i class="bi bi-plus-circle me-2"></i>নতুন কুপন যোগ করুন
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Coupons List -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($coupons)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>কুপন কোড</th>
                                        <th>বিবরণ</th>
                                        <th>ডিসকাউন্ট</th>
                                        <th>মেয়াদ</th>
                                        <th>ব্যবহার</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary"><?php echo htmlspecialchars($coupon['code']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($coupon['description']); ?></td>
                                        <td>
                                            <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                                <span class="badge bg-success"><?php echo $coupon['discount_value']; ?>%</span>
                                                <?php if ($coupon['max_discount'] > 0): ?>
                                                    <br><small class="text-muted">সর্বোচ্চ: ৳<?php echo $coupon['max_discount']; ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-info">৳<?php echo $coupon['discount_value']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo formatDateBengali($coupon['start_date']); ?>
                                            <br><small class="text-muted">থেকে <?php echo formatDateBengali($coupon['end_date']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($coupon['usage_limit'] > 0): ?>
                                                <?php echo $coupon['used_count']; ?>/<?php echo $coupon['usage_limit']; ?>
                                            <?php else: ?>
                                                <span class="text-muted">অসীম</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $now = time();
                                            $startTime = strtotime($coupon['start_date']);
                                            $endTime = strtotime($coupon['end_date']);
                                            $isExpired = $now > $endTime;
                                            $isActive = $coupon['active'] && !$isExpired;
                                            ?>
                                            <span class="badge bg-<?php echo $isActive ? 'success' : ($isExpired ? 'danger' : 'secondary'); ?>">
                                                <?php echo $isActive ? 'সক্রিয়' : ($isExpired ? 'মেয়াদ শেষ' : 'নিষ্ক্রিয়'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info me-1" 
                                                    onclick="viewCoupon('<?php echo $coupon['id']; ?>')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editCoupon('<?php echo $coupon['id']; ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCoupon('<?php echo $coupon['id']; ?>')">
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
                            <i class="bi bi-tag text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">কোনো কুপন নেই</h5>
                            <p class="text-muted">প্রথম কুপন তৈরি করুন</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Coupon Modal -->
    <div class="modal fade" id="addCouponModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন কুপন যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">কুপন কোড *</label>
                                <input type="text" class="form-control" name="code" required style="text-transform: uppercase;">
                                <div class="form-text">ইংরেজি অক্ষর ও সংখ্যা ব্যবহার করুন</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ডিসকাউন্ট টাইপ *</label>
                                <select class="form-select" name="discount_type" id="discount_type_add" required onchange="toggleDiscountFields('add')">
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="percentage">শতকরা (%)</option>
                                    <option value="fixed">নির্দিষ্ট পরিমাণ (৳)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ডিসকাউন্ট পরিমাণ *</label>
                                <input type="number" class="form-control" name="discount_value" min="0" step="0.01" required>
                            </div>
                            
                            <div class="col-md-6" id="max_discount_add" style="display: none;">
                                <label class="form-label">সর্বোচ্চ ডিসকাউন্ট (৳)</label>
                                <input type="number" class="form-control" name="max_discount" min="0" step="0.01">
                                <div class="form-text">শতকরা ডিসকাউন্টের জন্য সর্বোচ্চ সীমা</div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">বিবরণ *</label>
                                <textarea class="form-control" name="description" rows="2" required></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">শুরুর তারিখ *</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">শেষ তারিখ *</label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ন্যূনতম অর্ডার পরিমাণ (৳)</label>
                                <input type="number" class="form-control" name="min_amount" min="0" step="0.01">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ব্যবহারের সীমা</label>
                                <input type="number" class="form-control" name="usage_limit" min="0">
                                <div class="form-text">০ = অসীম</div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">প্রযোজ্য সেবা</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="all_services_add" onchange="toggleAllServices('add')">
                                        <label class="form-check-label fw-bold" for="all_services_add">
                                            সকল সেবা
                                        </label>
                                    </div>
                                    <hr>
                                    <?php foreach ($services as $service): ?>
                                    <div class="form-check">
                                        <input class="form-check-input service-checkbox-add" type="checkbox" 
                                               name="applicable_services[]" value="<?php echo $service['id']; ?>" 
                                               id="service_<?php echo $service['id']; ?>_add">
                                        <label class="form-check-label" for="service_<?php echo $service['id']; ?>_add">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-text">কোনো সেবা নির্বাচন না করলে সব সেবায় প্রযোজ্য হবে</div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="active" id="active_add" checked>
                                    <label class="form-check-label" for="active_add">সক্রিয়</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">কুপন যোগ করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Coupon Modal -->
    <div class="modal fade" id="editCouponModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_coupon_id">
                    <div class="modal-header">
                        <h5 class="modal-title">কুপন এডিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">কুপন কোড *</label>
                                <input type="text" class="form-control" name="code" id="edit_code" required style="text-transform: uppercase;">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ডিসকাউন্ট টাইপ *</label>
                                <select class="form-select" name="discount_type" id="edit_discount_type" required onchange="toggleDiscountFields('edit')">
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="percentage">শতকরা (%)</option>
                                    <option value="fixed">নির্দিষ্ট পরিমাণ (৳)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ডিসকাউন্ট পরিমাণ *</label>
                                <input type="number" class="form-control" name="discount_value" id="edit_discount_value" min="0" step="0.01" required>
                            </div>
                            
                            <div class="col-md-6" id="max_discount_edit" style="display: none;">
                                <label class="form-label">সর্বোচ্চ ডিসকাউন্ট (৳)</label>
                                <input type="number" class="form-control" name="max_discount" id="edit_max_discount" min="0" step="0.01">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">বিবরণ *</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="2" required></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">শুরুর তারিখ *</label>
                                <input type="date" class="form-control" name="start_date" id="edit_start_date" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">শেষ তারিখ *</label>
                                <input type="date" class="form-control" name="end_date" id="edit_end_date" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ন্যূনতম অর্ডার পরিমাণ (৳)</label>
                                <input type="number" class="form-control" name="min_amount" id="edit_min_amount" min="0" step="0.01">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ব্যবহারের সীমা</label>
                                <input type="number" class="form-control" name="usage_limit" id="edit_usage_limit" min="0">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">প্রযোজ্য সেবা</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="all_services_edit" onchange="toggleAllServices('edit')">
                                        <label class="form-check-label fw-bold" for="all_services_edit">
                                            সকল সেবা
                                        </label>
                                    </div>
                                    <hr>
                                    <?php foreach ($services as $service): ?>
                                    <div class="form-check">
                                        <input class="form-check-input service-checkbox-edit" type="checkbox" 
                                               name="applicable_services[]" value="<?php echo $service['id']; ?>" 
                                               id="service_<?php echo $service['id']; ?>_edit">
                                        <label class="form-check-label" for="service_<?php echo $service['id']; ?>_edit">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="active" id="edit_active">
                                    <label class="form-check-label" for="edit_active">সক্রিয়</label>
                                </div>
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

    <!-- View Coupon Modal -->
    <div class="modal fade" id="viewCouponModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">কুপনের বিবরণ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="couponDetails">
                    <!-- Coupon details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCouponModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_coupon_id">
                    <div class="modal-header">
                        <h5 class="modal-title">কুপন ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই কুপন ডিলিট করতে চান?</p>
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
    const coupons = <?php echo json_encode($coupons); ?>;
    const services = <?php echo json_encode($services); ?>;
    
    function toggleDiscountFields(mode) {
        const discountType = document.getElementById(`discount_type_${mode}`).value;
        const maxDiscountField = document.getElementById(`max_discount_${mode}`);
        
        if (discountType === 'percentage') {
            maxDiscountField.style.display = 'block';
        } else {
            maxDiscountField.style.display = 'none';
        }
    }
    
    function toggleAllServices(mode) {
        const allServicesCheckbox = document.getElementById(`all_services_${mode}`);
        const serviceCheckboxes = document.querySelectorAll(`.service-checkbox-${mode}`);
        
        serviceCheckboxes.forEach(checkbox => {
            checkbox.checked = allServicesCheckbox.checked;
        });
    }
    
    function viewCoupon(id) {
        const coupon = coupons.find(c => c.id === id);
        if (coupon) {
            const now = new Date().getTime();
            const startTime = new Date(coupon.start_date).getTime();
            const endTime = new Date(coupon.end_date).getTime();
            const isExpired = now > endTime;
            const isActive = coupon.active && !isExpired;
            
            const applicableServices = coupon.applicable_services && coupon.applicable_services.length > 0 
                ? services.filter(s => coupon.applicable_services.includes(s.id)).map(s => s.name).join(', ')
                : 'সকল সেবা';
            
            const details = document.getElementById('couponDetails');
            details.innerHTML = `
                <div class="row">
                    <div class="col-6"><strong>কুপন কোড:</strong></div>
                    <div class="col-6"><span class="badge bg-primary">${coupon.code}</span></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12"><strong>বিবরণ:</strong></div>
                    <div class="col-12">${coupon.description}</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>ডিসকাউন্ট:</strong></div>
                    <div class="col-6">
                        ${coupon.discount_type === 'percentage' 
                            ? `${coupon.discount_value}%${coupon.max_discount > 0 ? ` (সর্বোচ্চ: ৳${coupon.max_discount})` : ''}`
                            : `৳${coupon.discount_value}`
                        }
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>মেয়াদ:</strong></div>
                    <div class="col-6">${coupon.start_date} থেকে ${coupon.end_date}</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>ব্যবহার:</strong></div>
                    <div class="col-6">
                        ${coupon.usage_limit > 0 
                            ? `${coupon.used_count}/${coupon.usage_limit}` 
                            : 'অসীম'
                        }
                    </div>
                </div>
                ${coupon.min_amount > 0 ? `
                    <hr>
                    <div class="row">
                        <div class="col-6"><strong>ন্যূনতম অর্ডার:</strong></div>
                        <div class="col-6">৳${coupon.min_amount}</div>
                    </div>
                ` : ''}
                <hr>
                <div class="row">
                    <div class="col-6"><strong>প্রযোজ্য সেবা:</strong></div>
                    <div class="col-6">${applicableServices}</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>স্ট্যাটাস:</strong></div>
                    <div class="col-6">
                        <span class="badge bg-${isActive ? 'success' : (isExpired ? 'danger' : 'secondary')}">
                            ${isActive ? 'সক্রিয়' : (isExpired ? 'মেয়াদ শেষ' : 'নিষ্ক্রিয়')}
                        </span>
                    </div>
                </div>
                <hr>
                <small class="text-muted">তৈরি: ${coupon.created_at}</small>
            `;
            
            new bootstrap.Modal(document.getElementById('viewCouponModal')).show();
        }
    }
    
    function editCoupon(id) {
        const coupon = coupons.find(c => c.id === id);
        if (coupon) {
            document.getElementById('edit_coupon_id').value = coupon.id;
            document.getElementById('edit_code').value = coupon.code;
            document.getElementById('edit_description').value = coupon.description;
            document.getElementById('edit_discount_type').value = coupon.discount_type;
            document.getElementById('edit_discount_value').value = coupon.discount_value;
            document.getElementById('edit_max_discount').value = coupon.max_discount || '';
            document.getElementById('edit_min_amount').value = coupon.min_amount || '';
            document.getElementById('edit_usage_limit').value = coupon.usage_limit || '';
            document.getElementById('edit_start_date').value = coupon.start_date;
            document.getElementById('edit_end_date').value = coupon.end_date;
            document.getElementById('edit_active').checked = coupon.active ?? true;
            
            // Clear all service checkboxes
            document.querySelectorAll('.service-checkbox-edit').forEach(cb => cb.checked = false);
            
            // Set applicable services
            if (coupon.applicable_services && coupon.applicable_services.length > 0) {
                coupon.applicable_services.forEach(serviceId => {
                    const checkbox = document.getElementById(`service_${serviceId}_edit`);
                    if (checkbox) checkbox.checked = true;
                });
            } else {
                document.getElementById('all_services_edit').checked = true;
                toggleAllServices('edit');
            }
            
            toggleDiscountFields('edit');
            new bootstrap.Modal(document.getElementById('editCouponModal')).show();
        }
    }
    
    function deleteCoupon(id) {
        document.getElementById('delete_coupon_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteCouponModal')).show();
    }
    </script>
</body>
</html>
