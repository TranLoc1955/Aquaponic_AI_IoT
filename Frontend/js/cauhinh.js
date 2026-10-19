// cauhinh.js - Logic Quản lý Cấu hình Cảnh báo & Telegram
// HydroSmart / Aquaponic AIoT

const API_ROOT = (typeof API_BASE !== 'undefined' ? API_BASE : 'http://localhost/Test/API').replace(/\/$/, '');
const API_GET = `${API_ROOT}/cauhinh/get_config.php`;
const API_SAVE = `${API_ROOT}/cauhinh/save_config.php`;
const API_TELEGRAM = `${API_ROOT}/cauhinh/telegram.php`;

// Metadata cho các loại cảm biến
const SENSOR_META = {
    1: { icon: 'thermostat', name: 'Nhiệt độ', unit: '°C', theme: 'theme-temp' },
    2: { icon: 'water_drop', name: 'Độ ẩm', unit: '%', theme: 'theme-humidity' },
    3: { icon: 'waves', name: 'Mức nước', unit: 'cm', theme: 'theme-water' },
    4: { icon: 'light_mode', name: 'Ánh sáng', unit: 'Lux', theme: 'theme-light' }
};

/**
 * Lấy User ID từ localStorage
 */
function getCurrentUserId() {
    try {
        const user = JSON.parse(localStorage.getItem('user_info'));
        return user?.id || user?.id_nguoidung || null;
    } catch {
        return null;
    }
}

/**
 * Hiển thị Toast thông báo hiện đại
 */
function showToast(message, type = 'success', duration = 3500) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const iconMap = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="material-symbols-outlined toast-icon">${iconMap[type] || 'info'}</span>
        <div class="toast-msg">${message}</div>
        <button type="button" class="toast-close" aria-label="Đóng" onclick="this.parentElement.remove()">
            <span class="material-symbols-outlined" style="font-size:16px;">close</span>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 250);
    }, duration);
}

/**
 * Tải danh sách cấu hình cảm biến từ server
 */
