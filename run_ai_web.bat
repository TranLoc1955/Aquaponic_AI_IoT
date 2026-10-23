@echo off
chcp 65001 >nul
title Aquaponic - AI Web Subscriber
echo ====================================================================
echo             AQUAPONIC - TIẾN TRÌNH AI WEB SUBSCRIBER
echo ====================================================================
echo.
echo [i] Tiến trình này lắng nghe dữ liệu cảm biến từ MQTT và chạy phân tích AI Web.
echo [i] Nhớ mở trang Dashboard (dashboardW.php) và gạt công tắc "AI Web: BẬT".
echo [i] Nhấn Ctrl+C để dừng tiến trình bất cứ lúc nào.
echo.
echo --------------------------------------------------------------------
cd /d C:\xampp\htdocs\Test
php AI_Web\web_ai_subscriber.php
pause
