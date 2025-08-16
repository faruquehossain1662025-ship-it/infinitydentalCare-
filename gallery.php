<?php
require_once 'config/config.php';

trackVisitor();
$settings = getSettings();
$gallery = loadJsonData(GALLERY_FILE);
$activeGallery = array_filter($gallery, fn($g) => $g['active'] ?? true);

// Filter by type
$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all') {
    $activeGallery = array_filter($activeGallery, fn($g) => $g['type'] === $filter);
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$total = count($activeGallery);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedGallery = array_slice($activeGallery, $offset, $perPage);

$pageTitle = 'গ্যালারি - ' . $settings['site_name'];
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="আমাদের ক্লিনিকের ছবি ও ভিডিও গ্যালারি দেখুন">
    
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
                    <h1 class="display-4 fw-bold mb-3">গ্যালারি</h1>
                    <p class="lead">আমাদের ক্লিনিক ও সেবার ছবি ও ভিডিও দেখুন</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Tabs -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                                   href="?filter=all">সব</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'image' ? 'active' : ''; ?>" 
                                   href="?filter=image">ছবি</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'video' ? 'active' : ''; ?>" 
                                   href="?filter=video">ভিডিও</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Grid -->
    <section class="py-5">
        <div class="container">
            <?php if (!empty($paginatedGallery)): ?>
            <div class="row g-4">
                <?php foreach ($paginatedGallery as $item): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="gallery-item" data-type="<?php echo $item['type']; ?>" 
                         data-src="<?php echo htmlspecialchars($item['url']); ?>" 
                         data-title="<?php echo htmlspecialchars($item['title']); ?>">
                        
                        <?php if ($item['type'] === 'image'): ?>
                            <img src="<?php echo htmlspecialchars($item['thumbnail'] ?: $item['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="img-fluid">
                        <?php elseif ($item['type'] === 'video'): ?>
                            <?php if (strpos($item['url'], 'youtube.com') !== false || strpos($item['url'], 'youtu.be') !== false): ?>
                                <div class="video-thumbnail position-relative">
                                    <img src="<?php echo htmlspecialchars($item['thumbnail'] ?: 'https://img.youtube.com/vi/' . getYouTubeVideoId($item['url']) . '/maxresdefault.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                         class="img-fluid">
                                    <div class="video-play-button">
                                        <i class="bi bi-play-circle-fill text-white" style="font-size: 3rem;"></i>
                                    </div>
                                </div>
                            <?php else: ?>
                                <video class="img-fluid">
                                    <source src="<?php echo htmlspecialchars($item['url']); ?>" type="video/mp4">
                                </video>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="gallery-overlay">
                            <div class="text-center text-white">
                                <i class="bi bi-<?php echo $item['type'] === 'video' ? 'play' : 'zoom-in'; ?> fs-2 mb-2"></i>
                                <h6><?php echo htmlspecialchars($item['title']); ?></h6>
                                <?php if (!empty($item['description'])): ?>
                                    <p class="small"><?php echo htmlspecialchars($item['description']); ?></p>
                                <?php endif; ?>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>">পূর্ববর্তী</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>">পরবর্তী</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-images text-muted" style="font-size: 4rem;"></i>
                <h3 class="text-muted mt-3">কোনো মিডিয়া পাওয়া যায়নি</h3>
                <p class="text-muted">এই বিভাগে এখনো কোনো ছবি বা ভিডিও নেই।</p>
            </div>
            <?php endif; ?>
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
</body>
</html>

<?php
// Helper function to extract YouTube video ID
function getYouTubeVideoId($url) {
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $url, $matches);
    return $matches[1] ?? '';
}
?>
