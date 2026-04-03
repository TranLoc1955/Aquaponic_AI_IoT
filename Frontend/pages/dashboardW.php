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

                    <!-- Control Panel -->
                    <div class="control-panel">
                        <div class="control-header">
                            <div class="control-header-icon">
                                <span class="material-symbols-outlined" style="font-size: 25px;">
missing_controller
</span>
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
                                        <span class="material-symbols-outlined">shower</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="sw-fan" onchange="toggleDevice('quatgio', 'sw-fan')">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            <!--Phun sương sẽ được điều khiển bằng quạt gió, nên vẫn dùng tên quatgio để gửi lệnh lên API-->
                                <div class="control-info">
                                    <h4>Phun Sương</h4>
                                    <p class="control-status" id="txt-fan">Đang đồng bộ...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="../js/auth.js"></script>
    <script src="../js/config.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/notification.js"></script>
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
            if (!userId) {
                alert("Phiên đăng nhập hết hạn!");
                return;
            }

            const checkbox = document.getElementById(idBox);
            const val = checkbox.checked ? 1 : 0;
            
            checkbox.disabled = true;

            try {
                const res = await fetch(`${API_BASE}/dieukhien/update.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ field: field, value: val, user_id: userId })
                });
                const data = await res.json();
                
                if(data.status !== 'success') {
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

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            // Auth is handled by auth.js

            loadData();
            setInterval(loadData, 3000); // Poll every 5 seconds
        });
    </script>
</body>
</html>