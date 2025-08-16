<?php
require_once '../config/config.php';

// Admin login check
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $news = loadJsonData(NEWS_FILE);
        
        $newsItem = [
            'id' => $action === 'add' ? generateId() : sanitizeInput($_POST['id']),
            'title' => sanitizeInput($_POST['title']),
            'content' => sanitizeInput($_POST['content']),
            'active' => isset($_POST['active']),
            'created_at' => $action === 'add' ? date('Y-m-d H:i:s') : ($_POST['created_at'] ?? date('Y-m-d H:i:s')),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($action === 'add') {
            $news[] = $newsItem;
        } else {
            // Update existing
            foreach ($news as &$item) {
                if ($item['id'] === $newsItem['id']) {
                    $item = $newsItem;
                    break;
                }
            }
        }
        
        saveJsonData(NEWS_FILE, $news);
        $_SESSION['success'] = 'নিউজ সফলভাবে ' . ($action === 'add' ? 'যোগ' : 'আপডেট') . ' করা হয়েছে';
        
    } elseif ($action === 'delete') {
        $news = loadJsonData(NEWS_FILE);
        $id = sanitizeInput($_POST['id']);
        
        $news = array_filter($news, fn($item) => $item['id'] !== $id);
        saveJsonData(NEWS_FILE, array_values($news));
        $_SESSION['success'] = 'নিউজ সফলভাবে মুছে ফেলা হয়েছে';
        
    } elseif ($action === 'toggle') {
        $news = loadJsonData(NEWS_FILE);
        $id = sanitizeInput($_POST['id']);
        
        foreach ($news as &$item) {
            if ($item['id'] === $id) {
                $item['active'] = !($item['active'] ?? true);
                $item['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        
        saveJsonData(NEWS_FILE, $news);
        $_SESSION['success'] = 'নিউজের স্ট্যাটাস পরিবর্তন করা হয়েছে';
    }
    
    header('Location: news.php');
    exit;
}

// Load news
$news = loadJsonData(NEWS_FILE);
$editItem = null;

if (isset($_GET['edit'])) {
    foreach ($news as $item) {
        if ($item['id'] === $_GET['edit']) {
            $editItem = $item;
            break;
        }
    }
}

$pageTitle = 'নিউজ ম্যানেজমেন্ট';
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - অ্যাডমিন প্যানেল</title>
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
                    <h1 class="h2">নিউজ ম্যানেজমেন্ট</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newsModal">
                        <i class="bi bi-plus-circle me-2"></i>নতুন নিউজ যোগ করুন
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>শিরোনাম</th>
                                <th>বিষয়বস্তু</th>
                                <th>স্ট্যাটাস</th>
                                <th>তৈরি তারিখ</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($news)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="bi bi-newspaper text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">কোনো নিউজ পাওয়া যায়নি</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($news as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($item['content'], 0, 100)) . (strlen($item['content']) > 100 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($item['active'] ?? true) ? 'success' : 'secondary'; ?>">
                                            <?php echo ($item['active'] ?? true) ? 'সক্রিয়' : 'নিষ্ক্রিয়'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateBengali($item['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত?')">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-outline-warning">
                                                    <i class="bi bi-toggle-<?php echo ($item['active'] ?? true) ? 'on' : 'off'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত এই নিউজ মুছে ফেলতে চান?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- News Modal -->
    <div class="modal fade" id="newsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo $editItem ? 'নিউজ সম্পাদনা' : 'নতুন নিউজ যোগ করুন'; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editItem ? 'edit' : 'add'; ?>">
                        <?php if ($editItem): ?>
                            <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                            <input type="hidden" name="created_at" value="<?php echo $editItem['created_at']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">শিরোনাম *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $editItem ? htmlspecialchars($editItem['title']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">বিষয়বস্তু *</label>
                            <textarea class="form-control" id="content" name="content" rows="4" required><?php echo $editItem ? htmlspecialchars($editItem['content']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" 
                                   <?php echo (!$editItem || ($editItem['active'] ?? true)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="active">সক্রিয় রাখুন</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editItem ? 'আপডেট করুন' : 'সংরক্ষণ করুন'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($editItem): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('newsModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>