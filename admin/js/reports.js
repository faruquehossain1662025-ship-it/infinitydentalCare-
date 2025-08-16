// Global variables
let selectedTeeth = [];
let currentReportData = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDentalChart();
});

// Patient selection handler
function updatePatientInfo() {
    const select = document.getElementById('patient_select');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('patient_name').value = selectedOption.dataset.name || '';
        document.getElementById('patient_age').value = selectedOption.dataset.age || '';
        document.getElementById('patient_phone').value = selectedOption.dataset.phone || '';
        document.getElementById('patient_address').value = selectedOption.dataset.address || '';
    } else {
        // Clear fields
        document.getElementById('patient_name').value = '';
        document.getElementById('patient_age').value = '';
        document.getElementById('patient_phone').value = '';
        document.getElementById('patient_address').value = '';
    }
}

// Dental chart functions
function initializeDentalChart() {
    selectedTeeth = [];
    updateAffectedTeethInput();
}

function toggleTooth(element) {
    const toothNumber = element.dataset.tooth;
    
    if (selectedTeeth.includes(toothNumber)) {
        // Remove from selected
        selectedTeeth = selectedTeeth.filter(t => t !== toothNumber);
        element.classList.remove('affected');
    } else {
        // Add to selected
        selectedTeeth.push(toothNumber);
        element.classList.add('affected');
    }
    
    updateAffectedTeethInput();
}

function updateAffectedTeethInput() {
    document.getElementById('affected_teeth_input').value = selectedTeeth.join(',');
}

// View report
function viewReport(reportId) {
    const report = window.reportsData.find(r => r.id === reportId);
    if (!report) {
        alert('রিপোর্ট খুঁজে পাওয়া যায়নি!');
        return;
    }
    
    currentReportData = report;
    generateReportHTML(report);
    
    const modal = new bootstrap.Modal(document.getElementById('viewReportModal'));
    modal.show();
}

// Generate report HTML
function generateReportHTML(report) {
    const settings = window.clinicSettings;
    const currentDate = new Date().toLocaleDateString('bn-BD');
    
    const affectedTeethList = report.affected_teeth ? 
        (Array.isArray(report.affected_teeth) ? report.affected_teeth : report.affected_teeth.split(','))
        .filter(t => t.trim())
        .join(', ') : 'কোনো নির্দিষ্ট দাঁত নির্বাচিত নয়';

    const html = `
        <div class="report-header">
            ${settings.logo_path && settings.logo_path !== 'uploads/logo.png' ? 
                `<img src="../${settings.logo_path}" alt="ক্লিনিক লোগো" class="clinic-logo">` : 
                '<i class="bi bi-heart-pulse text-primary" style="font-size: 3rem;"></i>'
            }
            <div class="clinic-name">${settings.clinic_name}</div>
            <div class="clinic-details">
                ${settings.clinic_address}<br>
                ফোন: ${settings.clinic_phone} | ইমেইল: ${settings.clinic_email}
            </div>
        </div>

        <div class="text-center mb-4">
            <h4 class="text-primary">ডেন্টাল মেডিকেল রিপোর্ট</h4>
            <p class="text-muted">রিপোর্ট নং: ${report.id.substring(0, 8).toUpperCase()}</p>
        </div>

        <div class="patient-info">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">রোগীর তথ্য</h6>
                    <table class="table table-borderless">
                        <tr><td><strong>নাম:</strong></td><td>${report.patient_name}</td></tr>
                        <tr><td><strong>বয়স:</strong></td><td>${report.patient_age || 'উল্লেখ নেই'} বছর</td></tr>
                        <tr><td><strong>ফোন:</strong></td><td>${report.patient_phone || 'উল্লেখ নেই'}</td></tr>
                        <tr><td><strong>ঠিকানা:</strong></td><td>${report.patient_address || 'উল্লেখ নেই'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">রিপোর্টের তথ্য</h6>
                    <table class="table table-borderless">
                        <tr><td><strong>রিপোর্টের ধরন:</strong></td><td><span class="badge bg-info">${report.report_type}</span></td></tr>
                        <tr><td><strong>তারিখ:</strong></td><td>${formatBengaliDate(report.created_at)}</td></tr>
                        <tr><td><strong>ডাক্তার:</strong></td><td>${report.doctor_name || settings.doctor_name}</td></tr>
                        <tr><td><strong>আক্রান্ত দাঁত:</strong></td><td>${affectedTeethList}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="diagnosis-section">
            <h6 class="text-primary mb-3">ক্লিনিক্যাল তথ্য</h6>
            
            <div class="mb-3">
                <strong>প্রধান সমস্যা (Chief Complaint):</strong>
                <p class="ms-3">${report.chief_complaint || 'উল্লেখ নেই'}</p>
            </div>
            
            ${report.clinical_findings ? `
            <div class="mb-3">
                <strong>ক্লিনিক্যাল ফাইন্ডিংস:</strong>
                <p class="ms-3">${report.clinical_findings}</p>
            </div>
            ` : ''}
            
            <div class="mb-3">
                <strong>ডায়াগনোসিস:</strong>
                <p class="ms-3 text-danger fw-bold">${report.diagnosis}</p>
            </div>
        </div>

        ${report.treatment_plan || report.treatment_done ? `
        <div class="treatment-plan">
            <h6 class="text-primary mb-3">চিকিৎসার তথ্য</h6>
            
            ${report.treatment_plan ? `
            <div class="mb-3">
                <strong>চিকিৎসা পরিকল্পনা:</strong>
                <p class="ms-3">${report.treatment_plan}</p>
            </div>
            ` : ''}
            
            ${report.treatment_done ? `
            <div class="mb-3">
                <strong>সম্পন্ন চিকিৎসা:</strong>
                <p class="ms-3">${report.treatment_done}</p>
            </div>
            ` : ''}
        </div>
        ` : ''}

        ${report.prescription ? `
        <div class="prescription-section">
            <h6 class="text-primary mb-3">প্রেসক্রিপশন</h6>
            <div class="mb-3">
                <pre style="white-space: pre-wrap; font-family: inherit;">${report.prescription}</pre>
            </div>
        </div>
        ` : ''}

        ${report.follow_up_date || report.follow_up_instructions ? `
        <div class="mb-4">
            <h6 class="text-primary mb-3">ফলো-আপ তথ্য</h6>
            
            ${report.follow_up_date ? `
            <div class="mb-2">
                <strong>পরবর্তী অ্যাপয়েন্টমেন্ট:</strong> 
                <span class="badge bg-warning text-dark">${formatBengaliDate(report.follow_up_date)}</span>
            </div>
            ` : ''}
            
            ${report.follow_up_instructions ? `
            <div class="mb-2">
                <strong>নির্দেশনা:</strong>
                <p class="ms-3">${report.follow_up_instructions}</p>
            </div>
            ` : ''}
        </div>
        ` : ''}

        ${report.notes ? `
        <div class="mb-4">
            <h6 class="text-primary mb-3">অতিরিক্ত মন্তব্য</h6>
            <p>${report.notes}</p>
        </div>
        ` : ''}

        <div class="doctor-signature">
            <div class="signature-line"></div>
            <p class="mb-0"><strong>${settings.doctor_name}</strong></p>
            <p class="mb-0">${settings.doctor_degree}</p>
            <p class="mb-0">${settings.doctor_reg}</p>
            <small class="text-muted">ডিজিটাল স্বাক্ষর</small>
        </div>
    `;

    document.getElementById('report-content').innerHTML = html;
}

