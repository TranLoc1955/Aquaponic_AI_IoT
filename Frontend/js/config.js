
const API_BASE = 'http://localhost/Test/API';

const API = {
    // Auth
    LOGIN: API_BASE + '/auth/login.php',
    REGISTER: API_BASE + '/auth/register.php',
    LOGOUT: API_BASE + '/auth/logout.php',
    RESET_PASSWORD: API_BASE + '/auth/reset_password.php',
    
    // Dashboard
    DASHBOARD: API_BASE + '/dashboard/tongquat.php',
    
    // Data Sensor
    SENSOR_INPUT: API_BASE + '/data_sensor/input.php',
    SENSOR_HISTORY: API_BASE + '/data_sensor/history.php',
    
    // Control
    CONTROL_UPDATE: API_BASE + '/dieukhien/update.php',
    CONTROL_GET: API_BASE + '/dieukhien/get_control.php',
    
    // Alerts
    ALERT_GET: API_BASE + '/canhbao/get_canhbao.php',
    ALERT_READ: API_BASE + '/canhbao/read.php',
    ALERT_DELETE: API_BASE + '/canhbao/delete.php',
    
    // Config
    CONFIG_GET: API_BASE + '/cauhinh/get_config.php',
    CONFIG_SAVE: API_BASE + '/cauhinh/save_config.php'
};
// Intervals
const INTERVALS = {
    SENSOR_DATA: 5000,      // 5 seconds
    NOTIFICATIONS: 10000,   // 10 seconds
    CONTROL_LOG: 8000       // 8 seconds
};

// Sensor IDs
const SENSOR_IDS = {
    TEMPERATURE: 1,
    HUMIDITY: 2,
    PH: 3,
    LIGHT: 4
};

// Sensor Configuration
const SENSOR_CONFIG = {
    1: {
        label: 'Nhiệt độ',
        color: '#fb923c',
        unit: '°C',
        min: 10,
        max: 35,
        step: 5
    },
    2: {
        label: 'Độ ẩm',
        color: '#3b82f6',
        unit: '%',
        min: 40,
        max: 80,
        step: 5
    },
    3: {
        label: 'Mức nước',
        color: '#a855f7',
        unit: '',
        min: 4,
        max: 10,
        step: 1
    },
    4: {
        label: 'Ánh sáng',
        color: '#eab308',
        unit: 'Lux',
        min: 10000,
        max: 20000,
        step: 2500
    }
};
const MQTT_CONFIG = {
    host: '56865ed923c247919013bdef6430f5c6.s1.eu.hivemq.cloud',
    username: 'esp32_user',
    password: '123456Esp32@',
    clientId: 'hydrosmart_web_' + Math.random().toString(16).substr(2, 8)
};

const MQTT_TOPICS = {
    SENSOR_ALL:   'aquaponic/sensor/data',  
    DEVICE_PUMP:  'aquaponic/device/maybom',
    DEVICE_LIGHT: 'aquaponic/device/denled',
    DEVICE_FAN:   'aquaponic/device/quatgio',
};