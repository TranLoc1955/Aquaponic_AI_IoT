const API_HISTORY = 'http://localhost/Test/API/data_sensor/history.php';
let myChart = null;
let currentSensor = {};

// 1. CẤU HÌNH TRUNG TÂM
const SENSOR_CONFIG = {
    1: {
        label: 'Nhiệt độ', color: '#fb923c', unit: '°C',
        min: 10, max: 35, step: 5
    },
    2: {
        label: 'Độ ẩm', color: '#3b82f6', unit: '%',
        min: 40, max: 80, step: 15
    },
    3: {
        label: 'Mức nước', color: '#a855f7', unit: '',
        min: 200, max: 500, step: 50
    },
    4: {
        label: 'Ánh sáng', color: '#eab308', unit: '',
        min: 10000, max: 20000, step: 2500
    }
};

function getUserId() {
    try { return JSON.parse(localStorage.getItem('user_info')).id; } catch { return null; }
}

function changeSensor(id, label, color, unit, min, max, step) {
    // Use passed parameters or fallback to SENSOR_CONFIG
    const config = SENSOR_CONFIG[id] || SENSOR_CONFIG[1];
    currentSensor = {
        id: id,
        label: label || config.label,
        color: color || config.color,
        unit: unit || config.unit,
        min: min !== undefined ? min : config.min,
        max: max !== undefined ? max : config.max,
        step: step !== undefined ? step : config.step
    };

    // Remove active state from all tabs
    document.querySelectorAll('.sensor-tab').forEach(el => {
        el.classList.remove('active');
        const indicator = el.querySelector('.tab-active-indicator');
        if (indicator) indicator.style.display = 'none';
    });

    // Add active state to selected tab
    const activeTab = document.getElementById(`tab-${id}`);
    if (activeTab) {
        activeTab.classList.add('active');
        const indicator = activeTab.querySelector('.tab-active-indicator');
        if (indicator) indicator.style.display = 'block';
    }

    document.getElementById('chart-title').innerText = `Biểu đồ ${currentSensor.label}`;

    const sDate = document.getElementById('start-date').value;
    const eDate = document.getElementById('end-date').value;

    if (sDate && eDate) {
        loadHistory(true);
    } else {
        loadHistory(false);
    }
}

// 3. Bộ lọc nhanh (Giữ nguyên)
function quickFilter(type) {
    const today = new Date();
    const endStr = today.toISOString().split('T')[0];
    let startStr = '';

    // Remove active from all filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Add active to selected button
    document.getElementById(`btn-${type}`).classList.add('active');

    if (type === 'today') { startStr = endStr; }
    else if (type === 'week') {
        const lastWeek = new Date();
        lastWeek.setDate(today.getDate() - 7);
        startStr = lastWeek.toISOString().split('T')[0];
    } else {
        document.getElementById('start-date').value = '';
        document.getElementById('end-date').value = '';
        loadHistory(false);
        return;
    }

    document.getElementById('start-date').value = startStr;
    document.getElementById('end-date').value = endStr;
    loadHistory(true);
}

// 4. Tải dữ liệu (Giữ nguyên)
async function loadHistory(useDateFilter = false) {
    const userId = getUserId();
    if (!userId) { window.location.href = 'login.php'; return; }

    let url = `${API_HISTORY}?user_id=${userId}&sensor_id=${currentSensor.id}`;
    const sDate = document.getElementById('start-date').value;
    const eDate = document.getElementById('end-date').value;

    if (useDateFilter && sDate && eDate) {
        url += `&start=${sDate}&end=${eDate}`;
    } else {
        url += `&limit=50`;
    }

    try {
        const res = await fetch(url);
        const json = await res.json();
        if (json.status === 'success') {
            renderChart(json.data);
            renderStats(json.data);
            renderTable(json.data);
        }
    } catch (e) { console.error(e); }
}

