<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$services = loadJsonData(SERVICES_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $service = [
            'id' => generateId(),
            'name' => sanitizeInput($_POST['name']),
            'description' => sanitizeInput($_POST['description']),
            'price' => (float)$_POST['price'],
            'duration' => sanitizeInput($_POST['duration']),
            'features' => array_filter(array_map('trim', explode("\n", $_POST['features'] ?? ''))),
            'image' => '',
            'active' => isset($_POST['active']),
            'order' => (int)($_POST['order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                $service['image'] = $upload['url'];
            }
        }
        
        $services[] = $service;
        
        if (saveJsonData(SERVICES_FILE, $services)) {
            $message = 'সেবা সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($services, 'id'));
        
        if ($key !== false) {
            $services[$key]['name'] = sanitizeInput($_POST['name']);
            $services[$key]['description'] = sanitizeInput($_POST['description']);
            $services[$key]['price'] = (float)$_POST['price'];
            $services[$key]['duration'] = sanitizeInput($_POST['duration']);
            $services[$key]['features'] = array_filter(array_map('trim', explode("\n", $_POST['features'] ?? '')));
            $services[$key]['active'] = isset($_POST['active']);
            $services[$key]['order'] = (int)($_POST['order'] ?? 0);
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['image'], ALLOWED_IMAGE_TYPES);
                if ($upload['success']) {
                    // Delete old image
                    if (!empty($services[$key]['image'])) {
                        $oldFile = basename($services[$key]['image']);
                        deleteFile($oldFile);
                    }
                    $services[$key]['image'] = $upload['url'];
                }
            }
            
            if (saveJsonData(SERVICES_FILE, $services)) {
                $message = 'সেবা সফলভাবে আপডেট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($services, 'id'));
        
        if ($key !== false) {
            // Delete image file
            if (!empty($services[$key]['image'])) {
                $file = basename($services[$key]['image']);
                deleteFile($file);
            }
            
            unset($services[$key]);
            $services = array_values($services);
            
            if (saveJsonData(SERVICES_FILE, $services)) {
                $message = 'সেবা সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Sort by order
usort($services, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

$pageTitle = 'সেবা ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">সেবা ম্যানেজমেন্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="bi bi-plus-circle me-2"></i>নতুন সেবা যোগ করুন
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Services List -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($services)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ছবি</th>
                                        <th>সেবার নাম</th>
                                        <th>মূল্য</th>
                                        <th>সময়কাল</th>
                                        <th>অর্ডার</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($service['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($service['image']); ?>" 
                                                     alt="Service" class="img-thumbnail" style="max-width: 80px;">
                                            <?php else: ?>
                                                <div class="bg-light text-center p-2" style="width: 80px; height: 50px;">
                                                    <i class="bi bi-heart-pulse text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($service['name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . '...'; ?></small>
                                        </td>
                                        <td>৳<?php echo number_format($service['price']); ?></td>
                                        <td><?php echo htmlspecialchars($service['duration']); ?></td>
                                        <td><?php echo $service['order'] ?? 0; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($service['active'] ?? true) ? 'success' : 'secondary'; ?>">
                                                <?php echo ($service['active'] ?? true) ? 'সক্রিয়' : 'নিষ্ক্রিয়'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editService('<?php echo $service['id']; ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteService('<?php echo $service['id']; ?>')">
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
                            <i class="bi bi-heart-pulse text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">কোনো সেবা নেই</h5>
                            <p class="text-muted">প্রথম সেবা যোগ করুন</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন সেবা যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">সেবার নাম</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">বিবরণ</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">মূল্য (৳)</label>
                                <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">সময়কাল</label>
                                <input type="text" class="form-control" name="duration" placeholder="যেমন: ৩০ মিনিট">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">বৈশিষ্ট্য (প্রতি লাইনে একটি)</label>
                                <textarea class="form-control" name="features" rows="4" placeholder="আধুনিক যন্ত্রপাতি&#10;অভিজ্ঞ ডাক্তার&#10;নিরাপদ চিকিৎসা"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">সেবার ছবি</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">অর্ডার</label>
                                <input type="number" class="form-control" name="order" value="0" min="0">
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="active" id="active_add" checked>
                                    <label class="form-check-label" for="active_add">সক্রিয়</label>
                                </div>
                            </div>
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

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_service_id">
                    <div class="modal-header">
                        <h5 class="modal-title">সেবা এডিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">সেবার নাম</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">বিবরণ</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">মূল্য (৳)</label>
                                <input type="number" class="form-control" name="price" id="edit_price" min="0" step="0.01" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">সময়কাল</label>
                                <input type="text" class="form-control" name="duration" id="edit_duration">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">বৈশিষ্ট্য (প্রতি লাইনে একটি)</label>
                                <textarea class="form-control" name="features" id="edit_features" rows="4"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">নতুন ছবি (ঐচ্ছিক)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">অর্ডার</label>
                                <input type="number" class="form-control" name="order" id="edit_order" value="0" min="0">
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="active" id="edit_active">
                                    <label class="form-check-label" for="edit_active">সক্রিয়</label>
                                </div>
                            </div>
                            
                            <div class="col-12" id="current_image_preview"></div>
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
    <div class="modal fade" id="deleteServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_service_id">
                    <div class="modal-header">
                        <h5 class="modal-title">সেবা ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই সেবা ডিলিট করতে চান?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            এই সেবা সম্পর্কিত সকল অ্যাপয়েন্টমেন্ট প্রভাবিত হতে পারে।
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
    const services = <?php echo json_encode($services); ?>;
    
    function editService(id) {
        const service = services.find(s => s.id === id);
        if (service) {
            document.getElementById('edit_service_id').value = service.id;
            document.getElementById('edit_name').value = service.name;
            document.getElementById('edit_description').value = service.description;
            document.getElementById('edit_price').value = service.price;
            document.getElementById('edit_duration').value = service.duration;
            document.getElementById('edit_features').value = (service.features || []).join('\n');
            document.getElementById('edit_order').value = service.order || 0;
            document.getElementById('edit_active').checked = service.active ?? true;
            
            const preview = document.getElementById('current_image_preview');
            if (service.image) {
                preview.innerHTML = `<img src="${service.image}" alt="Current" class="img-thumbnail" style="max-width: 200px;">`;
            } else {
                preview.innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('editServiceModal')).show();
        }
    }
    
    function deleteService(id) {
        document.getElementById('delete_service_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteServiceModal')).show();
    }
    </script>
</body>
</html>
