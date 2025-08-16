<?php
require_once 'config/config.php';

// Handle AJAX review form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    try {
        // Validate required fields
        $required_fields = ['name', 'rating', 'comment'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                echo json_encode(['success' => false, 'message' => 'সব প্রয়োজনীয় তথ্য দিন']);
                exit;
            }
        }

        // Validate rating
        $rating = (int)$_POST['rating'];
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'রেটিং ১ থেকে ৫ এর মধ্যে দিন']);
            exit;
        }

        // Validate email if provided
        if (!empty($_POST['email']) && !validateEmail($_POST['email'])) {
            echo json_encode(['success' => false, 'message' => 'সঠিক ইমেইল দিন']);
            exit;
        }

        // Load existing reviews
        $reviews = loadJsonData(REVIEWS_FILE);

        // Create review entry
        $review = [
            'id' => generateId(),
            'name' => sanitizeInput($_POST['name']),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'service' => sanitizeInput($_POST['service'] ?? ''),
            'rating' => $rating,
            'comment' => sanitizeInput($_POST['comment']),
            'approved' => false, // Admin needs to approve
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Save review
        $reviews[] = $review;
        if (!saveJsonData(REVIEWS_FILE, $reviews)) {
            echo json_encode(['success' => false, 'message' => 'ডেটা সেভ করতে সমস্যা হয়েছে']);
            exit;
        }

        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'আপনার রিভিউ সফলভাবে জমা হয়েছে। অনুমোদনের পর এটি ওয়েবসাইটে প্রদর্শিত হবে।'
        ]);
        exit;

    } catch (Exception $e) {
        error_log('Review Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'সিস্টেম ত্রুটি হয়েছে']);
        exit;
    }
}

trackVisitor();
$settings = getSettings();
$reviews = loadJsonData(REVIEWS_FILE);
$approvedReviews = array_filter($reviews, fn($r) => $r['approved'] ?? false);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$total = count($approvedReviews);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedReviews = array_slice($approvedReviews, $offset, $perPage);

$pageTitle = 'রোগীদের রিভিউ - ' . $settings['site_name'];
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="আমাদের সেবা নিয়ে রোগীদের মতামত ও রিভিউ দেখুন">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="py-5 bg-gradient-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">রোগীদের মতামত</h1>
                    <p class="lead">আমাদের সেবা নিয়ে রোগীরা কী বলছেন দেখুন</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Grid -->
    <section class="py-5">
        <div class="container">
            <?php if (!empty($paginatedReviews)): ?>
            <div class="row g-4">
                <?php foreach ($paginatedReviews as $review): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="review-card card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="star-rating mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill text-warning' : ' text-muted'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="card-text mb-3">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3">
                                    <?php echo strtoupper(substr($review['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($review['name']); ?></h6>
                                    <small class="text-muted"><?php echo formatDateBengali($review['created_at']); ?></small>
                                    <?php if (!empty($review['service'])): ?>
                                        <div><small class="text-primary"><?php echo htmlspecialchars($review['service']); ?></small></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">পূর্ববর্তী</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">পরবর্তী</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-chat-square-text text-muted" style="font-size: 4rem;"></i>
                <h3 class="text-muted mt-3">এখনো কোনো রিভিউ নেই</h3>
                <p class="text-muted">প্রথম রিভিউ দিয়ে অন্যদের সাহায্য করুন!</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Write Review Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow">
                        <div class="card-body p-5">
                            <h3 class="text-center mb-4">আপনার মতামত দিন</h3>
                            
                            <form id="reviewForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">আপনার নাম <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">ইমেইল</label>
                                        <input type="email" class="form-control" name="email">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">ফোন নম্বর</label>
                                        <input type="tel" class="form-control" name="phone">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">সেবা</label>
                                        <input type="text" class="form-control" name="service" placeholder="যে সেবা নিয়েছেন">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">রেটিং <span class="text-danger">*</span></label>
                                        <div class="rating-input d-flex gap-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <label class="rating-star" data-rating="<?php echo $i; ?>">
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" class="d-none" required>
                                                <i class="bi bi-star fs-4 text-muted"></i>
                                            </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label">আপনার মন্তব্য <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="comment" rows="4" 
                                                placeholder="আমাদের সেবা সম্পর্কে আপনার মতামত লিখুন" required></textarea>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-send me-2"></i>রিভিউ পাঠান
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- WhatsApp Float Button -->
    <?php if ($settings['whatsapp_enabled'] && !empty($settings['whatsapp_number'])): ?>
    <a href="#" class="whatsapp-float" data-phone="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>">
        <i class="bi bi-whatsapp"></i>
    </a>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    
    <script>
    // Star rating interaction
    document.addEventListener('DOMContentLoaded', function() {
        const ratingStars = document.querySelectorAll('.rating-star');
        
        ratingStars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Update star display
                ratingStars.forEach((s, i) => {
                    const icon = s.querySelector('i');
                    if (i < rating) {
                        icon.className = 'bi bi-star-fill fs-4 text-warning';
                    } else {
                        icon.className = 'bi bi-star fs-4 text-muted';
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = this.dataset.rating;
                ratingStars.forEach((s, i) => {
                    const icon = s.querySelector('i');
                    if (i < rating) {
                        icon.className = 'bi bi-star-fill fs-4 text-warning';
                    } else {
                        icon.className = 'bi bi-star fs-4 text-muted';
                    }
                });
            });
        });
        
        // Reset on mouse leave
        document.querySelector('.rating-input').addEventListener('mouseleave', function() {
            const checkedInput = document.querySelector('input[name="rating"]:checked');
            if (checkedInput) {
                const rating = checkedInput.value;
                ratingStars.forEach((s, i) => {
                    const icon = s.querySelector('i');
                    if (i < rating) {
                        icon.className = 'bi bi-star-fill fs-4 text-warning';
                    } else {
                        icon.className = 'bi bi-star fs-4 text-muted';
                    }
                });
            } else {
                ratingStars.forEach(s => {
                    s.querySelector('i').className = 'bi bi-star fs-4 text-muted';
                });
            }
        });
    });
    </script>
</body>
</html>