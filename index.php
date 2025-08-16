<?php
require_once 'config/config.php';

// ভিজিটর ট্র্যাক
trackVisitor();

// সেটিংস লোড
$settings = getSettings();
$banners = loadJsonData(BANNERS_FILE);
$activeBanners = array_filter($banners, fn($b) => $b['active'] ?? true);
$services = loadJsonData(SERVICES_FILE);
$activeServices = array_filter($services, fn($s) => $s['active'] ?? true);
$offers = loadJsonData(OFFERS_FILE);
$activeOffers = array_filter($offers, fn($o) => $o['active'] ?? true);
$reviews = loadJsonData(REVIEWS_FILE);
$approvedReviews = array_filter($reviews, fn($r) => $r['approved'] ?? true);
$news = loadJsonData(NEWS_FILE);
$activeNews = array_filter($news, fn($n) => $n['active'] ?? true);

// Meta tags for SEO
$pageTitle = $settings['seo_title'] ?: ($settings['site_name'] . ' - ' . $settings['site_description']);
$pageDescription = $settings['seo_description'] ?: $settings['site_description'];
$pageKeywords = $settings['seo_keywords'] ?: '';
?>

<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($settings['site_name']); ?>">
    
    <!-- Twitter Card tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    
    <!-- Google Analytics -->
    <?php if (!empty($settings['google_analytics'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $settings['google_analytics']; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo $settings['google_analytics']; ?>');
    </script>
    <?php endif; ?>
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "MedicalBusiness",
        "name": "<?php echo htmlspecialchars($settings['site_name']); ?>",
        "description": "<?php echo htmlspecialchars($settings['site_description']); ?>",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "<?php echo htmlspecialchars($settings['address']); ?>"
        },
        "telephone": "<?php echo htmlspecialchars($settings['contact_phone']); ?>",
        "email": "<?php echo htmlspecialchars($settings['contact_email']); ?>",
        "openingHours": "Mo-Su <?php echo htmlspecialchars($settings['working_hours']); ?>",
        "url": "<?php echo SITE_URL; ?>"
    }
    </script>
</head>
<body>
    
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-TGGQ2KHJ0P"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-TGGQ2KHJ0P');
</script>
    
    
    <!-- Tawk.to Live Chat -->
    <script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/689879e722fdc61926d1b3b4/default';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
    })();
    </script>
    
    <!-- App Download Banner -->
    <?php if ($settings['app_popup_enabled'] && !empty($settings['app_download_url'])): ?>
    <div class="app-download-banner" id="appBanner">
        <button class="app-download-close">&times;</button>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-0">আমাদের মোবাইল অ্যাপ ডাউনলোড করুন আরও ভালো সেবার জন্য!</h6>
                </div>
                <div class="col-md-4 text-end">
                    <a href="<?php echo htmlspecialchars($settings['app_download_url']); ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-download me-1"></i> ডাউনলোড করুন
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Breaking News -->
    <?php if ($settings['breaking_news_enabled'] && !empty($activeNews)): ?>
    <div class="breaking-news bg-primary text-white py-2">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <span class="badge bg-light text-primary me-3 fw-bold">সর্বশেষ</span>
                <div class="news-ticker overflow-hidden flex-grow-1">
                    <?php foreach ($activeNews as $newsItem): ?>
                        <span class="news-item"><?php echo htmlspecialchars($newsItem['title']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                        <a class="nav-link active" href="/">হোম</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">সেবাসমূহ</a>
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
                        <a class="nav-link" href="track.php">অ্যাপয়েন্টমেন্ট ট্র্যাক</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">যোগাযোগ</a>
                    </li>
                    
                     <li class="nav-item">
                        <a class="nav-link" href="drinfo.html">ডাক্তার সম্পর্কে</a>
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

    <!-- Hero Slider -->
    <?php if (!empty($activeBanners)): ?>
    <div id="heroSlider" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($activeBanners as $index => $banner): ?>
                <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="<?php echo $index; ?>" 
                        <?php echo $index === 0 ? 'class="active"' : ''; ?>></button>
            <?php endforeach; ?>
        </div>
        
        
        
        
        
        <div class="carousel-inner">
            <?php foreach ($activeBanners as $index => $banner): ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?php echo $banner['image'] ?: 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80'; ?>') center/cover;">
                    <div class="container text-center text-white">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <h1 class="display-4 fw-bold mb-4"><?php echo htmlspecialchars($banner['title']); ?></h1>
                                <?php if (!empty($banner['subtitle'])): ?>
                                    <p class="lead mb-4"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                                <?php endif; ?>
                                <div class="d-flex gap-3 justify-content-center">
                                    <a href="appointment.php" class="btn btn-primary btn-lg px-5">
                                        <i class="bi bi-calendar-plus me-2"></i>অ্যাপয়েন্টমেন্ট বুক করুন
                                    </a>
                                    <a href="services.php" class="btn btn-outline-light btn-lg px-5">
                                        সেবা দেখুন
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Special Offers with Countdown -->
    <?php if (!empty($activeOffers)): ?>
    <section class="py-5 bg-gradient-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="display-6 fw-bold mb-3">
                        <i class="bi bi-gift me-3"></i>বিশেষ অফার
                    </h2>
                    <?php foreach ($activeOffers as $offer): ?>
                    <div class="offer-card mb-4">
                        <h3 class="h4 fw-bold"><?php echo htmlspecialchars($offer['title']); ?></h3>
                        <p class="mb-3"><?php echo htmlspecialchars($offer['description']); ?></p>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-warning text-dark fs-5 px-3 py-2">
                                <?php echo htmlspecialchars($offer['discount']); ?> ছাড়
                            </span>
                            <a href="appointment.php" class="btn btn-light text-primary fw-bold">
                                এখনই বুক করুন
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-4">
                    <div class="countdown-timer text-center p-4 rounded">
                        <h4 class="mb-3">অফার শেষ হতে বাকি:</h4>
                        <div id="countdown" class="d-flex justify-content-center gap-3">
                            <div class="time-unit">
                                <div class="time-value fs-2 fw-bold" id="days">00</div>
                                <div class="time-label">দিন</div>
                            </div>
                            <div class="time-unit">
                                <div class="time-value fs-2 fw-bold" id="hours">00</div>
                                <div class="time-label">ঘণ্টা</div>
                            </div>
                            <div class="time-unit">
                                <div class="time-value fs-2 fw-bold" id="minutes">00</div>
                                <div class="time-label">মিনিট</div>
                            </div>
                            <div class="time-unit">
                                <div class="time-value fs-2 fw-bold" id="seconds">00</div>
                                <div class="time-label">সেকেন্ড</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    
    <!-- Doctor Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">আমাদের প্রধান ডাক্তার</h2>
            <p class="text-muted">অভিজ্ঞতা ও যত্নের সাথে আপনার সেবায়</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-3 doctor-card">
                    <div class="row g-0 align-items-center">
                        <!-- Doctor Image -->
                        <div class="col-md-5">
                            <img src="https://infinitydentalcare.top/uploads/5R%20%281%29.png" 
                                 alt="ডা. কামরুল হাসানন"
                                 class="img-fluid rounded"
                                 style="height: 300px; object-fit: cover;">
                        </div>

                        <!-- Doctor Info -->
                        <div class="col-md-7">
                            <div class="card-body">
                                <h4 class="fw-bold mb-1">ডা. কামরুল হাসান</h4>
                                <p class="text-primary mb-1">BDS, MDS (Prosthodontics)</p>
                                <p class="text-muted small mb-3">প্রধান কনসালট্যান্ট ও প্রস্থোডন্টিক বিশেষজ্ঞ</p>
                                
                                <ul class="list-unstyled small mb-3">
                                    <li><strong>অভিজ্ঞতা:</strong> ২০+ বছর</li>
                                    <li><strong>বিশেষত্ব:</strong> কসমেটিক ডেন্টিস্ট্রি, ডেন্টাল ইমপ্ল্যান্ট, ব্রিজ, ডেন্টুর</li>
                                    <li><strong>চেম্বারের সময়:</strong> প্রতিদিন সকাল১০টা - রাত ৯টা</li>
                                    <li><strong>ফোন:</strong> +880 1706685943</li>
                                    <li><strong>ইমেইল:</strong> infinitydentalcare2025@gmail.com
                                        </li>
                                </ul>

                                <div class="d-flex gap-2">
                                    <a href="appointment.php?doctor=1" class="btn btn-primary btn-sm">
                                        <i class="bi bi-calendar-plus me-1"></i> অ্যাপয়েন্টমেন্ট
                                    </a>
                                    <a href="contact.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-telephone me-1"></i> যোগাযোগ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>   
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.doctor-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 10px;
}
.doctor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}
</style>

    

    <!-- Services Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary mb-3">আমাদের সেবাসমূহ</h2>
                <p class="lead text-muted">আধুনিক যন্ত্রপাতি ও অভিজ্ঞ ডাক্তারের মাধ্যমে সর্বোচ্চ মানের সেবা</p>
            </div>
            
            <div class="row g-4">
                <?php foreach (array_slice($activeServices, 0, 6) as $service): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="service-icon mb-3">
                                <i class="bi bi-heart-pulse text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h4 class="card-title mb-3"><?php echo htmlspecialchars($service['name']); ?></h4>
                            <p class="card-text text-muted mb-3"><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-primary fw-bold fs-5">৳<?php echo htmlspecialchars($service['price']); ?></span>
                                <span class="text-muted small"><?php echo htmlspecialchars($service['duration']); ?></span>
                            </div>
                            <a href="appointment.php?service=<?php echo urlencode($service['name']); ?>" class="btn btn-primary">
                                অ্যাপয়েন্টমেন্ট নিন
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="services.php" class="btn btn-outline-primary btn-lg">
                    সকল সেবা দেখুন <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <?php if (!empty($approvedReviews)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary mb-3">রোগীদের মতামত</h2>
                <p class="lead text-muted">আমাদের সেবা নিয়ে রোগীরা কী বলছেন</p>
            </div>
            
            <div class="row g-4">
                <?php foreach (array_slice($approvedReviews, 0, 3) as $review): ?>
                <div class="col-lg-4">
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="reviews.php" class="btn btn-outline-primary btn-lg">
                    সকল রিভিউ দেখুন <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold text-primary mb-4">যোগাযোগ করুন</h2>
                    <p class="lead mb-4">আমাদের সাথে যোগাযোগ করুন যেকোনো প্রয়োজনে</p>
                    
                    <div class="contact-info">
                        <div class="d-flex align-items-center mb-3">
                            <div class="contact-icon me-3">
                                <i class="bi bi-telephone text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">ফোন</h6>
                                <span><?php echo htmlspecialchars($settings['contact_phone']); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="contact-icon me-3">
                                <i class="bi bi-envelope text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">ইমেইল</h6>
                                <span><?php echo htmlspecialchars($settings['contact_email']); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="contact-icon me-3">
                                <i class="bi bi-geo-alt text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">ঠিকানা</h6>
                                <span><?php echo htmlspecialchars($settings['address']); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <div class="contact-icon me-3">
                                <i class="bi bi-clock text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">সময়</h6>
                                <span><?php echo htmlspecialchars($settings['working_hours']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card border-0 shadow">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">দ্রুত যোগাযোগ</h5>
                            <form id="contactForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="name" placeholder="আপনার নাম" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="tel" class="form-control" name="phone" placeholder="ফোন নম্বর" required>
                                    </div>
                                    <div class="col-12">
                                        <input type="email" class="form-control" name="email" placeholder="ইমেইল">
                                    </div>
                                    <div class="col-12">
                                        <textarea class="form-control" name="message" rows="4" placeholder="আপনার বার্তা" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">
                                            বার্তা পাঠান <i class="bi bi-send ms-2"></i>
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

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="mb-3"><?php echo htmlspecialchars($settings['site_name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($settings['site_description']); ?></p>
                    <div class="social-links d-flex gap-2">
                        <?php if (!empty($settings['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['facebook_url']); ?>" target="_blank">
                                <i class="bi bi-facebook"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['twitter_url']); ?>" target="_blank">
                                <i class="bi bi-twitter"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['instagram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['instagram_url']); ?>" target="_blank">
                                <i class="bi bi-instagram"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['youtube_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['youtube_url']); ?>" target="_blank">
                                <i class="bi bi-youtube"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-2">
                    <h6 class="mb-3">লিংক</h6>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-muted text-decoration-none">হোম</a></li>
                        <li><a href="services.php" class="text-muted text-decoration-none">সেবাসমূহ</a></li>
                        <li><a href="appointment.php" class="text-muted text-decoration-none">অ্যাপয়েন্টমেন্ট</a></li>
                        <li><a href="reviews.php" class="text-muted text-decoration-none">রিভিউ</a></li>
                        <li><a href="contact.php" class="text-muted text-decoration-none">যোগাযোগ</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3">
                    <h6 class="mb-3">যোগাযোগ</h6>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-telephone me-2"></i>
                        <?php echo htmlspecialchars($settings['contact_phone']); ?>
                    </p>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-envelope me-2"></i>
                        <?php echo htmlspecialchars($settings['contact_email']); ?>
                    </p>
                    <p class="text-muted small">
                        <i class="bi bi-geo-alt me-2"></i>
                        <?php echo htmlspecialchars($settings['address']); ?>
                    </p>
                </div>
                
                <div class="col-lg-3">
                    <h6 class="mb-3">কাজের সময়</h6>
                    <p class="text-muted small"><?php echo htmlspecialchars($settings['working_hours']); ?></p>
                    
                    <?php if (!empty($settings['app_download_url'])): ?>
                    <div class="mt-3">
                        <a href="<?php echo htmlspecialchars($settings['app_download_url']); ?>" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-download me-1"></i> অ্যাপ ডাউনলোড
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted small mb-0">
                        © <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. সব অধিকার সংরক্ষিত।
                    </p>
                </div>
                
            </div>
        </div>
    </footer>

    <!-- WhatsApp Float Button -->
    <?php if ($settings['whatsapp_enabled'] && !empty($settings['whatsapp_number'])): ?>
    <a href="#" class="whatsapp-float" data-phone="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>">
        <i class="bi bi-whatsapp"></i>
    </a>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
