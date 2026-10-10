// cauhinh.js - Configuration page logic

const API_ROOT = (typeof API_BASE !== 'undefined' ? API_BASE : 'http://localhost/Test/API').replace(/\/$/, '');

const API_GET = `${API_ROOT}/cauhinh/get_config.php`;
const API_SAVE = `${API_ROOT}/cauhinh/save_config.php`;
const API_TELEGRAM = `${API_ROOT}/cauhinh/telegram.php`;

function getCurrentUserId() {
    try {
        const user = JSON.parse(localStorage.getItem('user_info'));
        return user?.id || user?.id_nguoidung || null;
    } catch {
        return null;
    }
}

async function loadConfig() {
    const userId = getCurrentUserId();

    if (!userId) {
        window.location.href = 'login.php';
        return;
    }

    try {
        const res = await fetch(`${API_GET}?user_id=${encodeURIComponent(userId)}`);
        const json = await res.json();

        if (json.status === 'success') {
            renderForm(json.data || []);
        } else {
            alert('Lỗi: ' + json.message);
        }
    } catch (e) {
        console.error(e);
        alert('Không thể tải cấu hình cảm biến');
    }
}

function renderForm(configs) {
    const container = document.getElementById('config-list');

    const meta = {
        1: { icon: 'thermostat', name: 'Nhiệt độ' },
        2: { icon: 'water_drop', name: 'Độ ẩm' },
        3: { icon: 'water', name: 'Mức nước' },
        4: { icon: 'light_mode', name: 'Ánh sáng' },
    };

    if (!configs || configs.length === 0) {
        container.innerHTML = `
            <div class="empty-box">
                <span class="material-symbols-outlined">sensors_off</span>
                <p>Chưa có cấu hình cảm biến</p>
            </div>
        `;
        return;
    }

    let html = '';

    configs.forEach(cfg => {
        const m = meta[cfg.idcambien] || {
            icon: 'sensors',
            name: cfg.loaicambien || 'Cảm biến'
        };

        const isChecked = cfg.trangthai == 1;
        const activeClass = isChecked ? 'active' : '';

        const maxValue = (cfg.nguongtren !== null && cfg.nguongtren !== undefined && cfg.nguongtren !== '')
            ? cfg.nguongtren
            : '';

        const minValue = (cfg.nguongduoi !== null && cfg.nguongduoi !== undefined && cfg.nguongduoi !== '')
            ? cfg.nguongduoi
            : '';

        html += `
            <div class="config-card sensor-card ${activeClass}" id="card-${cfg.id}" data-id="${cfg.id}">
                
                <div class="card-header">
                    <div class="sensor-info">
                        <div class="icon-box">
                            <span class="material-symbols-outlined">${m.icon}</span>
                        </div>

                        <div>
                            <h3 class="sensor-name">${m.name}</h3>
                            <p class="sensor-unit">Đơn vị: ${cfg.donvido || '-'}</p>
                        </div>
                    </div>
                    
                    <button type="button" onclick="clearCard('${cfg.id}')" class="delete-btn" title="Xóa cấu hình và tắt giám sát">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
                
                <div class="input-group">
                    <div class="input-field">
                        <label class="input-label">Cảnh báo khi &gt; Max</label>
                        <input 
                            type="number" 
                            step="0.1" 
                            class="input-max" 
                            placeholder="Không giới hạn"
                            value="${maxValue}"
                            oninput="activeCard('${cfg.id}')"
                        >
                    </div>

                    <div class="input-field">
                        <label class="input-label">Cảnh báo khi &lt; Min</label>
                        <input 
                            type="number" 
                            step="0.1" 
                            class="input-min" 
                            placeholder="Không giới hạn"
                            value="${minValue}"
                            oninput="activeCard('${cfg.id}')"
                        >
                    </div>
                </div>

                <div class="card-footer">
                    <span class="footer-label">Trạng thái giám sát</span>

                    <label class="toggle-switch">
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
        `;
    });

    container.innerHTML = html;
}

