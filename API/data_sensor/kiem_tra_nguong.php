<?php
// kiem_tra_nguong.php
// Gọi hàm này sau mỗi lần nhận dữ liệu từ MQTT

function kiemTraNguong($db, $loaicambien, $gia_tri, $user_id = null) {

    // ── Bảng ngưỡng cho rau ăn lá, ngoài trời có mái che ──
    $nguong = [
        'nhiet_do' => [
            'don_vi'   => '°C',
            'icon'     => 'thermostat',
            'rules'    => [
                ['min' => null, 'max' => 18,    'muc_do' => 'critical', 'title' => 'Nhiệt độ quá thấp',    'action' => 'Tắt quạt, theo dõi cây'],
                ['min' => 18,   'max' => 22,    'muc_do' => 'warning',  'title' => 'Nhiệt độ hơi thấp',    'action' => 'Theo dõi thêm'],
                ['min' => 22,   'max' => 28,    'muc_do' => null,       'title' => '',                      'action' => ''], // bình thường - không tạo cảnh báo
                ['min' => 28,   'max' => 32,    'muc_do' => 'warning',  'title' => 'Nhiệt độ hơi cao',     'action' => 'Quạt đã bật tự động'],
                ['min' => 32,   'max' => null,  'muc_do' => 'critical', 'title' => 'Nhiệt độ quá cao',     'action' => 'Quạt bật + cần kiểm tra ngay'],
            ]
        ],
        'do_am' => [
            'don_vi'   => '%',
            'icon'     => 'water_drop',
            'rules'    => [
                ['min' => null, 'max' => 50,    'muc_do' => 'critical', 'title' => 'Độ ẩm quá thấp',       'action' => 'Nguy cơ cây mất nước nhanh'],
                ['min' => 50,   'max' => 60,    'muc_do' => 'warning',  'title' => 'Độ ẩm hơi thấp',       'action' => 'Theo dõi thêm'],
                ['min' => 60,   'max' => 75,    'muc_do' => null,       'title' => '',                      'action' => ''], // bình thường
                ['min' => 75,   'max' => 85,    'muc_do' => 'warning',  'title' => 'Độ ẩm cao',            'action' => 'Quạt đã bật tự động'],
                ['min' => 85,   'max' => null,  'muc_do' => 'critical', 'title' => 'Độ ẩm nguy hiểm',      'action' => 'Nguy cơ nấm mốc — quạt bật'],
            ]
        ],
        'anh_sang' => [
            'don_vi'   => 'lux',
            'icon'     => 'light_mode',
            'rules'    => [
                ['min' => null, 'max' => 1000,  'muc_do' => 'critical', 'title' => 'Ánh sáng quá yếu',     'action' => 'Đèn LED đã bật tự động'],
                ['min' => 1000, 'max' => 5000,  'muc_do' => 'warning',  'title' => 'Ánh sáng thiếu',       'action' => 'Đèn LED đã bật'],
                ['min' => 5000, 'max' => 20000, 'muc_do' => null,       'title' => '',                      'action' => ''], // bình thường
                ['min' => 20000,'max' => 30000, 'muc_do' => 'warning',  'title' => 'Ánh sáng quá mạnh',    'action' => 'Theo dõi, cảnh báo cháy lá'],
                ['min' => 30000,'max' => null,  'muc_do' => 'critical', 'title' => 'Ánh sáng cực mạnh',    'action' => 'Nguy cơ cháy lá nghiêm trọng'],
            ]
        ],
        'muc_nuoc' => [
            'don_vi'   => '',
            'icon'     => 'water',
            'rules'    => [
                ['min' => null, 'max' => 20,    'muc_do' => 'critical', 'title' => 'Cạn bể — khẩn cấp',   'action' => 'Bơm đã TẮT để bảo vệ thiết bị'],
                ['min' => 20,   'max' => 40,    'muc_do' => 'critical', 'title' => 'Mức nước nguy hiểm',   'action' => 'Bơm đã TẮT — cần bổ sung nước ngay'],
                ['min' => 40,   'max' => 60,    'muc_do' => 'warning',  'title' => 'Mức nước thấp',        'action' => 'Bơm chạy liên tục — cần thêm nước'],
                ['min' => 60,   'max' => 90,    'muc_do' => null,       'title' => '',                      'action' => ''], // bình thường
                ['min' => 90,   'max' => 95,    'muc_do' => 'warning',  'title' => 'Mức nước gần đầy',     'action' => 'Theo dõi, gần tràn bể'],
                ['min' => 95,   'max' => null,  'muc_do' => 'critical', 'title' => 'Nguy cơ tràn bể',      'action' => 'Bơm đã TẮT tự động'],
            ]
        ],
    ];

    // Không có cấu hình cho loại cảm biến này → bỏ qua
    $key = strtolower(trim(str_replace(' ', '_', $loaicambien)));
    if (!isset($nguong[$key])) return;

    $cfg = $nguong[$key];

    // Tìm rule phù hợp với giá trị hiện tại
    $matched = null;
    foreach ($cfg['rules'] as $rule) {
        $min_ok = ($rule['min'] === null || $gia_tri >= $rule['min']);
        $max_ok = ($rule['max'] === null || $gia_tri <  $rule['max']);
        if ($min_ok && $max_ok) { $matched = $rule; break; }
    }

    // Giá trị bình thường hoặc không khớp rule nào → không tạo cảnh báo
    if (!$matched || $matched['muc_do'] === null) return;

    // ── Chống spam: không tạo cảnh báo trùng trong vòng 10 phút ──
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM canhbao
        WHERE loaicambien = ?
          AND muc_do      = ?
          AND thoigian    > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $stmt->execute([$key, $matched['muc_do']]);
    if ($stmt->fetchColumn() > 0) return; // đã có cảnh báo gần đây → bỏ qua

    // ── Tạo nội dung thông báo đầy đủ ──
    $noi_dung = sprintf(
        "%s: %.1f%s — %s",
        $matched['title'],
        $gia_tri,
        $cfg['don_vi'],
        $matched['action']
    );

    // ── INSERT vào bảng canhbao ──
    $stmt = $db->prepare("
        INSERT INTO canhbao
            (loaicambien, gia_tri_thuc, donvido, muc_do, noi_dung, thoigian, trangthai, id_nguoidung)
        VALUES
            (?, ?, ?, ?, ?, NOW(), 0, ?)
    ");
    $stmt->execute([
        $key,
        $gia_tri,
        $cfg['don_vi'],
        $matched['muc_do'],
        $noi_dung,
        $user_id
    ]);
}