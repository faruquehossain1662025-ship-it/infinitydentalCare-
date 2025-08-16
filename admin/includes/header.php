<?php
$settings = getSettings();
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white admin-header fixed-top">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary me-3 d-md-none" type="button" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-heart-pulse me-2"></i>
            <?php echo htmlspecialchars($settings['site_name']); ?> - অ্যাডমিন
        </a>
        
        <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" 
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar-circle me-2" style="width: 32px; height: 32px; font-size: 0.875rem;">
                        A
                    </div>
                    <span class="d-none d-sm-inline">অ্যাডমিন</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="settings.php">
                            <i class="bi bi-gear me-2"></i>সেটিংস
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../" target="_blank">
                            <i class="bi bi-globe me-2"></i>সাইট দেখুন
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>লগআউট
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
.admin-header {
    top: 0;
    z-index: 1030;
}

body {
    padding-top: 76px;
}

.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--admin-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}
</style>
