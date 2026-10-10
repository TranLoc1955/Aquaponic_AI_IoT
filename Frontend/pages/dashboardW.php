<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HydroSmart</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <style>
        /* ============ Khung "Phân tích AI" — phong cách Clean Data-Driven:
           ưu tiên số liệu/chỉ số dạng lưới thay vì văn xuôi, bảng dữ liệu
           thay vì danh sách. Font kế thừa var(--font-main) toàn trang, màu
           dùng đúng biến hiện có trong variables.css để dễ đổi bảng màu sau
           này mà không phải sửa lại phần này. ============ */
        .ai-badge {
            font-size: var(--text-xs); font-weight: var(--font-semibold);
            padding: 4px var(--space-3); border-radius: var(--radius-full);
            white-space: nowrap;
        }
        .ai-badge-idle    { background: var(--bg-hover); color: var(--text-muted); }
        .ai-badge-normal  { background: #ecfdf5; color: var(--success); }
        .ai-badge-warning { background: #fffbeb; color: var(--warning); }
        .ai-badge-danger  { background: #fef2f2; color: var(--danger); }

        /* ---- Lưới chỉ số ---- */
        .ai-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: var(--space-6);
            padding-bottom: var(--space-5);
            border-bottom: 1px solid var(--border-light);
            margin-bottom: var(--space-5);
        }
        .ai-stat-label {
            font-size: var(--text-xs); font-weight: var(--font-semibold);
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: 0.05em; margin-bottom: 6px;
        }
        .ai-stat-value {
            font-size: var(--text-2xl); font-weight: var(--font-bold);
            color: var(--text-primary); line-height: 1.1;
        }
        .ai-stat-value.normal  { color: var(--success); }
        .ai-stat-value.warning { color: var(--warning); }
        .ai-stat-value.danger  { color: var(--danger); }
        .ai-stat-sub { font-size: var(--text-xs); color: var(--text-muted); margin-top: 2px; }

        .ai-score-track {
            width: 100%; height: 5px; border-radius: var(--radius-full);
            background: var(--bg-hover); margin-top: 8px; overflow: hidden;
        }
        .ai-score-fill {
            height: 100%; border-radius: var(--radius-full);
            transition: width 0.4s ease, background 0.4s ease;
            background: var(--text-muted);
        }
        .ai-score-fill.normal  { background: var(--success); }
        .ai-score-fill.warning { background: var(--warning); }
        .ai-score-fill.danger  { background: var(--danger); }

        /* ---- Diễn giải + khuyến nghị (phụ, chữ nhỏ, không lấn số liệu) ---- */
        .ai-summary-text { font-size: var(--text-sm); color: var(--text-secondary); line-height: 1.6; margin: 0; }

        .ai-detail-toggle {
            background: none; border: none; cursor: pointer; padding: 0;
            font-size: var(--text-xs); font-weight: var(--font-semibold);
            color: var(--primary); margin-top: var(--space-2);
        }
        .ai-detail-toggle:hover { text-decoration: underline; }
        .ai-detail-box {
            margin-top: var(--space-3); padding: var(--space-4);
            background: var(--bg-hover); border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
        }
        .ai-detail-table { width: 100%; font-size: var(--text-xs); border-collapse: collapse; }
        .ai-detail-table th {
            text-align: left; padding: var(--space-2); color: var(--text-muted);
            font-weight: var(--font-semibold); text-transform: uppercase; font-size: 11px;
            border-bottom: 1px solid var(--border-color);
        }
        .ai-detail-table td { padding: var(--space-2); border-bottom: 1px solid var(--border-light); }
        .ai-detail-table tr:last-child td { border-bottom: none; }
        .ai-detail-table td.ai-detail-zscore-high { color: var(--danger); font-weight: var(--font-bold); }
        .ai-detail-note { font-size: 11px; color: var(--text-muted); margin: var(--space-3) 0 0 0; line-height: 1.5; }

        .ai-reco-row {
            display: flex; align-items: center; gap: var(--space-3);
            margin-top: var(--space-4); padding-top: var(--space-4);
            border-top: 1px dashed var(--border-light);
        }
        .ai-reco-chip {
            font-size: var(--text-xs); font-weight: var(--font-semibold);
            color: var(--primary-dark); background: var(--primary-bg);
            padding: 3px var(--space-2); border-radius: var(--radius-sm);
            flex-shrink: 0; white-space: nowrap;
        }
        .ai-reco-msg { font-size: var(--text-sm); color: var(--text-primary); }

        /* ---- Khung "AI vừa làm gì" — tách biệt khỏi cảnh báo, màu tím nhẹ
               để không lẫn với badge severity (xanh/vàng/đỏ) ---- */
        .ai-action-box {
            display: flex; align-items: center; gap: var(--space-2);
            margin-top: var(--space-3); padding: var(--space-3) var(--space-4);
            background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: var(--radius-md);
            font-size: var(--text-sm); color: #6d28d9;
        }
        .ai-action-box .material-symbols-outlined { color: #7c3aed; }

        /* ---- Badge Mode (AUTO/MANUAL) — dùng riêng màu để phân biệt với
               badge severity (Bình thường/Cảnh báo/Nguy hiểm) ---- */
        .ai-badge-auto   { background: #e0f2fe; color: #0369a1; }
        .ai-badge-manual { background: #fef3c7; color: #92400e; }

        /* ---- Phân biệt 2 loại bất thường bằng màu khác hẳn severity ---- */
        .ai-type-environment  { color: #b45309; }
        .ai-type-sensor_fault { color: #be123c; }
        .ai-type-none         { color: var(--text-muted); }

        /* ---- Bảng lịch sử — tái sử dụng đúng phong cách .data-table của
               trang Lịch sử dữ liệu (historyW.php) để đồng bộ toàn hệ thống ---- */
        .ai-table-header {
            font-size: var(--text-xs); font-weight: var(--font-semibold);
            color: var(--text-muted); margin: var(--space-6) 0 var(--space-3) 0;
        }
        .ai-table-wrap {
            border: 1px solid var(--border-color); border-radius: var(--radius-lg);
            overflow: hidden; max-height: 240px; overflow-y: auto;
        }
        .ai-table { width: 100%; font-size: var(--text-xs); text-align: left; border-collapse: collapse; }
        .ai-table thead {
            font-size: 11px; color: var(--text-secondary); text-transform: uppercase;
            background: var(--bg-main); position: sticky; top: 0;
        }
        .ai-table th { padding: var(--space-3) var(--space-4); font-weight: var(--font-semibold); letter-spacing: 0.04em; }
        .ai-table td { padding: var(--space-2) var(--space-4); color: var(--text-primary); border-top: 1px solid var(--border-light); }
        .ai-row-main { cursor: pointer; }
        .ai-row-main:hover { background: var(--bg-hover); }
        .ai-row-expand-link {
            text-decoration: underline; text-decoration-style: dotted;
            text-underline-offset: 3px; color: var(--text-secondary);
        }
        .ai-row-detail td { padding: 0; border-top: none; }
        .ai-row-detail-inner {
            background: var(--bg-hover); padding: var(--space-4) var(--space-5);
            border-left: 3px solid var(--primary);
        }
        .ai-row-detail-line { font-size: var(--text-xs); color: var(--text-primary); margin-bottom: var(--space-2); line-height: 1.6; }
        .ai-row-detail-line:last-child { margin-bottom: 0; }
        .ai-row-detail-meta { color: var(--text-muted); }
        .ai-mini-tag {
            display: inline-block; font-size: 11px; font-weight: var(--font-semibold);
            color: var(--primary-dark); background: var(--primary-bg);
            padding: 2px var(--space-2); border-radius: var(--radius-sm);
            margin-right: 4px;
        }
        .ai-table-time { color: var(--text-muted); white-space: nowrap; }
        .ai-table-empty { text-align: center; padding: var(--space-6); color: var(--text-muted); font-size: var(--text-sm); }
        .ai-table-wrap::-webkit-scrollbar { width: 4px; }
        .ai-table-wrap::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }

        /* ============ Biểu đồ realtime dưới mỗi thẻ cảm biến ============
           4 mini line-chart tương ứng 4 cảm biến, tự đẩy điểm mới vào mỗi
           khi có dữ liệu MQTT tới (xem pushDashboardChartPoint trong script
           bên dưới), song song với việc cập nhật số ở .sensor-value. ==== */
        .mini-chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-6);
            margin-bottom: var(--space-8);
        }
        .mini-chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-2xl);
            padding: var(--space-4) var(--space-5);
            box-shadow: var(--shadow-sm);
        }
        .mini-chart-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: var(--space-2);
        }
        .mini-chart-title {
            font-size: var(--text-xs); font-weight: var(--font-semibold);
            color: var(--text-secondary);
        }
        .mini-chart-live {
            display: flex; align-items: center; gap: 5px;
            font-size: 10px; font-weight: var(--font-semibold);
            color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;
        }
        .mini-chart-live .mini-chart-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--success, #22c55e);
            animation: mini-chart-pulse 1.6s ease-in-out infinite;
        }
        @keyframes mini-chart-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .3; }
        }
        .mini-chart-canvas-wrap { height: 120px; position: relative; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../components/sidebar_light.php'; ?>

        <main class="main-content">
            <header class="header">
                <!-- Left: Title -->
                <div class="header-left">
                    <h2 class="header-title" >Tổng quan hệ thống</h2>
                </div>
                <!-- Center: Clock -->
                <div class="header-center">
                    <div class="clock" id="clock">--:--:--</div>
                </div>
                <!-- Right: Notification -->
                <div class="header-right">
                    <a href="thongbaoW.php" class="notif-wrapper" title="Thông báo" style="text-decoration:none;">
                        <div class="notif-btn"><span class="material-symbols-outlined">notifications</span></div>
                        <span class="notif-badge" id="notif-badge"></span>
                    </a>
                </div>
            </header>

            <div class="content">
                <div class="container">
                    <div class="sensor-grid">
                        <div class="sensor-card temp">
                            <div class="sensor-header">
                                <div class="sensor-icon">
                                    <span class="material-symbols-outlined">thermostat</span>
                                </div>
                                <div class="sensor-badge">Cảm biến 1</div>
                            </div>
                            <div class="sensor-label">Nhiệt độ</div>
                            <div class="sensor-value">
                                <span id="val-temp">--</span>
                                <span class="sensor-unit">°C</span>
                            </div>
                        </div>

                        <div class="sensor-card humidity">
                            <div class="sensor-header">
                                <div class="sensor-icon">
                                    <span class="material-symbols-outlined">water_drop</span>
                                </div>
                                <div class="sensor-badge">Cảm biến 2</div>
                            </div>
                            <div class="sensor-label">Độ ẩm</div>
                            <div class="sensor-value">
                                <span id="val-hum">--</span>
                                <span class="sensor-unit">%</span>
                            </div>
                        </div>


                        <div class="sensor-card light">
                            <div class="sensor-header">
                                <div class="sensor-icon">
                                    <span class="material-symbols-outlined">light_mode</span>
                                </div>
                                <div class="sensor-badge">Cảm biến 3</div>
                            </div>
                            <div class="sensor-label">Ánh sáng</div>
                            <div class="sensor-value">
                                <span id="val-lux">--</span>
                                <span class="sensor-unit">Lux</span>
                            </div>
                        </div>

                        <!-- Water Card -->
                        <div class="sensor-card ph">
                            <div class="sensor-header">
                                <div class="sensor-icon">
                                    <span class="material-symbols-outlined" >water</span>
                                </div>
                                <div class="sensor-badge">Cảm biến 4</div>
                            </div>
                            <div class="sensor-label">Mức nước</div>
                            <div class="sensor-value">
                                <span id="val-ph">--</span>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ realtime 4 cảm biến -->
                    <div class="mini-chart-grid">
                        <div class="mini-chart-card">
                            <div class="mini-chart-header">
                                <span class="mini-chart-title">Biểu đồ Nhiệt độ</span>
                                <span class="mini-chart-live"><span class="mini-chart-dot"></span>Realtime</span>
                            </div>
                            <div class="mini-chart-canvas-wrap"><canvas id="chart-temp"></canvas></div>
                        </div>

                        <div class="mini-chart-card">
                            <div class="mini-chart-header">
                                <span class="mini-chart-title">Biểu đồ Độ ẩm</span>
                                <span class="mini-chart-live"><span class="mini-chart-dot"></span>Realtime</span>
                            </div>
                            <div class="mini-chart-canvas-wrap"><canvas id="chart-hum"></canvas></div>
                        </div>

                        <div class="mini-chart-card">
                            <div class="mini-chart-header">
                                <span class="mini-chart-title">Biểu đồ Ánh sáng</span>
                                <span class="mini-chart-live"><span class="mini-chart-dot"></span>Realtime</span>
                            </div>
                            <div class="mini-chart-canvas-wrap"><canvas id="chart-light"></canvas></div>
                        </div>

                        <div class="mini-chart-card">
                            <div class="mini-chart-header">
                                <span class="mini-chart-title">Biểu đồ Mực nước</span>
                                <span class="mini-chart-live"><span class="mini-chart-dot"></span>Realtime</span>
                            </div>
                            <div class="mini-chart-canvas-wrap"><canvas id="chart-water"></canvas></div>
                        </div>
                    </div>

                    <!-- Control Panel -->
                    <div class="control-panel">
                        <div class="control-header">
                            <div class="control-header-icon">
                                <span class="material-symbols-outlined" style="font-size: 25px;">missing_controller</span>
                            </div>
                            <h3>Trung tâm điều khiển</h3>
                        </div>
                        
                        <div class="control-grid">
                            <!-- Pump Control -->
                            <div class="control-item">
                                <div class="control-top">
                                    <div class="control-icon-box pump">
                                        <span class="material-symbols-outlined">water_pump</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="sw-pump" onchange="toggleDevice('maybom', 'sw-pump')">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="control-info">
                                    <h4>Máy Bơm</h4>
                                    <p class="control-status" id="txt-pump">Đang đồng bộ...</p>
                                </div>
                            </div>

                            <!-- Light Control -->
                            <div class="control-item">
                                <div class="control-top">
                                    <div class="control-icon-box light">
                                        <span class="material-symbols-outlined">wb_incandescent</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="sw-light" onchange="toggleDevice('denled', 'sw-light')">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="control-info">
                                    <h4>Đèn</h4>
                                    <p class="control-status" id="txt-light">Đang đồng bộ...</p>
                                </div>
                            </div>

                            <!-- Fan Control -->
                            <div class="control-item">
                                <div class="control-top">
                                    <div class="control-icon-box fan">
                                        <span class="material-symbols-outlined">wind_power</span>                                    
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="sw-fan" onchange="toggleDevice('quatgio', 'sw-fan')">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="control-info">
                                    <h4>Quạt gió</h4>
                                    <p class="control-status" id="txt-fan">Đang đồng bộ...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phân tích AI -->
                    <div class="control-panel" id="ai-panel" style="margin-top: var(--space-6);">
                        <div class="control-header">
                            <div class="control-header-icon">
                                <span class="material-symbols-outlined" style="font-size: 20px;">insights</span>
                            </div>
                            <h3>Phân tích AI <span style="font-weight:400; color:var(--text-muted); font-size:var(--text-sm);">· Edge (ESP32)</span></h3>
                            <div style="margin-left:auto; display:flex; align-items:center; gap:var(--space-2);">
                                <span id="ai-mode-badge" class="ai-badge ai-badge-idle">--</span>
                                <span id="ai-status-badge" class="ai-badge ai-badge-idle">Đang chờ dữ liệu...</span>
                            </div>
                        </div>

                        <div style="padding: var(--space-6);">
                            <!-- Lưới chỉ số -->
                            <div class="ai-stats">
                                <div>
                                    <div class="ai-stat-label">Loại</div>
                                    <div id="ai-stat-type" class="ai-stat-value" style="font-size: var(--text-lg);">--</div>
                                </div>
                                <div>
                                    <div class="ai-stat-label">Mức độ</div>
                                    <div id="ai-stat-severity" class="ai-stat-value">--</div>
                                </div>
                                <div>
                                    <div class="ai-stat-label">Khung giờ</div>
                                    <div id="ai-stat-window" class="ai-stat-value" style="font-size: var(--text-lg);">--</div>
                                </div>
                                <div>
                                    <div class="ai-stat-label">Cập nhật</div>
                                    <div id="ai-stat-time" class="ai-stat-value" style="font-size: var(--text-lg);">--:--:--</div>
                                    <div class="ai-stat-sub" id="ai-stat-device">Thiết bị #--</div>
                                </div>
                            </div>

                            <p id="ai-summary" class="ai-summary-text">
                                Chưa có dữ liệu phân tích. Đảm bảo ESP32 đang publish kết quả AI.
                            </p>

                            <div id="ai-recommendation" class="ai-reco-row" style="display:none;">
                                <span id="ai-reco-chip" class="ai-reco-chip">--</span>
                                <span id="ai-reco-msg" class="ai-reco-msg"></span>
                            </div>

                            <div id="ai-action-box" class="ai-action-box" style="display:none;">
                                <span class="material-symbols-outlined" style="font-size:18px;">bolt</span>
                                <span id="ai-action-text"></span>
                            </div>

                            <div class="ai-table-header">Lịch sử phân tích</div>
                            <div class="ai-table-wrap">
                                <table class="ai-table">
                                    <thead>
                                        <tr>
                                            <th>Thời gian</th>
                                            <th>Ngày</th>
                                            <th>Thiết bị</th>
                                            <th>Khung giờ</th>
                                            <th>Loại</th>
                                            <th>Mức độ</th>
                                            <th>Diễn giải</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ai-table-body">
                                        <tr><td colspan="7" class="ai-table-empty">Chưa có bản ghi nào.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="../js/auth.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/notification.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mqtt/dist/mqtt.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/config.js"></script>
    <script src="../js/mqtt.js"></script>
    <script>

        // Get User ID
        function getUserId() {
            try {
                const user = JSON.parse(localStorage.getItem('user_info'));
                return user.id || user.id_nguoidung;
            } catch {
                return null;
            }
        }

        // Load Dashboard Data
        async function loadData() {
            const userId = getUserId();
            if (!userId) return;

            try {
                const res = await fetch(`${API_BASE}/dashboard/tongquat.php?user_id=${userId}`);
                const json = await res.json();
                
                if (json.status === 'success') {
                    const { device, sensor } = json.data;

                    // Update sensor values
                    if (sensor) {
                        document.getElementById('val-temp').innerText = sensor.nhiet_do;
                        document.getElementById('val-hum').innerText = sensor.do_am;
                        document.getElementById('val-lux').innerText = sensor.anh_sang;
                        document.getElementById('val-ph').innerText = sensor.ph;
                    }

                    // Update device switches
                    if (device) {
                        updateSwitch('sw-pump', 'txt-pump', device.maybom);
                        updateSwitch('sw-light', 'txt-light', device.denled);
                        updateSwitch('sw-fan', 'txt-fan', device.quatgio);
                    }
                }
            } catch (e) {
                console.error("Lỗi tải dữ liệu:", e);
            }
        }

        // Update Switch
        function updateSwitch(idBox, idText, val) {
            const checkbox = document.getElementById(idBox);
            const statusText = document.getElementById(idText);
            
            if (checkbox && statusText) {
                const isOn = (val == 1);
                checkbox.checked = isOn;
                statusText.innerText = isOn ? "Đang chạy" : "Đã tắt";
                statusText.className = isOn ? "control-status active" : "control-status";
            }
        }

        // Toggle Device
        async function toggleDevice(field, idBox) {
    const userId = getUserId();
    if (!userId) { alert('Phiên đăng nhập hết hạn!'); return; }

    const checkbox = document.getElementById(idBox);
    const val = checkbox.checked ? 1 : 0;
    checkbox.disabled = true;

    try {
        // Gửi lệnh qua MQTT đến firmware
        mqttPublishControl(field, val);

        // Ghi log vào DB
        const res = await fetch(`${API_BASE}/dieukhien/update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ field, value: val, user_id: userId })
        });
        const data = await res.json();
        console.log(data);

        if (data.status !== 'success') {
            alert('Lỗi: ' + data.message);
            checkbox.checked = !checkbox.checked;
        } else {
            updateSwitch(idBox, idBox.replace('sw-', 'txt-'), val);
        }
    } catch(e) {
        console.error(e);
        checkbox.checked = !checkbox.checked;
    } finally {
        checkbox.disabled = false;
    }
}

        // Logout
        function logout() {
            localStorage.clear();
            window.location.href = 'login.php';
        }
        async function loadDeviceId() {
    const userId = getUserId();
    if (!userId) return;
    try {
        const res = await fetch(`${API_BASE}/dashboard/tongquat.php?user_id=${userId}`);
        const json = await res.json();
        if (json.status === 'success') {
            window.cachedDeviceId = json.data.device_id;
            console.log('📦 Device ID:', window.cachedDeviceId);
        }
    } catch (e) {
        console.error('Lỗi lấy device ID:', e);
    }
}


// ============================================================
// Biểu đồ realtime 4 cảm biến (dùng lại SENSOR_CONFIG + API.SENSOR_HISTORY
// đã có sẵn ở config.js / API history.php của trang Lịch sử dữ liệu)
// ============================================================

// ---- CÁC HẰNG SỐ GIỚI HẠN — chỉnh trực tiếp ở đây khi cần ----
const DASHBOARD_CHART_RANGE_DAYS = 2;   // phạm vi lịch sử tải khi mở trang: 2 ngày gần nhất (đổi thành 7 nếu muốn 1 tuần)
const DASHBOARD_CHART_MAX_POINTS = 40;  // số điểm tối đa hiển thị mỗi biểu đồ (áp dụng cả khi tải lịch sử lẫn khi nhận realtime)

// Khoảng giá trị trục Y cố định cho từng cảm biến — để độ cao đường biểu đồ
// phản ánh đúng bản chất từng loại (không tự auto-scale theo min/max của
// chính tập dữ liệu như mặc định của Chart.js). Số liệu lấy theo đúng dải
// giá trị thật của cảm biến (giống config trang Lịch sử — Frontend/js/history.js),
// KHÔNG dùng min/max trong SENSOR_CONFIG của config.js vì số ở đó đang sai/cũ
// (vd "Mức nước" ghi 4-10 trong khi giá trị thật hàng nghìn).
const DASHBOARD_CHART_Y_RANGE = {
    [SENSOR_IDS.TEMPERATURE]: { min: 15,   max: 45 },     // °C
    [SENSOR_IDS.HUMIDITY]:    { min: 40,   max: 100 },     // %
    [SENSOR_IDS.PH]:          { min: 500,    max: 2500 },   // Mực nước
    [SENSOR_IDS.LIGHT]:       { min: 0,    max: 2500 },  // Lux
};

window.dashboardCharts = {};

const DASHBOARD_CHARTS_CONFIG = [
    { key: 'temp',  canvasId: 'chart-temp',  sensorId: SENSOR_IDS.TEMPERATURE, field: 'nhiet_do' },
    { key: 'hum',   canvasId: 'chart-hum',   sensorId: SENSOR_IDS.HUMIDITY,    field: 'do_am' },
    { key: 'light', canvasId: 'chart-light', sensorId: SENSOR_IDS.LIGHT,       field: 'anh_sang' },
    { key: 'water', canvasId: 'chart-water', sensorId: SENSOR_IDS.PH,          field: 'muc_nuoc' },
];

// Lấy đều maxPoints phần tử trong mảng (dùng khi lịch sử DB trong khoảng
// DASHBOARD_CHART_RANGE_DAYS ngày có quá nhiều bản ghi để vẽ hết).
function downsampleRows(rows, maxPoints) {
    if (rows.length <= maxPoints) return rows;
    const step = rows.length / maxPoints;
    const result = [];
    for (let i = 0; i < maxPoints; i++) {
        result.push(rows[Math.floor(i * step)]);
    }
    return result;
}

function buildMiniChart(cfg) {
    const canvas = document.getElementById(cfg.canvasId);
    if (!canvas || typeof Chart === 'undefined') return null;

    const meta = SENSOR_CONFIG[cfg.sensorId] || {};
    const color = meta.color || '#00b4d8';
    const yRange = DASHBOARD_CHART_Y_RANGE[cfg.sensorId] || {};

    const ctx = canvas.getContext('2d');
    const height = canvas.parentElement.offsetHeight || 110;
    const gradient = ctx.createLinearGradient(0, 0, 0, height);
    gradient.addColorStop(0, color + '55');
    gradient.addColorStop(1, color + '00');

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                data: [],
                borderColor: color,
                backgroundColor: gradient,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHitRadius: 12,
                pointHoverRadius: 4,
                pointHoverBackgroundColor: color,
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false, // tắt animation để cập nhật realtime mượt, không giật
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a2332',
                    titleColor: '#9aa5b1',
                    bodyColor: '#ffffff',
                    borderColor: color,
                    borderWidth: 1,
                    padding: 8,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        title: () => '',
                        label: (c) => `${c.parsed.y}${meta.unit ? ' ' + meta.unit : ''}`
                    }
                }
            },
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { display: false },
                y: {
                    display: true,   // hiện trục Y để thấy rõ ngưỡng trên/dưới
                    min: yRange.min,
                    max: yRange.max,
                    ticks: {
                        maxTicksLimit: 4,       // chỉ hiện ~4 mốc, tránh rối vì khung nhỏ
                        font: { size: 9 },
                        color: '#94a3b8',
                        padding: 4,
                        callback: (v) => {
                            // rút gọn số lớn (10000 -> 10k) cho gọn trong khung nhỏ
                            if (Math.abs(v) >= 1000) return (v / 1000) + 'k';
                            return v;
                        }
                    },
                    grid: {
                        color: 'rgba(148,163,184,0.15)',
                        drawTicks: false,
                    },
                    border: { display: false },
                }
            }
        }
    });
}

// Nạp sẵn lịch sử DASHBOARD_CHART_RANGE_DAYS ngày gần nhất từ DB (API.SENSOR_HISTORY)
// để biểu đồ không trống khi vừa mở trang, và không kéo hết toàn bộ lịch sử.
async function loadDashboardChartHistory(cfg) {
    const userId = getUserId();
    if (!userId) return;
    try {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - DASHBOARD_CHART_RANGE_DAYS);
        const fmt = (d) => d.toISOString().split('T')[0]; // YYYY-MM-DD

        const url = `${API.SENSOR_HISTORY}?user_id=${userId}&sensor_id=${cfg.sensorId}`
            + `&start=${fmt(start)}&end=${fmt(end)}`;

        const res = await fetch(url);
        const json = await res.json();
        const chart = window.dashboardCharts[cfg.key];
        if (json.status === 'success' && Array.isArray(json.data) && chart) {
            const rows = downsampleRows(json.data, DASHBOARD_CHART_MAX_POINTS);
            chart.data.labels = rows.map(row => {
                const parts = (row.thoigian || '').split(' ');
                return parts.length > 1 ? parts[1].substring(0, 5) : (row.thoigian || '');
            });
            chart.data.datasets[0].data = rows.map(row => parseFloat(row.giatri));
            chart.update();
        }
    } catch (e) {
        console.warn(`Không tải được lịch sử biểu đồ [${cfg.key}]:`, e);
    }
}

function initDashboardCharts() {
    if (typeof Chart === 'undefined') { console.warn('Chart.js chưa được nạp'); return; }
    DASHBOARD_CHARTS_CONFIG.forEach(cfg => {
        window.dashboardCharts[cfg.key] = buildMiniChart(cfg);
        loadDashboardChartHistory(cfg);
    });
}

// Đẩy 1 điểm dữ liệu mới vào cả 4 biểu đồ — được mqtt.js gọi mỗi khi nhận
// được tin nhắn cảm biến mới (topic aquaponic/sensor/data), cùng lúc với
// lúc các ô số .sensor-value được cập nhật, nên biểu đồ luôn đồng bộ realtime
// với phần số liệu phía trên. Giới hạn theo DASHBOARD_CHART_MAX_POINTS: điểm
// cũ nhất bị đẩy ra khi vượt quá, để biểu đồ luôn chỉ thể hiện khoảng thời
// gian gần nhất, không phình to vô hạn theo thời gian chạy.
function pushDashboardChartPoint(data) {
    const now = new Date();
    const label = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    DASHBOARD_CHARTS_CONFIG.forEach(cfg => {
        const chart = window.dashboardCharts[cfg.key];
        const value = data[cfg.field];
        if (!chart || value === undefined) return;

        chart.data.labels.push(label);
        chart.data.datasets[0].data.push(parseFloat(value));

        if (chart.data.labels.length > DASHBOARD_CHART_MAX_POINTS) {
            chart.data.labels.shift();
            chart.data.datasets[0].data.shift();
        }
        chart.update('none'); // 'none' = không animation, tránh giật khi update liên tục
    });
}

async function loadAIHistory() {
    const userId = getUserId();
    if (!userId) return;
    try {
        const res  = await fetch(`${API.AI_HISTORY}?user_id=${userId}&limit=15`);
        const json = await res.json();
        if (json.status === 'success' && Array.isArray(json.data)) {
            initAIHistoryFromDB(json.data);
        }
    } catch (e) {
        console.warn('Chưa nạp được lịch sử AI :', e);//(có thể bảng ai_ket_qua chưa tồn tại)
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    initDashboardCharts();
    await loadDeviceId();
    loadAIHistory();
    connectMQTT();
    loadData();
});
    </script>
</body>
</html>