<?php
/**
 * Sidebar Component - Fresh Botanical Green SaaS Minimalist & Collapsible Navigation
 * Grouped Navigation with Tooltips & Mobile Drawer
 */
$current = basename($_SERVER['PHP_SELF']);
?>
<script>
    // Phục hồi trạng thái thu gọn trước khi render tránh giật giao diện
    if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth > 1024) {
        document.documentElement.classList.add('sidebar-is-collapsed');
    }
</script>

<aside class="sidebar" id="app-sidebar" aria-label="Thanh điều hướng chính">
    <!-- Header / Logo -->
    <div class="sidebar-header">
        <a href="dashboardW.php" class="sidebar-logo" title="Trang chủ Hệ thống">
            <div class="logo-icon">
                <span class="material-symbols-outlined">eco</span>
            </div>
            <div class="logo-text">
                <h5 class="logo-title">AQUAPONIC</h5>
                <p class="logo-subtitle">AIoT Smart Farm</p>
            </div>
        </a>

        <!-- Nút Toggle thu gọn/mở rộng (Desktop) -->
        <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle-btn" 
                aria-label="Thu gọn hoặc mở rộng thanh bên" 
                title="Thu gọn / Mở rộng menu">
            <span class="material-symbols-outlined">menu_open</span>
        </button>

        <!-- Nút Đóng Drawer (Mobile) -->
        <button type="button" class="sidebar-close-btn" id="sidebar-close-btn" 
                aria-label="Đóng menu" 
                title="Đóng">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    
    <!-- Navigation (Grouped) -->
    <nav class="sidebar-nav">
        <!-- Nhóm 1: Giám sát & Dữ liệu -->
        <div class="nav-group">
            <div class="nav-group-title">
                <span>GIÁM SÁT & DỮ LIỆU</span>
            </div>
            
            <a class="nav-item <?= $current == 'dashboardW.php' ? 'active' : '' ?>" 
               href="dashboardW.php" 
               data-tooltip="Tổng quan">
                <span class="nav-icon material-symbols-outlined">home</span>
                <span class="nav-text">Tổng quan</span>
            </a>

            <a class="nav-item <?= $current == 'historyW.php' ? 'active' : '' ?>" 
               href="historyW.php" 
               data-tooltip="Dữ liệu cảm biến">
                <span class="nav-icon material-symbols-outlined">history</span>
                <span class="nav-text">Dữ liệu cảm biến</span>
            </a>
        </div>

        <!-- Nhóm 2: Hệ thống & Cấu hình -->
        <div class="nav-group">
            <div class="nav-group-title">
                <span>HỆ THỐNG & CẤU HÌNH</span>
            </div>

            <a class="nav-item <?= $current == 'cauhinhW.php' ? 'active' : '' ?>" 
               href="cauhinhW.php" 
               data-tooltip="Cài đặt cấu hình">
                <span class="nav-icon material-symbols-outlined">tune</span>
                <span class="nav-text">Cài đặt cấu hình</span>
            </a>

            <a class="nav-item <?= $current == 'thongbaoW.php' ? 'active' : '' ?>" 
               href="thongbaoW.php" 
               data-tooltip="Thông báo">
                <span class="nav-icon material-symbols-outlined">notifications</span>
                <span class="nav-text">Thông báo</span>
            </a>

            <a class="nav-item <?= $current == 'guideW.php' ? 'active' : '' ?>" 
               href="guideW.php" 
               data-tooltip="Hướng dẫn">
                <span class="nav-icon material-symbols-outlined">menu_book</span>
                <span class="nav-text">Hướng dẫn</span>
            </a>
        </div>
    </nav>
    
    <!-- Footer / User Profile -->
    <div class="sidebar-footer">
        <div class="user-profile" data-tooltip="Tài khoản">
            <div class="user-avatar" title="Hồ sơ người dùng">
                <span class="material-symbols-outlined">person</span>
            </div>
            <div class="user-info">
                <h4 id="user-display">Admin</h4>
            </div>
            <button type="button" class="logout-btn" id="logout-btn" 
                    onclick="localStorage.clear(); window.location.href='login.php'" 
                    title="Đăng xuất" 
                    aria-label="Đăng xuất">
                <span class="material-symbols-outlined">logout</span>
            </button>
        </div>
    </div>
</aside>

<!-- Backdrop overlay khi mở mobile drawer -->
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<!-- Nạp script quản lý sidebar -->
<script src="../js/sidebar.js" defer></script>