async function loadConfig() {
    const userId = getCurrentUserId();

    if (!userId) {
        window.location.href = 'login.php';
        return;
    }

    const container = document.getElementById('config-list');
    if (container) {
        container.innerHTML = `
            <div class="loading-box">
                <span class="material-symbols-outlined animate-spin">progress_activity</span>
                <p>Đang tải cấu hình cảm biến...</p>
            </div>
        `;
    }

    try {
        const res = await fetch(`${API_GET}?user_id=${encodeURIComponent(userId)}`);
        const json = await res.json();

        if (json.status === 'success') {
            renderForm(json.data || []);
        } else {
            showToast('Lỗi: ' + json.message, 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('Không thể kết nối đến máy chủ', 'error');
    }
}

/**
 * Tạo mô tả vùng an toàn cho cảm biến
 */
function getSafeZoneText(min, max, unit) {
    const hasMin = min !== '' && min !== null && !isNaN(min);
    const hasMax = max !== '' && max !== null && !isNaN(max);

    if (hasMin && hasMax) {
        return `Vùng an toàn: <strong>${min}${unit} ~ ${max}${unit}</strong>`;
    } else if (hasMin) {
        return `Cảnh báo khi <strong>&lt; ${min}${unit}</strong>`;
    } else if (hasMax) {
        return `Cảnh báo khi <strong>&gt; ${max}${unit}</strong>`;
    } else {
        return `<em>Chưa đặt giới hạn ngưỡng</em>`;
    }
}

/**
 * Render giao diện các thẻ cảm biến
 */
function renderForm(configs) {
    const container = document.getElementById('config-list');
    if (!container) return;

    if (!configs || configs.length === 0) {
        container.innerHTML = `
            <div class="empty-box">
                <span class="material-symbols-outlined">sensors_off</span>
                <p>Chưa có cấu hình cảm biến nào cho thiết bị này</p>
            </div>
        `;
        updateSummary();
        return;
    }

    let html = '';

    configs.forEach(cfg => {
        const m = SENSOR_META[cfg.idcambien] || {
            icon: 'sensors',
            name: cfg.loaicambien || 'Cảm biến',
            unit: cfg.donvido || '',
            theme: 'theme-default'
        };

        const sensorName = (cfg.loaicambien && cfg.loaicambien.trim() !== '') ? cfg.loaicambien : m.name;
        const unit = (cfg.donvido && cfg.donvido.trim() !== '') ? cfg.donvido : (m.unit || '');
        const isChecked = cfg.trangthai == 1;

        const maxValue = (cfg.nguongtren !== null && cfg.nguongtren !== undefined && cfg.nguongtren !== '')
            ? cfg.nguongtren
            : '';

        const minValue = (cfg.nguongduoi !== null && cfg.nguongduoi !== undefined && cfg.nguongduoi !== '')
            ? cfg.nguongduoi
            : '';

        const safeZoneText = getSafeZoneText(minValue, maxValue, unit);

        html += `
            <div class="sensor-card ${isChecked ? 'active' : 'is-disabled'}" id="card-${cfg.id}" data-id="${cfg.id}" data-unit="${unit}">
                
                <!-- Card Header -->
                <div class="card-head">
                    <div class="sensor-meta">
                        <div class="sensor-icon-box ${m.theme}">
                            <span class="material-symbols-outlined">${m.icon}</span>
                        </div>
                        <div class="sensor-details">
                            <div class="sensor-name-row">
                                <h4 class="sensor-name">${sensorName}</h4>
                                ${unit ? `<span class="unit-tag">${unit}</span>` : ''}
                            </div>
                            <div class="sensor-status-label ${isChecked ? 'is-on' : ''}">
                                ${isChecked ? 'Đang giám sát' : 'Tạm tắt'}
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-head-actions">
                        <button type="button" 
                                onclick="clearCard('${cfg.id}')" 
                                class="card-reset-btn" 
                                title="Xóa ngưỡng & tắt giám sát"
                                aria-label="Xóa ngưỡng">
                            <span class="material-symbols-outlined">delete_outline</span>
                        </button>

                        <label class="toggle-switch" title="Bật/Tắt giám sát">
                            <input 
                                type="checkbox" 
                                class="toggle-status" 
                                ${isChecked ? 'checked' : ''} 
                                onchange="toggleCard('${cfg.id}')"
                            >
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Input Fields -->
                <div class="threshold-inputs">
                    <div class="field-group">
                        <label class="field-label">
                            <span>Ngưỡng dưới</span>
                            <span class="sub-sign">&lt; Min</span>
                        </label>
                        <div class="input-box">
                            <input 
                                type="number" 
                                step="0.1" 
                                class="input-min" 
                                placeholder="Không đặt"
                                value="${minValue}"
                                oninput="onThresholdInput('${cfg.id}')"
                            >
                            ${unit ? `<span class="unit-suffix">${unit}</span>` : ''}
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">
                            <span>Ngưỡng trên</span>
                            <span class="sub-sign">&gt; Max</span>
                        </label>
                        <div class="input-box">
                            <input 
                                type="number" 
                                step="0.1" 
                                class="input-max" 
                                placeholder="Không đặt"
                                value="${maxValue}"
                                oninput="onThresholdInput('${cfg.id}')"
                            >
                            ${unit ? `<span class="unit-suffix">${unit}</span>` : ''}
                        </div>
                    </div>
                </div>

                <!-- Safe Zone Indicator -->
                <div class="safe-zone-bar" id="safe-zone-${cfg.id}">
                    <span class="material-symbols-outlined">verified</span>
                    <span class="safe-zone-text">${safeZoneText}</span>
                </div>

            </div>
        `;
    });

    container.innerHTML = html;
    updateSummary();
}

/**
 * Xử lý khi người dùng nhập ngưỡng
 */
function onThresholdInput(id) {
    const card = document.getElementById(`card-${id}`);
    if (!card) return;

    // Tự động bật giám sát nếu đang tắt và người dùng nhập ngưỡng
    const toggle = card.querySelector('.toggle-status');
    if (toggle && !toggle.checked) {
        toggle.checked = true;
        toggleCard(id);
    } else {
        updateCardSafeZone(id);
    }
}

/**
 * Cập nhật dòng preview vùng an toàn
 */
function updateCardSafeZone(id) {
    const card = document.getElementById(`card-${id}`);
    if (!card) return;

    const unit = card.getAttribute('data-unit') || '';
    const minVal = card.querySelector('.input-min')?.value.trim() ?? '';
    const maxVal = card.querySelector('.input-max')?.value.trim() ?? '';
    const safeZoneEl = card.querySelector(`#safe-zone-${id} .safe-zone-text`);

    if (safeZoneEl) {
        safeZoneEl.innerHTML = getSafeZoneText(minVal, maxVal, unit);
    }
}

/**
 * Kích hoạt thẻ
 */
function activeCard(id) {
    const card = document.getElementById(`card-${id}`);
    if (!card) return;

    card.classList.add('active');
    card.classList.remove('is-disabled');

    const toggle = card.querySelector('.toggle-status');
    if (toggle) toggle.checked = true;

    const statusLabel = card.querySelector('.sensor-status-label');
    if (statusLabel) {
        statusLabel.textContent = 'Đang giám sát';
        statusLabel.classList.add('is-on');
    }

    updateCardSafeZone(id);
    updateSummary();
}

/**
 * Chuyển đổi trạng thái bật/tắt của cảm biến
 */
function toggleCard(id) {
    const card = document.getElementById(`card-${id}`);
    if (!card) return;

    const toggle = card.querySelector('.toggle-status');
    const isChecked = toggle ? toggle.checked : false;

    card.classList.toggle('active', isChecked);
    card.classList.toggle('is-disabled', !isChecked);

    const statusLabel = card.querySelector('.sensor-status-label');
    if (statusLabel) {
        statusLabel.textContent = isChecked ? 'Đang giám sát' : 'Tạm tắt';
        statusLabel.classList.toggle('is-on', isChecked);
    }

    updateCardSafeZone(id);
    updateSummary();
}

/**
 * Xóa cấu hình của 1 thẻ
 */
function clearCard(id) {
    const card = document.getElementById(`card-${id}`);
    if (!card) return;

    const maxEl = card.querySelector('.input-max');
    const minEl = card.querySelector('.input-min');
    const toggle = card.querySelector('.toggle-status');

    if (maxEl) maxEl.value = '';
    if (minEl) minEl.value = '';
    if (toggle) toggle.checked = false;

    toggleCard(id);
    showToast('Đã xóa ngưỡng của cảm biến này', 'info', 2000);
}

/**
 * Công tắc tổng gộp: Bật / Tắt tất cả cảm biến bằng 1 radio/toggle button duy nhất
 */
function toggleAllSensors(isChecked) {
    const cards = document.querySelectorAll('.sensor-card[data-id]');
    cards.forEach(card => {
        const id = card.getAttribute('data-id');
        const toggle = card.querySelector('.toggle-status');
        if (toggle) toggle.checked = isChecked;

        card.classList.toggle('active', isChecked);
        card.classList.toggle('is-disabled', !isChecked);

        const statusLabel = card.querySelector('.sensor-status-label');
        if (statusLabel) {
            statusLabel.textContent = isChecked ? 'Đang giám sát' : 'Tạm tắt';
            statusLabel.classList.toggle('is-on', isChecked);
        }

        updateCardSafeZone(id);
    });

    updateSummary();

    if (isChecked) {
        showToast('Đã bật giám sát tất cả cảm biến', 'success', 2000);
    } else {
        showToast('Đã tạm tắt giám sát tất cả cảm biến', 'info', 2000);
    }
}

/**
 * Hỗ trợ tương thích cho các lệnh gọi cũ
 */
function enableAllSensors() {
    toggleAllSensors(true);
}

function disableAllSensors() {
    toggleAllSensors(false);
}

/**
 * Cập nhật số lượng cảm biến đang bật trên thanh tóm tắt & đồng bộ công tắc tổng
 */
function updateSummary() {
    const cards = document.querySelectorAll('.sensor-card[data-id]');
    const total = cards.length;
    let activeCount = 0;

    cards.forEach(card => {
        const toggle = card.querySelector('.toggle-status');
        if (toggle && toggle.checked) activeCount++;
    });

    const countEl = document.getElementById('monitored-count');
    if (countEl) {
        countEl.textContent = `${activeCount} / ${total} cảm biến`;
    }

    // Đồng bộ công tắc tổng (Master toggle)
    const masterToggle = document.getElementById('master-toggle');
    const masterLabel = document.getElementById('master-toggle-label');

    if (masterToggle) {
        masterToggle.checked = (total > 0 && activeCount === total);
    }

    if (masterLabel) {
        if (total > 0 && activeCount === total) {
            masterLabel.textContent = 'Bật tất cả';
        } else if (activeCount === 0) {
            masterLabel.textContent = 'Tắt tất cả';
        } else {
            masterLabel.textContent = `Bật tất cả (${activeCount}/${total})`;
        }
    }
}

/**
 * Lưu toàn bộ cấu hình cảm biến
 */
async function saveConfig() {
    const userId = getCurrentUserId();
    const items = document.querySelectorAll('.sensor-card[data-id]');
    const dataToSave = [];
    const btn = document.getElementById('save-config-btn');

    if (!userId) {
        showToast('Phiên làm việc hết hạn. Vui lòng đăng nhập lại.', 'error');
        setTimeout(() => window.location.href = 'login.php', 1500);
        return;
    }

    if (!btn) return;

    const oldBtnHtml = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">sync</span><span>Đang lưu...</span>';
    btn.disabled = true;

    items.forEach(card => {
        const id = card.getAttribute('data-id');
        const maxEl = card.querySelector('.input-max');
        const minEl = card.querySelector('.input-min');
        const statusEl = card.querySelector('.toggle-status');

        if (!id || !maxEl || !minEl || !statusEl) return;

        dataToSave.push({
            id: id,
            nguongtren: maxEl.value.trim() !== '' ? maxEl.value.trim() : null,
            nguongduoi: minEl.value.trim() !== '' ? minEl.value.trim() : null,
            trangthai: statusEl.checked ? 1 : 0
        });
    });

    try {
        const res = await fetch(API_SAVE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                configs: dataToSave
            })
        });

        const json = await res.json();

        if (json.status === 'success') {
            showToast('✅ Đã lưu cấu hình ngưỡng cảnh báo thành công!', 'success');
        } else {
            showToast('❌ Lỗi: ' + (json.message || 'Không thể lưu'), 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('❌ Lỗi kết nối máy chủ', 'error');
    } finally {
        btn.innerHTML = oldBtnHtml;
        btn.disabled = false;
    }
}

/**
 * Ẩn / Hiện Token Telegram
 */
function toggleTokenVisibility() {
    const input = document.getElementById('tg-token');
    const icon = document.getElementById('tg-eye-icon');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}

/**
 * Tải cấu hình Telegram
 */
async function loadTelegram() {
    const userId = getCurrentUserId();
    if (!userId) return;

    try {
        const res = await fetch(`${API_TELEGRAM}?user_id=${encodeURIComponent(userId)}`);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            const tokenEl = document.getElementById('tg-token');
            const chatIdEl = document.getElementById('tg-chatid');
            const statusEl = document.getElementById('tg-status');

            if (tokenEl) tokenEl.value = json.data.bot_token || '';
            if (chatIdEl) chatIdEl.value = json.data.chat_id || '';
            if (statusEl) statusEl.checked = json.data.trangthai == 1;
        }
    } catch (e) {
        console.error('Lỗi tải Telegram:', e);
    }
}

