// Main JavaScript for Healthcare Website

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeCountdown();
    initializePopups();
    initializeWhatsApp();
    initializeAppDownload();
    initializeGallery();
    initializeForms();
    initializeNotifications();
});

// Countdown timer functionality
function initializeCountdown() {
    const countdown = document.getElementById('countdown');
    if (!countdown) return;

    function updateCountdown() {
        // Set target date (you can modify this or get from server)
        const targetDate = new Date();
        targetDate.setDate(targetDate.getDate() + 7); // 7 days from now
        
        const now = new Date().getTime();
        const target = targetDate.getTime();
        const difference = target - now;

        if (difference > 0) {
            const days = Math.floor(difference / (1000 * 60 * 60 * 24));
            const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((difference % (1000 * 60)) / 1000);

            document.getElementById('days').textContent = days.toString().padStart(2, '0');
            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
        } else {
            document.getElementById('days').textContent = '00';
            document.getElementById('hours').textContent = '00';
            document.getElementById('minutes').textContent = '00';
            document.getElementById('seconds').textContent = '00';
        }
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
}

// Popup management
function initializePopups() {
    // Check for active popups
    setTimeout(() => {
        showActivePopups();
    }, 2000); // Show popups after 2 seconds

    // Close popup functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('popup-close') || e.target.classList.contains('popup-overlay')) {
            closePopup(e.target.closest('.popup-overlay'));
        }
    });
}

function showActivePopups() {
    fetch('api/get_popups.php')
        .then(response => response.json())
        .then(popups => {
            popups.forEach(popup => {
                if (shouldShowPopup(popup)) {
                    displayPopup(popup);
                }
            });
        })
        .catch(error => console.error('Error loading popups:', error));
}

function shouldShowPopup(popup) {
    const storageKey = `popup_${popup.id}_shown`;
    const lastShown = localStorage.getItem(storageKey);
    
    if (!lastShown) return true;
    
    const daysSinceShown = (Date.now() - parseInt(lastShown)) / (1000 * 60 * 60 * 24);
    return daysSinceShown >= (popup.frequency_days || 1);
}

function displayPopup(popup) {
    const popupHtml = `
        <div class="popup-overlay" data-popup-id="${popup.id}">
            <div class="popup-content">
                <button class="popup-close">&times;</button>
                <div class="text-center">
                    ${popup.image ? `<img src="${popup.image}" alt="${popup.title}" class="img-fluid mb-3" style="max-height: 200px;">` : ''}
                    <h3 class="text-primary">${popup.title}</h3>
                    <p>${popup.content}</p>
                    ${popup.button_text && popup.button_url ? `<a href="${popup.button_url}" class="btn btn-primary">${popup.button_text}</a>` : ''}
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', popupHtml);
    
    // Mark as shown
    localStorage.setItem(`popup_${popup.id}_shown`, Date.now().toString());
}

function closePopup(popupOverlay) {
    if (popupOverlay) {
        popupOverlay.remove();
    }
}

// WhatsApp integration
function initializeWhatsApp() {
    const whatsappFloat = document.querySelector('.whatsapp-float');
    if (whatsappFloat) {
        whatsappFloat.addEventListener('click', function(e) {
            e.preventDefault();
            const phoneNumber = this.dataset.phone || '+8801700000000';
            const message = 'আমি আপনার সেবা নিতে চাই।';
            const url = `https://wa.me/${phoneNumber.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(message)}`;
            window.open(url, '_blank');
        });
    }
}

// App download banner
function initializeAppDownload() {
    const appBanner = document.querySelector('.app-download-banner');
    if (appBanner) {
        const closeBtn = appBanner.querySelector('.app-download-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                appBanner.style.display = 'none';
                localStorage.setItem('app_banner_closed', 'true');
            });
        }
        
        // Show banner if not previously closed
        if (localStorage.getItem('app_banner_closed') === 'true') {
            appBanner.style.display = 'none';
        }
    }
}

// Gallery functionality
function initializeGallery() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const mediaType = this.dataset.type;
            const mediaSrc = this.dataset.src;
            const mediaTitle = this.dataset.title;
            
            if (mediaType === 'image') {
                showImageModal(mediaSrc, mediaTitle);
            } else if (mediaType === 'video') {
                showVideoModal(mediaSrc, mediaTitle);
            }
        });
    });
}