// Edit report
function editReport(reportId) {
    const report = window.reportsData.find(r => r.id === reportId);
    if (!report) {
        alert('রিপোর্ট খুঁজে পাওয়া যায়নি!');
        return;
    }
    
    // Populate form fields
    document.querySelector('input[name="action"]').value = 'edit';
    
    // Add hidden ID field
    let idField = document.querySelector('input[name="id"]');
    if (!idField) {
        idField = document.createElement('input');
        idField.type = 'hidden';
        idField.name = 'id';
        document.querySelector('#addReportModal form').appendChild(idField);
    }
    idField.value = reportId;
    
    // Fill form
    document.querySelector('input[name="patient_name"]').value = report.patient_name;
    document.querySelector('input[name="patient_age"]').value = report.patient_age || '';
    document.querySelector('input[name="patient_phone"]').value = report.patient_phone || '';
    document.querySelector('textarea[name="patient_address"]').value = report.patient_address || '';
    document.querySelector('select[name="report_type"]').value = report.report_type;
    document.querySelector('textarea[name="chief_complaint"]').value = report.chief_complaint || '';
    document.querySelector('textarea[name="clinical_findings"]').value = report.clinical_findings || '';
    document.querySelector('textarea[name="diagnosis"]').value = report.diagnosis;
    document.querySelector('textarea[name="treatment_plan"]').value = report.treatment_plan || '';
    document.querySelector('textarea[name="treatment_done"]').value = report.treatment_done || '';
    document.querySelector('textarea[name="prescription"]').value = report.prescription || '';
    document.querySelector('input[name="follow_up_date"]').value = report.follow_up_date || '';
    document.querySelector('textarea[name="follow_up_instructions"]').value = report.follow_up_instructions || '';
    document.querySelector('textarea[name="notes"]').value = report.notes || '';
    
    // Handle affected teeth
    if (report.affected_teeth) {
        const teeth = Array.isArray(report.affected_teeth) ? report.affected_teeth : report.affected_teeth.split(',');
        selectedTeeth = teeth.filter(t => t.trim());
        
        // Clear all teeth selections
        document.querySelectorAll('.tooth').forEach(tooth => {
            tooth.classList.remove('affected');
        });
        
        // Mark selected teeth
        selectedTeeth.forEach(toothNumber => {
            const toothElement = document.querySelector(`.tooth[data-tooth="${toothNumber}"]`);
            if (toothElement) {
                toothElement.classList.add('affected');
            }
        });
        
        updateAffectedTeethInput();
    }
    
    // Change modal title
    document.querySelector('#addReportModal .modal-title').innerHTML = 
        '<i class="bi bi-pencil me-2"></i>রিপোর্ট সম্পাদনা করুন';
    
    const modal = new bootstrap.Modal(document.getElementById('addReportModal'));
    modal.show();
}

