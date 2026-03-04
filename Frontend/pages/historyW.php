<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Lịch sử & Phân tích - HydroSmart</title>

    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/history.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>
<body>
<div class="history-container">

    <?php include '../components/sidebar_light.php'; ?>

    <main class="history-main">

        <header class="history-header">
            <h2 class="history-title">Lịch sử dữ liệu - Phân tích</h2>
            <div class="header-actions">
                <div class="filter-group">
                    <button onclick="quickFilter('today')" class="filter-btn" id="btn-today">Hôm nay</button>
                    <button onclick="quickFilter('week')" class="filter-btn" id="btn-week">7 ngày</button>
                    <button onclick="quickFilter('all')" class="filter-btn active" id="btn-all">Tất cả</button>
                </div>
            </div>
        </header>

        <div class="content-area">
            <div class="content-wrapper">

                <!-- Sensor Tabs -->
                <div class="sensor-grid section-gap">

                    <button onclick="changeSensor(1, 'Nhiệt độ', '#fb923c', '°C', 10, 35, 5)"
                            id="tab-1" class="sensor-tab">
                        <div class="tab-header">
                            <span class="tab-icon temp material-symbols-outlined">thermostat</span>
                            <span class="tab-badge">Cảm biến thứ 1</span>
                        </div>
                        <div class="tab-label">Cảm biến</div>
                        <div class="tab-name temp-text">Nhiệt độ</div>
                        <div class="active-indicator temp-bg"></div>
                    </button>

                    <button onclick="changeSensor(2, 'Độ ẩm', '#3b82f6', '%', 40, 80, 15)"
                            id="tab-2" class="sensor-tab">
                        <div class="tab-header">
                            <span class="tab-icon humidity material-symbols-outlined">water_drop</span>
                            <span class="tab-badge">Cảm biến thứ 2</span>
                        </div>
                        <div class="tab-label">Cảm biến</div>
                        <div class="tab-name humidity-text">Độ ẩm</div>
                        <div class="active-indicator humidity-bg"></div>
                    </button>

                    <button onclick="changeSensor(4, 'Ánh sáng', '#eab308', 'Lux', 10000, 20000, 500)"
                            id="tab-4" class="sensor-tab">
                        <div class="tab-header">
                            <span class="tab-icon light material-symbols-outlined">light_mode</span>
                            <span class="tab-badge">Cảm biến thứ 3</span>
                        </div>
                        <div class="tab-label">Cảm biến</div>
                        <div class="tab-name light-text">Ánh sáng</div>
                        <div class="active-indicator light-bg"></div>
                    </button>

                    <button onclick="changeSensor(3, 'Độ pH', '#a855f7', '', 4, 10, 1)"
                            id="tab-3" class="sensor-tab">
                        <div class="tab-header">
                            <span class="tab-icon ph material-symbols-outlined">water_ph</span>
                            <span class="tab-badge">Cảm biến thứ 4</span>
                        </div>
                        <div class="tab-label">Cảm biến</div>
                        <div class="tab-name ph-text">Độ pH</div>
                        <div class="active-indicator ph-bg"></div>
                    </button>

                </div>

                <!-- Chart + Stats Layout -->
                <div class="chart-stats-layout section-gap">

                    <!-- Chart Card -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3 class="chart-title" id="chart-title">Biểu đồ theo dõi</h3>
                            <div class="chart-controls">
                                <input type="date" id="start-date" class="date-input">
                                <span class="date-separator">-</span>
                                <input type="date" id="end-date" class="date-input">
                                <button onclick="loadHistory(true)" class="search-btn material-symbols-outlined">search</button>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="myChart"></canvas>
                        </div>
                    </div> 
                </div>

                <!-- Stats Card -->
                    <div class="stats-card">
                        <div class="stats-header">
                            <h3 class="stats-title">
                                <span class="material-symbols-outlined">monitoring</span>
                                Thống kê
                            </h3>
                        </div>
                        <div class="stats-content">
                            <div class="stat-row">
                                <div class="stat-icon max material-symbols-outlined">arrow_upward</div>
                                <div class="stat-details">
                                    <p class="stat-label">Cao nhất</p>
                                    <p class="stat-value" id="stat-max">--</p>
                                </div>
                            </div>
                            <div class="stat-row">
                                <div class="stat-icon min material-symbols-outlined">arrow_downward</div>
                                <div class="stat-details">
                                    <p class="stat-label">Thấp nhất</p>
                                    <p class="stat-value" id="stat-min">--</p>
                                </div>
                            </div>
                            <div class="stat-row">
                                <div class="stat-icon avg material-symbols-outlined">functions</div>
                                <div class="stat-details">
                                    <p class="stat-label">Trung bình</p>
                                    <p class="stat-value" id="stat-avg">--</p>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Data Table -->
                <div class="table-container section-gap">
                    <div class="table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Thời gian</th>
                                    <th>Trạng thái</th>
                                    <th style="text-align: right;">Giá trị đo</th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script src="../js/auth.js"></script>
<script src="../js/history.js"></script>
</body>
</html>
