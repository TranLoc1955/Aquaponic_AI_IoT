<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Lịch sử phân tích AI - HydroSmart</title>

    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/ai_history.css">
</head>
<body>
<div class="app-container">

    <!-- Sidebar dùng chung (Fresh Botanical Green + Mục Lịch sử AI) -->
    <?php include '../components/sidebar_light.php'; ?>

    <main class="main-content">
        <!-- Header chuẩn của hệ thống -->
        <header class="header">
            <div class="header-left">
                <div class="header-info">
                    <h2 class="header-title" style="display:flex; align-items:center; gap:8px;">
                        <span class="material-symbols-outlined" style="color:var(--primary, #23834A); font-size:26px;">psychology</span>
                        Lịch sử phân tích AI
                    </h2>
                    <p style="font-size:12px; color:var(--text-secondary, #718071); margin-top:2px;">
                        Quản lý và tra cứu các sự kiện bất thường, cảnh báo và hành động tự động từ AI Edge & Web
                    </p>
                </div>
            </div>

            <div class="header-center">
                <div class="clock" id="clock">--:--:--</div>
            </div>

            <div class="header-right">
                <a href="thongbaoW.php" class="notif-wrapper" title="Thông báo" style="text-decoration:none;">
                    <div class="notif-btn"><span class="material-symbols-outlined">notifications</span></div>
                    <span class="notif-badge" id="notif-badge"></span>
                </a>
            </div>
        </header>

        <!-- Content Area -->
        <div class="ai-history-page">
            <div class="ai-history-inner">

                <!-- 1. THẺ THỐNG KÊ (METRICS) -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon-wrap">
                            <span class="material-symbols-outlined">analytics</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Tổng sự kiện</span>
                            <span class="stat-value" id="stat-total">--</span>
                            <span class="stat-sub">Sự kiện bất thường AI</span>
                        </div>
                    </div>

                    <div class="stat-card danger">
                        <div class="stat-icon-wrap">
                            <span class="material-symbols-outlined">warning</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Mức Nguy hiểm</span>
                            <span class="stat-value" id="stat-danger">--</span>
                            <span class="stat-sub">Cần xử lý khẩn cấp</span>
                        </div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-icon-wrap">
                            <span class="material-symbols-outlined">error</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Mức Cảnh báo</span>
                            <span class="stat-value" id="stat-warning">--</span>
                            <span class="stat-sub">Cần chú ý theo dõi</span>
                        </div>
                    </div>

                    <div class="stat-card unresolved">
                        <div class="stat-icon-wrap">
                            <span class="material-symbols-outlined">pending_actions</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Chưa xử lý</span>
                            <span class="stat-value" id="stat-unresolved">--</span>
                            <span class="stat-sub">Đang mở / Chờ xác nhận</span>
                        </div>
                    </div>
                </div>

                <!-- 2. KHỐI BỘ LỌC ĐA CHIỀU (FILTERS) -->
                <div class="filters-card">
                    <div class="filters-header">
                        <div class="filters-title">
                            <span class="material-symbols-outlined">filter_alt</span>
                            <span>Bộ lọc tìm kiếm sự kiện</span>
                        </div>
                        <button type="button" class="btn-reset-filter" onclick="resetFilters()">
                            <span class="material-symbols-outlined">restart_alt</span>
                            Đặt lại bộ lọc
                        </button>
                    </div>

                    <div class="filters-row">
                        <!-- Lọc thời gian -->
                        <div class="filter-item">
                            <label class="filter-label">Khoảng thời gian</label>
                            <div class="time-presets">
                                <button type="button" class="btn-time-preset active" data-time="all" onclick="setTimePreset('all', this)">Tất cả</button>
                                <button type="button" class="btn-time-preset" data-time="today" onclick="setTimePreset('today', this)">Hôm nay</button>
                                <button type="button" class="btn-time-preset" data-time="7days" onclick="setTimePreset('7days', this)">7 ngày qua</button>
                                <button type="button" class="btn-time-preset" data-time="30days" onclick="setTimePreset('30days', this)">30 ngày</button>
                                <button type="button" class="btn-time-preset" data-time="custom" onclick="setTimePreset('custom', this)">Tùy chọn</button>
                            </div>
                        </div>

                        <!-- Lọc mức độ -->
                        <div class="filter-item">
                            <label class="filter-label" for="filter-severity">Mức độ</label>
                            <select id="filter-severity" class="filter-select" onchange="applyFilters()">
                                <option value="all">Tất cả mức độ</option>
                                <option value="danger">🔴 NGUY HIỂM (Danger)</option>
                                <option value="warning">🟠 CẢNH BÁO (Warning)</option>
                            </select>
                        </div>

                        <!-- Lọc loại cảm biến -->
                        <div class="filter-item">
                            <label class="filter-label" for="filter-sensor">Cảm biến liên quan</label>
                            <select id="filter-sensor" class="filter-select" onchange="applyFilters()">
                                <option value="all">Tất cả cảm biến</option>
                                <option value="nhiet_do">🌡️ Nhiệt độ</option>
                                <option value="do_am">💧 Độ ẩm</option>
                                <option value="anh_sang">☀️ Ánh sáng</option>
                                <option value="muc_nuoc">🌊 Mực nước</option>
                            </select>
                        </div>

                        <!-- Lọc trạng thái xử lý -->
                        <div class="filter-item">
                            <label class="filter-label" for="filter-status">Trạng thái xử lý</label>
                            <select id="filter-status" class="filter-select" onchange="applyFilters()">
                                <option value="all">Tất cả trạng thái</option>
                                <option value="0">Chưa xử lý (Đang mở)</option>
                                <option value="1">Đã xử lý</option>
                            </select>
                        </div>

                        <!-- Nút Lọc -->
                        <div class="filter-item" style="justify-content: flex-end;">
                            <button type="button" class="btn-action-view" onclick="applyFilters()" style="height:38px; padding:0 18px; background:var(--primary, #23834A); color:#fff; border-color:var(--primary, #23834A);">
                                <span class="material-symbols-outlined">search</span>
                                Lọc dữ liệu
                            </button>
                        </div>
                    </div>

                    <!-- Ô chọn ngày tùy chỉnh (Hiện ra khi chọn 'Tùy chọn') -->
                    <div class="custom-date-box" id="custom-date-box">
                        <div class="filter-item">
                            <label class="filter-label" for="filter-from-date">Từ ngày</label>
                            <input type="date" id="filter-from-date" class="filter-input" onchange="applyFilters()">
                        </div>
                        <div class="filter-item">
                            <label class="filter-label" for="filter-to-date">Đến ngày</label>
                            <input type="date" id="filter-to-date" class="filter-input" onchange="applyFilters()">
                        </div>
                    </div>
                </div>

                <!-- 3. BẢNG DANH SÁCH SỰ KIỆN AI -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="ai-events-table">
                            <thead>
                                <tr>
                                    <th>Thời gian phát hiện</th>
                                    <th>Tên sự kiện / Loại bất thường</th>
                                    <th>Cảm biến liên quan</th>
                                    <th>Giá trị ghi nhận</th>
                                    <th>Mức độ</th>
                                    <th>Hành động AI</th>
                                    <th>Trạng thái xử lý</th>
                                    <th style="text-align:right;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="ai-events-body">
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:40px; color:var(--text-secondary);">
                                        <span class="material-symbols-outlined" style="animation:spin 1s linear infinite; vertical-align:middle; margin-right:6px;">sync</span>
                                        Đang tải dữ liệu lịch sử AI...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Phân trang (Pagination) -->
                    <div class="pagination-bar" id="pagination-bar" style="display:none;">
                        <div id="pagination-info">Hiển thị 0 - 0 trong 0 sự kiện</div>
                        <div class="pagination-controls" id="pagination-controls">
                            <!-- Các nút phân trang render bằng JS -->
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- 4. MODAL CHI TIẾT SỰ KIỆN AI -->
<div class="modal-overlay" id="event-detail-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title-group">
                <div class="modal-title">
                    <span id="modal-event-type">Bất thường môi trường</span>
                    <span id="modal-event-badge" class="severity-badge danger">
                        <span class="badge-dot"></span>
                        <span id="modal-event-severity-text">DANGER</span>
                    </span>
                </div>
                <div class="modal-meta" id="modal-event-meta">
                    Phát hiện lúc: 12:42:42 · Thiết bị #18
                </div>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeDetailModal()" title="Đóng">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="modal-body">
            <!-- Khối 1: Lưới 4 cảm biến tại thời điểm phát hiện -->
            <div class="detail-section">
                <div class="detail-section-title">
                    <span class="material-symbols-outlined">sensors</span>
                    Giá trị cảm biến tại thời điểm phát hiện
                </div>
                <div class="modal-sensor-grid">
                    <div class="modal-sensor-box" id="box-sensor-temp">
                        <span class="modal-sensor-name">Nhiệt độ</span>
                        <span class="modal-sensor-val" id="modal-val-temp">--°C</span>
                        <span class="modal-sensor-tag" id="modal-tag-temp">Bình thường</span>
                    </div>
                    <div class="modal-sensor-box" id="box-sensor-hum">
                        <span class="modal-sensor-name">Độ ẩm</span>
                        <span class="modal-sensor-val" id="modal-val-hum">--%</span>
                        <span class="modal-sensor-tag" id="modal-tag-hum">Bình thường</span>
                    </div>
                    <div class="modal-sensor-box" id="box-sensor-lux">
                        <span class="modal-sensor-name">Ánh sáng</span>
                        <span class="modal-sensor-val" id="modal-val-lux">-- lux</span>
                        <span class="modal-sensor-tag" id="modal-tag-lux">Bình thường</span>
                    </div>
                    <div class="modal-sensor-box" id="box-sensor-water">
                        <span class="modal-sensor-name">Mực nước</span>
                        <span class="modal-sensor-val" id="modal-val-water">--</span>
                        <span class="modal-sensor-tag" id="modal-tag-water">Bình thường</span>
                    </div>
                </div>
            </div>

            <!-- Khối 2: Nhận định AI & Phân tích chi tiết -->
            <div class="detail-section">
                <div class="detail-section-title">
                    <span class="material-symbols-outlined">psychology</span>
                    Nhận định & Phân tích của AI
                </div>
                <div class="analysis-box danger" id="modal-analysis-box">
                    <p id="modal-analysis-text" style="margin:0;">Đang phân tích...</p>
                </div>
            </div>

            <!-- Khối 3: Hành động điều khiển & Khuyến nghị -->
            <div class="detail-section" id="modal-action-section">
                <div class="detail-section-title">
                    <span class="material-symbols-outlined">bolt</span>
                    Hành động điều khiển & Khuyến nghị
                </div>
                <div class="action-box-modal" id="modal-action-box">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span id="modal-action-text">AI đã tự động điều khiển thiết bị</span>
                </div>
                <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;" id="modal-reco-wrap">
                    <strong>Khuyến nghị vận hành:</strong> <span id="modal-reco-text">--</span>
                </div>
            </div>

            <!-- Khối 4: Cập nhật trạng thái xử lý -->
            <div class="detail-section">
                <div class="detail-section-title">
                    <span class="material-symbols-outlined">task_alt</span>
                    Cập nhật trạng thái xử lý sự kiện
                </div>
                <div class="status-update-card">
                    <div class="status-update-row">
                        <label for="modal-status-select" style="font-size:12px; font-weight:600; color:var(--text-primary); min-width:90px;">Trạng thái:</label>
                        <select id="modal-status-select" class="filter-select" style="flex:1;">
                            <option value="0">⚠️ Chưa xử lý (Đang mở)</option>
                            <option value="1">✅ Đã xử lý (Khắc phục xong)</option>
                        </select>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label for="modal-note-input" style="font-size:12px; font-weight:600; color:var(--text-primary);">Ghi chú xử lý:</label>
                        <textarea id="modal-note-input" class="filter-input" style="height:70px; padding:8px 12px; resize:vertical;" placeholder="Nhập ghi chú hoặc biên bản kiểm tra thực tế..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-action-view" onclick="closeDetailModal()">Đóng</button>
            <button type="button" class="btn-save-status" id="btn-save-modal" onclick="saveEventStatus()">
                <span class="material-symbols-outlined" style="font-size:16px;">save</span>
                Lưu trạng thái
            </button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="../js/auth.js"></script>
