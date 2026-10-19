<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Cài đặt Cấu hình - HydroSmart AIoT</title>

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/cauhinh.css?v=1005">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>

<body>
<div class="app-container">

    <?php include '../components/sidebar_light.php'; ?>

    <main class="main-content">

        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button type="button" class="sidebar-mobile-trigger" id="sidebar-mobile-trigger" aria-label="Mở menu">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="header-info">
                    <h2 class="header-title">Cài đặt cấu hình</h2>
                    <p class="header-subtitle">Ngưỡng cảnh báo an toàn & Kênh thông báo</p>
                </div>
            </div>

            <div class="header-center">
                <div class="clock" id="clock">--:--:--</div>
            </div>

            <div class="header-right">
                <a href="thongbaoW.php" class="notif-wrapper" title="Thông báo">
                    <div class="notif-btn">
                        <span class="material-symbols-outlined">notifications</span>
                    </div>
                    <span class="notif-badge" id="notif-badge"></span>
                </a>

                <button type="button" id="save-config-btn" class="header-save-btn" onclick="saveConfig()">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span>Lưu tất cả</span>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <div class="content">
            <div class="content-container">

                <!-- Quick Toolbar / Summary Strip -->
                <div class="config-toolbar">
                    <div class="toolbar-left">
                        <div class="stat-pill">
                            <span class="pulse-dot"></span>
                            <span>Trạng thái: <strong id="monitored-count">Đang tải...</strong></span>
                        </div>
                    </div>

                    <div class="toolbar-right">
                        <label class="master-toggle-box" title="Bật hoặc tắt giám sát cho toàn bộ cảm biến">
                            <span class="master-toggle-label" id="master-toggle-label">Bật tất cả</span>
                            <span class="toggle-switch">
                                <input type="checkbox" id="master-toggle" onchange="toggleAllSensors(this.checked)">
                                <span class="toggle-slider"></span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Settings 2-Column Grid -->
                <div class="settings-layout">

                    <!-- CỘT TRÁI: DANH SÁCH NGƯỠNG CẢM BIẾN -->
                    <section class="sensor-column">
                        <div class="section-header">
                            <div class="section-title">
                                <div class="section-icon-badge">
                                    <span class="material-symbols-outlined">tune</span>
                                </div>
                                <div>
                                    <h3>Ngưỡng cảnh báo cảm biến</h3>
                                    <p>Hệ thống sẽ kích hoạt cảnh báo khi giá trị đo thực tế nằm ngoài phạm vi này</p>
                                </div>
                            </div>
                        </div>

                        <!-- Danh sách cảm biến dạng lưới 2 cột -->
                        <div id="config-list" class="sensor-grid">
                            <div class="loading-box">
                                <span class="material-symbols-outlined animate-spin">progress_activity</span>
                                <p>Đang tải cấu hình cảm biến...</p>
                            </div>
                        </div>
                    </section>

                    <!-- CỘT PHẢI: KÊNH TELEGRAM & GỢI Ý -->
                    <aside class="telegram-column">

                        <!-- Card Cấu hình Telegram -->
                        <div class="telegram-card" id="telegram-card">
                            <div class="telegram-head">
                                <div class="tg-brand">
                                    <div class="tg-icon">
                                        <span class="material-symbols-outlined">send</span>
                                    </div>
                                    <div class="tg-title">
                                        <h4>Cảnh báo Telegram</h4>
                                        <p>Gửi tin nhắn tức thì qua bot</p>
                                    </div>
                                </div>

                                <label class="toggle-switch" title="Bật/Tắt gửi qua Telegram">
                                    <input type="checkbox" id="tg-status" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="telegram-form">
                                <div class="field-group">
                                    <label class="field-label" for="tg-token">Bot Token</label>
                                    <div class="input-password-wrap">
                                        <input type="password" id="tg-token" class="telegram-input" placeholder="Nhập Bot Token (7785506442:AAFd...)">
                                        <button type="button" class="btn-toggle-eye" onclick="toggleTokenVisibility()" title="Ẩn/Hiện Token">
                                            <span class="material-symbols-outlined" id="tg-eye-icon" style="font-size: 18px;">visibility</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <label class="field-label" for="tg-chatid">Chat ID</label>
                                    <input type="text" id="tg-chatid" class="telegram-input" placeholder="Nhập Chat ID cá nhân hoặc nhóm">
                                </div>
                            </div>

                            <div class="telegram-actions">
                                <button type="button" id="btn-test-tg" onclick="testTelegram()" class="btn-tg-test" title="Kiểm tra gửi tin nhắn">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">send</span>
                                    <span>Gửi thử</span>
                                </button>

                                <button type="button" id="btn-save-tg" onclick="saveTelegram()" class="btn-tg-save" title="Lưu cấu hình Telegram">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">save</span>
                                    <span>Lưu</span>
                                </button>
                            </div>

                            <!-- Hướng dẫn nhanh Telegram -->
                            <div class="tg-guide-box">
                                <details class="tg-guide-details">
                                    <summary>
                                        <span>Cách lấy Bot Token & Chat ID</span>
                                        <span class="material-symbols-outlined" style="font-size: 16px;">expand_more</span>
                                    </summary>
                                    <div class="tg-guide-content">
                                        <ol>
                                            <li>Chat với <code>@BotFather</code> trên Telegram &gt; gửi lệnh <code>/newbot</code> để tạo bot và nhận Token.</li>
                                            <li>Chat với <code>@userinfobot</code> trên Telegram &gt; gửi tin nhắn bất kỳ để lấy Chat ID của bạn.</li>
                                            <li>Bấm <strong>Gửi thử</strong> để xác nhận kết nối trước khi lưu.</li>
                                        </ol>
                                    </div>
                                </details>
                            </div>
                        </div>

                        <!-- Tips Box -->
                        <div class="tips-card">
                            <div class="tips-head">
                                <span class="material-symbols-outlined">lightbulb</span>
                                <span>Mẹo thiết lập</span>
                            </div>
                            <ul class="tips-list">
                                <li>Ô để trống nghĩa là <strong>không giới hạn</strong> ngưỡng đó (ví dụ: chỉ cảnh báo nhiệt độ cao thì chỉ điền Max).</li>
                                <li>Tắt công tắc cảm biến sẽ <strong>tạm dừng cảnh báo</strong> cho chỉ số đó.</li>
                                <li>Nhớ bấm <strong>Lưu tất cả</strong> sau khi thay đổi ngưỡng.</li>
                            </ul>
                        </div>

                    </aside>

                </div>

            </div>
        </div>

    </main>
</div>

<!-- Floating Toast Container -->
<div id="toast-container" class="toast-container"></div>

<!-- Scripts -->
<script src="../js/auth.js"></script>
<script src="../js/config.js"></script>
<script src="../js/notification.js"></script>
<script src="../js/cauhinh.js"></script>
</body>
</html>