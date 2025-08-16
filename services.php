<?php
require_once 'config/config.php';

trackVisitor();
$settings = getSettings();
$services = loadJsonData(SERVICES_FILE);
$activeServices = array_filter($services, fn($s) => $s['active'] ?? true);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$total = count($activeServices);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedServices = array_slice($activeServices, $offset, $perPage);

$pageTitle = 'সেবাসমূহ - ' . $settings['site_name'];
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="আমাদের সকল দন্ত চিকিৎসা সেবা দেখুন">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary fs-3" href="/">
                <i class="bi bi-heart-pulse me-2"></i>
                <?php echo htmlspecialchars($settings['site_name']); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">হোম</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="services.php">সেবাসমূহ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointment.php">অ্যাপয়েন্টমেন্ট</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reviews.php">রিভিউ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gallery.php">গ্যালারি</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="track.php">ট্র্যাক</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">যোগাযোগ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-2 px-3" href="appointment.php">
                            <i class="bi bi-calendar-plus me-1"></i> বুক করুন
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="py-5 bg-gradient-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">আমাদের সেবাসমূহ</h1>
                    <p class="lead">আধুনিক যন্ত্রপাতি ও অভিজ্ঞ ডাক্তারের মাধ্যমে সর্বোচ্চ মানের দন্ত চিকিৎসা সেবা</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Grid -->
    <section class="py-5">
        <div class="container">
            <?php if (!empty($paginatedServices)): ?>
            <div class="row g-4">
                <?php foreach ($paginatedServices as $service): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card card h-100 border-0 shadow-sm">
                        <?php if (!empty($service['image'])): ?>
                        <img src="<?php echo htmlspecialchars($service['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($service['name']); ?>" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div class="card-body p-4">
                            <div class="service-icon mb-3">
                                <i class="bi bi-heart-pulse text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h4 class="card-title mb-3"><?php echo htmlspecialchars($service['name']); ?></h4>
                            <p class="card-text text-muted mb-3"><?php echo htmlspecialchars($service['description']); ?></p>
                            
                            <?php if (!empty($service['features'])): ?>
                            <ul class="list-unstyled mb-3">
                                <?php foreach (array_slice($service['features'], 0, 3) as $feature): ?>
                                <li class="small text-muted mb-1">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <?php echo htmlspecialchars($feature); ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-primary fw-bold fs-5">৳<?php echo htmlspecialchars($service['price']); ?></span>
                                <span class="text-muted small"><?php echo htmlspecialchars($service['duration']); ?></span>
                            </div>
                            
                            <a href="appointment.php?service=<?php echo urlencode($service['name']); ?>" class="btn btn-primary w-100">
                                <i class="bi bi-calendar-plus me-2"></i>অ্যাপয়েন্টমেন্ট নিন
                            </a>
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
                <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                <h3 class="text-muted mt-3">কোনো সেবা পাওয়া যায়নি</h3>
                <p class="text-muted">দুঃখিত, এই মুহূর্তে কোনো সেবা উপলব্ধ নেই।</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-light">
        <div class="container text-center">
            <h2 class="display-6 fw-bold text-primary mb-3">এখনই অ্যাপয়েন্টমেন্ট নিন</h2>
            <p class="lead text-muted mb-4">আমাদের অভিজ্ঞ ডাক্তারদের সাথে পরামর্শ নিন</p>
            <a href="appointment.php" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-calendar-plus me-2"></i>অ্যাপয়েন্টমেন্ট বুক করুন
            </a>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- WhatsApp Float Button -->
    <?php if ($settings['whatsapp_enabled'] && !empty($settings['whatsapp_number'])): ?>
    <a href="#" class="whatsapp-float" data-phone="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>">
        <i class="bi bi-whatsapp"></i>
    </a>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
