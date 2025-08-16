<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$popups = loadJsonData(POPUPS_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $popup = [
            'id' => generateId(),
            'title' => sanitizeInput($_POST['title']),
            'content' => sanitizeInput($_POST['content']),
            'type' => sanitizeInput($_POST['type']),
            'button_text' => sanitizeInput($_POST['button_text']),
            'button_url' => sanitizeInput($_POST['button_url']),
            'image' => '',
            'display_delay' => (int)($_POST['display_delay'] ?? 2),
            'frequency_days' => (int)($_POST['frequency_days'] ?? 1),
            'target_pages' => $_POST['target_pages'] ?? ['all'],
            'active' => isset($_POST['active']),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                $popup['image'] = $upload['url'];
            }
        }
        
        $popups[] = $popup;
        
        if (saveJsonData(POPUPS_FILE, $popups)) {
            $message = 'পপআপ সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($popups, 'id'));
        
        if ($key !== false) {
            $popups[$key]['title'] = sanitizeInput($_POST['title']);
            $popups[$key]['content'] = sanitizeInput($_POST['content']);
            $popups[$key]['type'] = sanitizeInput($_POST['type']);
            $popups[$key]['button_text'] = sanitizeInput($_POST['button_text']);
            $popups[$key]['button_url'] = sanitizeInput($_POST['button_url']);
            $popups[$key]['display_delay'] = (int)($_POST['display_delay'] ?? 2);
            $popups[$key]['frequency_days'] = (int)($_POST['frequency_days'] ?? 1);
            $popups[$key]['target_pages'] = $_POST['target_pages'] ?? ['all'];
            $popups[$key]['active'] = isset($_POST['active']);
            $popups[$key]['updated_at'] = date('Y-m-d H:i:s');
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['image'], ALLOWED_IMAGE_TYPES);
                if ($upload['success']) {
                    // Delete old image
                    if (!empty($popups[$key]['image'])) {
                        $oldFile = basename($popups[$key]['image']);
                        deleteFile($oldFile);
                    }
                    $popups[$key]['image'] = $upload['url'];
                }
            }
            
            if (saveJsonData(POPUPS_FILE, $popups)) {
                $message = 'পপআপ সফলভাবে আপডেট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($popups, 'id'));
        
        if ($key !== false) {
            // Delete image file
            if (!empty($popups[$key]['image'])) {
                $file = basename($popups[$key]['image']);
                deleteFile($file);
            }
            
            unset($popups[$key]);
            $popups = array_values($popups);
            
            if (saveJsonData(POPUPS_FILE, $popups)) {
                $message = 'পপআপ সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Sort by creation date
usort($popups, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$pageTitle = 'পপআপ ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">পপআপ ম্যানেজমেন্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPopupModal">
                        <i class="bi bi-plus-circle me-2"></i>নতুন পপআপ যোগ করুন
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Popups List -->
                <div class="row">
                    <?php if (!empty($popups)): ?>
                        <?php foreach ($popups as $popup): ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card h-100">
                                <?php if (!empty($popup['image'])): ?>
                                <img src="<?php echo htmlspecialchars($popup['image']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($popup['title']); ?>" 
                                     style="height: 150px; object-fit: cover;">
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($popup['title']); ?></h5>
                                        <div>
                                            <span class="badge bg-<?php echo ($popup['active'] ?? true) ? 'success' : 'secondary'; ?>">
                                                <?php echo ($popup['active'] ?? true) ? 'সক্রিয়' : 'নিষ্ক্রিয়'; ?>
                                            </span>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($popup['type']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text"><?php echo htmlspecialchars(substr($popup['content'], 0, 100)); ?>...</p>
                                    
                                    <div class="small text-muted mb-3">
                                        <div><i class="bi bi-clock me-1"></i>দেখানোর বিলম্ব: <?php echo $popup['display_delay']; ?> সেকেন্ড</div>
                                        <div><i class="bi bi-arrow-repeat me-1"></i>পুনরাবৃত্তি: <?php echo $popup['frequency_days']; ?> দিন পর</div>
                                        <div><i class="bi bi-file-text me-1"></i>পেজ: <?php echo in_array('all', $popup['target_pages']) ? 'সব পেজ' : count($popup['target_pages']) . ' টি নির্দিষ্ট পেজ'; ?></div>
                                    </div>
                                    
                                    <?php if (!empty($popup['button_text']) && !empty($popup['button_url'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">বাটন: </small>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($popup['button_text']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100">
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="previewPopup('<?php echo $popup['id']; ?>')">
                                            <i class="bi bi-eye"></i> প্রিভিউ
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editPopup('<?php echo $popup['id']; ?>')">
                                            <i class="bi bi-pencil"></i> এডিট
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deletePopup('<?php echo $popup['id']; ?>')">
                                            <i class="bi bi-trash"></i> ডিলিট
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-window text-muted" style="font-size: 4rem;"></i>
                            <h4 class="text-muted mt-3">কোনো পপআপ নেই</h4>
                            <p class="text-muted">প্রথম পপআপ তৈরি করুন</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Popup Modal -->
    <div class="modal fade" id="addPopupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন পপআপ যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">শিরোনাম *</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">কন্টেন্ট *</label>
                                <textarea class="form-control" name="content" rows="4" required></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পপআপ টাইপ</label>
                                <select class="form-select" name="type">
                                    <option value="info">তথ্য</option>
                                    <option value="offer">অফার</option>
                                    <option value="announcement">ঘোষণা</option>
                                    <option value="app">অ্যাপ ডাউনলোড</option>
                                    <option value="newsletter">নিউজলেটার</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পপআপ ছবি</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">বাটনের টেক্সট</label>
                                <input type="text" class="form-control" name="button_text" placeholder="যেমন: এখনই কিনুন">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">বাটনের লিংক</label>
                                <input type="url" class="form-control" name="button_url" placeholder="https://...">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">দেখানোর বিলম্ব (সেকেন্ড)</label>
                                <input type="number" class="form-control" name="display_delay" value="2" min="0" max="60">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পুনরাবৃত্তি (দিন)</label>
                                <input type="number" class="form-control" name="frequency_days" value="1" min="1">
                                <div class="form-text">কত দিন পর আবার দেখাবে</div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">লক্ষ্য পেজসমূহ</label>
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_pages[]" value="all" id="all_pages_add" checked onchange="toggleAllPages('add')">
                                        <label class="form-check-label fw-bold" for="all_pages_add">
                                            সকল পেজ
                                        </label>
                                    </div>
                                    <hr>
                                    <div class="form-check">
                                        <input class="form-check-input page-checkbox-add" type="checkbox" name="target_pages[]" value="home" id="home_page_add">
                                        <label class="form-check-label" for="home_page_add">হোম পেজ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input page-checkbox-add" type="checkbox" name="target_pages[]" value="services" id="services_page_add">
                                        <label class="form-check-label" for="services_page_add">সেবাসমূহ পেজ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input page-checkbox-add" type="checkbox" name="target_pages[]" value="appointment" id="appointment_page_add">
                                        <label class="form-check-label" for="appointment_page_add">অ্যাপয়েন্টমেন্ট পেজ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input page-checkbox-add" type="checkbox" name="target_pages[]" value="contact" id="contact_page_add">
                                        <label class="form-check-label" for="contact_page_add">যোগাযোগ পেজ</label>
                                    </div>
                                </div>
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
                        <button type="submit" class="btn btn-primary">পপআপ যোগ করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Popup Modal -->
    <div class="modal fade" id="editPopupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_popup_id">
                    <div class="modal-header">
                        <h5 class="modal-title">পপআপ এডিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">শিরোনাম *</label>
                                <input type="text" class="form-control" name="title" id="edit_title" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">কন্টেন্ট *</label>
                                <textarea class="form-control" name="content" id="edit_content" rows="4" required></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পপআপ টাইপ</label>
                                <select class="form-select" name="type" id="edit_type">
                                    <option value="info">তথ্য</option>
                                    <option value="offer">অফার</option>
                                    <option value="announcement">ঘোষণা</option>
                                    <option value="app">অ্যাপ ডাউনলোড</option>
                                    <option value="newsletter">নিউজলেটার</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">নতুন ছবি (ঐচ্ছিক)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                            
                            <div class="col-12" id="current_popup_image"></div>
                            
                            <div class="col-md-6">
                                <label class="form-label">বাটনের টেক্সট</label>
                                <input type="text" class="form-control" name="button_text" id="edit_button_text">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">বাটনের লিংক</label>
                                <input type="url" class="form-control" name="button_url" id="edit_button_url">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">দেখানোর বিলম্ব (সেকেন্ড)</label>
                                <input type="number" class="form-control" name="display_delay" id="edit_display_delay" min="0" max="60">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">পুনরাবৃত্তি (দিন)</label>
                                <input type="number" class="form-control" name="frequency_days" id="edit_frequency_days" min="1">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">লক্ষ্য পেজসমূহ</label>
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_pages[]" value="all" id="all_pages_edit" onchange="toggleAllPages('edit')">
                                        <label class="form-check-label fw-bold" for="all_pages_edit">
                                            সকল পেজ
                                        </label>
                                    </div>
                                    <hr>
                                    <div class="form-check">
                                        <input class="form-check-input page-checkbox-edit" type="checkbox" name="target_pages[]" value="home" id="home_page_edit">
                                        <label class="form-check-label" for="home_page_edit">হোম পেজ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input page-checkbox-edit" type="checkbox" name="target_pages[]" value="services" id="services_page_edit">
                                        <label class="form-check-label" for="services_page_edit">সেবাসমূহ পেজ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input page-checkbox-edit" type="checkbox" name="target_pages[]" value="appointment" id="appointment_page_edit">
                                        <label class="form-check-label" for="appointment_page_edit">অ্যাপয়েন্টমেন্ট পেজ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input page-checkbox-edit" type="checkbox" name="target_pages[]" value="contact" id="contact_page_edit">
                                        <label class="form-check-label" for="contact_page_edit">যোগাযোগ পেজ</label>
                                    </div>
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

    <!-- Preview Popup Modal -->
    <div class="modal fade" id="previewPopupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">পপআপ প্রিভিউ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="popup_preview_content">
                    <!-- Popup preview will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePopupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_popup_id">
                    <div class="modal-header">
                        <h5 class="modal-title">পপআপ ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই পপআপ ডিলিট করতে চান?</p>
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
    const popups = <?php echo json_encode($popups); ?>;
    
    function toggleAllPages(mode) {
        const allPagesCheckbox = document.getElementById(`all_pages_${mode}`);
        const pageCheckboxes = document.querySelectorAll(`.page-checkbox-${mode}`);
        
        pageCheckboxes.forEach(checkbox => {
            checkbox.checked = allPagesCheckbox.checked;
        });
    }
    
    function previewPopup(id) {
        const popup = popups.find(p => p.id === id);
        if (popup) {
            const preview = document.getElementById('popup_preview_content');
            preview.innerHTML = `
                <div class="text-center">
                    ${popup.image ? `<img src="${popup.image}" alt="${popup.title}" class="img-fluid mb-3" style="max-height: 200px;">` : ''}
                    <h3 class="text-primary">${popup.title}</h3>
                    <p>${popup.content}</p>
                    ${popup.button_text && popup.button_url ? `<a href="${popup.button_url}" class="btn btn-primary" target="_blank">${popup.button_text}</a>` : ''}
                </div>
                <hr>
                <div class="small text-muted">
                    <div><strong>টাইপ:</strong> ${popup.type}</div>
                    <div><strong>বিলম্ব:</strong> ${popup.display_delay} সেকেন্ড</div>
                    <div><strong>পুনরাবৃত্তি:</strong> ${popup.frequency_days} দিন পর</div>
                    <div><strong>পেজসমূহ:</strong> ${popup.target_pages.includes('all') ? 'সব পেজ' : popup.target_pages.join(', ')}</div>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('previewPopupModal')).show();
        }
    }
    
    function editPopup(id) {
        const popup = popups.find(p => p.id === id);
        if (popup) {
            document.getElementById('edit_popup_id').value = popup.id;
            document.getElementById('edit_title').value = popup.title;
            document.getElementById('edit_content').value = popup.content;
            document.getElementById('edit_type').value = popup.type;
            document.getElementById('edit_button_text').value = popup.button_text || '';
            document.getElementById('edit_button_url').value = popup.button_url || '';
            document.getElementById('edit_display_delay').value = popup.display_delay || 2;
            document.getElementById('edit_frequency_days').value = popup.frequency_days || 1;
            document.getElementById('edit_active').checked = popup.active ?? true;
            
            // Clear all page checkboxes
            document.querySelectorAll('.page-checkbox-edit').forEach(cb => cb.checked = false);
            document.getElementById('all_pages_edit').checked = false;
            
            // Set target pages
            popup.target_pages.forEach(page => {
                if (page === 'all') {
                    document.getElementById('all_pages_edit').checked = true;
                    toggleAllPages('edit');
                } else {
                    const checkbox = document.getElementById(`${page}_page_edit`);
                    if (checkbox) checkbox.checked = true;
                }
            });
            
            // Show current image
            const imagePreview = document.getElementById('current_popup_image');
            if (popup.image) {
                imagePreview.innerHTML = `
                    <label class="form-label">বর্তমান ছবি</label>
                    <div><img src="${popup.image}" alt="${popup.title}" class="img-thumbnail" style="max-width: 200px;"></div>
                `;
            } else {
                imagePreview.innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('editPopupModal')).show();
        }
    }
    
    function deletePopup(id) {
        document.getElementById('delete_popup_id').value = id;
        new bootstrap.Modal(document.getElementById('deletePopupModal')).show();
    }
    </script>
</body>
</html>