function showImageModal(src, title) {
    const modalHtml = `
        <div class="popup-overlay">
            <div class="popup-content" style="max-width: 800px;">
                <button class="popup-close">&times;</button>
                <img src="${src}" alt="${title}" class="img-fluid">
                <h5 class="text-center mt-3">${title}</h5>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function showVideoModal(src, title) {
    const isYouTube = src.includes('youtube.com') || src.includes('youtu.be');
    
    const modalHtml = `
        <div class="popup-overlay">
            <div class="popup-content" style="max-width: 800px;">
                <button class="popup-close">&times;</button>
                <div class="video-container">
                    ${isYouTube ? 
                        `<iframe src="${getYouTubeEmbedUrl(src)}" frameborder="0" allowfullscreen></iframe>` :
                        `<video controls><source src="${src}" type="video/mp4"></video>`
                    }
                </div>
                <h5 class="text-center mt-3">${title}</h5>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function getYouTubeEmbedUrl(url) {
    const videoId = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/);
    return videoId ? `https://www.youtube.com/embed/${videoId[1]}` : url;
}

// Form handling
function initializeForms() {
    // Appointment form
    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', handleAppointmentSubmit);
    }
    
    // Review form
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', handleReviewSubmit);
    }
    
    // Contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmit);
    }
    
    // Track appointment form
    const trackForm = document.getElementById('trackForm');
    if (trackForm) {
        trackForm.addEventListener('submit', handleTrackSubmit);
    }
    
    // Coupon validation
    const couponInput = document.getElementById('couponCode');
    if (couponInput) {
        couponInput.addEventListener('blur', validateCoupon);
    }
}

// ✅ ফিক্স করা অ্যাপয়েন্টমেন্ট ফাংশন
function handleAppointmentSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    fetch('appointment.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('অ্যাপয়েন্টমেন্ট সফলভাবে বুক হয়েছে!', 'success');
            e.target.reset();
            if (data.appointmentNumber) {
                showAppointmentSuccess(data.appointmentNumber);
            }
        } else {
            showNotification(data.message || 'একটি ত্রুটি ঘটেছে', 'error');
        }
    })
    .catch(error => {
        showNotification('নেটওয়ার্ক ত্রুটি ঘটেছে', 'error');
    })
    .finally(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    });
}

// ✅ ফিক্স করা রিভিউ ফাংশন - সঠিক URL
function handleReviewSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // ✅ এখানে সঠিক URL - reviews.php (api/review.php নয়)
    fetch('reviews.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('রিভিউ সফলভাবে জমা হয়েছে!', 'success');
            e.target.reset();
            // Reset star rating
            document.querySelectorAll('.rating-star i').forEach(star => {
                star.className = 'bi bi-star fs-4 text-muted';
            });
            document.querySelectorAll('input[name="rating"]').forEach(radio => {
                radio.checked = false;
            });
        } else {
            showNotification(data.message || 'একটি ত্রুটি ঘটেছে', 'error');
        }
    })
    .catch(error => {
        console.error('Review error:', error);
        showNotification('নেটওয়ার্ক ত্রুটি ঘটেছে', 'error');
    })
    .finally(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    });
}

// ✅ ফিক্স করা কন্টাক্ট ফাংশন
function handleContactSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    fetch('contact.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('বার্তা সফলভাবে পাঠানো হয়েছে!', 'success');
            e.target.reset();
        } else {
            showNotification(data.message || 'একটি ত্রুটি ঘটেছে', 'error');
        }
    })
    .catch(error => {
        showNotification('নেটওয়ার্ক ত্রুটি ঘটেছে', 'error');
    })
    .finally(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    });
}

// ✅ ফিক্স করা ট্র্যাক ফাংশন - সঠিক URL
function handleTrackSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // ✅ এখানে সঠিক URL - track.php (api/track.php নয়)
    fetch('track.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('trackResult');
        
        if (data.success) {
            displayAppointmentDetails(data.appointment, resultDiv);
        } else {
            resultDiv.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Track error:', error);
        document.getElementById('trackResult').innerHTML = 
            '<div class="alert alert-danger">নেটওয়ার্ক ত্রুটি ঘটেছে</div>';
    })
    .finally(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    });
}