function activeCard(id) {
    const card = document.getElementById(`card-${id}`);
    if (!card) return;

    card.classList.add('active');

    const toggle = card.querySelector('.toggle-status');
    if (toggle) toggle.checked = true;
}

function toggleCard(id) {
    const card = document.getElementById(`card-${id}`);
    if (!card) return;

    const toggle = card.querySelector('.toggle-status');
    if (!toggle) return;

    card.classList.toggle('active', toggle.checked);
}

function clearCard(id) {
    if (!confirm('Bạn có chắc muốn xóa cấu hình và tắt giám sát cảm biến này?')) return;

    const card = document.getElementById(`card-${id}`);
    if (!card) return;

    const maxEl = card.querySelector('.input-max');
    const minEl = card.querySelector('.input-min');
    const toggle = card.querySelector('.toggle-status');

    if (maxEl) maxEl.value = '';
    if (minEl) minEl.value = '';
    if (toggle) toggle.checked = false;

    card.classList.remove('active');
}

async function saveConfig() {
    const userId = getCurrentUserId();
    const items = document.querySelectorAll('.sensor-card[data-id]');
    const dataToSave = [];
    const btn = document.getElementById('save-config-btn');

    if (!userId) {
        alert('Không tìm thấy người dùng. Vui lòng đăng nhập lại.');
        return;
    }

    if (!btn) {
        console.error('Không tìm thấy nút #save-config-btn');
        return;
    }

    const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">sync</span> Đang lưu...';
    btn.disabled = true;

    items.forEach(card => {
        const id = card.getAttribute('data-id');

        const maxEl = card.querySelector('.input-max');
        const minEl = card.querySelector('.input-min');
        const statusEl = card.querySelector('.toggle-status');

        if (!id || !maxEl || !minEl || !statusEl) {
            console.warn('Card cảm biến bị thiếu dữ liệu:', card);
            return;
        }

        dataToSave.push({
            id: id,
            nguongtren: maxEl.value,
            nguongduoi: minEl.value,
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
            alert('✅ Đã cập nhật cấu hình thành công!');
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    } catch (e) {
        console.error(e);
        alert('❌ Lỗi kết nối server');
    } finally {
        btn.innerHTML = oldText;
        btn.disabled = false;
    }
}

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
        console.error(e);
    }
}

async function saveTelegram() {
    const userId = getCurrentUserId();

    const tokenEl = document.getElementById('tg-token');
    const chatIdEl = document.getElementById('tg-chatid');
    const statusEl = document.getElementById('tg-status');

    const token = tokenEl ? tokenEl.value.trim() : '';
    const chat_id = chatIdEl ? chatIdEl.value.trim() : '';
    const status = statusEl && statusEl.checked ? 1 : 0;

    if (!userId) {
        alert('Không tìm thấy người dùng. Vui lòng đăng nhập lại.');
        return;
    }

    if (!token || !chat_id) {
        alert('Vui lòng nhập đủ Token và Chat ID!');
        return;
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

        alert(json.status === 'success'
            ? '✅ Đã lưu cấu hình Telegram!'
            : '❌ Lỗi: ' + json.message
        );
    } catch (e) {
        console.error(e);
        alert('❌ Lỗi kết nối server');
    }
}

async function testTelegram() {
    const tokenEl = document.getElementById('tg-token');
    const chatIdEl = document.getElementById('tg-chatid');

    const token = tokenEl ? tokenEl.value.trim() : '';
    const chat_id = chatIdEl ? chatIdEl.value.trim() : '';

    if (!token || !chat_id) {
        alert('Vui lòng nhập Token và Chat ID trước!');
        return;
    }

    try {
        const url = `https://api.telegram.org/bot${token}/sendMessage`;

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: chat_id,
                text: '✅ HydroSmart đã kết nối thành công!'
            })
        });

        const json = await res.json();

        alert(json.ok
            ? '✅ Gửi thử thành công! Kiểm tra Telegram nhé!'
            : '❌ Lỗi: ' + json.description
        );
    } catch (e) {
        console.error(e);
        alert('❌ Không thể gửi tin nhắn thử');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadConfig();
    loadTelegram();
});