/**
 * Lưu cấu hình Telegram
 */
async function saveTelegram() {
    const userId = getCurrentUserId();
    const tokenEl = document.getElementById('tg-token');
    const chatIdEl = document.getElementById('tg-chatid');
    const statusEl = document.getElementById('tg-status');
    const btnSave = document.getElementById('btn-save-tg');

    const token = tokenEl ? tokenEl.value.trim() : '';
    const chat_id = chatIdEl ? chatIdEl.value.trim() : '';
    const status = statusEl && statusEl.checked ? 1 : 0;

    if (!userId) {
        showToast('Vui lòng đăng nhập lại.', 'error');
        return;
    }

    if (!token || !chat_id) {
        showToast('Vui lòng nhập đầy đủ Bot Token và Chat ID!', 'warning');
        return;
    }

    let oldHtml = '';
    if (btnSave) {
        oldHtml = btnSave.innerHTML;
        btnSave.innerHTML = '<span class="material-symbols-outlined animate-spin" style="font-size:16px;">sync</span> Đang lưu...';
        btnSave.disabled = true;
    }

    try {
        const res = await fetch(API_TELEGRAM, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                bot_token: token,
                chat_id: chat_id,
                trangthai: status
            })
        });

        const json = await res.json();

        if (json.status === 'success') {
            showToast('✅ Đã lưu cấu hình Telegram thành công!', 'success');
        } else {
            showToast('❌ Lỗi: ' + (json.message || 'Không thể lưu'), 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('❌ Lỗi kết nối khi lưu Telegram', 'error');
    } finally {
        if (btnSave) {
            btnSave.innerHTML = oldHtml;
            btnSave.disabled = false;
        }
    }
}