// Delete report
function deleteReport(reportId) {
    if (!confirm('আপনি কি নিশ্চিত যে এই রিপোর্টটি ডিলিট করতে চান? এই কাজটি পূর্বাবস্থায় ফিরানো যাবে না।')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="${reportId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Export functions
function exportReport(reportId, format) {
    const report = window.reportsData.find(r => r.id === reportId);
    if (!report) {
        alert('রিপোর্ট খুঁজে পাওয়া যায়নি!');
        return;
    }
    
    currentReportData = report;
    
    // Create temporary container for export
    const tempContainer = document.createElement('div');
    tempContainer.style.position = 'absolute';
    tempContainer.style.left = '-9999px';
    tempContainer.style.top = '0';
    tempContainer.style.width = '800px';
    tempContainer.style.background = 'white';
    tempContainer.style.padding = '40px';
    tempContainer.className = 'report-template';
    
    generateReportHTML(report);
    tempContainer.innerHTML = document.getElementById('report-content').innerHTML;
    document.body.appendChild(tempContainer);
    
    if (format === 'pdf') {
        exportToPDF(tempContainer, report);
    } else if (format === 'png' || format === 'jpg') {
        exportToImage(tempContainer, report, format);
    }
    
    // Clean up
    setTimeout(() => {
        document.body.removeChild(tempContainer);
    }, 1000);
}

function exportCurrentReport(format) {
    if (!currentReportData) {
        alert('কোনো রিপোর্ট সিলেক্ট করা নেই!');
        return;
    }
    
    const reportContent = document.getElementById('report-content');
    
    if (format === 'pdf') {
        exportToPDF(reportContent, currentReportData);
    } else if (format === 'png' || format === 'jpg') {
        exportToImage(reportContent, currentReportData, format);
    }
}

function exportToPDF(element, report) {
    const { jsPDF } = window.jspdf;
    
    html2canvas(element, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
        width: 800,
        height: element.scrollHeight
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        
        const fileName = `dental-report-${report.patient_name}-${report.id.substring(0, 8)}.pdf`;
        pdf.save(fileName);
    }).catch(error => {
        console.error('PDF export error:', error);
        alert('PDF এক্সপোর্ট করতে সমস্যা হয়েছে!');
    });
}

function exportToImage(element, report, format) {
    html2canvas(element, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
        width: 800,
        height: element.scrollHeight
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = `dental-report-${report.patient_name}-${report.id.substring(0, 8)}.${format}`;
        link.href = canvas.toDataURL(`image/${format}`, 1.0);
        link.click();
    }).catch(error => {
        console.error('Image export error:', error);
        alert(`${format.toUpperCase()} এক্সপোর্ট করতে সমস্যা হয়েছে!`);
    });
}

// Print current report
function printCurrentReport() {
    const reportContent = document.getElementById('report-content');
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>ডেন্টাল রিপোর্ট প্রিন্ট</title>
                <meta charset="UTF-8">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
                <link href="css/admin.css" rel="stylesheet">
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700&display=swap');
                    body { font-family: 'Noto Sans Bengali', sans-serif; background: white; }
                    @media print {
                        body { margin: 0; padding: 20px; }
                        .report-template { box-shadow: none; margin: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="report-template">
                    ${reportContent.innerHTML}
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        };
                    };
                </script>
            </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Filter reports by type
function filterReports(reportType) {
    const url = new URL(window.location);
    url.searchParams.set('filter_type', reportType);
    window.location = url;
}

// Utility functions
function formatBengaliDate(dateString) {
    const date = new Date(dateString);
    const bengaliMonths = [
        'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
        'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'
    ];
    
    const day = date.getDate();
    const month = bengaliMonths[date.getMonth()];
    const year = date.getFullYear();
    
    return `${day} ${month}, ${year}`;
}

// Reset form when modal is hidden
document.getElementById('addReportModal').addEventListener('hidden.bs.modal', function() {
    // Reset form
    this.querySelector('form').reset();
    
    // Reset action to add
    document.querySelector('input[name="action"]').value = 'add';
    
    // Remove ID field if exists
    const idField = document.querySelector('input[name="id"]');
    if (idField) {
        idField.remove();
    }
    
    // Reset title
    document.querySelector('#addReportModal .modal-title').innerHTML = 
        '<i class="bi bi-file-earmark-medical-fill me-2"></i>নতুন ডেন্টাল রিপোর্ট';
    
    // Clear dental chart
    selectedTeeth = [];
    document.querySelectorAll('.tooth').forEach(tooth => {
        tooth.classList.remove('affected');
    });
    updateAffectedTeethInput();
});

// Add some helper functions for better UX
function showPatients() {
    alert('রোগীদের তালিকা ফিচার শীঘ্রই যোগ করা হবে।');
}

function showAppointments() {
    alert('অ্যাপয়েন্টমেন্ট ফিচার শীঘ্রই যোগ করা হবে।');
}
