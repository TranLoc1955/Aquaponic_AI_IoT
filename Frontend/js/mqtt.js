let mqttClient = null;
window.cachedDeviceId = null;

function connectMQTT() {
    const url = `wss://${MQTT_CONFIG.host}:8884/mqtt`;
    console.log('🚀 Đang kết nối tới:', url);
// Cấu hình MQTT
    mqttClient = mqtt.connect(url, {
        username: MQTT_CONFIG.username,
        password: MQTT_CONFIG.password,
        clientId: MQTT_CONFIG.clientId,
        clean: true,
        reconnectPeriod: 1500
    });

    mqttClient.on('connect', () => {
        console.log('✅ Đã kết nối HiveMQ thành công!');
        mqttClient.subscribe('aquaponic/#');
    });

mqttClient.on('message', async (topic, payload) => {
    const value = payload.toString();
    console.log('📩 Nhận:', topic, '->', value);

    if (topic === MQTT_TOPICS.SENSOR_ALL) {
    try {
        const data = JSON.parse(value);

        // Cập nhật UI
        if (data.nhiet_do  !== undefined) document.getElementById('val-temp').innerText = data.nhiet_do;
        if (data.do_am     !== undefined) document.getElementById('val-hum').innerText  = data.do_am;
        if (data.muc_nuoc  !== undefined) document.getElementById('val-ph').innerText   = data.muc_nuoc;
        if (data.anh_sang  !== undefined) document.getElementById('val-lux').innerText  = data.anh_sang;

        // Đẩy điểm dữ liệu mới vào 4 biểu đồ realtime trên Dashboard (nếu trang
        // hiện tại có khung biểu đồ — hàm được định nghĩa trong dashboardW.php)
        if (typeof pushDashboardChartPoint === 'function') pushDashboardChartPoint(data);

        // Lưu DB luôn
        if (window.cachedDeviceId) {
            const res = await fetch(API.SENSOR_INPUT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idthietbi: window.cachedDeviceId,
                    ...data
                })
            });
            const json = await res.json();
            console.log('💾 Lưu DB:', json);
        }

    } catch (e) {
        console.error('Lỗi xử lý data:', e);
    }
}
        // Cập nhật trạng thái thiết bị
    if (topic === 'aquaponic/device/status') {
    try {
        const data = JSON.parse(value);

        // Cập nhật UI
        if (data.maybom !== undefined)  updateSwitch('sw-pump',  'txt-pump',  data.maybom);
        if (data.denled !== undefined)   updateSwitch('sw-light', 'txt-light', data.denled);
        if (data.quatgio !== undefined)  updateSwitch('sw-fan',   'txt-fan',   data.quatgio);

        // Lưu DB
        const userId = getUserId();
        const fields = ['maybom', 'denled', 'quatgio'];
        for (const field of fields) {
            if (data[field] !== undefined) {
                await fetch(`${API_BASE}/dieukhien/update.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        field: field,
                        value: parseInt(data[field]),
                        user_id: userId
                    })
                });
            }
        }
        console.log('💾 Lưu trạng thái thiết bị xong');

    } catch(e) {
        console.error('Lỗi xử lý device status:', e);
    }
}

    // 🤖 Kết quả từ AI Service (Python, Isolation Forest 3 tầng)
    // AI publish lên topic dạng "aquaponic/ai/result/<device_id>", ví dụ
    // "aquaponic/ai/result/18". Vì trình duyệt đã subscribe "aquaponic/#"
    // (dấu # là mọi topic con) nên message này TỰ NHIÊN đã tới đây rồi,
    // chỉ cần thêm nhánh xử lý là hiển thị được lên UI, không cần sửa gì
    // ở backend PHP hay kết nối MQTT.
    if (topic.startsWith('aquaponic/ai/result/')) {
        try {
            const aiDeviceId = topic.split('/').pop();
            // Chỉ hiển thị nếu đúng thiết bị đang xem trên Dashboard này
            if (window.cachedDeviceId && String(aiDeviceId) !== String(window.cachedDeviceId)) return;

            const data = JSON.parse(value);
            if (typeof renderAIInsight === 'function') renderAIInsight(data);
        } catch (e) {
            console.error('Lỗi xử lý kết quả AI:', e);
        }
    }
});

    mqttClient.on('error', (err) => console.log('❌ Lỗi MQTT:', err));
    mqttClient.on('offline', () => console.warn('⚠️ MQTT mất kết nối'));
    mqttClient.on('reconnect', () => console.log('🔄 Đang kết nối lại...'));
}

function mqttPublishControl(device, value) {
    if (mqttClient && mqttClient.connected) {
        const topic = `aquaponic/control`;
        const payload = JSON.stringify({ [device]: parseInt(value) });
        mqttClient.publish(topic, payload, { qos: 0 });
        console.log(`📤 Gửi lệnh: ${topic} ->`, payload);
    } else {
        console.warn('MQTT chưa kết nối, không gửi được lệnh');
    }
}

// ============================================================
// AI (Edge - ESP32) — đọc đúng JSON contract trong AI_JSON_CONTRACT.md
// Không còn Isolation Forest / score_samples / z-score phía Web nữa — mọi
// tính toán AI giờ nằm trên ESP32, Web chỉ hiển thị đúng nguyên JSON gửi về.
// ============================================================

window.aiHistory = window.aiHistory || [];
const AI_HISTORY_MAX = 15;

const AI_SEVERITY_STYLES = {
    normal:  { cls: 'normal',  label: 'Bình thường' },
    warning: { cls: 'warning', label: 'Cảnh báo' },
    danger:  { cls: 'danger',  label: 'Nguy hiểm' },
};

const AI_TYPE_LABELS = {
    none: 'Không có',
    environment: 'Môi trường',
    sensor_fault: 'Lỗi cảm biến',
};

const AI_TIME_WINDOW_LABELS = { day: 'Ban ngày', night: 'Ban đêm' };

const AI_ACTION_LABELS = {
    NONE: 'Không cần xử lý',
    CHECK_ENVIRONMENT: 'Kiểm tra môi trường',
    CHECK_WATER_LEVEL: 'Kiểm tra mức nước',
    CHECK_PUMP: 'Kiểm tra máy bơm',
    CHECK_LIGHTING: 'Kiểm tra ánh sáng',
    CHECK_SYSTEM: 'Kiểm tra hệ thống',
    CHECK_SENSOR: 'Kiểm tra cảm biến',
    CHECK_DEVICE_CONNECTIVITY: 'Kiểm tra kết nối thiết bị',
};

const AI_DEVICE_LABELS_VI = { maybom: 'Máy Bơm', denled: 'Đèn', quatgio: 'Quạt Gió' };

function renderAIInsight(data) {
    const badge = document.getElementById('ai-status-badge');
    if (!badge) return; // trang hiện tại không có khung AI -> bỏ qua

    const aiResult = data.ai_result || {};
    const analysis = data.analysis || {};
    const reco = data.recommendation || {};
    const actionTaken = data.action_taken || null;

    const style = AI_SEVERITY_STYLES[aiResult.severity] || AI_SEVERITY_STYLES.normal;
    const typeLabel = AI_TYPE_LABELS[aiResult.anomaly_type] || '--';
    const typeCls = 'ai-type-' + (aiResult.anomaly_type || 'none');
    const windowLabel = AI_TIME_WINDOW_LABELS[aiResult.time_window] || '--';

    const t = data.timestamp ? new Date(data.timestamp) : new Date();
    const timeStr = isNaN(t) ? '' : t.toLocaleTimeString('vi-VN');
    const dateStr = isNaN(t) ? '' : t.toLocaleDateString('vi-VN'); // dd/mm/yyyy

    // ---- Badge Mode (AUTO/MANUAL) ----
    const modeBadge = document.getElementById('ai-mode-badge');
    if (modeBadge) {
        if (data.mode === 'MANUAL') {
            modeBadge.className = 'ai-badge ai-badge-manual';
            const secs = data.auto_revert_in_seconds;
            modeBadge.innerText = (secs !== undefined && secs !== null)
                ? `MANUAL (tự động AUTO sau ${secs}s)` : 'MANUAL';
        } else {
            modeBadge.className = 'ai-badge ai-badge-auto';
            modeBadge.innerText = 'AUTO';
        }
    }

    // ---- Badge trạng thái chính ----
    badge.className = 'ai-badge ai-badge-' + style.cls;
    badge.innerText = style.label;

    // ---- Lưới chỉ số ----
    const typeEl = document.getElementById('ai-stat-type');
    if (typeEl) { typeEl.innerText = typeLabel; typeEl.className = 'ai-stat-value ' + typeCls; }

    const severityEl = document.getElementById('ai-stat-severity');
    if (severityEl) { severityEl.innerText = style.label; severityEl.className = 'ai-stat-value ' + style.cls; }

    const windowEl = document.getElementById('ai-stat-window');
    if (windowEl) windowEl.innerText = windowLabel;

    const timeEl = document.getElementById('ai-stat-time');
    if (timeEl) timeEl.innerText = timeStr || '--:--:--';
    const deviceSubEl = document.getElementById('ai-stat-device');
    if (deviceSubEl) deviceSubEl.innerText = `Thiết bị #${data.device_id}`;

    // ---- Diễn giải ----
    const summaryEl = document.getElementById('ai-summary');
    if (summaryEl) summaryEl.innerText = analysis.summary || 'Không có dữ liệu phân tích.';

    // ---- Khuyến nghị ----
    const recoEl = document.getElementById('ai-recommendation');
    const chipEl = document.getElementById('ai-reco-chip');
    const msgEl = document.getElementById('ai-reco-msg');
    if (recoEl) {
        if (reco.action && reco.action !== 'NONE' && reco.message) {
            recoEl.style.display = 'flex';
            if (chipEl) chipEl.innerText = AI_ACTION_LABELS[reco.action] || reco.action;
            if (msgEl) msgEl.innerText = reco.message;
        } else {
            recoEl.style.display = 'none';
        }
    }

    // ---- Khung "AI vừa làm gì" (action_taken) — chỉ hiện khi AI THỰC SỰ vừa hành động ----
    const actionBox = document.getElementById('ai-action-box');
    const actionText = document.getElementById('ai-action-text');
    if (actionBox) {
        if (actionTaken && actionTaken.device && actionTaken.command) {
            const deviceLabel = AI_DEVICE_LABELS_VI[actionTaken.device] || actionTaken.device;
            const commandLabel = actionTaken.command === 'ON' ? 'BẬT' : 'TẮT';
            actionBox.style.display = 'flex';
            if (actionText) {
                actionText.innerHTML = `<strong>${timeStr}</strong> — AI tự động <strong>${commandLabel} ${deviceLabel}</strong>` +
                    (actionTaken.reason ? ` — ${actionTaken.reason}` : '');
            }
        } else {
            actionBox.style.display = 'none';
        }
    }

    // ---- Lưu vào lịch sử phiên (cộng dồn, không ghi đè) ----
    window.aiHistory.unshift({
        date: dateStr,
        time: timeStr,
        deviceId: data.device_id,
        timeWindow: windowLabel,
        typeLabel: typeLabel,
        typeCls: typeCls,
        severityCls: style.cls,
        severityLabel: style.label,
        summary: analysis.summary || 'Không có dữ liệu.',
    });
    if (window.aiHistory.length > AI_HISTORY_MAX) window.aiHistory.length = AI_HISTORY_MAX;

    renderAITable();
}

function aiTruncate(text, max) {
    if (!text) return '';
    return text.length > max ? text.slice(0, max).trim() + '…' : text;
}

function renderAITable() {
    const bodyEl = document.getElementById('ai-table-body');
    if (!bodyEl) return;

    if (window.aiHistory.length === 0) {
        bodyEl.innerHTML = '<tr><td colspan="7" class="ai-table-empty">Chưa có bản ghi nào.</td></tr>';
        return;
    }

    bodyEl.innerHTML = window.aiHistory.map(item => `
        <tr>
            <td class="ai-table-date">${item.date || ''}</td>
            <td class="ai-table-time">${item.time}</td>
            <td>#${item.deviceId}</td>
            <td>${item.timeWindow}</td>
            <td class="${item.typeCls}">${item.typeLabel}</td>
            <td><span class="ai-badge ai-badge-${item.severityCls}">${item.severityLabel}</span></td>
            <td>${aiTruncate(item.summary, 48)}</td>
        </tr>
    `).join('');
}

// Nạp lịch sử AI từ Database (bảng ai_ket_qua qua API get_ai_history.php)
// ngay khi trang vừa mở, để khung AI không bị trống mỗi lần tải lại trang.
async function initAIHistoryFromDB(rows) {
    if (!Array.isArray(rows) || rows.length === 0) return;

    window.aiHistory = rows.map(row => {
        const style = AI_SEVERITY_STYLES[row.severity] || AI_SEVERITY_STYLES.normal;
        const t = row.thoigian ? new Date(row.thoigian.replace(' ', 'T')) : null;
        const timeStr = t && !isNaN(t) ? t.toLocaleTimeString('vi-VN') : '';
        const dateStr = t && !isNaN(t) ? t.toLocaleDateString('vi-VN') : '';
        return {
            date: dateStr,
            time: timeStr,
            deviceId: row.idthietbi,
            timeWindow: AI_TIME_WINDOW_LABELS[row.time_window] || '--',
            typeLabel: AI_TYPE_LABELS[row.anomaly_type] || '--',
            typeCls: 'ai-type-' + (row.anomaly_type || 'none'),
            severityCls: style.cls,
            severityLabel: style.label,
            summary: row.analysis_summary || 'Không có dữ liệu.',
        };
    }).slice(0, AI_HISTORY_MAX);
    renderAITable();

    // 👉 Đồng thời vẽ lại khối tóm tắt phía trên (Loại/Mức độ/Khung giờ/Cập nhật...)
    // bằng bản ghi MỚI NHẤT lấy từ DB (rows[0], vì API get_ai_history.php đã
    // ORDER BY thoigian DESC). Nếu không có bước này, khối trên sẽ đứng ở
    // trạng thái mặc định "--" cho tới khi có tin MQTT mới, dù DB đã có dữ liệu.
    const latest = rows[0];
    renderAIInsight({
        device_id: latest.idthietbi,
        timestamp: latest.thoigian ? latest.thoigian.replace(' ', 'T') : null,
        mode: latest.mode,
        ai_result: {
            anomaly_type: latest.anomaly_type,
            severity: latest.severity,
            time_window: latest.time_window,
            confidence: latest.confidence,
        },
        analysis: {
            summary: latest.analysis_summary,
        },
        recommendation: {
            action: latest.recommendation_action,
            message: latest.recommendation_message,
        },
        action_taken: latest.action_device ? {
            device: latest.action_device,
            command: latest.action_command,
            reason: latest.action_reason,
        } : null,
    });
    window.aiHistory.shift();
    renderAITable();
}