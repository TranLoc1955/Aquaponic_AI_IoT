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

    // 🤖 Kết quả từ AI Service (Edge / Web AI)
    // Publish lên topic "aquaponic/ai/result/<device_id>"
    if (topic.startsWith('aquaponic/ai/result/')) {
        try {
            const aiDeviceId = topic.split('/').pop();
            const data = JSON.parse(value);

            // 1. Cập nhật giao diện realtime ngay lập tức
            if (typeof renderAIInsight === 'function') renderAIInsight(data);

            // 2. 💾 LƯU BỀN VỮNG VÀO DATABASE QUA API nhan_ket_qua.php
            // Đảm bảo dữ liệu phân tích AI được lưu vào MySQL ngay khi nhận được,
            // để khi người dùng F5 (refresh) hoặc mở trang Lịch sử AI thì dữ liệu vẫn còn.
            const apiUrl = (typeof API !== 'undefined' && API.AI_RECEIVE)
                ? API.AI_RECEIVE
                : ((typeof API_BASE !== 'undefined' ? API_BASE : '../API') + '/ai/nhan_ket_qua.php');

            fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(resJson => {
                console.log('💾 [AI Result] Đã lưu vào Database:', resJson);
            })
            .catch(err => {
                console.warn('⚠️ [AI Result] Lỗi lưu DB:', err);
            });
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

    // ---- BẢNG CẢNH BÁO BẤT THƯỜNG TRÊN DASHBOARD ----
    // Chỉ ghi nhận WARNING và DANGER; KHÔNG hiển thị NORMAL (Yêu cầu nghiệp vụ #3 & #4)
    const sev = (aiResult.severity || '').toLowerCase();
    if (sev === 'warning' || sev === 'danger') {
        let abnormalSensor = 'moi_truong';
        let abnormalVal = '--';
        if (analysis.abnormal_sensors && analysis.abnormal_sensors.length > 0) {
            abnormalSensor = analysis.abnormal_sensors[0];
            if (data.sensor_values && data.sensor_values[abnormalSensor] !== undefined) {
                abnormalVal = data.sensor_values[abnormalSensor];
            }
        } else if (data.sensor_values) {
            const keys = Object.keys(data.sensor_values);
            if (keys.length > 0) {
                abnormalSensor = keys[0];
                abnormalVal = data.sensor_values[abnormalSensor];
            }
        }

        const newAlert = {
            id: null,
            thoigian: `${timeStr} ${dateStr}`,
            loai_canhbao: analysis.summary || typeLabel,
            cam_bien: abnormalSensor,
            gia_tri_cambien: abnormalVal,
            muc_do: sev,
            trangthai: 0
        };

        window.aiAlertEvents = window.aiAlertEvents || [];
        window.aiAlertEvents.unshift(newAlert);
        if (window.aiAlertEvents.length > 10) window.aiAlertEvents.length = 10;
        if (typeof renderDashboardAlertTable === 'function') {
            renderDashboardAlertTable(window.aiAlertEvents);
        }
    }
}

function aiTruncate(text, max) {
    if (!text) return '';
    return text.length > max ? text.slice(0, max).trim() + '…' : text;
}

function formatAiSensorName(sensorKey) {
    if (!sensorKey) return '--';
    const s = String(sensorKey).toLowerCase();
    if (s.includes('nhiet') || s.includes('temp')) return 'Nhiệt độ';
    if (s.includes('am') || s.includes('humid')) return 'Độ ẩm';
    if (s.includes('anh') || s.includes('light') || s.includes('lux')) return 'Ánh sáng';
    if (s.includes('nuoc') || s.includes('water')) return 'Mực nước';
    if (s.includes('moi_truong')) return 'Môi trường';
    return sensorKey;
}

function formatAiSensorValue(val, sensorKey) {
    if (val === null || val === undefined || val === '') return '--';
    
    // 1. Trường hợp JSON string
    if (typeof val === 'string' && val.startsWith('{')) {
        try {
            const obj = JSON.parse(val);
            const k = Object.keys(obj)[0];
            if (k) return formatAiSensorValue(obj[k], k);
        } catch (e) {}
    }

    // 2. Trường hợp chuỗi tổng hợp nhiều cảm biến: "Nhiệt độ: 34.4°C | Độ ẩm: 64.5% | ..."
    if (typeof val === 'string' && val.includes('|')) {
        const segments = val.split('|').map(s => s.trim());
        const s = String(sensorKey || '').toLowerCase();
        let matched = null;
        if (s.includes('nhiet') || s.includes('temp')) {
            matched = segments.find(seg => /nhiệt|temp/i.test(seg));
        } else if (s.includes('am') || s.includes('humid')) {
            matched = segments.find(seg => /độ ẩm|ẩm|humid/i.test(seg));
        } else if (s.includes('anh') || s.includes('light') || s.includes('lux')) {
            matched = segments.find(seg => /ánh sáng|light|lux/i.test(seg));
        } else if (s.includes('nuoc') || s.includes('water')) {
            matched = segments.find(seg => /mực nước|nước|water/i.test(seg));
        }
        if (matched) {
            const colonIdx = matched.indexOf(':');
            return colonIdx !== -1 ? matched.substring(colonIdx + 1).trim() : matched;
        }
        const first = segments[0] || '';
        const cIdx = first.indexOf(':');
        return cIdx !== -1 ? first.substring(cIdx + 1).trim() : first;
    }

    // 3. Nếu chuỗi chứa "Tên: Giá trị" (vd "Nhiệt độ: 34.4°C")
    if (typeof val === 'string' && val.includes(':')) {
        const cIdx = val.lastIndexOf(':');
        const extracted = val.substring(cIdx + 1).trim();
        if (/[°%lux]/i.test(extracted)) return extracted;
        val = extracted;
    }

    const num = parseFloat(val);
    if (isNaN(num)) return String(val);
    const s = String(sensorKey || '').toLowerCase();
    if (s.includes('nhiet') || s.includes('temp')) return `${num.toFixed(1)} °C`;
    if (s.includes('am') || s.includes('humid')) return `${num.toFixed(0)}%`;
    if (s.includes('anh') || s.includes('light') || s.includes('lux')) return `${num.toLocaleString()} Lux`;
    if (s.includes('nuoc') || s.includes('water')) {
        if (num <= 2) return `${num} (Cạn)`;
        if (num <= 5) return `${num} (Vừa)`;
        return `${num} (Đầy)`;
    }
    return String(val);
}

function formatAiEventName(eventName) {
    if (!eventName) return 'Bất thường';
    const map = {
        'nhiet_do_cao': 'Nhiệt độ cao',
        'nhiet_do_thap': 'Nhiệt độ thấp',
        'do_am_cao': 'Độ ẩm cao',
        'do_am_thap': 'Độ ẩm thấp',
        'anh_sang_yeu': 'Ánh sáng yếu',
        'anh_sang_gat': 'Ánh sáng gắt',
        'muc_nuoc_can': 'Mực nước cạn',
        'muc_nuoc_qua_tai': 'Mực nước quá tải',
        'mat_ket_noi': 'Mất kết nối cảm biến',
        'environment': 'Bất thường môi trường',
        'sensor_fault': 'Lỗi cảm biến',
    };
    return map[eventName] || eventName;
}

function renderDashboardAlertTable(events) {
    const bodyEl = document.getElementById('ai-table-body');
    if (!bodyEl) return;

    if (!Array.isArray(events) || events.length === 0) {
        bodyEl.innerHTML = '<tr><td colspan="6" class="ai-table-empty">Hệ thống đang hoạt động an toàn. Chưa có sự kiện cảnh báo nào.</td></tr>';
        return;
    }

    bodyEl.innerHTML = events.slice(0, 10).map(item => {
        let timeDisplay = item.thoigian || '--';
        if (item.thoigian && item.thoigian.includes(' ')) {
            const parts = item.thoigian.split(' ');
            const d = parts[0].split('-');
            if (d.length === 3) timeDisplay = `${parts[1]} ${d[2]}/${d[1]}/${d[0]}`;
        }

        const evName = formatAiEventName(item.loai_canhbao || item.noidung);
        const sName = formatAiSensorName(item.cam_bien);
        const sVal = formatAiSensorValue(item.gia_tri_cambien, item.cam_bien);
        const sev = (item.muc_do || 'warning').toLowerCase();

        const sevBadge = (sev === 'danger')
            ? `<span class="ai-badge ai-badge-danger" style="background:#FEE2E2; color:#DC2626; border:1px solid #FECACA; font-weight:600; display:inline-flex; align-items:center; gap:4px;"><span style="width:6px; height:6px; border-radius:50%; background:#DC2626;"></span>DANGER</span>`
            : `<span class="ai-badge ai-badge-warning" style="background:#FEF3C7; color:#B45309; border:1px solid #FDE68A; font-weight:600; display:inline-flex; align-items:center; gap:4px;"><span style="width:6px; height:6px; border-radius:50%; background:#F59E0B;"></span>WARNING</span>`;

        const isResolved = parseInt(item.trangthai) === 1;
        const statusBadge = isResolved
            ? `<span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:600; background:#ECFDF5; color:#047857; border:1px solid #A7F3D0;"><span style="width:6px; height:6px; border-radius:50%; background:#10B981;"></span>Đã xử lý</span>`
            : `<span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:600; background:#FFFBEB; color:#B45309; border:1px solid #FDE68A;"><span style="width:6px; height:6px; border-radius:50%; background:#F59E0B;"></span>Chưa xử lý</span>`;

        const repeatTag = (item.so_lan_lap && item.so_lan_lap > 1)
            ? `<span style="font-size:10px; color:#B45309; background:#FEF3C7; padding:1px 5px; border-radius:4px; font-weight:600; margin-left:4px;">×${item.so_lan_lap}</span>`
            : '';

        const targetUrl = item.id ? `ai_historyW.php?event_id=${item.id}` : `ai_historyW.php`;

        return `
            <tr style="cursor:pointer;" onclick="window.location.href='${targetUrl}'" title="Xem chi tiết tại trang Lịch sử AI">
                <td style="color:var(--text-muted); font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${timeDisplay}</td>
                <td>
                    <div style="font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${evName}">${evName}${repeatTag}</div>
                </td>
                <td style="font-weight:500; font-size:12px; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${sName}</td>
                <td style="font-weight:600; font-size:12px; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${item.gia_tri_cambien || sVal}">${sVal}</td>
                <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${sevBadge}</td>
                <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${statusBadge}</td>
            </tr>
        `;
    }).join('');
}

function renderAITable() {
    if (typeof renderDashboardAlertTable === 'function') {
        renderDashboardAlertTable(window.aiAlertEvents || []);
    }
}

// Nạp trạng thái phân tích AI gần nhất từ DB (bảng ai_ket_qua) để khung tóm tắt không bị trống
function initAILatestStatusFromDB(latest) {
    if (!latest) return;
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
}

async function initAIHistoryFromDB(rows) {
    if (!Array.isArray(rows) || rows.length === 0) return;
    initAILatestStatusFromDB(rows[0]);
}