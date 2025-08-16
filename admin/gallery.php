<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$gallery = loadJsonData(GALLERY_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $item = [
            'id' => generateId(),
            'title' => sanitizeInput($_POST['title']),
            'description' => sanitizeInput($_POST['description']),
            'type' => sanitizeInput($_POST['type']),
            'url' => '',
            'thumbnail' => '',
            'active' => isset($_POST['active']),
            'order' => (int)($_POST['order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($_POST['type'] === 'video' && !empty($_POST['youtube_url'])) {
            // YouTube video
            $item['url'] = sanitizeInput($_POST['youtube_url']);
            $item['thumbnail'] = sanitizeInput($_POST['thumbnail_url'] ?? '');
        } else {
            // Handle file upload
            $fileField = $_POST['type'] === 'image' ? 'image_file' : 'video_file';
            $allowedTypes = $_POST['type'] === 'image' ? ALLOWED_IMAGE_TYPES : ALLOWED_VIDEO_TYPES;
            
            if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES[$fileField], $allowedTypes);
                if ($upload['success']) {
                    $item['url'] = $upload['url'];
                    
                    // For images, use the same URL as thumbnail
                    if ($_POST['type'] === 'image') {
                        $item['thumbnail'] = $upload['url'];
                    }
                }
            }
        }
        
        $gallery[] = $item;
        
        if (saveJsonData(GALLERY_FILE, $gallery)) {
            $message = 'গ্যালারি আইটেম সফলভাবে যোগ করা হয়েছে!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($gallery, 'id'));
        
        if ($key !== false) {
            $gallery[$key]['title'] = sanitizeInput($_POST['title']);
            $gallery[$key]['description'] = sanitizeInput($_POST['description']);
            $gallery[$key]['active'] = isset($_POST['active']);
            $gallery[$key]['order'] = (int)($_POST['order'] ?? 0);
            
            // Handle URL update for YouTube videos
            if ($gallery[$key]['type'] === 'video' && !empty($_POST['youtube_url'])) {
                $gallery[$key]['url'] = sanitizeInput($_POST['youtube_url']);
                $gallery[$key]['thumbnail'] = sanitizeInput($_POST['thumbnail_url'] ?? '');
            }
            
            // Handle file upload
            $fileField = $gallery[$key]['type'] === 'image' ? 'image_file' : 'video_file';
            $allowedTypes = $gallery[$key]['type'] === 'image' ? ALLOWED_IMAGE_TYPES : ALLOWED_VIDEO_TYPES;
            
            if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES[$fileField], $allowedTypes);
                if ($upload['success']) {
                    // Delete old file
                    if (!empty($gallery[$key]['url']) && strpos($gallery[$key]['url'], 'youtube.com') === false) {
                        $oldFile = basename($gallery[$key]['url']);
                        deleteFile($oldFile);
                    }
                    
                    $gallery[$key]['url'] = $upload['url'];
                    
                    if ($gallery[$key]['type'] === 'image') {
                        $gallery[$key]['thumbnail'] = $upload['url'];
                    }
                }
            }
            
            if (saveJsonData(GALLERY_FILE, $gallery)) {
                $message = 'গ্যালারি আইটেম সফলভাবে আপডেট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($gallery, 'id'));
        
        if ($key !== false) {
            // Delete files
            if (!empty($gallery[$key]['url']) && strpos($gallery[$key]['url'], 'youtube.com') === false) {
                $file = basename($gallery[$key]['url']);
                deleteFile($file);
            }
            
            unset($gallery[$key]);
            $gallery = array_values($gallery);
            
            if (saveJsonData(GALLERY_FILE, $gallery)) {
                $message = 'গ্যালারি আইটেম সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Filter by type
$filter = $_GET['filter'] ?? 'all';
$filteredGallery = $gallery;

if ($filter !== 'all') {
    $filteredGallery = array_filter($gallery, fn($g) => $g['type'] === $filter);
}

// Sort by order
usort($filteredGallery, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

$pageTitle = 'গ্যালারি ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">গ্যালারি ম্যানেজমেন্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGalleryModal">
                        <i class="bi bi-plus-circle me-2"></i>নতুন মিডিয়া যোগ করুন
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filter Tabs -->
                <div class="card mb-4">
                    <div class="card-body">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">
                                    সব (<?php echo count($gallery); ?>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'image' ? 'active' : ''; ?>" href="?filter=image">
                                    ছবি (<?php echo count(array_filter($gallery, fn($g) => $g['type'] === 'image')); ?>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'video' ? 'active' : ''; ?>" href="?filter=video">
                                    ভিডিও (<?php echo count(array_filter($gallery, fn($g) => $g['type'] === 'video')); ?>)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Gallery Items -->
                <div class="row">
                    <?php if (!empty($filteredGallery)): ?>
                        <?php foreach ($filteredGallery as $item): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="position-relative">
                                    <?php if ($item['type'] === 'image'): ?>
                                        <img src="<?php echo htmlspecialchars($item['url']); ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                             style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <?php if (strpos($item['url'], 'youtube.com') !== false || strpos($item['url'], 'youtu.be') !== false): ?>
                                            <div class="video-thumbnail position-relative" style="height: 200px; background-color: #000;">
                                                <?php if (!empty($item['thumbnail'])): ?>
                                                    <img src="<?php echo htmlspecialchars($item['thumbnail']); ?>" 
                                                         class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                         style="height: 200px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div class="position-absolute top-50 start-50 translate-middle">
                                                    <i class="bi bi-play-circle-fill text-white" style="font-size: 3rem;"></i>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <video class="card-img-top" style="height: 200px; object-fit: cover;">
                                                <source src="<?php echo htmlspecialchars($item['url']); ?>" type="video/mp4">
                                            </video>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <span class="badge bg-<?php echo $item['type'] === 'image' ? 'info' : 'success'; ?>">
                                            <?php echo $item['type'] === 'image' ? 'ছবি' : 'ভিডিও'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="position-absolute bottom-0 start-0 p-2">
                                        <span class="badge bg-<?php echo ($item['active'] ?? true) ? 'success' : 'secondary'; ?>">
                                            <?php echo ($item['active'] ?? true) ? 'সক্রিয়' : 'নিষ্ক্রিয়'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <?php if (!empty($item['description'])): ?>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <?php endif; ?>
                                    <small class="text-muted">অর্ডার: <?php echo $item['order'] ?? 0; ?></small>
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100">
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="viewGalleryItem('<?php echo $item['id']; ?>')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editGalleryItem('<?php echo $item['id']; ?>')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteGalleryItem('<?php echo $item['id']; ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-images text-muted" style="font-size: 4rem;"></i>
                            <h4 class="text-muted mt-3">কোনো মিডিয়া নেই</h4>
                            <p class="text-muted">প্রথম ছবি বা ভিডিও যোগ করুন।</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Gallery Item Modal -->
    <div class="modal fade" id="addGalleryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">নতুন মিডিয়া যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">মিডিয়া টাইপ *</label>
                                <select class="form-select" name="type" id="media_type_add" required onchange="toggleMediaFields('add')">
                                    <option value="">নির্বাচন করুন</option>
                                    <option value="image">ছবি</option>
                                    <option value="video">ভিডিও</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">শিরোনাম *</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">বিবরণ</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                            
                            <!-- Image Upload -->
                            <div class="col-12" id="image_upload_add" style="display: none;">
                                <label class="form-label">ছবি আপলোড করুন *</label>
                                <input type="file" class="form-control" name="image_file" accept="image/*">
                            </div>
                            
                            <!-- Video Options -->
                            <div id="video_options_add" style="display: none;">
                                <div class="col-12">
                                    <label class="form-label">ভিডিও টাইপ</label>
                                    <select class="form-select" name="video_type" id="video_type_add" onchange="toggleVideoType('add')">
                                        <option value="">নির্বাচন করুন</option>
                                        <option value="youtube">YouTube ভিডিও</option>
                                        <option value="upload">ফাইল আপলোড</option>
                                    </select>
                                </div>
                                
                                <div class="col-12" id="youtube_fields_add" style="display: none;">
                                    <label class="form-label">YouTube URL *</label>
                                    <input type="url" class="form-control" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
                                    <div class="form-text">YouTube ভিডিওর লিংক দিন</div>
                                    
                                    <label class="form-label mt-2">থাম্বনেইল URL (ঐচ্ছিক)</label>
                                    <input type="url" class="form-control" name="thumbnail_url" placeholder="https://img.youtube.com/vi/VIDEO_ID/maxresdefault.jpg">
                                </div>
                                
                                <div class="col-12" id="video_upload_add" style="display: none;">
                                    <label class="form-label">ভিডিও ফাইল আপলোড করুন *</label>
                                    <input type="file" class="form-control" name="video_file" accept="video/*">
                                    <div class="form-text">সর্বোচ্চ ফাইল সাইজ: 5MB</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">অর্ডার</label>
                                <input type="number" class="form-control" name="order" value="0" min="0">
                            </div>
                            
                            <div class="col-md-6 d-flex align-items-end">
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

    <!-- Edit Gallery Item Modal -->
    <div class="modal fade" id="editGalleryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_gallery_id">
                    <div class="modal-header">
                        <h5 class="modal-title">মিডিয়া এডিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">শিরোনাম *</label>
                                <input type="text" class="form-control" name="title" id="edit_title" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">বিবরণ</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                            </div>
                            
                            <div class="col-12" id="current_media_preview">
                                <!-- Current media will be shown here -->
                            </div>
                            
                            <div class="col-12" id="edit_youtube_fields" style="display: none;">
                                <label class="form-label">YouTube URL</label>
                                <input type="url" class="form-control" name="youtube_url" id="edit_youtube_url">
                                
                                <label class="form-label mt-2">থাম্বনেইল URL</label>
                                <input type="url" class="form-control" name="thumbnail_url" id="edit_thumbnail_url">
                            </div>
                            
                            <div class="col-12" id="edit_file_upload">
                                <label class="form-label">নতুন ফাইল (ঐচ্ছিক)</label>
                                <input type="file" class="form-control" name="image_file" id="edit_file_input" accept="image/*,video/*">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">অর্ডার</label>
                                <input type="number" class="form-control" name="order" id="edit_order" value="0" min="0">
                            </div>
                            
                            <div class="col-md-6 d-flex align-items-end">
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

    <!-- View Gallery Item Modal -->
    <div class="modal fade" id="viewGalleryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="view_modal_title">মিডিয়া দেখুন</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="view_media_content">
                    <!-- Media content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteGalleryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_gallery_id">
                    <div class="modal-header">
                        <h5 class="modal-title">মিডিয়া ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই মিডিয়া ডিলিট করতে চান?</p>
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
    const gallery = <?php echo json_encode($gallery); ?>;
    
    function toggleMediaFields(mode) {
        const mediaType = document.getElementById(`media_type_${mode}`).value;
        const imageUpload = document.getElementById(`image_upload_${mode}`);
        const videoOptions = document.getElementById(`video_options_${mode}`);
        
        if (mediaType === 'image') {
            imageUpload.style.display = 'block';
            videoOptions.style.display = 'none';
        } else if (mediaType === 'video') {
            imageUpload.style.display = 'none';
            videoOptions.style.display = 'block';
        } else {
            imageUpload.style.display = 'none';
            videoOptions.style.display = 'none';
        }
    }
    
    function toggleVideoType(mode) {
        const videoType = document.getElementById(`video_type_${mode}`).value;
        const youtubeFields = document.getElementById(`youtube_fields_${mode}`);
        const videoUpload = document.getElementById(`video_upload_${mode}`);
        
        if (videoType === 'youtube') {
            youtubeFields.style.display = 'block';
            videoUpload.style.display = 'none';
        } else if (videoType === 'upload') {
            youtubeFields.style.display = 'none';
            videoUpload.style.display = 'block';
        } else {
            youtubeFields.style.display = 'none';
            videoUpload.style.display = 'none';
        }
    }
    
    function viewGalleryItem(id) {
        const item = gallery.find(g => g.id === id);
        if (item) {
            document.getElementById('view_modal_title').textContent = item.title;
            const content = document.getElementById('view_media_content');
            
            if (item.type === 'image') {
                content.innerHTML = `
                    <div class="text-center">
                        <img src="${item.url}" class="img-fluid" alt="${item.title}">
                    </div>
                    ${item.description ? `<p class="mt-3">${item.description}</p>` : ''}
                `;
            } else {
                if (item.url.includes('youtube.com') || item.url.includes('youtu.be')) {
                    const videoId = getYouTubeVideoId(item.url);
                    content.innerHTML = `
                        <div class="ratio ratio-16x9">
                            <iframe src="https://www.youtube.com/embed/${videoId}" allowfullscreen></iframe>
                        </div>
                        ${item.description ? `<p class="mt-3">${item.description}</p>` : ''}
                    `;
                } else {
                    content.innerHTML = `
                        <div class="text-center">
                            <video controls class="img-fluid">
                                <source src="${item.url}" type="video/mp4">
                            </video>
                        </div>
                        ${item.description ? `<p class="mt-3">${item.description}</p>` : ''}
                    `;
                }
            }
            
            new bootstrap.Modal(document.getElementById('viewGalleryModal')).show();
        }
    }
    
    function editGalleryItem(id) {
        const item = gallery.find(g => g.id === id);
        if (item) {
            document.getElementById('edit_gallery_id').value = item.id;
            document.getElementById('edit_title').value = item.title;
            document.getElementById('edit_description').value = item.description || '';
            document.getElementById('edit_order').value = item.order || 0;
            document.getElementById('edit_active').checked = item.active ?? true;
            
            const preview = document.getElementById('current_media_preview');
            const youtubeFields = document.getElementById('edit_youtube_fields');
            const fileUpload = document.getElementById('edit_file_upload');
            
            if (item.type === 'image') {
                preview.innerHTML = `
                    <label class="form-label">বর্তমান ছবি</label>
                    <div><img src="${item.url}" alt="${item.title}" class="img-thumbnail" style="max-width: 200px;"></div>
                `;
                youtubeFields.style.display = 'none';
                document.getElementById('edit_file_input').accept = 'image/*';
            } else {
                if (item.url.includes('youtube.com') || item.url.includes('youtu.be')) {
                    preview.innerHTML = `
                        <label class="form-label">বর্তমান ভিডিও</label>
                        <div class="ratio ratio-16x9" style="max-width: 300px;">
                            <iframe src="https://www.youtube.com/embed/${getYouTubeVideoId(item.url)}"></iframe>
                        </div>
                    `;
                    youtubeFields.style.display = 'block';
                    document.getElementById('edit_youtube_url').value = item.url;
                    document.getElementById('edit_thumbnail_url').value = item.thumbnail || '';
                } else {
                    preview.innerHTML = `
                        <label class="form-label">বর্তমান ভিডিও</label>
                        <div><video controls style="max-width: 300px;"><source src="${item.url}" type="video/mp4"></video></div>
                    `;
                    youtubeFields.style.display = 'none';
                }
                document.getElementById('edit_file_input').accept = 'video/*';
            }
            
            new bootstrap.Modal(document.getElementById('editGalleryModal')).show();
        }
    }
    
    function deleteGalleryItem(id) {
        document.getElementById('delete_gallery_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteGalleryModal')).show();
    }
    
    function getYouTubeVideoId(url) {
        const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/);
        return match ? match[1] : '';
    }
    </script>
</body>
</html>
