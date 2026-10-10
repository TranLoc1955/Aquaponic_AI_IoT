<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Cấu hình Cảnh báo - HydroSmart</title>

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/cauhinh.css?v=999">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>

<body>
<div class="app-container">

    <?php include '../components/sidebar_light.php'; ?>

    <main class="main-content">

        <header class="header">
            <div class="header-left">
                <div class="header-info">
                    <h2 class="header-title">Thiết lập ngưỡng cảnh báo</h2>
                    <p class="header-subtitle">Quản lý các quy tắc an toàn cho vườn</p>
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

                <div class="save-all-frame">
    <button type="button" id="save-config-btn" class="header-save-btn" onclick="saveConfig()">
        <span class="material-symbols-outlined">save</span>
        <span class="save-text">Lưu tất cả</span>
    </button>
</div>
            </div>
        </header>

        <div class="content">
            <div class="content-container">

                <div class="settings-layout">

                    <!-- CỘT TRÁI: DANH SÁCH CẢM BIẾN -->
                    <section class="sensor-column">
                        <div class="section-heading">
                            <div>
                                <h3>Danh sách cảm biến</h3>
                                <p>Cài đặt ngưỡng cảnh báo cho từng chỉ số trong vườn</p>
                            </div>
                        </div>

                        <div id="config-list" class="sensor-list">
                            <div class="loading-box">
                                <span class="material-symbols-outlined animate-spin">progress_activity</span>
                                <p>Đang tải cấu hình...</p>
                            </div>
                        </div>
                    </section>

                    <!-- CỘT PHẢI: TELEGRAM -->
                    <aside class="telegram-column">

                        <div class="telegram-card" id="telegram-card">
                            <div class="telegram-header">
                                <div class="sensor-info">
                                    <div class="telegram-icon">
                                        <span class="material-symbols-outlined">send</span>
                                    </div>

                                    <div>
                                        <h3 class="sensor-name">Telegram</h3>
                                        <p class="sensor-unit">Nhận cảnh báo trực tiếp trên điện thoại</p>
                                    </div>
                                </div>

                                <label class="toggle-switch">
                                    <input type="checkbox" id="tg-status" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="telegram-form">
                                <div class="input-field">
                                    <label class="input-label">Bot Token</label>
                                    <input type="text" id="tg-token" class="telegram-input" placeholder="Nhập Bot Token">
                                </div>

                                <div class="input-field">
                                    <label class="input-label">Chat ID</label>
                                    <input type="text" id="tg-chatid" class="telegram-input" placeholder="Nhập Chat ID">
                                </div>
                            </div>

                            <div class="telegram-actions">
                                <button type="button" onclick="testTelegram()" class="telegram-test-btn">
                                    <span class="material-symbols-outlined">send</span>
                                    Gửi thử
                                </button>

                                <button type="button" onclick="saveTelegram()" class="save-button">
                                    <span class="material-symbols-outlined">save</span>Lưu</button>
                            </div>
                        </div>

                        <div class="info-box telegram-help">
                            <span class="material-symbols-outlined">live_help</span>
                            <div class="info-text">
                                <p><strong>Gợi ý Telegram:</strong></p>
                                <ul>
                                    <li>Nhập đúng Bot Token và Chat ID.</li>
                                    <li>Bấm <strong>Gửi thử</strong> để kiểm tra kết nối.</li>
                                    <li>Bật công tắc để cho phép gửi cảnh báo.</li>
                                </ul>
                            </div>
                        </div>

                    </aside>

                </div>

                <div class="info-box mt-8 flex items-start gap-3">
                    <span class="material-symbols-outlined">live_help</span>

                    <div class="info-text">
                        <p><strong>Hướng dẫn</strong></p>
                        <ul>
                            <li>Bật nút <strong>Giám sát</strong> để kích hoạt cảnh báo cho cảm biến đó.</li>
                            <li>Các ô để trống nghĩa là không giới hạn ngưỡng đó.</li>
                            <li>Bấm nút <strong>Thùng rác</strong> để xóa nhanh cấu hình và tắt giám sát.</li>
                            <li>Nhớ bấm <strong>Lưu tất cả</strong> sau khi chỉnh sửa.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

    </main>
</div>

<script src="../js/auth.js"></script>
<script src="../js/config.js"></script>
<script src="../js/notification.js"></script>
<script src="../js/cauhinh.js"></script>
<script>

const API_TELEGRAM = API_BASE + '/cauhinh/telegram.php';

function getUserIdCH() {
    try { return JSON.parse(localStorage.getItem('user_info')).id; } catch { return null; }
}

// Load cấu hình Telegram khi vào trang
async function loadTelegram() {
    const userId = getUserIdCH();
    if (!userId) return;
    try {
        const res  = await fetch(`${API_TELEGRAM}?user_id=${userId}`);
        const json = await res.json();
        if (json.status === 'success' && json.data) {
            document.getElementById('tg-token').value  = json.data.bot_token || '';
            document.getElementById('tg-chatid').value = json.data.chat_id   || '';
            document.getElementById('tg-status').checked = json.data.trangthai == 1;
        }
    } catch(e) { console.error(e); }
}

// Lưu cấu hình Telegram
async function saveTelegram() {
    const userId  = getUserIdCH();
    const token   = document.getElementById('tg-token').value.trim();
    const chat_id = document.getElementById('tg-chatid').value.trim();
    const status  = document.getElementById('tg-status').checked ? 1 : 0;

    if (!token || !chat_id) { alert('Vui lòng nhập đủ Token và Chat ID!'); return; }

    const res  = await fetch(API_TELEGRAM, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, bot_token: token, chat_id, trangthai: status })
    });
    const json = await res.json();
    alert(json.status === 'success' ? '✅ Đã lưu cấu hình Telegram!' : '❌ Lỗi: ' + json.message);
}

// Gửi tin nhắn thử
async function testTelegram() {
    const token   = document.getElementById('tg-token').value.trim();
    const chat_id = document.getElementById('tg-chatid').value.trim();
    if (!token || !chat_id) { alert('Vui lòng nhập Token và Chat ID trước!'); return; }

    const url = `https://api.telegram.org/bot${token}/sendMessage`;
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chat_id, text: '✅ HydroSmart đã kết nối thành công!' })
    });
    const json = await res.json();
    alert(json.ok ? '✅ Gửi thử thành công! Kiểm tra Telegram nhé!' : '❌ Lỗi: ' + json.description);
}

document.addEventListener('DOMContentLoaded', loadTelegram);
</script>
</body>
</html>