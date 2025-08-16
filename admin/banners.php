<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$banners = loadJsonData(BANNERS_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $banner = [
            'id' => generateId(),
            'title' => sanitizeInput($_POST['title']),
            'subtitle' => sanitizeInput($_POST['subtitle']),
            'image' => '',
            'active' => isset($_POST['active']),
            'order' => (int)($_POST['order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                $banner['image'] = $upload['url'];
            }
        }
        
        $banners[] = $banner;
        
        if (saveJsonData(BANNERS_FILE, $banners)) {
            $message = 'ব্যানার সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($banners, 'id'));
        
        if ($key !== false) {
            $banners[$key]['title'] = sanitizeInput($_POST['title']);
            $banners[$key]['subtitle'] = sanitizeInput($_POST['subtitle']);
            $banners[$key]['active'] = isset($_POST['active']);
            $banners[$key]['order'] = (int)($_POST['order'] ?? 0);
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['image'], ALLOWED_IMAGE_TYPES);
                if ($upload['success']) {
                    // Delete old image
                    if (!empty($banners[$key]['image'])) {
                        $oldFile = basename($banners[$key]['image']);
                        deleteFile($oldFile);
                    }
                    $banners[$key]['image'] = $upload['url'];
                }
            }
            
            if (saveJsonData(BANNERS_FILE, $banners)) {
                $message = 'ব্যানার সফলভাবে আপডেট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($banners, 'id'));
        
        if ($key !== false) {
            // Delete image file
            if (!empty($banners[$key]['image'])) {
                $file = basename($banners[$key]['image']);
                deleteFile($file);
            }
            
            unset($banners[$key]);
            $banners = array_values($banners);
            
            if (saveJsonData(BANNERS_FILE, $banners)) {
                $message = 'ব্যানার সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Sort by order
usort($banners, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

$pageTitle = 'ব্যানার ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">ব্যানার ম্যানেজমেন্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBannerModal">
                        <i class="bi bi-plus-circle me-2"></i>নতুন ব্যানার যোগ করুন
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Banners List -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($banners)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ছবি</th>
                                        <th>শিরোনাম</th>
                                        <th>উপশিরোনাম</th>
                                        <th>অর্ডার</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($banners as $banner): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($banner['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($banner['image']); ?>" 
                                                     alt="Banner" class="img-thumbnail" style="max-width: 80px;">
                                            <?php else: ?>
                                                <div class="bg-light text-center p-2" style="width: 80px; height: 50px;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($banner['title']); ?></td>
                                        <td><?php echo htmlspecialchars($banner['subtitle']); ?></td>
                                        <td><?php echo $banner['order'] ?? 0; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($banner['active'] ?? true) ? 'success' : 'secondary'; ?>">
                                                <?php echo ($banner['active'] ?? true) ? 'সক্রিয়' : 'নিষ্ক্রিয়'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editBanner('<?php echo $banner['id']; ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteBanner('<?php echo $banner['id']; ?>')">
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
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">কোনো ব্যানার নেই</h5>
                            <p class="text-muted">প্রথম ব্যানার যোগ করুন</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Banner Modal -->
    <div class="modal fade" id="addBannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন ব্যানার যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">শিরোনাম</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">উপশিরোনাম</label>
                                <textarea class="form-control" name="subtitle" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ব্যানার ছবি</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <div class="form-text">সুপারিশকৃত সাইজ: 1200x400 pixels</div>
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

    <!-- Edit Banner Modal -->
    <div class="modal fade" id="editBannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_banner_id">
                    <div class="modal-header">
                        <h5 class="modal-title">ব্যানার এডিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">শিরোনাম</label>
                                <input type="text" class="form-control" name="title" id="edit_title" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">উপশিরোনাম</label>
                                <textarea class="form-control" name="subtitle" id="edit_subtitle" rows="2"></textarea>
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
    <div class="modal fade" id="deleteBannerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_banner_id">
                    <div class="modal-header">
                        <h5 class="modal-title">ব্যানার ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই ব্যানার ডিলিট করতে চান?</p>
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
    const banners = <?php echo json_encode($banners); ?>;
    
    function editBanner(id) {
        const banner = banners.find(b => b.id === id);
        if (banner) {
            document.getElementById('edit_banner_id').value = banner.id;
            document.getElementById('edit_title').value = banner.title;
            document.getElementById('edit_subtitle').value = banner.subtitle;
            document.getElementById('edit_order').value = banner.order || 0;
            document.getElementById('edit_active').checked = banner.active ?? true;
            
            const preview = document.getElementById('current_image_preview');
            if (banner.image) {
                preview.innerHTML = `<img src="${banner.image}" alt="Current" class="img-thumbnail" style="max-width: 200px;">`;
            } else {
                preview.innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('editBannerModal')).show();
        }
    }
    
    function deleteBanner(id) {
        document.getElementById('delete_banner_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteBannerModal')).show();
    }
    </script>
</body>
</html>
