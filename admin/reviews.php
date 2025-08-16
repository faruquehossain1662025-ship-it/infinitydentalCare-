<?php
require_once '../config/config.php';

if (!checkAdminTimeout()) {
    header('Location: login.php');
    exit;
}

$reviews = loadJsonData(REVIEWS_FILE);
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($reviews, 'id'));
        
        if ($key !== false) {
            $reviews[$key]['approved'] = true;
            $reviews[$key]['approved_at'] = date('Y-m-d H:i:s');
            
            if (saveJsonData(REVIEWS_FILE, $reviews)) {
                $message = 'রিভিউ অনুমোদিত হয়েছে!';
                $messageType = 'success';
            }
        }
    }
    
    elseif ($action === 'reject') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($reviews, 'id'));
        
        if ($key !== false) {
            $reviews[$key]['approved'] = false;
            $reviews[$key]['rejected_at'] = date('Y-m-d H:i:s');
            
            if (saveJsonData(REVIEWS_FILE, $reviews)) {
                $message = 'রিভিউ প্রত্যাখ্যান করা হয়েছে!';
                $messageType = 'warning';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $key = array_search($id, array_column($reviews, 'id'));
        
        if ($key !== false) {
            unset($reviews[$key]);
            $reviews = array_values($reviews);
            
            if (saveJsonData(REVIEWS_FILE, $reviews)) {
                $message = 'রিভিউ সফলভাবে ডিলিট করা হয়েছে!';
                $messageType = 'success';
            }
        }
    }
}

// Filter reviews
$filter = $_GET['filter'] ?? 'all';
$filteredReviews = $reviews;

if ($filter === 'approved') {
    $filteredReviews = array_filter($reviews, fn($r) => $r['approved'] ?? false);
} elseif ($filter === 'pending') {
    $filteredReviews = array_filter($reviews, fn($r) => !($r['approved'] ?? false));
}

// Sort by creation date
usort($filteredReviews, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$pageTitle = 'রিভিউ ম্যানেজমেন্ট - অ্যাডমিন প্যানেল';
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
                    <h1 class="h2">রিভিউ ম্যানেজমেন্ট</h1>
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
                                    সব রিভিউ (<?php echo count($reviews); ?>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" href="?filter=pending">
                                    অপেক্ষমাণ (<?php echo count(array_filter($reviews, fn($r) => !($r['approved'] ?? false))); ?>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'approved' ? 'active' : ''; ?>" href="?filter=approved">
                                    অনুমোদিত (<?php echo count(array_filter($reviews, fn($r) => $r['approved'] ?? false)); ?>)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="row">
                    <?php if (!empty($filteredReviews)): ?>
                        <?php foreach ($filteredReviews as $review): ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="star-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill text-warning' : ' text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="badge bg-<?php echo ($review['approved'] ?? false) ? 'success' : 'warning'; ?>">
                                            <?php echo ($review['approved'] ?? false) ? 'অনুমোদিত' : 'অপেক্ষমাণ'; ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                                    
                                    <div class="border-top pt-3 mt-auto">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="avatar-circle me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                                <?php echo strtoupper(substr($review['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($review['name']); ?></h6>
                                                <small class="text-muted"><?php echo formatDateBengali($review['created_at']); ?></small>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($review['phone'])): ?>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($review['phone']); ?>
                                        </small>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($review['service'])): ?>
                                        <small class="text-primary d-block">
                                            <i class="bi bi-heart-pulse me-1"></i><?php echo htmlspecialchars($review['service']); ?>
                                        </small>
                                        <?php endif; ?>
                                        
                                        <div class="btn-group w-100 mt-3">
                                            <?php if (!($review['approved'] ?? false)): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="approveReview('<?php echo $review['id']; ?>')">
                                                <i class="bi bi-check-circle"></i> অনুমোদন
                                            </button>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="rejectReview('<?php echo $review['id']; ?>')">
                                                <i class="bi bi-x-circle"></i> প্রত্যাখ্যান
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteReview('<?php echo $review['id']; ?>')">
                                                <i class="bi bi-trash"></i> ডিলিট
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-chat-square-text text-muted" style="font-size: 4rem;"></i>
                            <h4 class="text-muted mt-3">কোনো রিভিউ নেই</h4>
                            <p class="text-muted">নির্বাচিত ফিল্টার অনুযায়ী কোনো রিভিউ পাওয়া যায়নি।</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Approve Confirmation Modal -->
    <div class="modal fade" id="approveReviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="id" id="approve_review_id">
                    <div class="modal-header">
                        <h5 class="modal-title">রিভিউ অনুমোদন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি এই রিভিউ অনুমোদন করতে চান?</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            অনুমোদিত রিভিউ ওয়েবসাইটে প্রদর্শিত হবে।
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-success">অনুমোদন করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <div class="modal fade" id="rejectReviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="id" id="reject_review_id">
                    <div class="modal-header">
                        <h5 class="modal-title">রিভিউ প্রত্যাখ্যান</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি এই রিভিউ প্রত্যাখ্যান করতে চান?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            প্রত্যাখ্যাত রিভিউ ওয়েবসাইটে প্রদর্শিত হবে না।
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-warning">প্রত্যাখ্যান করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteReviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_review_id">
                    <div class="modal-header">
                        <h5 class="modal-title">রিভিউ ডিলিট করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে এই রিভিউ ডিলিট করতে চান?</p>
                        <div class="alert alert-danger">
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
    function approveReview(id) {
        document.getElementById('approve_review_id').value = id;
        new bootstrap.Modal(document.getElementById('approveReviewModal')).show();
    }
    
    function rejectReview(id) {
        document.getElementById('reject_review_id').value = id;
        new bootstrap.Modal(document.getElementById('rejectReviewModal')).show();
    }
    
    function deleteReview(id) {
        document.getElementById('delete_review_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteReviewModal')).show();
    }
    </script>
</body>
</html>
