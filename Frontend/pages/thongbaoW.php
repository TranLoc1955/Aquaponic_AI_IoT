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
            <h2>
                <span class="material-symbols-outlined">notifications</span>
                Trung tâm thông báo
            </h2>
            <button class="mark-all-btn" onclick="markAllRead()">
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
    let currentFilter = 'all';
    let currentUserId = null;

    const sensorMap = {
        'nhiet do':  { icon: 'thermostat', cls: 'danger',  label: 'Nhiệt độ vượt ngưỡng' },
        'nhiet_do':  { icon: 'thermostat', cls: 'danger',  label: 'Nhiệt độ vượt ngưỡng' },
        'do am':     { icon: 'water_drop', cls: 'info',    label: 'Độ ẩm bất thường' },
        'do_am':     { icon: 'water_drop', cls: 'info',    label: 'Độ ẩm bất thường' },
        'anh sang':  { icon: 'light_mode', cls: 'warning', label: 'Ánh sáng bất thường' },
        'anh_sang':  { icon: 'light_mode', cls: 'warning', label: 'Ánh sáng bất thường' },
        'muc nuoc':  { icon: 'water',      cls: 'danger',  label: 'Mức nước thấp' },
        'muc_nuoc':  { icon: 'water',      cls: 'danger',  label: 'Mức nước thấp' },
    };

    function getInfo(loaicambien) {
        if (!loaicambien) return { icon: 'warning', cls: 'warning', label: 'Cảnh báo hệ thống' };
        return sensorMap[loaicambien.toLowerCase().trim()]
            || { icon: 'sensors', cls: 'warning', label: loaicambien };
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
                <p>Hệ thống chưa ghi nhận sự kiện nào cần thông báo.</p>
            </div>`;
            return;
        }

        el.innerHTML = items.map((item, idx) => {
            const info   = getInfo(item.loaicambien);
            const isRead = item.trangthai == 1;
            const desc   = item.noi_dung
                || (item.loaicambien
                    ? `${item.loaicambien} đã vượt ngưỡng cảnh báo${item.donvido ? ' (' + item.donvido + ')' : ''}`
                    : 'Hệ thống phát hiện giá trị bất thường');
            return `
            <div class="notif-card ${isRead ? '' : 'unread'}" onclick="markRead(${idx})">
                <div class="nc-icon ${info.cls} material-symbols-outlined">${info.icon}</div>
                <div class="nc-body">
                    <div class="nc-title">${info.label}</div>
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
        let list = allNotifs;
        if (type === 'unread')  list = allNotifs.filter(i => i.trangthai != 1);
        if (type === 'warning') list = allNotifs.filter(i => i.trangthai != 1);
        renderList(list);
    }

    function markRead(idx) {
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
        } catch(e) {
            console.error('[Thông báo]', e.message);
            renderList([]);
        }
    }

    document.addEventListener('DOMContentLoaded', loadNotifications);
</script>
</body>
</html>
