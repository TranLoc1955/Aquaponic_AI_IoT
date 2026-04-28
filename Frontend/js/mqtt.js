let mqttClient = null;
window.cachedDeviceId = null;

function connectMQTT() {
    const url = `wss://${MQTT_CONFIG.host}:8884/mqtt`;
    console.log('🚀 Đang kết nối tới:', url);

    mqttClient = mqtt.connect(url, {
        username: MQTT_CONFIG.username,
        password: MQTT_CONFIG.password,
        clientId: MQTT_CONFIG.clientId,
        clean: true,
        reconnectPeriod: 3000
    });

    mqttClient.on('connect', () => {
        console.log('✅ Đã kết nối HiveMQ thành công!');
        mqttClient.subscribe('aquaponic/#');
    });

mqttClient.on('message', async (topic, payload) => {
    const value = payload.toString();
    console.log('📩 Nhận:', topic, '->', value);

    // Nhận data tất cả cảm biến 1 lần
    if (topic === 'aquaponic/sensor/data') {
        try {
            const data = JSON.parse(value);

            if (data.nhietdo !== undefined) document.getElementById('val-temp').innerText = data.nhietdo;
            if (data.do_am !== undefined)   document.getElementById('val-hum').innerText = data.do_am;
            if (data.muc_nuoc !== undefined)      document.getElementById('val-ph').innerText = data.muc_nuoc;
            if (data.anhsang !== undefined) document.getElementById('val-lux').innerText = data.anhsang;

            // Lưu DB 1 lần
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
            if (json.status === 'success') loadData();

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
});

    mqttClient.on('error', (err) => console.log('❌ Lỗi MQTT:', err));
    mqttClient.on('offline', () => console.warn('⚠️ MQTT mất kết nối'));
    mqttClient.on('reconnect', () => console.log('🔄 Đang kết nối lại...'));
}

async function saveToDatabase(topic, value) {
    if (!window.cachedDeviceId) {
        console.warn('Chưa có device ID, thử lấy lại...');
        await loadDeviceId();
        if (!window.cachedDeviceId) return;
    }

 const fieldMap = {
    [MQTT_TOPICS.SENSOR_TEMP]:  'nhiet_do',
    [MQTT_TOPICS.SENSOR_HUMID]: 'do_am',
    [MQTT_TOPICS.SENSOR_PH]:    'muc_nuoc',
    [MQTT_TOPICS.SENSOR_LIGHT]: 'anh_sang'
};

    const field = fieldMap[topic];
    if (!field) return;

    try {
        const res = await fetch(API.SENSOR_INPUT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                idthietbi: window.cachedDeviceId,
                [field]: parseFloat(value)
            })
        });
        const json = await res.json();
        console.log('💾 Lưu DB:', json);
    } catch (e) {
        console.error('Lỗi lưu DB:', e);
    }
}

function mqttPublishControl(device, value) {
    if (mqttClient && mqttClient.connected) {
        const payload = JSON.stringify({ [device]: parseInt(value) });
        mqttClient.publish('aquaponic/control', payload, { qos: 1 });
        console.log(`📤 Gửi lệnh: aquaponic/control ->`, payload);
    } else {
        console.warn('MQTT chưa kết nối, không gửi được lệnh');
    }
}