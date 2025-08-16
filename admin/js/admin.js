// Admin Panel JavaScript

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
    initializeNotifications();
    initializeFileUploads();
    initializeDataTables();
    initializeCharts();
    initializeFormValidation();
});

// Initialize admin panel
function initializeAdmin() {
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }
    
    // Set active navigation
    setActiveNavigation();
    
    // Initialize tooltips and popovers
    initializeBootstrapComponents();
    
    // Auto-refresh data every 30 seconds
    setInterval(refreshDashboardData, 30000);
}

// Set active navigation item
function setActiveNavigation() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.admin-nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Initialize Bootstrap components
function initializeBootstrapComponents() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// Notification system
function initializeNotifications() {
    // Auto-remove notifications after 5 seconds
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    });
}

function showNotification(message, type = 'success', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-${getNotificationIcon(type)} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// File upload functionality
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        const container = input.closest('.file-upload-container');
        if (!container) return;
        
        // Create upload area if it doesn't exist
        let uploadArea = container.querySelector('.file-upload-area');
        if (!uploadArea) {
            uploadArea = document.createElement('div');
            uploadArea.className = 'file-upload-area';
            uploadArea.innerHTML = `
                <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                <p class="mt-2 mb-0">ফাইল এখানে টেনে আনুন বা ক্লিক করুন</p>
            `;
            container.appendChild(uploadArea);
        }
        
        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                handleFileSelection(input, files[0]);
            }
        });
        
        uploadArea.addEventListener('click', function() {
            input.click();
        });
        
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFileSelection(this, this.files[0]);
            }
        });
    });
}

function handleFileSelection(input, file) {
    const container = input.closest('.file-upload-container');
    const preview = container.querySelector('.file-preview');
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" class="image-preview img-fluid">`;
            }
        };
        reader.readAsDataURL(file);
    } else {
        if (preview) {
            preview.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-file-earmark me-2"></i>
                    ${file.name} (${formatFileSize(file.size)})
                </div>
            `;
        }
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Data tables functionality
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                const column = this.dataset.sort;
                const direction = this.dataset.direction === 'asc' ? 'desc' : 'asc';
                this.dataset.direction = direction;
                
                // Update header icon
                headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
                this.classList.add(`sorted-${direction}`);
                
                sortTable(table, column, direction);
            });
        });
        
        // Add search functionality
        const searchInput = document.querySelector(`[data-table="${table.id}"]`);
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterTable(table, this.value);
            });
        }
    });
}

function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = a.querySelector(`[data-${column}]`)?.dataset[column] || 
                    a.querySelector(`td:nth-child(${getColumnIndex(table, column)})`)?.textContent || '';
        const bVal = b.querySelector(`[data-${column}]`)?.dataset[column] || 
                    b.querySelector(`td:nth-child(${getColumnIndex(table, column)})`)?.textContent || '';
        
        if (direction === 'asc') {
            return aVal.localeCompare(bVal, 'bn', { numeric: true });
        } else {
            return bVal.localeCompare(aVal, 'bn', { numeric: true });
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

function getColumnIndex(table, column) {
    const headers = table.querySelectorAll('th');
    for (let i = 0; i < headers.length; i++) {
        if (headers[i].dataset.sort === column) {
            return i + 1;
        }
    }
    return 1;
}

function filterTable(table, query) {
    const rows = table.querySelectorAll('tbody tr');
    const searchTerm = query.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// Charts functionality (using Chart.js if available)
function initializeCharts() {
    // Income chart
    const incomeChart = document.getElementById('incomeChart');
    if (incomeChart && typeof Chart !== 'undefined') {
        createIncomeChart(incomeChart);
    }
    
    // Appointments chart
    const appointmentsChart = document.getElementById('appointmentsChart');
    if (appointmentsChart && typeof Chart !== 'undefined') {
        createAppointmentsChart(appointmentsChart);
    }
    
    // Visitors chart
    const visitorsChart = document.getElementById('visitorsChart');
    if (visitorsChart && typeof Chart !== 'undefined') {
        createVisitorsChart(visitorsChart);
    }
}

function createIncomeChart(canvas) {
    // This would be populated with real data from the server
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'আয়',
                data: [],
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Custom validation rules
    addCustomValidationRules();
}

function addCustomValidationRules() {
    // Phone number validation
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            const phoneRegex = /^[+]?[0-9\s\-()]+$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.setCustomValidity('সঠিক ফোন নম্বর দিন');
            } else {
                this.setCustomValidity('');
            }
        });
    });
    
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value && !this.validity.valid) {
                this.setCustomValidity('সঠিক ইমেইল ঠিকানা দিন');
            } else {
                this.setCustomValidity('');
            }
        });
    });
}






// AJAX functions
function makeAjaxRequest(url, method = 'GET', data = null) {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: data ? JSON.stringify(data) : null
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    });
}

// Dashboard data refresh
function refreshDashboardData() {
    if (window.location.pathname.includes('dashboard.php')) {
        makeAjaxRequest('../api/dashboard_stats.php')
            .then(data => {
                updateDashboardStats(data);
            })
            .catch(error => {
                console.error('Failed to refresh dashboard data:', error);
            });
    }
}

function updateDashboardStats(data) {
    // Update statistics cards
    Object.keys(data.stats || {}).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = data.stats[key];
        }
    });
}

// Utility functions
function confirmDelete(message = 'আপনি কি নিশ্চিত যে এটি ডিলিট করতে চান?') {
    return confirm(message);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('bn-BD');
}

function formatTime(timeString) {
    const time = new Date(`2000-01-01 ${timeString}`);
    return time.toLocaleTimeString('bn-BD', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('bn-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0
    }).format(amount);
}

// Export functions for global use
window.AdminPanel = {
    showNotification,
    confirmDelete,
    formatDate,
    formatTime,
    formatCurrency,
    makeAjaxRequest
};
