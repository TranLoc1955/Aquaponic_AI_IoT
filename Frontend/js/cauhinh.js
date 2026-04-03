// cauhinh.js - Configuration page logic
const API_GET = 'http://localhost/Test/API/cauhinh/get_config.php';
const API_SAVE = 'http://localhost/Test/API/cauhinh/save_config.php';

function getUserId() {
    try {
        return JSON.parse(localStorage.getItem('user_info')).id;
    } catch {
        return null;
    }
}

async function loadConfig() {
    const userId = getUserId();
    if (!userId) {
        window.location.href = 'login.php';
        return;
    }

    try {
        const res = await fetch(`${API_GET}?user_id=${userId}`);
        const json = await res.json();

        if (json.status === 'success') {
            renderForm(json.data);
        } else {
            alert('Lỗi: ' + json.message);
        }
    } catch (e) {
        console.error(e);
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

    let html = '';
    configs.forEach(cfg => {
        const m = meta[cfg.idcambien] || { icon: 'sensors', name: cfg.loaicambien };
        const isChecked = cfg.trangthai == 1;
        const activeClass = isChecked ? 'active' : '';

        html += `
        <div class="config-card ${activeClass}" id="card-${cfg.id}" data-id="${cfg.id}">
            
            <div class="card-header">
                <div class="sensor-info">
                    <div class="icon-box">
                        <span class="material-symbols-outlined">${m.icon}</span>
                    </div>
                    <div>
                        <h3 class="sensor-name">${m.name}</h3>
                        <p class="sensor-unit">Đơn vị: ${cfg.donvido}</p>
                    </div>
                </div>
                
                <button onclick="clearCard('${cfg.id}')" class="delete-btn" title="Xóa cấu hình & Tắt">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
            
            <div class="input-group">
                <div class="input-field">
                    <label class="input-label">Cảnh báo khi > (Max)</label>
                    <input type="number" step="0.1" class="input-max" 
                        placeholder="Không giới hạn" value="${(cfg.nguongtren != null && cfg.nguongtren !== '') ? cfg.nguongtren : ''}" oninput="activeCard('${cfg.id}')">
                </div>

                <div class="input-field">
                    <label class="input-label">Cảnh báo khi < (Min)</label>
                    <input type="number" step="0.1" class="input-min" 
                        placeholder="Không giới hạn" value="${(cfg.nguongduoi != null && cfg.nguongduoi !== '') ? cfg.nguongduoi : ''}" oninput="activeCard('${cfg.id}')">
                </div>
            </div>

            <div class="card-footer">
                <span class="footer-label">Trạng thái giám sát</span>
                <label class="toggle-switch">
                    <input type="checkbox" class="toggle-status" ${isChecked ? 'checked' : ''} onchange="toggleCard('${cfg.id}')">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function activeCard(id) {
    const card = document.getElementById(`card-${id}`);
    // Card is already styled by CSS
}

function toggleCard(id) {
    const card = document.getElementById(`card-${id}`);
    const toggle = card.querySelector('.toggle-status');

    if (toggle.checked) {
        card.classList.add('active');
    } else {
        card.classList.remove('active');
    }
}

function clearCard(id) {
    if (!confirm('Bạn có chắc muốn xóa cấu hình và tắt giám sát cảm biến này?')) return;

    const card = document.getElementById(`card-${id}`);

    card.querySelector('.input-max').value = '';
    card.querySelector('.input-min').value = '';

    const toggle = card.querySelector('.toggle-status');
    toggle.checked = false;

    card.classList.remove('active');
}

async function saveConfig() {
    const userId = getUserId();
    const items = document.querySelectorAll('.config-card');
    const dataToSave = [];
    const btn = document.querySelector('button[onclick="saveConfig()"]');

    // Loading state
    const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">sync</span> Đang lưu...';
    btn.disabled = true;

    items.forEach(div => {
        const id = div.getAttribute('data-id');
        const max = div.querySelector('.input-max').value;
        const min = div.querySelector('.input-min').value;
        const status = div.querySelector('.toggle-status').checked ? 1 : 0;

        dataToSave.push({
            id: id,
            nguongtren: max,
            nguongduoi: min,
            trangthai: status
        });
    });

    try {
        const res = await fetch(API_SAVE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, configs: dataToSave })
        });
        const json = await res.json();

        setTimeout(() => {
            if (json.status === 'success') alert('Đã cập nhật cấu hình thành công!');
            else alert('Lỗi: ' + json.message);

            btn.innerHTML = oldText;
            btn.disabled = false;
        }, 500);

    } catch (e) {
        console.error(e);
        alert('Lỗi kết nối server');
        btn.innerHTML = oldText;
        btn.disabled = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', loadConfig);
