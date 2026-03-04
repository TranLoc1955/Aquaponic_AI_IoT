/**
 * Auth.js - Centralized Authentication & User Info Handler
 * Auto-loads username from localStorage and handles logout
 */

// Check if user is logged in
function checkAuth() {
    if (!localStorage.getItem('is_logged_in')) {
        window.location.href = 'login.php';
        return false;
    }
    return true;
}

// Display username in sidebar
function displayUsername() {
    try {
        const user = JSON.parse(localStorage.getItem('user_info'));
        const displayElement = document.getElementById('user-display');

        if (user && displayElement) {
            displayElement.innerText = user.hoten || user.ten || "Admin";
        }
    } catch (error) {
        console.error('Error loading user info:', error);
    }
}

// Get current user ID
function getUserId() {
    try {
        const user = JSON.parse(localStorage.getItem('user_info'));
        return user.id || user.id_nguoidung || null;
    } catch {
        return null;
    }
}

// Logout function
function logout() {
    localStorage.clear();
    window.location.href = 'login.php';
}

// Auto-run on page load
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    displayUsername();
});
