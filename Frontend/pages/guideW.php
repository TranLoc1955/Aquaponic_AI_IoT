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
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>
<body>
<div class="app-container">

    <?php include '../components/sidebar_light.php'; ?>

    <main class="main-content">
        <header class="header">
            <h2 class="header-title">
                <span class="material-symbols-outlined">menu_book</span>
                Hướng dẫn & Kiến thức
            </h2>
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
                            <h3 class="section-title">Hệ thống Thủy canh Thông minh</h3>
                            <p class="section-subtitle">Giải pháp IoT cho nông nghiệp đô thị</p>
                        </div>
                    </div>
                    <p class="intro-text">
                        Hệ thống HydroSmart sử dụng công nghệ IoT và AI để tự động hóa việc chăm sóc cây trồng thủy canh.
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
                                        Trang Dashboard hiển thị dữ liệu thời gian thực từ các cảm biến: nhiệt độ, độ ẩm, pH và ánh sáng.
                                        Dữ liệu được cập nhật tự động mỗi 2 giây.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="step-item">
                            <div class="step-header">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4 class="step-title">Điều khiển thiết bị</h4>
                                    <p class="step-text">
                                        Sử dụng các nút bật/tắt trên Dashboard để điều khiển máy bơm, đèn LED và quạt gió.
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
                                Nhiệt độ phù hợp cho cây thủy canh thường dao động từ 18-25°C.
                                Nhiệt độ quá cao (>30°C) có thể gây héo lá, quá thấp (<15°C) làm chậm phát triển.
                            </p>
                        </div>

                        <div class="knowledge-item">
                            <div class="knowledge-header">
                                <span class="material-symbols-outlined icon-humid">water_drop</span>
                                <h4>Độ ẩm không khí</h4>
                            </div>
                            <p>
                                Độ ẩm lý tưởng cho nhà kính thủy canh là 50-70%.
                                Độ ẩm quá cao dễ gây bệnh nấm, quá thấp khiến cây mất nước nhanh.
                            </p>
                        </div>

                        <div class="knowledge-item">
                            <div class="knowledge-header">
                                <span class="material-symbols-outlined icon-ph">water_ph</span>
                                <h4>Độ pH dung dịch</h4>
                            </div>
                            <p>
                                Độ pH tối ưu cho dung dịch thủy canh là 5.5-6.5.
                                pH quá cao (>7) hoặc quá thấp (<5) đều ảnh hưởng đến khả năng hấp thụ dinh dưỡng của cây.
                            </p>
                        </div>

                        <div class="knowledge-item">
                            <div class="knowledge-header">
                                <span class="material-symbols-outlined icon-light">light_mode</span>
                                <h4>Ánh sáng cây trồng</h4>
                            </div>
                            <p>
                                Cây cần 12-16 giờ ánh sáng mỗi ngày.
                                Đèn LED grow light nên cách cây 20-30cm và được bật theo chu kỳ ngày/đêm tự động.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mẹo và lưu ý -->
                <div class="guide-card">
                    <h3 class="card-title">
                        <span class="material-symbols-outlined">tips_and_updates</span>
                        Mẹo và lưu ý
                    </h3>

                    <div class="tips-list">
                        <div class="tip-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <p>Kiểm tra dung dịch dinh dưỡng mỗi tuần và thay định kỳ sau 2-3 tuần</p>
                        </div>
                        <div class="tip-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <p>Vệ sinh hệ thống bơm và ống dẫn định kỳ để tránh tắc nghẽn</p>
                        </div>
                        <div class="tip-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <p>Theo dõi cảnh báo hệ thống và xử lý kịp thời khi có sự cố</p>
                        </div>
                        <div class="tip-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <p>Ghi chép lịch sử dữ liệu để phân tích xu hướng phát triển cây trồng</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>