function validateCoupon() {
    const couponCode = this.value.trim();
    const serviceId = document.getElementById('service')?.value;
    
    if (!couponCode) return;
    
    fetch('api/validate_coupon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            code: couponCode,
            service_id: serviceId
        })
    })
    .then(response => response.json())
    .then(data => {
        const feedback = document.getElementById('couponFeedback');
        
        if (data.valid) {
            feedback.innerHTML = `<div class="text-success small">কুপন প্রয়োগ হয়েছে! ${data.discount}% ছাড়</div>`;
            updatePriceWithDiscount(data.discount);
        } else {
            feedback.innerHTML = `<div class="text-danger small">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Coupon validation error:', error);
    });
}

function updatePriceWithDiscount(discount) {
    const priceElement = document.getElementById('totalPrice');
    if (priceElement) {
        const originalPrice = parseFloat(priceElement.dataset.original || priceElement.textContent);
        const discountAmount = (originalPrice * discount) / 100;
        const finalPrice = originalPrice - discountAmount;
        
        priceElement.innerHTML = `
            <span class="text-muted text-decoration-line-through">৳${originalPrice}</span>
            <span class="text-success fw-bold">৳${finalPrice}</span>
        `;
    }
}

// Notification system
function initializeNotifications() {
    // Auto-remove notifications after 5 seconds
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('notification')) {
            e.target.remove();
        }
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
        color: ${type === 'success' ? '#155724' : '#721c24'};
        border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};
        border-radius: 5px;
        z-index: 9999;
        cursor: pointer;
        font-weight: 500;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function showAppointmentSuccess(appointmentNumber) {
    const modalHtml = `
        <div class="popup-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;">
            <div class="popup-content" style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; text-align: center; position: relative;">
                <button class="popup-close" style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                <div class="text-success mb-3">
                    <i class="bi bi-check-circle" style="font-size: 3rem;"></i>
                </div>
                <h3 class="text-primary">অ্যাপয়েন্টমেন্ট সফল!</h3>
                <p>আপনার অ্যাপয়েন্টমেন্ট নম্বর:</p>
                <h4 style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">${appointmentNumber}</h4>
                <p class="small text-muted">এই নম্বর দিয়ে আপনি আপনার অ্যাপয়েন্টমেন্ট ট্র্যাক করতে পারবেন।</p>
                <button class="btn btn-primary" onclick="this.closest('.popup-overlay').remove()">ধন্যবাদ</button>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function displayAppointmentDetails(appointment, container) {
    const statusClass = appointment.status === 'approved' ? 'success' : 
                      appointment.status === 'cancelled' ? 'danger' : 'warning';
    
    const statusText = appointment.status === 'approved' ? 'অনুমোদিত' : 
                      appointment.status === 'cancelled' ? 'বাতিল' : 'অপেক্ষমাণ';
    
    const html = `
        <div class="card border-0 shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>অ্যাপয়েন্টমেন্ট বিস্তারিত</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">অ্যাপয়েন্টমেন্ট নম্বর</label>
                        <div class="fw-bold">${appointment.appointment_number}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">স্ট্যাটাস</label>
                        <div><span class="badge bg-${statusClass} px-3 py-2">${statusText}</span></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">রোগীর নাম</label>
                        <div class="fw-bold">${appointment.name}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">ফোন নম্বর</label>
                        <div>${appointment.phone}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">সেবা</label>
                        <div>${appointment.service}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">তারিখ ও সময়</label>
                        <div>${appointment.date} - ${appointment.time}</div>
                    </div>
                    ${appointment.notes ? `
                    <div class="col-12">
                        <label class="form-label text-muted">নোট</label>
                        <div class="bg-light p-3 rounded">${appointment.notes}</div>
                    </div>
                    ` : ''}
                    <div class="col-12">
                        <label class="form-label text-muted">বুকিং তারিখ</label>
                        <div>${appointment.created_at}</div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

// CSS for loading button
const style = document.createElement('style');
style.textContent = `
    .btn.loading {
        position: relative;
        color: transparent !important;
    }
    .btn.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: button-loading-spinner 1s ease infinite;
    }
    @keyframes button-loading-spinner {
        from { transform: rotate(0turn); }
        to { transform: rotate(1turn); }
    }
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }
    .popup-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 90vw;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }
`;
document.head.appendChild(style);