<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Cấu hình Cảnh báo - HydroSmart</title>
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/cauhinh.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>
<body>
<div class="app-container">
    
    <?php include '../components/sidebar_light.php'; ?>
    <main class="main-content">
        <header class="header">
            <div class="header-info">
                <h2 class="header-title">Thiết lập ngưỡng cảnh báo</h2>
                <p class="header-subtitle">Quản lý các quy tắc an toàn cho vườn</p>
            </div>
            <button onclick="saveConfig()" class="save-button">
                <span class="material-symbols-rounded"></span> Lưu thay đổi
            </button>
        </header>

        <div class="content">
            <div class="content-container">
                
                <div id="config-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-full flex justify-center mt-20 text-primary">
                        <span class="material-symbols-rounded animate-spin text-4xl">progress_activity</span>
                    </div>
                </div>

                <div class="info-box mt-8 flex items-start gap-3">
                    <span class="material-symbols-rounded info-icon">info</span>
                    <div class="info-text">
                        <p><strong>Hướng dẫn:</strong></p>
                        <ul>
                            <li>Bật nút <strong>Giám sát</strong> để kích hoạt cảnh báo cho cảm biến đó.</li>
                            <li>Các ô để trống nghĩa là không giới hạn ngưỡng đó.</li>
                            <li>Bấm nút <strong>Thùng rác</strong> để xóa nhanh cấu hình và tắt giám sát.</li>
                            <li>Nhớ bấm <strong>Lưu thay đổi</strong> sau khi chỉnh sửa.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="../js/auth.js"></script>
<script src="../js/cauhinh.js"></script>
</body>
</html>