<script src="../js/auth.js"></script>
<script src="../js/guide.js"></script>
</body>
</html>


    <?php include '../components/sidebar_light.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="h-20 flex items-center justify-between px-8 border-b border-[#234839] bg-surface-dark/80 backdrop-blur-md z-20 shrink-0">
            <h2 class="text-2xl font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">menu_book</span>
                Hướng dẫn & Kiến thức
            </h2>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 scroll-smooth">
            <div class="max-w-5xl mx-auto space-y-8">

                <!-- Giới thiệu -->
                <div class="rounded-3xl bg-surface-dark border border-[#234839] p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 bg-primary/10 rounded-xl text-primary">
                            <span class="material-symbols-outlined text-3xl">eco</span>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Hệ thống Thủy canh Thông minh</h3>
                            <p class="text-slate-400 text-sm mt-1">Giải pháp IoT cho nông nghiệp đô thị</p>
                        </div>
                    </div>
                    <p class="text-slate-300 leading-relaxed">
                        Hệ thống HydroSmart sử dụng công nghệ IoT và AI để tự động hóa việc chăm sóc cây trồng thủy canh.
                        Với các cảm biến thông minh và hệ thống điều khiển tự động, bạn có thể quản lý vườn rau sạch ngay tại nhà một cách dễ dàng và hiệu quả.
                    </p>
                </div>

                <!-- Hướng dẫn sử dụng -->
                <div class="rounded-3xl bg-surface-dark border border-[#234839] p-8">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">play_circle</span>
                        Hướng dẫn sử dụng
                    </h3>

                    <div class="space-y-6">
                        <div class="p-6 bg-[#13261f] rounded-2xl border border-[#234839]">
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold shrink-0">1</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-white mb-2">Kết nối thiết bị</h4>
                                    <p class="text-slate-400 text-sm leading-relaxed">
                                        Sau khi đăng ký, hệ thống sẽ tự động cấp cho bạn một mã serial thiết bị.
                                        Sử dụng mã này để kết nối thiết bị IoT với hệ thống qua WiFi.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-[#13261f] rounded-2xl border border-[#234839]">
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold shrink-0">2</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-white mb-2">Theo dõi cảm biến</h4>
                                    <p class="text-slate-400 text-sm leading-relaxed">
                                        Trang Dashboard hiển thị dữ liệu thời gian thực từ các cảm biến: nhiệt độ, độ ẩm, pH và ánh sáng.
                                        Dữ liệu được cập nhật tự động mỗi 2 giây.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-[#13261f] rounded-2xl border border-[#234839]">
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold shrink-0">3</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-white mb-2">Điều khiển thiết bị</h4>
                                    <p class="text-slate-400 text-sm leading-relaxed">
                                        Sử dụng các nút bật/tắt trên Dashboard để điều khiển máy bơm, đèn LED và quạt gió.
                                        Trạng thái thiết bị được đồng bộ ngay lập tức với hệ thống.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-[#13261f] rounded-2xl border border-[#234839]">
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold shrink-0">4</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-white mb-2">Thiết lập cảnh báo</h4>
                                    <p class="text-slate-400 text-sm leading-relaxed">
                                        Trong trang Cài đặt cấu hình, bạn có thể thiết lập ngưỡng cho từng cảm biến.
                                        Hệ thống sẽ tự động gửi cảnh báo khi giá trị vượt quá ngưỡng cho phép.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kiến thức thủy canh -->
                <div class="rounded-3xl bg-surface-dark border border-[#234839] p-8">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">lightbulb</span>
                        Kiến thức thủy canh
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="p-6 bg-[#13261f] rounded-2xl border border-[#234839]">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="material-symbols-outlined text-orange-400">thermostat</span>
                                <h4 class="text-lg font-bold text-white">Nhiệt độ lý tưởng</h4>
                            </div>
                            <p class="text-slate-400 text-sm leading-relaxed">
                                Nhiệt độ phù hợp cho cây thủy canh thường dao động từ 18-25°C.
                                Nhiệt độ quá cao (>30°C) có thể gây héo lá, quá thấp (<15°C) làm chậm phát triển.
                            </p>
                        </div>

                        <div class="p-6 bg-[#13261f] rounded-2xl border border-[#234839]">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="material-symbols-outlined text-blue-400">water_drop</span>
                                <h4 class="text-lg font-bold text-white">Độ ẩm không khí</h4>
                            </div>
                            <p class="text-slate-400 text-sm leading-relaxed">
                                Độ ẩm lý tưởng cho nhà kính thủy canh là 50-70%.
                                Độ ẩm quá cao dễ gây bệnh nấm, quá thấp khiến cây mất nước nhanh.
                            </p>
                        </div>

                        <div class="p-6 bg-[#13261f] rounded-2xl border border-[#234839]">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="material-symbols-outlined text-purple-400">water_ph</span>
                                <h4 class="text-lg font-bold text-white">Độ pH dung dịch</h4>
                            </div>
                            <p class="text-slate-400 text-sm leading-relaxed">
                                Độ pH tối ưu cho dung dịch thủy canh là 5.5-6.5.
                                pH quá cao (>7) hoặc quá thấp (<5) đều ảnh hưởng đến khả năng hấp thụ dinh dưỡng của cây.
                            </p>
                        </div>

                        <div class="p-6 bg-[#13261f] rounded-2xl border border-[#234839]">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="material-symbols-outlined text-yellow-400">light_mode</span>
                                <h4 class="text-lg font-bold text-white">Ánh sáng cây trồng</h4>
                            </div>
                            <p class="text-slate-400 text-sm leading-relaxed">
                                Cây cần 12-16 giờ ánh sáng mỗi ngày.
                                Đèn LED grow light nên cách cây 20-30cm và được bật theo chu kỳ ngày/đêm tự động.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mẹo và lưu ý -->
                <div class="rounded-3xl bg-surface-dark border border-[#234839] p-8">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">tips_and_updates</span>
                        Mẹo và lưu ý
                    </h3>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3 p-4 bg-[#13261f] rounded-xl border border-[#234839]">
                            <span class="material-symbols-outlined text-primary shrink-0">check_circle</span>
                            <p class="text-slate-300 text-sm">Kiểm tra dung dịch dinh dưỡng mỗi tuần và thay định kỳ sau 2-3 tuần</p>
                        </div>
                        <div class="flex items-start gap-3 p-4 bg-[#13261f] rounded-xl border border-[#234839]">
                            <span class="material-symbols-outlined text-primary shrink-0">check_circle</span>
                            <p class="text-slate-300 text-sm">Vệ sinh hệ thống bơm và ống dẫn định kỳ để tránh tắc nghẽn</p>
                        </div>
                        <div class="flex items-start gap-3 p-4 bg-[#13261f] rounded-xl border border-[#234839]">
                            <span class="material-symbols-outlined text-primary shrink-0">check_circle</span>
                            <p class="text-slate-300 text-sm">Theo dõi cảnh báo hệ thống và xử lý kịp thời khi có sự cố</p>
                        </div>
                        <div class="flex items-start gap-3 p-4 bg-[#13261f] rounded-xl border border-[#234839]">
                            <span class="material-symbols-outlined text-primary shrink-0">check_circle</span>
                            <p class="text-slate-300 text-sm">Ghi chép lịch sử dữ liệu để phân tích xu hướng phát triển cây trồng</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>
<script src="../js/guide.js"></script>
</body>
</html>