<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Thông báo - HydroSmart</title>
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <style>
        .notif-page-container { display: flex; height: 100vh; width: 100%; }
        .notif-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

        .notif-page-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 var(--space-10);
            background: var(--bg-header);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            flex-shrink: 0;
        }
        .notif-page-header h2 {
            font-size: var(--text-2xl);
            font-weight: var(--font-bold);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        .notif-page-header h2 .material-symbols-outlined { color: var(--primary); }
        .mark-all-btn {
            display: flex; align-items: center; gap: var(--space-2);
            padding: var(--space-2) var(--space-5);
            border-radius: var(--radius-xl);
            background: var(--primary-bg); border: 1px solid var(--primary);
            color: var(--primary); font-size: var(--text-sm); font-weight: var(--font-semibold);
            cursor: pointer; font-family: var(--font-main); transition: all var(--transition-base);
        }
        .mark-all-btn:hover { background: var(--primary); color: #fff; }

        .notif-content { flex: 1; overflow-y: auto; padding: var(--space-8) var(--space-10); }
        .notif-content-inner { max-width: 760px; margin: 0 auto; }

        .notif-tabs {
            display: flex; gap: var(--space-2); margin-bottom: var(--space-6);
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: var(--radius-xl); padding: var(--space-1); width: fit-content;
        }
        .notif-tab-btn {
            padding: var(--space-2) var(--space-5); border-radius: var(--radius-lg);
            border: none; background: none; font-size: var(--text-sm); font-weight: var(--font-medium);
            color: var(--text-secondary); cursor: pointer; font-family: var(--font-main);
            transition: all var(--transition-base);
        }
        .notif-tab-btn.active { background: var(--primary); color: #fff; box-shadow: var(--shadow-primary); }

        .notif-card-list { display: flex; flex-direction: column; gap: var(--space-3); }
        .notif-card {
            display: flex; align-items: flex-start; gap: var(--space-4);
            padding: var(--space-5); background: var(--bg-card);
            border: 1px solid var(--border-color); border-radius: var(--radius-xl);
            cursor: pointer; transition: all var(--transition-base); position: relative;
        }
        .notif-card:hover { border-color: var(--primary); box-shadow: var(--shadow-md); transform: translateX(4px); }
        .notif-card.unread { border-left: 3px solid var(--primary); background: var(--primary-bg); }
        .notif-card.unread::before {
            content: ''; position: absolute; top: 50%; right: var(--space-5);
            transform: translateY(-50%); width: 8px; height: 8px;
            background: var(--primary); border-radius: 50%;
        }
        .nc-icon {
            width: 44px; height: 44px; border-radius: var(--radius-full);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .nc-icon.warning  { background: #fff3cd; color: #f59e0b; }
        .nc-icon.danger   { background: #fde8e8; color: var(--danger); }
        .nc-icon.success  { background: #d1fae5; color: var(--success); }
        .nc-icon.info     { background: var(--primary-bg); color: var(--primary); }
        .nc-icon.ai-action { background: #f5f3ff; color: #7c3aed; }
        .nc-desc.ai-multiline { white-space: pre-line; }

        .nc-body { flex: 1; }
        .nc-title { font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-primary); margin-bottom: 4px; }
        .nc-desc  { font-size: var(--text-sm); color: var(--text-secondary); line-height: 1.5; margin-bottom: 6px; }
        .nc-time  { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }

        .notif-empty-state { text-align: center; padding: var(--space-12) 0; color: var(--text-muted); }
        .notif-empty-state .material-symbols-outlined { font-size: 64px; color: var(--border-dark); margin-bottom: var(--space-4); display: block; }
        .notif-empty-state h3 { font-size: var(--text-lg); font-weight: var(--font-semibold); color: var(--text-secondary); margin-bottom: var(--space-2); }

        .skeleton {
            background: linear-gradient(90deg, var(--bg-hover) 25%, var(--bg-card) 50%, var(--bg-hover) 75%);
            background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: var(--radius-lg);
        }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    </style>
</head>
<body>
<div class="notif-page-container">
    <?php include '../components/sidebar_light.php'; ?>
    <main class="notif-main">
        <div class="notif-page-header">
            <div class="header-left">
                <h2>
                    <span class="material-symbols-outlined">notifications</span>
                    Trung tâm thông báo
                </h2>
            </div>
            <button class="mark-all-btn" onclick="markAllReadCurrentTab()">
                <span class="material-symbols-outlined" style="font-size:18px;">done_all</span>
                Đánh dấu tất cả đã đọc
            </button>
        </div>

        <div class="notif-content">
            <div class="notif-content-inner">
                <div class="notif-tabs">
                    <button class="notif-tab-btn active" onclick="filterNotif('all', this)">Tất cả</button>
                    <button class="notif-tab-btn" onclick="filterNotif('unread', this)">Chưa đọc</button>
                    <button class="notif-tab-btn" onclick="filterNotif('warning', this)">Cảnh báo</button>
                    <button class="notif-tab-btn" onclick="filterNotif('ai', this)">Cảnh báo AI</button>
                </div>

                <div class="notif-card-list" id="notif-card-list">
                    <!-- Loading skeleton -->
                    <div class="notif-card">
                        <div class="skeleton" style="width:44px;height:44px;border-radius:50%;flex-shrink:0"></div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:8px;">
                            <div class="skeleton" style="height:16px;width:60%"></div>
                            <div class="skeleton" style="height:13px;width:90%"></div>
                            <div class="skeleton" style="height:11px;width:30%"></div>
                        </div>
                    </div>
                    <div class="notif-card">
                        <div class="skeleton" style="width:44px;height:44px;border-radius:50%;flex-shrink:0"></div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:8px;">
                            <div class="skeleton" style="height:16px;width:50%"></div>
                            <div class="skeleton" style="height:13px;width:80%"></div>
                            <div class="skeleton" style="height:11px;width:25%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../js/auth.js"></script>
<script src="../js/config.js"></script>
<script>
    let allNotifs    = [];
    let allAiNotifs  = [];
    let currentFilter = 'all';
    let currentUserId = null;

    const sensorIconMap = {
    'nhiet do':  { icon: 'thermostat', label: 'Nhiệt độ' },
    'nhiet_do':  { icon: 'thermostat', label: 'Nhiệt độ' },
    'do am':     { icon: 'water_drop', label: 'Độ ẩm' },
    'do_am':     { icon: 'water_drop', label: 'Độ ẩm' },
    'anh sang':  { icon: 'light_mode', label: 'Ánh sáng' },
    'anh_sang':  { icon: 'light_mode', label: 'Ánh sáng' },
    'muc nuoc':  { icon: 'water',      label: 'Mức nước' },
    'muc_nuoc':  { icon: 'water',      label: 'Mức nước' },
};

// Màu sắc theo mucdo từ DB
const mucDoMap = {
    'danger':  { cls: 'danger',  badge: '🔴 Nguy hiểm' },
    'warning': { cls: 'warning', badge: '🟡 Cảnh báo' },
};

function getInfo(loaicambien, mucdo) {
    const sensor = loaicambien
        ? (sensorIconMap[loaicambien.toLowerCase().trim()] || { icon: 'sensors', label: loaicambien })
        : { icon: 'warning', label: 'Hệ thống' };
    const level = mucDoMap[mucdo] || { cls: 'info', badge: 'Thông tin' };
    return { ...sensor, ...level };
}

function formatTime(str) {
    if (!str) return '';
    const d = new Date(str.replace(' ', 'T'));
    if (isNaN(d)) return str;
    const diff = Math.floor((new Date() - d) / 60000);
    if (diff < 1)    return 'Vừa xong';
    if (diff < 60)   return `${diff} phút trước`;
    if (diff < 1440) return `${Math.floor(diff/60)} giờ trước`;
    return d.toLocaleDateString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function renderList(items) {
    const el = document.getElementById('notif-card-list');
    if (!items || !items.length) {
        el.innerHTML = `<div class="notif-empty-state">
            <span class="material-symbols-outlined">notifications_off</span>
            <h3>Không có thông báo nào</h3>
            <p> Hệ thống chưa ghi nhận sự kiện nào cần thông báo.</p>
        </div>`;
        return;
    }

    el.innerHTML = items.map((item, idx) => {
        const info   = getInfo(item.loaicambien, item.mucdo);
        const isRead = item.trangthai == 1;

        // Dùng noidung từ DB (đã được sửa trong input.php)
        const desc = item.noidung || item.noi_dung || 'Hệ thống phát hiện giá trị bất thường';

        return `
        <div class="notif-card ${isRead ? '' : 'unread'}" onclick="markRead(${idx})">
            <div class="nc-icon ${info.cls} material-symbols-outlined">${info.icon}</div>
            <div class="nc-body">
                <div class="nc-title" style="display:flex;align-items:center;gap:8px;">
                    ${info.label}
                    <span style="font-size:11px;padding:2px 8px;border-radius:99px;font-weight:600;
                        background:${info.cls === 'danger' ? '#fde8e8' : '#fff3cd'};
                        color:${info.cls === 'danger' ? 'var(--danger)' : '#f59e0b'}">
                        ${info.badge}
                    </span>
                </div>
                <div class="nc-desc">${desc}</div>
                <div class="nc-time">
                    <span class="material-symbols-outlined" style="font-size:13px">schedule</span>
                    ${formatTime(item.thoigian)}
                </div>
            </div>
        </div>`;
    }).join('');
}
    function filterNotif(type, btn) {
    currentFilter = type;
    document.querySelectorAll('.notif-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Tab "Cảnh báo AI" dùng nguồn dữ liệu HOÀN TOÀN RIÊNG (bảng ai_canhbao),
    // không lọc từ allNotifs (vốn là cảnh báo ngưỡng cứng) — tách biệt theo
    // đúng thiết kế đã chốt.
    if (type === 'ai') { renderAiList(allAiNotifs); return; }

    let list = allNotifs;
    if (type === 'unread')  list = allNotifs.filter(i => i.trangthai != 1);
    if (type === 'warning') list = allNotifs.filter(i => i.mucdo === 'danger' || i.mucdo === 'warning');
    renderList(list);
}

    function markRead(idx) {
        if (currentFilter === 'ai') return; // tab AI dùng markReadAI() riêng
        if (allNotifs[idx]) allNotifs[idx].trangthai = 1;
        filterNotif(currentFilter, document.querySelector('.notif-tab-btn.active'));
        if (currentUserId) {
            fetch(`${API_BASE}/canhbao/read.php`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            }).catch(() => {});
        }
    }

    async function markAllRead() {
        allNotifs = allNotifs.map(i => ({ ...i, trangthai: 1 }));
        filterNotif(currentFilter, document.querySelector('.notif-tab-btn.active'));
        if (currentUserId) {
            await fetch(`${API_BASE}/canhbao/read.php`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            }).catch(() => {});
        }
    }


    // ============================================================
    // TAB "Cảnh báo AI" — nguồn riêng: bảng ai_canhbao qua
    // API/ai/get_ai_canhbao.php. Không liên quan allNotifs ở trên.
    // ============================================================
    function getAiInfo(anomaly_type, mucdo) {
        if (anomaly_type === 'sensor_fault')
            return { icon: 'sensors_off', label: 'Lỗi cảm biến', cls: 'danger', badge: '🔴 Lỗi cảm biến' };
        if (anomaly_type === 'action')
            return { icon: 'bolt', label: 'AI tự động điều khiển', cls: 'ai-action', badge: '⚡ Tự động' };
        const isDanger = mucdo === 'danger';
        return {
            icon: 'thermostat', label: 'Bất thường môi trường',
            cls: isDanger ? 'danger' : 'warning',
            badge: isDanger ? '🔴 Nguy hiểm' : '🟡 Cảnh báo',
        };
    }

    function renderAiList(items) {
        const el = document.getElementById('notif-card-list');
        if (!items || !items.length) {
            el.innerHTML = `<div class="notif-empty-state">
                <span class="material-symbols-outlined">smart_toy</span>
                <h3>Chưa có cảnh báo AI nào</h3>
                <p>AI chưa ghi nhận bất thường hoặc hành động tự động nào.</p>
            </div>`;
            return;
        }
        el.innerHTML = items.map((item, idx) => {
            const info   = getAiInfo(item.anomaly_type, item.mucdo);
            const isRead = item.trangthai == 1;
            const desc   = item.noidung || 'AI vừa ghi nhận 1 sự kiện.';
            const bg = info.cls === 'danger' ? '#fde8e8' : (info.cls === 'ai-action' ? '#f5f3ff' : '#fff3cd');
            const fg = info.cls === 'danger' ? 'var(--danger)' : (info.cls === 'ai-action' ? '#7c3aed' : '#f59e0b');
            return `
            <div class="notif-card ${isRead ? '' : 'unread'}" onclick="markReadAI(${idx})">
                <div class="nc-icon ${info.cls} material-symbols-outlined">${info.icon}</div>
                <div class="nc-body">
                    <div class="nc-title" style="display:flex;align-items:center;gap:8px;">
                        ${info.label}
                        <span style="font-size:11px;padding:2px 8px;border-radius:99px;font-weight:600;background:${bg};color:${fg}">
                            ${info.badge}
                        </span>
                    </div>
                    <div class="nc-desc ai-multiline">${desc}</div>
                    <div class="nc-time">
                        <span class="material-symbols-outlined" style="font-size:13px">schedule</span>
                        ${formatTime(item.thoigian)}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function markReadAI(idx) {
        if (allAiNotifs[idx]) allAiNotifs[idx].trangthai = 1;
        if (currentFilter === 'ai') renderAiList(allAiNotifs);
        updateAiTabLabel();
        if (currentUserId) {
            fetch(`${API_BASE}/ai/read_ai_canhbao.php`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            }).catch(() => {});
        }
    }

    async function markAllReadAI() {
        allAiNotifs = allAiNotifs.map(i => ({ ...i, trangthai: 1 }));
        if (currentFilter === 'ai') renderAiList(allAiNotifs);
        updateAiTabLabel();
        if (currentUserId) {
            await fetch(`${API_BASE}/ai/read_ai_canhbao.php`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            }).catch(() => {});
        }
    }

    // Nút header cần biết đang ở tab nào, tránh đánh dấu nhầm nguồn kia
    function markAllReadCurrentTab() {
        if (currentFilter === 'ai') markAllReadAI(); else markAllRead();
    }

    function updateAiTabLabel() {
        const unread = allAiNotifs.filter(i => i.trangthai != 1).length;
        const tab = document.querySelectorAll('.notif-tab-btn')[3];
        if (tab) tab.innerText = unread > 0 ? `Cảnh báo AI (${unread})` : 'Cảnh báo AI';
    }

    async function loadAiNotifications() {
        try {
            if (!currentUserId) return;
            const res  = await fetch(`${API_BASE}/ai/get_ai_canhbao.php?user_id=${currentUserId}`);
            const json = await res.json();
            if (json.status === 'success') {
                allAiNotifs = json.data || [];
                updateAiTabLabel();
                if (currentFilter === 'ai') renderAiList(allAiNotifs);
            }
        } catch (e) {
            console.error('[Thông báo AI]', e.message);
        }
    }

    async function loadNotifications() {
        try {
            const user = JSON.parse(localStorage.getItem('user_info') || '{}');
            currentUserId = user.id || user.id_nguoidung;
            if (!currentUserId) { window.location.href = 'login.php'; return; }

            const res  = await fetch(`${API_BASE}/canhbao/get_canhbao.php?user_id=${currentUserId}`);
            const json = await res.json();

            if (json.status === 'success') {
                allNotifs = json.data || [];
                renderList(allNotifs);
                // Hiển thị số chưa đọc trên tab
                const unread = allNotifs.filter(i => i.trangthai != 1).length;
                const tab = document.querySelectorAll('.notif-tab-btn')[1];
                if (tab) tab.innerText = unread > 0 ? `Chưa đọc (${unread})` : 'Chưa đọc';
            } else {
                renderList([]);
            }

            loadAiNotifications(); // nạp song song dữ liệu tab AI
        } catch(e) {
            console.error('[Thông báo]', e.message);
            renderList([]);
        }
    }

    document.addEventListener('DOMContentLoaded', loadNotifications);
</script>
</body>
</html>