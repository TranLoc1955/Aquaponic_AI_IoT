<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <span class="material-symbols-outlined">
potted_plant
</span>
        </div>
        <div class="logo-text">
            <h5>HỆ THỐNG THỦY CANH  - AQUAPONIC</h5>
            <p style="font-size: 10px;">NCKH_WEB</p>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">
        <?php
        $current = basename($_SERVER['PHP_SELF']);
        ?>
        <a class="nav-item <?= $current == 'dashboardW.php' ? 'active' : '' ?>" href="dashboardW.php">
            <span class="material-symbols-outlined" style="font-size: 20px;">Home</span>
            <span style="font-size: 15px;">Tổng Quan</span>
        </a>
        <a class="nav-item <?= $current == 'historyW.php' ? 'active' : '' ?>" href="historyW.php">
            <span class="material-symbols-outlined" style="font-size: 20px;">history</span>
            <span style="font-size: 15px;">Dữ liệu cảm biến</span>
        </a>
        <a class="nav-item <?= $current == 'cauhinhW.php' ? 'active' : '' ?>" href="cauhinhW.php">
            <span class="material-symbols-outlined" style="font-size: 20px;">
settings_heart
</span>
            <span style="font-size: 15px;">Cài đặt cấu hình</span>
        </a>
        <a class="nav-item <?= $current == 'thongbaoW.php' ? 'active' : '' ?>" href="thongbaoW.php" style="position:relative;">
            <span class="material-symbols-outlined" style="font-size: 20px;">notifications</span>
            <span style="font-size: 15px;">Thông báo</span>
        </a>
        <a class="nav-item <?= $current == 'guideW.php' ? 'active' : '' ?>" href="guideW.php">
            <span class="material-symbols-outlined" style="font-size: 20px;">
dictionary
</span>
            <span style="font-size: 15px;">Hướng dẫn</span>
        </a>
        
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar" >
                <span class="material-symbols-outlined">
logout
</span>
            </div>
            <div class="user-info">
                <h4 id="user-display" style="font-size: 15px;">Admin</h4>
                <button class="logout-btn" onclick="localStorage.clear(); window.location.href='login.php'" style="font-size: 10px;">Đăng xuất</button>
            </div>
        </div>
    </div>
</aside>
