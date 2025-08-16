<?php
$settings = getSettings();
?>

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
            <div class="col-md-6 text-end">
                <a href="admin/login.php" class="text-muted small text-decoration-none">অ্যাডমিন প্যানেল</a>
            </div>
        </div>
    </div>
</footer>
