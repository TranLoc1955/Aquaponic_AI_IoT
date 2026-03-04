<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Đăng nhập / Đăng ký - Aquaponic AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="../css/login.css" rel="stylesheet"/>
</head>
<body>
    <div class="app-wrapper">
        
        <header class="login-header">
            <div class="logo-container">
                <div class="logo-icon">
                    <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path clip-rule="evenodd" d="M47.2426 24L24 47.2426L0.757355 24L24 0.757355L47.2426 24ZM12.2426 21H35.7574L24 9.24264L12.2426 21Z" fill="currentColor" fill-rule="evenodd"></path>
                    </svg>
                </div>
                <h2 class="logo-title">Aquaponic  AI</h2>
            </div>
        </header>

        <main class="login-main">
            
            <div class="login-hero">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="hero-icon">
                        <span class="material-symbols-outlined">eco</span>
                    </div>
                    <h1 class="hero-title">Giải pháp Thủy canh thông minh</h1>
                    <p class="hero-description">
                        Hệ thống IoT và AI tiên tiến giúp bạn quản lý vườn rau sạch tại đô thị.
                    </p>
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="login-form-container">
                <div class="form-wrapper">
                    <div class="form-header">
                        <h2 class="form-title">Xin chào!</h2>
                        <p class="form-subtitle">Kết nối với hệ thống thủy canh của bạn ngay hôm nay.</p>
                    </div>

                    <!-- Tabs -->
                    <div class="tabs-container">
                        <div class="tabs">
                            <button id="tab-login" class="tab-item active">
                                <span>Đăng nhập</span>
                                <span class="tab-indicator"></span>
                            </button>
                            <button id="tab-register" class="tab-item">
                                <span>Đăng ký</span>
                                <span class="tab-indicator"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Alert Message -->
                    <div id="alert-message" class="alert-box hidden">
                        <div class="alert-content">
                            <span class="material-symbols-outlined alert-icon"></span>
                            <p class="alert-text"></p>
                        </div>
                    </div>

                    <!-- Login Form -->
                    <form id="form-login" class="auth-form active">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div class="input-wrapper">
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">person</span>
                                </div>
                                <input id="login-email" class="form-input" placeholder="user@example.com" type="email" required/>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="label-row">
                                <label class="form-label">Mật khẩu</label>
                                <a href="forgot_password.php" class="forgot-link">Quên mật khẩu?</a>
                            </div>
                            <div class="input-wrapper">
                                <div class="input-icon">
                                    <span class="material-symbols-outlined">lock</span>
                                </div>
                                <input id="login-password" class="form-input" placeholder="Nhập mật khẩu" type="password" required/>
                                <button class="toggle-password" type="button">
                                    <span class="material-symbols-outlined">visibility_off</span>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            <span class="btn-text">Đăng nhập</span>
                            <span class="spinner hidden">
                                <svg class="spinner-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </form>

                    <!-- Register Form -->
                    <form id="form-register" class="auth-form">
                        <div class="form-group">
                            <label class="form-label">Họ tên</label>
                            <input id="register-name" class="form-input" placeholder="Nguyễn Văn A" type="text" required/>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input id="register-email" class="form-input" placeholder="user@example.com" type="email" required/>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Số điện thoại</label>
                            <input id="register-phone" class="form-input" placeholder="0987654321" type="tel" pattern="[0-9]{10,11}"/>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mật khẩu</label>
                            <input id="register-password" class="form-input" placeholder="Tối thiểu 6 ký tự" type="password" required/>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Xác nhận mật khẩu</label>
                            <input id="register-confirm" class="form-input" placeholder="Nhập lại mật khẩu" type="password" required/>
                        </div>

                        <button type="submit" class="btn-submit">
                            <span class="btn-text">Đăng ký</span>
                            <span class="spinner hidden">
                                <svg class="spinner-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        const API_URL = 'http://localhost/Test/API';
        
        // DOM Elements
        const tabLogin = document.getElementById('tab-login');
        const tabRegister = document.getElementById('tab-register');
        const formLogin = document.getElementById('form-login');
        const formRegister = document.getElementById('form-register');
        const alertMessage = document.getElementById('alert-message');

        // Tab Switching
        tabLogin.addEventListener('click', () => {
            tabLogin.classList.add('active');
            tabRegister.classList.remove('active');
            formLogin.classList.add('active');
            formRegister.classList.remove('active');
            hideAlert();
        });

        tabRegister.addEventListener('click', () => {
            tabRegister.classList.add('active');
            tabLogin.classList.remove('active');
            formRegister.classList.add('active');
            formLogin.classList.remove('active');
            hideAlert();
        });

        // Password Toggle
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                const icon = btn.querySelector('.material-symbols-outlined');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.textContent = 'visibility';
                } else {
                    input.type = 'password';
                    icon.textContent = 'visibility_off';
                }
            });
        });

        // Alert Functions
        function showAlert(message, type = 'error') {
            const alert = alertMessage;
            const icon = alert.querySelector('.alert-icon');
            const text = alert.querySelector('.alert-text');
            
            alert.classList.remove('hidden', 'alert-success', 'alert-error');
            
            if (type === 'success') {
                alert.classList.add('alert-success');
                icon.textContent = 'check_circle';
            } else {
                alert.classList.add('alert-error');
                icon.textContent = 'error';
            }
            
            text.textContent = message;
        }

        function hideAlert() {
            alertMessage.classList.add('hidden');
        }

        // Loading State
        function setLoading(form, isLoading) {
            const btn = form.querySelector('.btn-submit');
            const btnText = btn.querySelector('.btn-text');
            const spinner = btn.querySelector('.spinner');
            
            if (isLoading) {
                btn.disabled = true;
                btn.classList.add('loading');
                btnText.textContent = 'Đang xử lý...';
                spinner.classList.remove('hidden');
            } else {
                btn.disabled = false;
                btn.classList.remove('loading');
                btnText.textContent = form.id === 'form-login' ? 'Đăng nhập' : 'Đăng ký';
                spinner.classList.add('hidden');
            }
        }

        // Login Handler
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();
            
            const email = document.getElementById('login-email').value;
            const matkhau = document.getElementById('login-password').value;
            
            if (!email || !matkhau) {
                showAlert('Vui lòng nhập đầy đủ thông tin');
                return;
            }
            
            setLoading(formLogin, true);
            
            try {
                const response = await fetch(`${API_URL}/auth/login.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, matkhau })
                });
                
                const data = await response.json();
                
                if (data.thanh_cong) {
                    localStorage.setItem('is_logged_in', 'true');
                    if (data.data) {
                        localStorage.setItem('user_info', JSON.stringify(data.data));
                    } else if (data.user) {
                        localStorage.setItem('user_info', JSON.stringify(data.user));
                    }
                    
                    showAlert('Đăng nhập thành công!', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboardW.php';
                    }, 1000);
                } else {
                    showAlert(data.thong_bao || 'Đăng nhập thất bại');
                }
            } catch (error) {
                showAlert('Lỗi kết nối server. Vui lòng thử lại!');
                console.error('Login error:', error);
            } finally {
                setLoading(formLogin, false);
            }
        });

        // Register Handler
        formRegister.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();
            
            const hoten = document.getElementById('register-name').value;
            const email = document.getElementById('register-email').value;
            const sodienthoai = document.getElementById('register-phone').value;
            const matkhau = document.getElementById('register-password').value;
            const confirm = document.getElementById('register-confirm').value;
            
            if (!hoten || !email || !matkhau || !confirm) {
                showAlert('Vui lòng nhập đầy đủ thông tin');
                return;
            }
            
            if (matkhau !== confirm) {
                showAlert('Mật khẩu xác nhận không khớp');
                return;
            }
            
            if (matkhau.length < 6) {
                showAlert('Mật khẩu phải có ít nhất 6 ký tự');
                return;
            }
            
            setLoading(formRegister, true);
            
            try {
                const response = await fetch(`${API_URL}/auth/register.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ hoten, email, matkhau, sodienthoai: sodienthoai || null, vaitro: 'user' })
                });
                
                const data = await response.json();
                
                if (data.thanh_cong) {
                    showAlert('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
                    setTimeout(() => {
                        tabLogin.click();
                        formRegister.reset();
                    }, 2000);
                } else {
                    showAlert(data.thong_bao || 'Đăng ký thất bại');
                }
            } catch (error) {
                showAlert('Lỗi kết nối server. Vui lòng thử lại!');
                console.error('Register error:', error);
            } finally {
                setLoading(formRegister, false);
            }
        });
    </script>
</body>
</html>