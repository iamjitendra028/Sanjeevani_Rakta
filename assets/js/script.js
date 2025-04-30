// Blood Bank Management System - Main JavaScript File

// DOM Ready Handler
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all interactive components
    initFormValidations();
    initTableSorting();
    initTooltips();
    
    // Handle blood type icon hover effects
    const bloodTypeCards = document.querySelectorAll('.blood-type');
    bloodTypeCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

// Form Validations
function initFormValidations() {
    // Donor Registration Form Validation
    const donorForm = document.querySelector('form[action*="donor_register.php"]');
    if (donorForm) {
        donorForm.addEventListener('submit', function(event) {
            if (!validateDonorForm()) {
                event.preventDefault();
            }
        });
    }
    
    // Blood Request Form Validation
    const requestForm = document.querySelector('form[action*="request.php"]');
    if (requestForm) {
        requestForm.addEventListener('submit', function(event) {
            if (!validateRequestForm()) {
                event.preventDefault();
            }
        });
    }
    
    // Registration Form Validation
    const registerForm = document.querySelector('form[id="registration-form"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            if (!validateRegistrationForm()) {
                event.preventDefault();
            }
        });
    }
}

// Donor Form Validation
function validateDonorForm() {
    const name = document.getElementById('name');
    const bloodType = document.getElementById('blood_type');
    const phone = document.getElementById('phone');
    
    if (!name || !bloodType || !phone) {
        return true; // Elements not found, skip client-side validation
    }

    if (name.value.trim() === '' || bloodType.value === '' || phone.value.trim() === '') {
        alert('Please fill in all required fields');
        return false;
    }

    // Basic phone validation
    const phoneRegex = /^[0-9+\- ]{10,15}$/;
    if (!phoneRegex.test(phone.value.trim())) {
        alert('Please enter a valid phone number');
        return false;
    }
    
    // Email validation if provided
    const email = document.getElementById('email');
    if (email && email.value.trim() !== '') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value.trim())) {
            alert('Please enter a valid email address');
            return false;
        }
    }
    
    return true;
}

// Blood Request Form Validation
function validateRequestForm() {
    const bloodType = document.getElementById('blood_type');
    const contact = document.getElementById('contact');
    
    if (!bloodType || !contact) {
        return true; // Elements not found, skip client-side validation
    }

    if (bloodType.value === '' || contact.value.trim() === '') {
        alert('Please fill in all required fields');
        return false;
    }

    // Basic contact validation
    if (contact.value.trim().length < 10) {
        alert('Please enter valid contact information');
        return false;
    }
    
    return true;
}

// Registration Form Validation
function validateRegistrationForm() {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (!username || !password || !confirmPassword) {
        return true; // Elements not found, skip client-side validation
    }
    
    if (username.value.trim() === '') {
        alert('Please enter a username');
        return false;
    }
    
    if (password.value.length < 6) {
        alert('Password must be at least 6 characters long');
        return false;
    }
    
    if (password.value !== confirmPassword.value) {
        alert('Passwords do not match');
        return false;
    }
    
    return true;
}

// Table Sorting
function initTableSorting() {
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            header.addEventListener('click', function() {
                sortTable(table, index);
            });
            header.style.cursor = 'pointer';
            header.title = 'Click to sort';
        });
    });
}

function sortTable(table, columnIndex) {
    const rows = Array.from(table.querySelectorAll('tr')).slice(1); // Skip header
    const isAscending = table.getAttribute('data-sort') !== 'asc';
    const direction = isAscending ? 1 : -1;
    
    // Sort rows
    rows.sort((rowA, rowB) => {
        const cellA = rowA.querySelectorAll('td')[columnIndex];
        const cellB = rowB.querySelectorAll('td')[columnIndex];
        
        if (!cellA || !cellB) return 0;
        
        const valueA = cellA.textContent.trim();
        const valueB = cellB.textContent.trim();
        
        if (!isNaN(valueA) && !isNaN(valueB)) {
            return direction * (Number(valueA) - Number(valueB));
        } else {
            return direction * valueA.localeCompare(valueB);
        }
    });
    
    // Update table
    const tbody = table.querySelector('tbody') || table;
    rows.forEach(row => tbody.appendChild(row));
    
    table.setAttribute('data-sort', isAscending ? 'asc' : 'desc');
}

// Tooltips
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.style.position = 'relative';
        element.style.cursor = 'help';
        
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.position = 'absolute';
            tooltip.style.bottom = '100%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translateX(-50%)';
            tooltip.style.padding = '5px 10px';
            tooltip.style.backgroundColor = 'rgba(0,0,0,0.8)';
            tooltip.style.color = 'white';
            tooltip.style.borderRadius = '4px';
            tooltip.style.fontSize = '0.8em';
            tooltip.style.whiteSpace = 'nowrap';
            tooltip.style.zIndex = '100';
            
            this.appendChild(tooltip);
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('div');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });
}