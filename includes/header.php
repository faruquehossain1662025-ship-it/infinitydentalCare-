<?php
// Common header for all pages
$settings = getSettings();
?>

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
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="/">হোম</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : ''; ?>" href="services.php">সেবাসমূহ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'appointment.php' ? 'active' : ''; ?>" href="appointment.php">অ্যাপয়েন্টমেন্ট</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : ''; ?>" href="reviews.php">রিভিউ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'gallery.php' ? 'active' : ''; ?>" href="gallery.php">গ্যালারি</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'track.php' ? 'active' : ''; ?>" href="track.php">ট্র্যাক</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : ''; ?>" href="contact.php">যোগাযোগ</a>
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
