<!-- Admin Footer Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/admin.js"></script>

<!-- Chart.js for dashboard charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom admin scripts -->
<script>
// Global admin configuration
window.ADMIN_CONFIG = {
    baseUrl: '<?php echo ADMIN_URL; ?>',
    apiUrl: '<?php echo SITE_URL; ?>/api',
    uploadUrl: '<?php echo UPLOADS_URL; ?>',
    locale: 'bn-BD'
};

// Initialize page-specific functionality
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
    
    // Page-specific initializations
    switch(currentPage) {
        case 'dashboard':
            initializeDashboard();
            break;
        case 'appointments':
            initializeAppointments();
            break;
        case 'patients':
            initializePatients();
            break;
        case 'services':
            initializeServices();
            break;
        case 'gallery':
            initializeGallery();
            break;
        case 'reports':
            initializeReports();
            break;
        // Add more page-specific initializations as needed
    }
});

// Page-specific initialization functions
function initializeDashboard() {
    // Dashboard-specific code
    console.log('Dashboard initialized');
}

function initializeAppointments() {
    // Appointments page specific code
    console.log('Appointments page initialized');
}

function initializePatients() {
    // Patients page specific code
    console.log('Patients page initialized');
}

function initializeServices() {
    // Services page specific code
    console.log('Services page initialized');
}

function initializeGallery() {
    // Gallery page specific code
    console.log('Gallery page initialized');
}

function initializeReports() {
    // Reports page specific code
    console.log('Reports page initialized');
}
</script>
