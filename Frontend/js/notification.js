// ===== NOTIFICATION SYSTEM (simplified) =====

// ----- CLOCK -----
function startClock() {
    const el = document.getElementById('clock');
    if (!el) return;
    const tick = () => el.innerText = new Date().toLocaleTimeString('vi-VN', { hour12: false });
    tick();
    setInterval(tick, 1000);
}

// ----- BADGE (số thông báo chưa đọc – chuông màu đỏ) -----
function setBadgeVisible(badge, unread) {
    if (!badge) return;
    const n = parseInt(unread, 10) || 0;
    badge.textContent = n > 9 ? '9+' : (n > 0 ? String(n) : '');
    badge.classList.toggle('has-notif', n > 0);
    badge.style.display = n > 0 ? 'flex' : 'none';
    badge.style.visibility = n > 0 ? 'visible' : 'hidden';
}

async function loadNotifBadge() {
    const badge = document.getElementById('notif-badge');
    if (!badge) return;
    try {
        const userRaw = localStorage.getItem('user_info');
        if (!userRaw) {
            setBadgeVisible(badge, 0);
            return;
        }
        const user = JSON.parse(userRaw);
        const userId = user.id || user.id_nguoidung;
        if (!userId) {
            setBadgeVisible(badge, 0);
            return;
        }

        const apiBase = (typeof API_BASE !== 'undefined' ? API_BASE : 'http://localhost/Test/API').replace(/\/$/, '');
        const res = await fetch(apiBase + '/canhbao/get_canhbao.php?user_id=' + encodeURIComponent(userId));
        const json = await res.json();

        if (json.status === 'success') {
            setBadgeVisible(badge, json.unread);
        } else {
            setBadgeVisible(badge, 0);
        }
    } catch (e) {
        setBadgeVisible(badge, 0);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    startClock();
    loadNotifBadge();
    setInterval(loadNotifBadge, 15000);
});
