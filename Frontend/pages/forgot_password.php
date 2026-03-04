<!DOCTYPE html>
<html class="dark" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Khôi phục mật khẩu - HydroSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = { 
            darkMode: "class", 
            theme: { 
                extend: { 
                    colors: { 
                        "primary": "#00b4d8", 
                        "primary-dark": "#0096c7",
                        "background-dark": "#0a0e27", 
                        "surface-dark": "#141b3d" 
                    }, 
                    fontFamily: { 
                        "display": ["Space Grotesk", "sans-serif"] 
                    } 
                } 
            } 
        }
    </script>
</head>
<body class="bg-[#f6f8f7] dark:bg-background-dark text-white font-display flex items-center justify-center min-h-screen p-4 relative overflow-hidden">
    
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] bg-primary/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[400px] h-[400px] bg-blue-500/10 rounded-full blur-[100px]"></div>
    </div>

    <div class="w-full max-w-md bg-surface-dark/80 backdrop-blur-xl border border-[#234839] rounded-3xl shadow-2xl p-8 relative">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2">Quên mật khẩu?</h1>
            <p class="text-slate-400 text-sm">Nhập Email và SĐT đã đăng ký để lấy lại mật khẩu</p>
        </div>

        <div class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-primary mb-2 uppercase tracking-wider">Email đăng ký</label>
                <input type="email" id="email" class="w-full bg-[#10221a] border border-[#234839] text-white rounded-xl px-4 py-3.5 focus:border-primary focus:outline-none transition-colors placeholder-slate-600" placeholder="Ví dụ: khue2@gmail.com">
            </div>

            <div>
                <label class="block text-xs font-bold text-primary mb-2 uppercase tracking-wider">Số điện thoại</label>
                <input type="number" id="phone" class="w-full bg-[#10221a] border border-[#234839] text-white rounded-xl px-4 py-3.5 focus:border-primary focus:outline-none transition-colors placeholder-slate-600" placeholder="Ví dụ: 0909123456">
            </div>

            <div>
                <label class="block text-xs font-bold text-primary mb-2 uppercase tracking-wider">Mật khẩu mới</label>
                <input type="password" id="new_pass" class="w-full bg-[#10221a] border border-[#234839] text-white rounded-xl px-4 py-3.5 focus:border-primary focus:outline-none transition-colors placeholder-slate-600" placeholder="••••••••">
            </div>

            <button onclick="resetPassword()" id="btn-reset" class="w-full bg-primary hover:bg-emerald-400 text-[#10221a] font-bold py-4 rounded-xl transition-all shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] mt-2 cursor-pointer">
                Xác nhận đổi mật khẩu
            </button>

            <div class="text-center mt-6">
                <a href="login.php" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors">
                    Quay lại Đăng nhập
                </a>
            </div>
        </div>
    </div>

    <script>
        async function resetPassword() {
            console.log("Đã bấm nút..."); // Kiểm tra xem nút có ăn không
            
            // 1. Lấy dữ liệu từ các ô nhập (Đảm bảo ID khớp với HTML ở trên)
            const emailInp = document.getElementById('email');
            const phoneInp = document.getElementById('phone');
            const passInp  = document.getElementById('new_pass');
            const btn      = document.getElementById('btn-reset');

            const email = emailInp.value.trim();
            const phone = phoneInp.value.trim();
            const pass  = passInp.value.trim();

            if(!email || !phone || !pass) {
                alert('Vui lòng nhập đầy đủ: Email, SĐT và Mật khẩu mới!');
                return;
            }

            // 2. Hiệu ứng đang tải
            const oldText = btn.innerText;
            btn.innerText = 'Đang xử lý...';
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');

            try {
                // 3. Gửi API
                console.log("Đang gửi đến API...");
                const res = await fetch('http://localhost/Test/API/auth/reset_password.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ 
                        email: email, 
                        phone: phone, 
                        new_pass: pass 
                    })
                });
                
                // 4. Xử lý phản hồi
                const json = await res.json();
                console.log("Kết quả:", json);

                if(json.status === 'success') {
                    alert('✅ ' + json.message);
                    window.location.href = 'login.php'; 
                } else {
                    alert('❌ Lỗi: ' + json.message);
                }
            } catch(e) {
                console.error(e);
                alert('⚠️ Lỗi kết nối Server! Vui lòng kiểm tra lại API.');
            } finally {
                // Trả lại nút bấm
                btn.innerText = oldText;
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    </script>
</body>
</html>