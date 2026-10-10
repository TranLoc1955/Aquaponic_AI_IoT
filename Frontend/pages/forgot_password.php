<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Khôi phục mật khẩu - HydroSmart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="../css/forgot_password.css" rel="stylesheet"/>
</head>
<body>
    <div class="forgot-card">
        <div class="status-dot" style="justify-content:center;">
            <span class="dot"></span>
            <span>Hệ thống đang hoạt động</span>
        </div>

        <!-- Header -->
        <div class="forgot-header">
            <div class="forgot-icon">
                <span class="material-symbols-outlined">lock_reset</span>
            </div>
            <h1 class="forgot-title">Quên mật khẩu?</h1>
            <p class="forgot-subtitle">Nhập Email và SĐT đã đăng ký để lấy lại mật khẩu</p>
        </div>

        <!-- Form -->
        <div>
            <div class="form-group">
                <label class="form-label" for="email">Email đăng ký</label>
                <input type="email" id="email" class="form-input" placeholder="Ví dụ: khue2@gmail.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Số điện thoại</label>
                <input type="number" id="phone" class="form-input" placeholder="Ví dụ: 0909123456">
            </div>

            <div class="form-group">
                <label class="form-label" for="new_pass">Mật khẩu mới</label>
                <input type="password" id="new_pass" class="form-input" placeholder="••••••••">
            </div>

            <button onclick="resetPassword()" id="btn-reset" class="btn-reset">
                Xác nhận đổi mật khẩu
            </button>

            <a href="login.php" class="back-link">
                <span class="material-symbols-outlined">arrow_back</span>
                Quay lại Đăng nhập
            </a>
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
