// ===== NOTIFICATION SYSTEM (simplified) =====

// ----- CLOCK -----
function startClock() {
    const el = document.getElementById('clock');
    if (!el) return;
    const tick = () => el.innerText = new Date().toLocaleTimeString('vi-VN', { hour12: false });
    tick();
    setInterval(tick, 1000);
}

// ----- BADGE (đếm chưa đọc) -----
async function loadNotifBadge() {
    try {
        const userRaw = localStorage.getItem('user_info');
        if (!userRaw) return;
        const user = JSON.parse(userRaw);
        const userId = user.id || user.id_nguoidung;
        if (!userId) return;

        const res = await fetch(`${API_BASE}/canhbao/list.php?user_id=${userId}&limit=50`);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            const unread = json.data.filter(i => !i.da_doc).length;
            const badge = document.getElementById('notif-badge');
            if (badge) {
                badge.innerText = unread > 9 ? '9+' : unread;
                unread > 0 ? badge.classList.add('has-notif') : badge.classList.remove('has-notif');
            }
        }
    } catch (e) {
        // API chưa sẵn sàng
    }
}

document.addEventListener('DOMContentLoaded', () => {
    startClock();
    loadNotifBadge();
    setInterval(loadNotifBadge, 60000);
});