/**
 * Gửi tin nhắn kiểm tra kết nối Telegram
 */
async function testTelegram() {
    const tokenEl = document.getElementById('tg-token');
    const chatIdEl = document.getElementById('tg-chatid');
    const btnTest = document.getElementById('btn-test-tg');

    const token = tokenEl ? tokenEl.value.trim() : '';
    const chat_id = chatIdEl ? chatIdEl.value.trim() : '';

    if (!token || !chat_id) {
        showToast('Vui lòng nhập Bot Token và Chat ID trước khi gửi thử!', 'warning');
        return;
    }

    let oldHtml = '';
    if (btnTest) {
        oldHtml = btnTest.innerHTML;
        btnTest.innerHTML = '<span class="material-symbols-outlined animate-spin" style="font-size:16px;">sync</span> Đang gửi...';
        btnTest.disabled = true;
    }

    try {
        const url = `https://api.telegram.org/bot${token}/sendMessage`;
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: chat_id,
                text: '🌱 [HydroSmart AIoT] Kiểm tra kết nối thành công! Bạn sẽ nhận được cảnh báo tại đây khi các chỉ số vượt ngưỡng an toàn.'
            })
        });

        const json = await res.json();

        if (json.ok) {
            showToast('✅ Gửi thử thành công! Hãy kiểm tra tin nhắn Telegram.', 'success');
        } else {
            showToast('❌ Telegram báo lỗi: ' + (json.description || 'Không thể gửi tin'), 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('❌ Không thể gửi tin thử nghiệm. Kiểm tra kết nối mạng!', 'error');
    } finally {
        if (btnTest) {
            btnTest.innerHTML = oldHtml;
            btnTest.disabled = false;
        }
    }
}

// Khởi tạo trang
document.addEventListener('DOMContentLoaded', () => {
    loadConfig();
    loadTelegram();
});