<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Hướng dẫn & Kiến thức - HydroSmart</title>
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/guide.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>
<body>
<div class="app-container">

    <?php include '../components/sidebar_light.php'; ?>

    <main class="main-content">
        <header class="header">
                <!-- Left: Title -->
                <div class="header-left">
                    <h2 class="header-title">
                        <span class="material-symbols-outlined">dictionary</span>
                        Hướng dẫn & Kiến thức
                    </h2>
                </div>
                <!-- Center: Clock -->
                <div class="header-center">
                    <div class="clock" id="clock">--:--:--</div>
                </div>
                <!-- Right: Notification -->
                <div class="header-right">
                    <a href="thongbaoW.php" class="notif-wrapper" title="Thông báo" style="text-decoration:none;">
                        <div class="notif-btn"><span class="material-symbols-outlined">notifications</span></div>
                        <span class="notif-badge" id="notif-badge"></span>
                    </a>
                </div>
            </header>

        <div class="content">
            <div class="content-container">

                <!-- Giới thiệu -->
                <div class="guide-card intro-card">
                    <div class="card-header-section">
                        <div class="icon-badge">
                            <span class="material-symbols-outlined">eco</span>
                        </div>
                        <div>
                            <h3 class="section-title">Hệ thống Giám sát Thủy canh Thông minh</h3>
                            <p class="section-subtitle">Giải pháp IoT cho nông nghiệp tại đô thị</p>
                        </div>
                    </div>
                    <p class="intro-text">
                        Hệ thống Aquaponic sử dụng công nghệ IoT và AI để tự động hóa việc chăm sóc cây trồng thủy canh.
                        Với các cảm biến thông minh và hệ thống điều khiển tự động, bạn có thể quản lý vườn rau sạch ngay tại nhà một cách dễ dàng và hiệu quả.
                    </p>
                </div>

                <!-- Hướng dẫn sử dụng -->
                <div class="guide-card">
                    <h3 class="card-title">
                        <span class="material-symbols-outlined">play_circle</span>
                        Hướng dẫn sử dụng
                    </h3>

                    <div class="steps-list">
                        <div class="step-item">
                            <div class="step-header">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4 class="step-title">Kết nối thiết bị</h4>
                                    <p class="step-text">
                                        Sau khi đăng ký, hệ thống sẽ tự động cấp cho bạn một mã serial thiết bị.
                                        Sử dụng mã này để kết nối thiết bị IoT với hệ thống qua WiFi.
                                        Nhập thông tin WiFi để thiết bị gửi dữ liệu cảm biến (nhiệt độ, độ ẩm, mực nước, ánh sáng) về hệ thống.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="step-item">
                            <div class="step-header">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4 class="step-title">Theo dõi cảm biến</h4>
                                    <p class="step-text">
                                        Trang Dashboard hiển thị dữ liệu thời gian thực từ các cảm biến: nhiệt độ, độ ẩm, mức nước và ánh sáng.
                                        Dữ liệu được cập nhật tự động mỗi 5 giây.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="step-item">
                            <div class="step-header">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4 class="step-title">Điều khiển thiết bị </h4>
                                    <p class="step-text">
                                        Sử dụng các nút bật/tắt trên Dashboard để điều khiển Máy bơm, Đèn và Phun sương.
                                        Trạng thái thiết bị được đồng bộ ngay lập tức với hệ thống.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="step-item">
                            <div class="step-header">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4 class="step-title">Thiết lập cảnh báo</h4>
                                    <p class="step-text">
                                        Trong trang Cài đặt cấu hình, bạn có thể thiết lập ngưỡng cho từng cảm biến.
                                        Hệ thống sẽ tự động gửi cảnh báo khi giá trị vượt quá ngưỡng cho phép.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-header">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h4 class="step-title">Nhận thông báo</h4>
                                    <p class="step-text">
                                        Trong trang Thông báo, bạn có thể xem lịch sử các cảnh báo đã được gửi khi giá trị vượt ngưỡng mà bạn cấu hình trước đó.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kiến thức thủy canh -->
                <div class="guide-card">
                    <h3 class="card-title">
                        <span class="material-symbols-outlined">lightbulb</span>
                        Kiến thức thủy canh
                    </h3>

                    <div class="knowledge-grid">
                        <div class="knowledge-item">
                            <div class="knowledge-header">
                                <span class="material-symbols-outlined icon-temp">thermostat</span>
                                <h4>Nhiệt độ lý tưởng</h4>
                            </div>
                            <p>
                                Nhiệt độ phù hợp cho cây thủy canh thường dao động từ 13-34°C.
                                Nhiệt độ quá cao (> 30°C) có thể gây héo lá, quá thấp (< 15°C) làm chậm phát triển.
                            </p>
                        </div>

                        <div class="knowledge-item">
                            <div class="knowledge-header">
                                <span class="material-symbols-outlined icon-humid">water_drop</span>
                                <h4>Độ ẩm không khí</h4>
                            </div>
                            <p>
                                Độ ẩm lý tưởng cho nhà kính thủy canh là 50-80%.
                                Độ ẩm quá cao dễ gây bệnh nấm hoặc thối cho cây, còn quá thấp khiến cây mất nước nhanh hơn.
                            </p>
                        </div>

                        <div class="knowledge-item">
                            <div class="knowledge-header">
                                <span class="material-symbols-outlined icon-ph">water</span>
                                <h4>Mức nước trong bể</h4>
                            </div>
                            <p>
                                Mức nước trong hệ thống thủy canh được đo bằng water sensor để theo dõi lượng dung dịch dinh dưỡng cho cây. Khi giá trị cảm biến quá thấp (≈ < 300) cần bổ sung nước, còn mức ổn định khoảng 500 – 800 giúp cây phát triển tốt.
                            </p>
                        </div>

                        <div class="knowledge-item">
                            <div class="knowledge-header">
                                <span class="material-symbols-outlined icon-light">light_mode</span>
                                <h4>Ánh sáng cây trồng</h4>
                            </div>
                            <p>
                                Cây cần 12-16 giờ ánh sáng mỗi ngày.
                                Đèn nên cách cây 20-30cm và được bật theo chu kỳ ngày/đêm tự động.
                                Độ sáng của đèn nên để ở mức 12000-19000 Lux để cây quang hợp hiệu quả.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mẹo và lưu ý -->
                <div class="guide-card">
                    <h3 class="card-title">
                        <span class="material-symbols-outlined">help</span>
                        Mẹo và lưu ý
                    </h3>
                    <div class="tips-list">
                        <div class="tip-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <p>Kiểm tra thiết bị cảm biến mỗi tuần sau 2-3 tuần sử dụng tránh bị lỗi</p>
                        </div>
                        <div class="tip-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <p>Vệ sinh hệ thống bơm nước và ống dẫn định kỳ để tránh tắc nghẽn làm cháy bơm</p>
                        </div>
                        <div class="tip-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <p>Theo dõi cảnh báo hệ thống và xử lý kịp thời khi có sự cố</p>
                        </div>
                        <div class="tip-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <p>Ghi chép lịch sử dữ liệu để phân tích xu hướng phát triển cây trồng và cấu hình lại theo mức độ phát triển theo từng giai đoạn của cây</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>
<script src="../js/auth.js"></script>
<script src="../js/notification.js"></script>
<script src="../js/guide.js"></script>
</body>
</html>
