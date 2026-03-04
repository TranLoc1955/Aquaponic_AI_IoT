<!DOCTYPE html>
<html class="dark" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Giả lập Thiết bị IoT - HydroSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>
        tailwind.config = { darkMode: "class", theme: { extend: { colors: { "primary": "#13ec92", "background-dark": "#10221a", "surface-dark": "#1a332a" }, fontFamily: { "display": ["Space Grotesk", "sans-serif"] } } } }
    </script>
</head>
<body class="bg-[#f6f8f7] dark:bg-background-dark text-slate-900 dark:text-white font-display flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-surface-dark border border-[#234839] rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-[#13221d] p-6 border-b border-[#234839] text-center">
            <div class="w-16 h-16 bg-primary/20 rounded-2xl flex items-center justify-center mx-auto mb-4 text-primary">
                <span class="material-symbols-outlined text-4xl">developer_board</span>
            </div>
            <h1 class="text-2xl font-bold text-white">IoT Simulator</h1>
            <p class="text-slate-400 text-sm mt-1">Giả lập gửi dữ liệu cảm biến</p>
        </div>

        <div class="p-6 space-y-5">
            
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2 uppercase">Mã Serial Thiết Bị</label>
                <input type="text" id="serial" value="UNO_93744" class="w-full bg-[#10221a] border border-[#234839] text-white rounded-xl px-4 py-3 focus:border-primary focus:outline-none font-mono text-center tracking-wider" placeholder="Nhập Serial...">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-orange-400 mb-2 uppercase">Nhiệt độ (°C)</label>
                    <input type="number" id="temp" value="30" class="w-full bg-[#10221a] border border-[#234839] text-white rounded-xl px-4 py-3 focus:border-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-blue-400 mb-2 uppercase">Cảm biến nước </label>
                    <input type="number" id="hum" value="65" class="w-full bg-[#10221a] border border-[#234839] text-white rounded-xl px-4 py-3 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-yellow-400 mb-2 uppercase">Ánh sáng (Lux)</label>
                    <input type="number" id="lux" value="1200" class="w-full bg-[#10221a] border border-[#234839] text-white rounded-xl px-4 py-3 focus:border-yellow-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-purple-400 mb-2 uppercase">Độ pH</label>
                    <input type="number" step="0.1" id="ph" value="6.5" class="w-full bg-[#10221a] border border-[#234839] text-white rounded-xl px-4 py-3 focus:border-purple-500 focus:outline-none">
                </div>
            </div>

            <button onclick="sendData()" id="btn-send" class="w-full bg-primary hover:bg-emerald-400 text-[#10221a] font-bold py-4 rounded-xl transition-all flex items-center justify-center gap-2 group">
                <span class="material-symbols-outlined group-hover:animate-ping">send</span>
                Gửi dữ liệu lên Server
            </button>

            <div id="log" class="text-xs text-center text-slate-500 h-5">...</div>
        </div>
        
        <div class="bg-[#13221d] p-4 border-t border-[#234839] text-center">
            <a href="../../Frontend/pages/dashboard.php" class="text-primary text-sm hover:underline">Quay về Dashboard</a>
        </div>
    </div>

    <script>
        const API_URL = 'http://localhost/Test/API/data_sensor/input.php';

        async function sendData() {
            const btn = document.getElementById('btn-send');
            const log = document.getElementById('log');
            
            // Lấy dữ liệu từ form
            const data = {
                serial: document.getElementById('serial').value,
                temp: document.getElementById('temp').value,
                hum: document.getElementById('hum').value,
                lux: document.getElementById('lux').value,
                ph: document.getElementById('ph').value
            };

            // Hiệu ứng loading
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Đang gửi...';
            log.innerText = '';
            log.className = 'text-xs text-center text-slate-500 h-5';

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();

                if(json.status === 'success') {
                    log.innerText = 'Gửi thành công! ' + new Date().toLocaleTimeString();
                    log.className = 'text-xs text-center text-primary h-5 font-bold';
                    
                    document.getElementById('temp').value = (parseFloat(data.temp) + (Math.random() - 0.5)).toFixed(1);
                    document.getElementById('hum').value = Math.floor(parseFloat(data.hum) + (Math.random() * 4 - 2));
                } else {
                    log.innerText = 'Lỗi: ' + json.message;
                    log.className = 'text-xs text-center text-red-500 h-5 font-bold';
                }

            } catch (e) {
                log.innerText = 'Lỗi kết nối Server!';
                log.className = 'text-xs text-center text-red-500 h-5 font-bold';
                console.error(e);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">send</span> Gửi dữ liệu lên Server';
            }
        }
    </script>
</body>
</html>