/* ==================== UTILS.JS ==================== */
/* Helper Functions */

/**
 * Get User ID from localStorage
 * @returns {number|null} User ID or null
 */
function getUserId() {
    try {
        const userStr = localStorage.getItem('user_info');
        if (!userStr) return null;
        
        const user = JSON.parse(userStr);
        return user.id || user.id_nguoidung || null;
    } catch (error) {
        console.error('Error getting user ID:', error);
        return null;
    }
}

/**
 * Check if user is logged in
 * @returns {boolean}
 */
function isLoggedIn() {
    return localStorage.getItem('is_logged_in') === 'true';
}

/**
 * Redirect to login if not authenticated
 */
function checkLogin() {
    if (!isLoggedIn()) {
        window.location.href = 'login.php';
        return false;
    }
    return true;
}

/**
 * Logout user
 */
function logout() {
    localStorage.clear();
    window.location.href = 'login.php';
}

/**
 * Show alert message
 * @param {string} message 
 * @param {string} type - 'success', 'error', 'warning', 'info'
 */
function showAlert(message, type = 'error') {
    const alertDiv = document.getElementById('alert-message');
    if (!alertDiv) {
        alert(message);
        return;
    }
    
    // Remove existing classes
    alertDiv.className = 'alert';
    
    // Add type class
    alertDiv.classList.add(`alert-${type}`);
    
    // Set message
    const icon = {
        'success': 'check_circle',
        'error': 'error',
        'warning': 'warning',
        'info': 'info'
    }[type] || 'info';
    
    alertDiv.innerHTML = `
        <span class="material-symbols-outlined">${icon}</span>
        <p>${message}</p>
    `;
    
    // Show alert
    alertDiv.classList.remove('hidden');
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        hideAlert();
    }, 5000);
}

/**
 * Hide alert message
 */
function hideAlert() {
    const alertDiv = document.getElementById('alert-message');
    if (alertDiv) {
        alertDiv.classList.add('hidden');
    }
}

/**
 * Set loading state for button
 * @param {HTMLButtonElement} button 
 * @param {boolean} isLoading 
 */
function setButtonLoading(button, isLoading) {
    if (!button) return;
    
    if (isLoading) {
        button.disabled = true;
        button.classList.add('btn-loading');
        
        // Save original text
        if (!button.dataset.originalText) {
            button.dataset.originalText = button.innerHTML;
        }
        
        // Show loading spinner
        button.innerHTML = `
            <span class="spinner"></span>
            <span>Đang xử lý...</span>
        `;
    } else {
        button.disabled = false;
        button.classList.remove('btn-loading');
        
        // Restore original text
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
        }
    }
}

/**
 * Format date to Vietnamese format
 * @param {string} dateString - ISO date string
 * @returns {string} Formatted date
 */
function formatDateVN(dateString) {
    if (!dateString) return '';
    
    const [datePart, timePart] = dateString.split(' ');
    const [year, month, day] = datePart.split('-');
    
    return `${day}/${month}/${year} <span class="text-muted">${timePart || ''}</span>`;
}

/**
 * Validate email format
 * @param {string} email 
 * @returns {boolean}
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate phone number (Vietnamese format)
 * @param {string} phone 
 * @returns {boolean}
 */
function isValidPhone(phone) {
    const phoneRegex = /^0[0-9]{9,10}$/;
    return phoneRegex.test(phone);
}

/**
 * Debounce function
 * @param {Function} func 
 * @param {number} wait 
 * @returns {Function}
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Fetch wrapper with error handling
 * @param {string} url 
 * @param {object} options 
 * @returns {Promise<object>}
 */
async function fetchAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return { success: true, data };
        
    } catch (error) {
        console.error('Fetch error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Format number with thousand separator
 * @param {number} num 
 * @returns {string}
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Get query parameter from URL
 * @param {string} param 
 * @returns {string|null}
 */
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

/**
 * Copy text to clipboard
 * @param {string} text 
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showAlert('Đã sao chép vào clipboard', 'success');
    } catch (err) {
        console.error('Failed to copy:', err);
        showAlert('Không thể sao chép', 'error');
    }
}

/**
 * Update clock element
 * @param {string} elementId 
 */
function startClock(elementId = 'clock') {
    const updateClock = () => {
        const clockEl = document.getElementById(elementId);
        if (!clockEl) return;
        
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('vi-VN', { hour12: false });
    };
    
    updateClock();
    setInterval(updateClock, 1000);
}

/**
 * Safe JSON parse
 * @param {string} str 
 * @param {*} defaultValue 
 * @returns {*}
 */
function safeJSONParse(str, defaultValue = null) {
    try {
        return JSON.parse(str);
    } catch {
        return defaultValue;
    }
}