// 5. Vẽ Biểu đồ
function renderChart(data) {
    const ctx = document.getElementById('myChart');
    if (myChart) myChart.destroy();

    const context = ctx.getContext('2d');

    // Gradient fill theo chiều cao thực của canvas
    const height = ctx.parentElement.offsetHeight || 320;
    const gradient = context.createLinearGradient(0, 0, 0, height);
    gradient.addColorStop(0, currentSensor.color + '55');   // 33% opacity ở trên
    gradient.addColorStop(0.6, currentSensor.color + '11'); // rất mờ ở giữa
    gradient.addColorStop(1, currentSensor.color + '00');   // trong suốt ở dưới

    const labels = data.map(item => {
        const parts = item.thoigian.split(' ');
        return parts.length > 1 ? parts[1].substring(0, 5) : item.thoigian;
    });
    const values = data.map(item => parseFloat(item.giatri));

    myChart = new Chart(context, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: currentSensor.label,
                data: values,
                borderColor: currentSensor.color,
                backgroundColor: gradient,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHitRadius: 20,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: currentSensor.color,
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 10, right: 10, bottom: 0, left: 0 } },
            animation: { duration: 600, easing: 'easeInOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a2332',
                    titleColor: '#9aa5b1',
                    bodyColor: '#ffffff',
                    borderColor: currentSensor.color,
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    titleFont: { size: 11, family: "'Be Vietnam Pro', sans-serif" },
                    bodyFont: { size: 14, weight: 'bold', family: "'Be Vietnam Pro', sans-serif" },
                    callbacks: {
                        label: ctx => `${ctx.parsed.y} ${currentSensor.unit}`
                    }
                }
            },
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: '#9aa5b1',
                        font: { size: 11, family: "'Be Vietnam Pro', sans-serif" },
                        maxTicksLimit: 8,
                        maxRotation: 0,
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                        drawBorder: false,
                    },
                    border: { display: false, dash: [4, 4] },
                    min: currentSensor.min,
                    max: currentSensor.max,
                    ticks: {
                        color: '#9aa5b1',
                        font: { size: 11, family: "'Be Vietnam Pro', sans-serif" },
                        stepSize: currentSensor.step,
                        callback: val => val + (currentSensor.unit ? ' ' + currentSensor.unit : '')
                    }
                }
            }
        }
    });
}

// 6. Stats & Table & Format (Giữ nguyên)
function renderStats(data) {
    if (data.length === 0) { ['max', 'min', 'avg'].forEach(id => document.getElementById(`stat-${id}`).innerText = '--'); return; }
    const values = data.map(d => parseFloat(d.giatri));
    const avg = (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1);
    document.getElementById('stat-max').innerText = `${Math.max(...values)} ${currentSensor.unit}`;
    document.getElementById('stat-min').innerText = `${Math.min(...values)} ${currentSensor.unit}`;
    document.getElementById('stat-avg').innerText = `${avg} ${currentSensor.unit}`;
    document.getElementById('stat-max').style.color = currentSensor.color;
}

function formatDateVN(dateString) {
    if (!dateString) return '';
    const [d, t] = dateString.split(' ');
    const [y, m, da] = d.split('-');
    return `${da}/${m}/${y} <span style="color: var(--text-secondary); margin-left: 0.25rem;">${t}</span>`;
}

function renderTable(data) {
    const tableData = [...data].reverse();
    const html = tableData.map(row => `
        <tr>
            <td style="font-family: monospace; color: var(--text-secondary); font-size: 0.75rem;">${formatDateVN(row.thoigian)}</td>
            <td><span class="status-badge normal"><span class="material-symbols-outlined">check_circle</span> HOẠT ĐỘNG</span></td>
            <td class="text-right" style="font-weight: 700; font-size: 0.875rem; color: ${currentSensor.color}">${row.giatri} <span style="font-size: 0.75rem; color: var(--text-secondary);">${currentSensor.unit}</span></td>
        </tr>
    `).join('');
    document.getElementById('table-body').innerHTML = html || '<tr><td colspan="3" style="text-align: center; padding: 1rem; color: var(--text-secondary);">Chưa có dữ liệu</td></tr>';
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const type = parseInt(urlParams.get('type')) || 1;
    changeSensor(type);
});
