<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <span class="material-symbols-outlined">potted_plant</span>
        </div>
        <div class="logo-text">
            <h4>Hệ thống thủy canh</h4>
            <p>NCKH_WEB</p>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">
        <?php
        $current = basename($_SERVER['PHP_SELF']);
        ?>
        <a class="nav-item <?= $current == 'dashboardW.php' ? 'active' : '' ?>" href="dashboardW.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Tổng Quan</span>
        </a>
        <a class="nav-item <?= $current == 'historyW.php' ? 'active' : '' ?>" href="historyW.php">
            <span class="material-symbols-outlined">history</span>
            <span>Dữ liệu cảm biến</span>
        </a>
        <a class="nav-item <?= $current == 'cauhinhW.php' ? 'active' : '' ?>" href="cauhinhW.php">
            <span class="material-symbols-outlined">settings</span>
            <span>Cài đặt cấu hình</span>
        </a>
        <a class="nav-item <?= $current == 'guide.php' ? 'active' : '' ?>" href="guideW.php">
            <span class="material-symbols-outlined">menu_book</span>
            <span>Hướng dẫn</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">U</div>
            <div class="user-info">
                <h4 id="user-display">Admin</h4>
                <button class="logout-btn" onclick="localStorage.clear(); window.location.href='login.php'">Đăng xuất</button>
            </div>
        </div>
    </div>
</aside>
