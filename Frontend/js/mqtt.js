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
    if (topic.includes('/device/') && topic.endsWith('/status')) {
        const device = topic.split('/')[2];
        const swMap = {
            maybom:  { sw: 'sw-pump',  txt: 'txt-pump' },
            denled:  { sw: 'sw-light', txt: 'txt-light' },
            quatgio: { sw: 'sw-fan',   txt: 'txt-fan' },
        };
        if (swMap[device]) {
            updateSwitch(swMap[device].sw, swMap[device].txt, value);
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
        const topic = `aquaponic/device/${device}/control`;
        mqttClient.publish(topic, String(value), { qos: 1 });
        console.log(`📤 Gửi lệnh: ${topic} -> ${value}`);
    } else {
        console.warn('MQTT chưa kết nối, không gửi được lệnh');
    }
}