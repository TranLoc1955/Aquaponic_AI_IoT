<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Thông báo - HydroSmart AIoT</title>

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/thongbao.css?v=101">

    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>

<body>
<div class="notif-page-container">

    <?php include '../components/sidebar_light.php'; ?>

    <main class="notif-main">

        <!-- Top Header -->
        <header class="notif-page-header">
            <div class="header-left">
                <button type="button" class="sidebar-mobile-trigger" id="sidebar-mobile-trigger" aria-label="Mở menu">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="header-title-wrap">
                    <h2>
                        <span class="material-symbols-outlined">notifications</span>
                        Thông báo
                    </h2>
                    <span class="header-unread-badge" id="header-unread-count">0 chưa đọc</span>
                </div>
            </div>

            <button type="button" class="mark-all-btn" onclick="markAllReadCurrentTab()" title="Đánh dấu tất cả thông báo hiện tại là đã đọc">
                <span class="material-symbols-outlined">done_all</span>
                <span>Đánh dấu đã đọc</span>
            </button>
        </header>

        <!-- Main Content Area -->
        <div class="notif-content">
            <div class="notif-content-inner">

                <!-- Segmented Tabs Filter (Linear Style) -->
                <div class="notif-toolbar">
                    <div class="notif-tabs" role="tablist">
                        <button type="button" class="notif-tab-btn active" onclick="filterNotif('all', this)">
                            <span>Tất cả</span>
                        </button>
                        <button type="button" class="notif-tab-btn" id="tab-unread-btn" onclick="filterNotif('unread', this)">
                            <span>Chưa đọc</span>
                            <span class="tab-badge unread-count" id="badge-unread-count" style="display:none;">0</span>
                        </button>
                        <button type="button" class="notif-tab-btn" onclick="filterNotif('warning', this)">
                            <span>Cảnh báo</span>
                        </button>
                        <button type="button" class="notif-tab-btn" id="tab-ai-btn" onclick="filterNotif('ai', this)">
                            <span>Cảnh báo AI</span>
                            <span class="tab-badge" id="badge-ai-count" style="display:none;">0</span>
                        </button>
                    </div>
                </div>

                <!-- Notifications Feed -->
                <div class="notif-card-list" id="notif-card-list">
                    <!-- Skeleton Loading States -->
                    <div class="skeleton-card">
                        <div class="skeleton" style="width: 32px; height: 32px; flex-shrink: 0;"></div>
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <div class="skeleton" style="height: 14px; width: 45%;"></div>
                            <div class="skeleton" style="height: 12px; width: 85%;"></div>
                            <div class="skeleton" style="height: 10px; width: 25%;"></div>
                        </div>
                    </div>
                    <div class="skeleton-card">
                        <div class="skeleton" style="width: 32px; height: 32px; flex-shrink: 0;"></div>
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <div class="skeleton" style="height: 14px; width: 40%;"></div>
                            <div class="skeleton" style="height: 12px; width: 75%;"></div>
                            <div class="skeleton" style="height: 10px; width: 20%;"></div>
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
    let allNotifs = [];
    let allAiNotifs = [];
    let currentFilter = 'all';
    let currentUserId = null;

    // Sensor icon mapping
    const sensorIconMap = {
        'nhiet do': { icon: 'thermostat', label: 'Nhiệt độ' },
        'nhiet_do': { icon: 'thermostat', label: 'Nhiệt độ' },
        'do am': { icon: 'water_drop', label: 'Độ ẩm' },
        'do_am': { icon: 'water_drop', label: 'Độ ẩm' },
        'anh sang': { icon: 'light_mode', label: 'Ánh sáng' },
        'anh_sang': { icon: 'light_mode', label: 'Ánh sáng' },
        'muc nuoc': { icon: 'water', label: 'Mức nước' },
        'muc_nuoc': { icon: 'water', label: 'Mức nước' }
    };

    /**
     * Map severity to minimalist status dot representation (NO colorful background badges)
     */
    function getStatusMeta(mucdo) {
        if (mucdo === 'danger') {
            return { dotCls: 'status-danger', statusText: 'Nguy hiểm' };
        }
        if (mucdo === 'warning') {
            return { dotCls: 'status-warning', statusText: 'Cảnh báo' };
        }
        return { dotCls: 'status-info', statusText: 'Thông tin' };
    }

    /**
     * Lấy metadata cho thông báo cảm biến tiêu chuẩn
     */
    function getStandardNotifInfo(loaicambien, mucdo) {
        const sensor = loaicambien
            ? (sensorIconMap[loaicambien.toLowerCase().trim()] || { icon: 'sensors', label: loaicambien })
            : { icon: 'sensors', label: 'Hệ thống' };
        const status = getStatusMeta(mucdo);
        return { ...sensor, ...status };
    }

    /**
     * Format timestamp to minimalist relative time
     */
    function formatTime(str) {
        if (!str) return '';
        const d = new Date(str.replace(' ', 'T'));
        if (isNaN(d)) return str;

        const diffMinutes = Math.floor((new Date() - d) / 60000);
        if (diffMinutes < 1) return 'Vừa xong';
        if (diffMinutes < 60) return `${diffMinutes} phút trước`;
        if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)} giờ trước`;
        if (diffMinutes < 2880) return 'Hôm qua';

        return d.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Render danh sách thông báo cảm biến
     */
    function renderList(items) {
        const el = document.getElementById('notif-card-list');
        if (!el) return;

        if (!items || !items.length) {
            el.innerHTML = `
                <div class="notif-empty-state">
                    <span class="material-symbols-outlined">notifications_off</span>
                    <h3>Hộp thư thông báo trống</h3>
                    <p>Hệ thống không ghi nhận cảnh báo nào trong mục này.</p>
                </div>
            `;
            return;
        }

        el.innerHTML = items.map((item, idx) => {
            const info = getStandardNotifInfo(item.loaicambien, item.mucdo);
            const isRead = item.trangthai == 1;
            const desc = item.noidung || item.noi_dung || 'Phát hiện thông số đo vượt ngưỡng an toàn.';

            return `
                <article class="notif-card ${isRead ? 'read' : 'unread'}" onclick="markRead(${idx})" role="button" tabindex="0">
                    <span class="nc-unread-indicator" title="${isRead ? 'Đã đọc' : 'Chưa đọc'}"></span>
                    <div class="nc-icon-box">
                        <span class="material-symbols-outlined">${info.icon}</span>
                    </div>
                    <div class="nc-body">
                        <div class="nc-header-line">
                            <div class="nc-title-group">
                                <span class="nc-title">${info.label}</span>
                                <span class="nc-separator">·</span>
                                <span class="status-indicator ${info.dotCls}">
                                    <span class="status-dot"></span>
                                    <span>${info.statusText}</span>
                                </span>
                            </div>
                            <time class="nc-time">${formatTime(item.thoigian)}</time>
                        </div>
                        <div class="nc-desc">${desc}</div>
                    </div>
                </article>
            `;
        }).join('');
    }

    /**
     * Lấy thông tin hiển thị cho thông báo AI
     */
    function getAiInfo(anomaly_type, mucdo) {
        if (anomaly_type === 'sensor_fault') {
            return {
                icon: 'sensors_off',
                label: 'Lỗi cảm biến',
                dotCls: 'status-danger',
                statusText: 'Hỏng phần cứng'
            };
        }
        if (anomaly_type === 'action') {
            return {
                icon: 'bolt',
                label: 'AI Tự động điều khiển',
                dotCls: 'status-action',
                statusText: 'Tự động'
            };
        }

        const isDanger = mucdo === 'danger';
        return {
            icon: 'psychology',
            label: 'AI Phát hiện bất thường',
            dotCls: isDanger ? 'status-danger' : 'status-warning',
            statusText: isDanger ? 'Nguy cấp' : 'Cảnh báo'
        };
    }

    /**
     * Render danh sách cảnh báo AI
     */
    function renderAiList(items) {
        const el = document.getElementById('notif-card-list');
        if (!el) return;

        if (!items || !items.length) {
            el.innerHTML = `
                <div class="notif-empty-state">
                    <span class="material-symbols-outlined">smart_toy</span>
                    <h3>Chưa có cảnh báo AI</h3>
                    <p>Mô hình AI chưa phát hiện bất thường hoặc hành động can thiệp nào.</p>
                </div>
            `;
            return;
        }

        el.innerHTML = items.map((item, idx) => {
            const info = getAiInfo(item.anomaly_type, item.mucdo);
            const isRead = item.trangthai == 1;
            const desc = item.noidung || 'Mô hình AI vừa ghi nhận một sự kiện.';

            const hasAction = item.action_device || item.action_command;
            const actionSnippet = hasAction
                ? `<div class="nc-action-tag">
                     <span class="material-symbols-outlined">bolt</span>
                     <span>Đã gửi lệnh: ${item.action_device || 'Thiết bị'} (${item.action_command || 'Kích hoạt'})</span>
                   </div>`
                : '';

            return `
                <article class="notif-card ${isRead ? 'read' : 'unread'}" onclick="markReadAI(${idx})" role="button" tabindex="0">
                    <span class="nc-unread-indicator" title="${isRead ? 'Đã đọc' : 'Chưa đọc'}"></span>
                    <div class="nc-icon-box">
                        <span class="material-symbols-outlined">${info.icon}</span>
                    </div>
                    <div class="nc-body">
                        <div class="nc-header-line">
                            <div class="nc-title-group">
                                <span class="nc-title">${info.label}</span>
                                <span class="nc-separator">·</span>
                                <span class="status-indicator ${info.dotCls}">
                                    <span class="status-dot"></span>
                                    <span>${info.statusText}</span>
                                </span>
                            </div>
                            <time class="nc-time">${formatTime(item.thoigian)}</time>
                        </div>
                        <div class="nc-desc ai-multiline">${desc}</div>
                        ${actionSnippet}
                    </div>
                </article>
            `;
        }).join('');
    }

    /**
     * Chuyển tab lọc
     */
    function filterNotif(type, btn) {
        currentFilter = type;
        document.querySelectorAll('.notif-tab-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        if (type === 'ai') {
            renderAiList(allAiNotifs);
            return;
        }

        let list = allNotifs;
        if (type === 'unread') list = allNotifs.filter(i => i.trangthai != 1);
        if (type === 'warning') list = allNotifs.filter(i => i.mucdo === 'danger' || i.mucdo === 'warning');

        renderList(list);
    }

    /**
     * Đánh dấu đã đọc cho 1 thông báo thường
     */
    function markRead(idx) {
        if (currentFilter === 'ai') return;
        if (allNotifs[idx]) {
            allNotifs[idx].trangthai = 1;
        }
        updateCounters();
        filterNotif(currentFilter, document.querySelector('.notif-tab-btn.active'));

        if (currentUserId) {
            fetch(`${API_BASE}/canhbao/read.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            }).catch(() => {});
        }
    }

    /**
     * Đánh dấu đã đọc cho 1 thông báo AI
     */
    function markReadAI(idx) {
        if (allAiNotifs[idx]) {
            allAiNotifs[idx].trangthai = 1;
        }
        updateCounters();
        if (currentFilter === 'ai') {
            renderAiList(allAiNotifs);
        }

        if (currentUserId) {
            fetch(`${API_BASE}/ai/read_ai_canhbao.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            }).catch(() => {});
        }
    }

    /**
     * Đánh dấu tất cả đã đọc cho tab thường
     */
    async function markAllRead() {
        allNotifs = allNotifs.map(i => ({ ...i, trangthai: 1 }));
        updateCounters();
        filterNotif(currentFilter, document.querySelector('.notif-tab-btn.active'));

        if (currentUserId) {
            await fetch(`${API_BASE}/canhbao/read.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            }).catch(() => {});
        }
    }

    /**
     * Đánh dấu tất cả đã đọc cho tab AI
     */
    async function markAllReadAI() {
        allAiNotifs = allAiNotifs.map(i => ({ ...i, trangthai: 1 }));
        updateCounters();
        if (currentFilter === 'ai') {
            renderAiList(allAiNotifs);
        }

        if (currentUserId) {
            await fetch(`${API_BASE}/ai/read_ai_canhbao.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            }).catch(() => {});
        }
    }

    /**
     * Nút "Đánh dấu đã đọc" trên thanh Header
     */
    function markAllReadCurrentTab() {
        if (currentFilter === 'ai') {
            markAllReadAI();
        } else {
            markAllRead();
        }
    }

    /**
     * Cập nhật số lượng thông báo chưa đọc trên badge
     */
    function updateCounters() {
        const unreadStd = allNotifs.filter(i => i.trangthai != 1).length;
        const unreadAi = allAiNotifs.filter(i => i.trangthai != 1).length;
        const totalUnread = unreadStd + unreadAi;

        // Header unread badge
        const headerBadge = document.getElementById('header-unread-count');
        if (headerBadge) {
            headerBadge.textContent = `${totalUnread} chưa đọc`;
            headerBadge.classList.toggle('has-unread', totalUnread > 0);
        }

        // Tab "Chưa đọc" badge
        const unreadBadge = document.getElementById('badge-unread-count');
        if (unreadBadge) {
            unreadBadge.textContent = unreadStd;
            unreadBadge.style.display = unreadStd > 0 ? 'inline-block' : 'none';
        }

        // Tab "Cảnh báo AI" badge
        const aiBadge = document.getElementById('badge-ai-count');
        if (aiBadge) {
            aiBadge.textContent = unreadAi;
            aiBadge.style.display = unreadAi > 0 ? 'inline-block' : 'none';
        }
    }

    /**
     * Nạp dữ liệu cảnh báo AI
     */
    async function loadAiNotifications() {
        try {
            if (!currentUserId) return;
            const res = await fetch(`${API_BASE}/ai/get_ai_canhbao.php?user_id=${currentUserId}`);
            const json = await res.json();
            if (json.status === 'success') {
                allAiNotifs = json.data || [];
                updateCounters();
                if (currentFilter === 'ai') {
                    renderAiList(allAiNotifs);
                }
            }
        } catch (e) {
            console.error('[Thông báo AI]', e);
        }
    }

    /**
     * Nạp dữ liệu thông báo cảm biến tiêu chuẩn
     */
    async function loadNotifications() {
        try {
            const user = JSON.parse(localStorage.getItem('user_info') || '{}');
            currentUserId = user.id || user.id_nguoidung;
            if (!currentUserId) {
                window.location.href = 'login.php';
                return;
            }

            const res = await fetch(`${API_BASE}/canhbao/get_canhbao.php?user_id=${currentUserId}`);
            const json = await res.json();

            if (json.status === 'success') {
                allNotifs = json.data || [];
                renderList(allNotifs);
            } else {
                renderList([]);
            }

            await loadAiNotifications();
            updateCounters();
        } catch (e) {
            console.error('[Thông báo]', e);
            renderList([]);
        }
    }

    document.addEventListener('DOMContentLoaded', loadNotifications);
</script>
</body>
</html>