<script src="../js/notification.js"></script>
<script>
    // Configuration & State
    const API_BASE = '../../API';
    let currentUserId = 12; // Fallback mặc định
    let currentPage = 1;
    let currentLimit = 10;
    let currentTimeRange = 'all';
    let currentEventDetail = null;

    // Khởi tạo người dùng
    function initUser() {
        try {
            const user = JSON.parse(localStorage.getItem('user_info'));
            if (user && (user.id || user.id_nguoidung)) {
                currentUserId = user.id || user.id_nguoidung;
            }
        } catch (e) {
            console.warn('Lỗi đọc user_info từ localStorage:', e);
        }
    }

    // Đồng hồ Realtime trên Header
    function startClock() {
        const clockEl = document.getElementById('clock');
        if (!clockEl) return;
        const update = () => {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('vi-VN');
        };
        update();
        setInterval(update, 1000);
    }

    // Chọn nhanh thời gian (Presets)
    function setTimePreset(preset, btn) {
        currentTimeRange = preset;
        document.querySelectorAll('.btn-time-preset').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const customBox = document.getElementById('custom-date-box');
        if (preset === 'custom') {
            customBox.classList.add('show');
        } else {
            customBox.classList.remove('show');
            applyFilters();
        }
    }

    // Đặt lại bộ lọc về mặc định
    function resetFilters() {
        currentTimeRange = 'all';
        document.querySelectorAll('.btn-time-preset').forEach(b => {
            b.classList.toggle('active', b.dataset.time === 'all');
        });
        document.getElementById('custom-date-box').classList.remove('show');
        document.getElementById('filter-from-date').value = '';
        document.getElementById('filter-to-date').value = '';
        document.getElementById('filter-severity').value = 'all';
        document.getElementById('filter-sensor').value = 'all';
        document.getElementById('filter-status').value = 'all';
        currentPage = 1;
        fetchEvents();
    }

    // Áp dụng bộ lọc
    function applyFilters() {
        currentPage = 1;
        fetchEvents();
    }

    // Gọi API lấy dữ liệu sự kiện
    async function fetchEvents() {
        const bodyEl = document.getElementById('ai-events-body');
        const paginationBar = document.getElementById('pagination-bar');

        const severity = document.getElementById('filter-severity').value;
        const sensor = document.getElementById('filter-sensor').value;
        const status = document.getElementById('filter-status').value;
        const fromDate = document.getElementById('filter-from-date').value;
        const toDate = document.getElementById('filter-to-date').value;

        let url = `${API_BASE}/ai/get_ai_events.php?user_id=${currentUserId}&page=${currentPage}&limit=${currentLimit}&time_range=${currentTimeRange}&severity=${severity}&sensor=${sensor}&status=${status}`;
        if (currentTimeRange === 'custom' && fromDate && toDate) {
            url += `&from_date=${fromDate}&to_date=${toDate}`;
        }

        try {
            const res = await fetch(url);
            const json = await res.json();

            if (json.status !== 'success') {
                throw new Error(json.message || 'Lỗi khi tải dữ liệu');
            }

            // 1. Cập nhật thẻ thống kê
            updateStats(json.stats);

            // 2. Render bảng dữ liệu
            renderTable(json.data);

            // 3. Render phân trang
            renderPagination(json.pagination);

        } catch (error) {
            console.error('Lỗi fetchEvents:', error);
            bodyEl.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color:#DC2626;">
                        <span class="material-symbols-outlined" style="vertical-align:middle; margin-right:4px;">error</span>
                        Không thể kết nối đến máy chủ: ${error.message}
                    </td>
                </tr>
            `;
            paginationBar.style.display = 'none';
        }
    }

    // Cập nhật các số liệu thống kê trên các thẻ
    function updateStats(stats) {
        if (!stats) return;
        document.getElementById('stat-total').textContent = (stats.total || 0).toLocaleString();
        document.getElementById('stat-danger').textContent = (stats.danger || 0).toLocaleString();
        document.getElementById('stat-warning').textContent = (stats.warning || 0).toLocaleString();
        document.getElementById('stat-unresolved').textContent = (stats.unresolved || 0).toLocaleString();
    }

    // Render danh sách sự kiện vào bảng
    function renderTable(events) {
        const bodyEl = document.getElementById('ai-events-body');

        if (!Array.isArray(events) || events.length === 0) {
            bodyEl.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <span class="material-symbols-outlined">search_off</span>
                            </div>
                            <div class="empty-title">Không tìm thấy sự kiện AI nào</div>
                            <div class="empty-desc">Không có bản ghi bất thường nào phù hợp với các tiêu chí bộ lọc đã chọn.</div>
                            <button type="button" class="btn-action-view" onclick="resetFilters()">
                                <span class="material-symbols-outlined">restart_alt</span>
                                Xóa bộ lọc
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        bodyEl.innerHTML = events.map(item => {
            const isDanger = (item.mucdo === 'danger');
            const sevBadgeClass = isDanger ? 'danger' : 'warning';
            const sevLabel = isDanger ? 'NGUY HIỂM' : 'CẢNH BÁO';

            const isResolved = (item.trangthai == 1);
            const statusClass = isResolved ? 'resolved' : 'unresolved';
            const statusLabel = isResolved ? 'Đã xử lý' : 'Chưa xử lý';

            // Phân giải tên sự kiện tiếng Việt
            let eventName = 'Bất thường môi trường';
            if (item.anomaly_type === 'sensor_fault') eventName = 'Cảm biến sai số';
            else if (item.anomaly_type === 'action') eventName = 'AI tự động điều khiển';

            // Phân giải chip cảm biến
            const sensorChips = renderSensorChips(item.cam_bien || item.noidung);

            // Tóm tắt hành động
            let actionText = 'Không';
            if (item.action_device) {
                const devName = item.action_device === 'maybom' ? 'Máy Bơm' : (item.action_device === 'quatgio' ? 'Quạt' : 'Đèn');
                const cmdName = item.action_command === 'ON' ? 'BẬT' : 'TẮT';
                actionText = `<span style="font-weight:600; color:#166534;">⚡ ${cmdName} ${devName}</span>`;
            }

            // Thời gian
            const timeFormatted = formatDateTime(item.thoigian);

            // Số lần lặp
            const repeatTag = item.so_lan_lap > 1 ? `<span style="font-size:10px; background:#fef3c7; color:#b45309; padding:1px 5px; border-radius:4px; margin-left:4px;">Lặp lại x${item.so_lan_lap}</span>` : '';

            return `
                <tr>
                    <td style="white-space:nowrap;">
                        <div style="font-weight:600;">${timeFormatted.time}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">${timeFormatted.date}</div>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--text-primary);">${eventName} ${repeatTag}</div>
                        <div style="font-size:11px; color:var(--text-muted);">Thiết bị #${item.idthietbi}</div>
                    </td>
                    <td>${sensorChips}</td>
                    <td style="font-size:12px; color:var(--text-secondary); max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${item.gia_tri_cambien || ''}">
                        ${item.gia_tri_cambien || '--'}
                    </td>
                    <td>
                        <span class="severity-badge ${sevBadgeClass}">
                            <span class="badge-dot"></span>
                            ${sevLabel}
                        </span>
                    </td>
                    <td>${actionText}</td>
                    <td>
                        <span class="status-pill ${statusClass}">
                            <span class="material-symbols-outlined" style="font-size:13px;">${isResolved ? 'check_circle' : 'pending'}</span>
                            ${statusLabel}
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <button type="button" class="btn-action-view" onclick="openDetailModal(${item.id})">
                            <span class="material-symbols-outlined">visibility</span>
                            Chi tiết
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Tạo các chip cảm biến nhỏ gọn
    function renderSensorChips(sensorsStr) {
        if (!sensorsStr) return '<span style="color:var(--text-muted); font-size:11px;">--</span>';
        const str = sensorsStr.toLowerCase();
        let chips = [];
        if (str.includes('nhiet') || str.includes('nhiệt')) {
            chips.push(`<span class="sensor-chip"><span class="material-symbols-outlined">thermostat</span>Nhiệt độ</span>`);
        }
        if (str.includes('am') || str.includes('ẩm') || str.includes('hum')) {
            chips.push(`<span class="sensor-chip"><span class="material-symbols-outlined">water_drop</span>Độ ẩm</span>`);
        }
        if (str.includes('anh_sang') || str.includes('sáng') || str.includes('lux')) {
            chips.push(`<span class="sensor-chip"><span class="material-symbols-outlined">light_mode</span>Ánh sáng</span>`);
        }
        if (str.includes('nuoc') || str.includes('nước') || str.includes('water')) {
            chips.push(`<span class="sensor-chip"><span class="material-symbols-outlined">water</span>Mực nước</span>`);
        }
        return chips.length ? `<div class="sensor-chips-wrap">${chips.join('')}</div>` : `<span style="font-size:11px;">Môi trường</span>`;
    }

    // Render thanh phân trang
    function renderPagination(pg) {
        const bar = document.getElementById('pagination-bar');
        const info = document.getElementById('pagination-info');
        const controls = document.getElementById('pagination-controls');

        if (!pg || pg.total_records === 0) {
            bar.style.display = 'none';
            return;
        }

        bar.style.display = 'flex';
        const start = (pg.page - 1) * pg.limit + 1;
        const end = Math.min(pg.page * pg.limit, pg.total_records);
        info.textContent = `Hiển thị ${start} - ${end} trong tổng số ${pg.total_records.toLocaleString()} sự kiện`;

        let html = '';
        // Nút Trước
        html += `<button type="button" class="btn-page" ${pg.page <= 1 ? 'disabled' : ''} onclick="goToPage(${pg.page - 1})" title="Trang trước"><span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span></button>`;

        // Tính toán các trang hiển thị
        const total = pg.total_pages;
        let startPage = Math.max(1, pg.page - 2);
        let endPage = Math.min(total, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<button type="button" class="btn-page ${i === pg.page ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        }

        // Nút Sau
        html += `<button type="button" class="btn-page" ${pg.page >= total ? 'disabled' : ''} onclick="goToPage(${pg.page + 1})" title="Trang sau"><span class="material-symbols-outlined" style="font-size:16px;">chevron_right</span></button>`;

        controls.innerHTML = html;
    }

    function goToPage(p) {
        currentPage = p;
        fetchEvents();
    }

    // Format ngày giờ thân thiện
    function formatDateTime(dtStr) {
        if (!dtStr) return { time: '--:--', date: '--/--/----' };
        const d = new Date(dtStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return { time: dtStr, date: '' };
        return {
            time: d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
            date: d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
        };
    }

    // Mở Modal xem chi tiết sự kiện
    async function openDetailModal(eventId) {
        const modal = document.getElementById('event-detail-modal');
        modal.classList.add('open');

        try {
            const res = await fetch(`${API_BASE}/ai/get_event_detail.php?id=${eventId}`);
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            const ev = json.data;
            currentEventDetail = ev;

            // Header modal
            const isDanger = (ev.mucdo === 'danger');
            document.getElementById('modal-event-type').textContent = ev.anomaly_type === 'sensor_fault' ? 'Cảm biến sai số' : 'Bất thường môi trường';
            const badgeEl = document.getElementById('modal-event-badge');
            badgeEl.className = `severity-badge ${isDanger ? 'danger' : 'warning'}`;
            document.getElementById('modal-event-severity-text').textContent = isDanger ? 'DANGER (Nguy hiểm)' : 'WARNING (Cảnh báo)';

            const t = formatDateTime(ev.thoigian);
            document.getElementById('modal-event-meta').textContent = `Phát hiện lúc: ${t.time} ngày ${t.date} · Thiết bị #${ev.idthietbi} · ID Sự kiện #${ev.id}`;

            // Cảm biến
            document.getElementById('modal-val-temp').textContent = ev.nhietdo !== null ? `${ev.nhietdo}°C` : '--';
            document.getElementById('modal-val-hum').textContent = ev.do_am !== null ? `${ev.do_am}%` : '--';
            document.getElementById('modal-val-lux').textContent = ev.anhsang !== null ? `${ev.anhsang} lux` : '--';
            document.getElementById('modal-val-water').textContent = ev.muc_nuoc !== null ? `${ev.muc_nuoc}` : '--';

            // Tag bất thường từng cảm biến
            const abStr = (ev.abnormal_sensors || ev.cam_bien || ev.noidung || '').toLowerCase();
            updateSensorBox('temp', abStr.includes('nhiet') || abStr.includes('nhiệt'));
            updateSensorBox('hum', abStr.includes('am') || abStr.includes('ẩm') || abStr.includes('hum'));
            updateSensorBox('lux', abStr.includes('anh_sang') || abStr.includes('sáng') || abStr.includes('lux'));
            updateSensorBox('water', abStr.includes('nuoc') || abStr.includes('nước') || abStr.includes('water'));

            // Phân tích
            const analysisBox = document.getElementById('modal-analysis-box');
            analysisBox.className = `analysis-box ${isDanger ? 'danger' : 'warning'}`;
            document.getElementById('modal-analysis-text').textContent = ev.analysis_summary || ev.noidung || 'Không có mô tả phân tích.';

            // Hành động điều khiển
            const actionSection = document.getElementById('modal-action-section');
            if (ev.action_device) {
                actionSection.style.display = 'flex';
                const dev = ev.action_device === 'maybom' ? 'Máy Bơm' : (ev.action_device === 'quatgio' ? 'Quạt Gió' : 'Đèn');
                const cmd = ev.action_command === 'ON' ? 'BẬT' : 'TẮT';
                document.getElementById('modal-action-text').innerHTML = `AI đã tự động <strong>${cmd} ${dev}</strong>` + (ev.action_reason ? ` — Lý do: ${ev.action_reason}` : '');
            } else {
                document.getElementById('modal-action-text').textContent = 'AI không thực hiện điều khiển tự động.';
            }

            // Khuyến nghị
            const recoText = ev.recommendation_message || 'Theo dõi tiếp diễn biến của vườn.';
            document.getElementById('modal-reco-text').textContent = recoText;

            // Form trạng thái
            document.getElementById('modal-status-select').value = ev.trangthai;
            document.getElementById('modal-note-input').value = ev.ghi_chu || '';

        } catch (e) {
            alert('Lỗi tải chi tiết sự kiện: ' + e.message);
            closeDetailModal();
        }
    }

    function updateSensorBox(type, isAbnormal) {
        const box = document.getElementById(`box-sensor-${type}`);
        const tag = document.getElementById(`modal-tag-${type}`);
        if (!box || !tag) return;
        if (isAbnormal) {
            box.classList.add('abnormal');
            tag.className = 'modal-sensor-tag abnormal';
            tag.textContent = 'Bất thường';
        } else {
            box.classList.remove('abnormal');
            tag.className = 'modal-sensor-tag normal';
            tag.textContent = 'Bình thường';
        }
    }

    function closeDetailModal() {
        document.getElementById('event-detail-modal').classList.remove('open');
        currentEventDetail = null;
    }

    // Đóng modal khi bấm phím ESC hoặc bấm ra ngoài dialog
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDetailModal();
    });
    document.getElementById('event-detail-modal').addEventListener('click', (e) => {
        if (e.target.id === 'event-detail-modal') closeDetailModal();
    });

    // Lưu trạng thái xử lý sự kiện
    async function saveEventStatus() {
        if (!currentEventDetail) return;
        const btn = document.getElementById('btn-save-modal');
        const status = document.getElementById('modal-status-select').value;
        const note = document.getElementById('modal-note-input').value;

        btn.disabled = true;
        btn.innerHTML = `<span class="material-symbols-outlined" style="animation:spin 1s linear infinite; font-size:16px;">sync</span> Đang lưu...`;

        try {
            const res = await fetch(`${API_BASE}/ai/update_event_status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    event_id: currentEventDetail.id,
                    status: parseInt(status),
                    note: note
                })
            });
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message);

            closeDetailModal();
            fetchEvents(); // Tải lại bảng để cập nhật trạng thái mới nhất

        } catch (e) {
            alert('Lỗi cập nhật: ' + e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;">save</span> Lưu trạng thái`;
        }
    }

    // Khởi chạy khi DOM sẵn sàng
    document.addEventListener('DOMContentLoaded', async () => {
        initUser();
        startClock();
        await fetchEvents();

        // Tự động mở modal chi tiết nếu có tham số ?event_id=... trên URL
        const urlParams = new URLSearchParams(window.location.search);
        const eventId = urlParams.get('event_id');
        if (eventId) {
            openDetailModal(parseInt(eventId));
        }
    });
</script>
</body>
</html>
