<?php
/**
 * AI_Web/engine.php
 * ------------------------------------------------------------
 * "Bộ não" rule-based của AI chạy trên Web Server (lớp 1 — nhanh, tự quyết,
 * dùng cho các trường hợp đơn giản 1 cảm biến / 1 ngưỡng).
 *
 * NGƯỠNG không còn hardcode ở đây nữa mà đọc từ AI_Web/thresholds.json —
 * file này được DÙNG CHUNG với lớp 2 (Python case search), nên 2 lớp AI
 * không bao giờ kết luận khác nhau vì lệch ngưỡng. Muốn đổi ngưỡng: sửa
 * thresholds.json (1 chỗ duy nhất), restart web_ai_subscriber.php và
 * python/case_service.py.
 *
 * Severity: 'normal' | 'warning' | 'danger' (khớp Frontend/js/mqtt.js).
 */

/** Đọc thresholds.json (cache trong biến static, chỉ đọc đĩa 1 lần). */
function layNguongWebAI(): array
{
    static $cache = null;
    if ($cache === null) {
        $path = __DIR__ . '/thresholds.json';
        $raw  = @file_get_contents($path);
        $cache = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($cache)) {
            throw new RuntimeException("Không đọc được file ngưỡng: {$path}");
        }
    }
    return $cache;
}

/**
 * Phân tích 1 bộ giá trị cảm biến. Trả về cấu trúc khớp JSON contract của
 * API/ai/nhan_ket_qua.php, cộng thêm 'abnormal_levels' (mức của từng cảm
 * biến bất thường) để web_ai_subscriber.php quyết định có gọi lớp 2 không.
 *
 * @param array $sv ['nhiet_do'=>..,'do_am'=>..,'anh_sang'=>..,'muc_nuoc'=>..]
 */
function phanTichWebAI(array $sv): array
{
    $cfg = layNguongWebAI();

    // Với mỗi cảm biến: 5 mức theo thứ tự [danger_low, warning_low, normal, warning_high, danger_high]
    // mỗi mức = [severity, title, device, command, reason]
    $mucTheoCamBien = [
        'nhiet_do' => [
            ['danger',  'Nhiệt độ quá thấp', null,      null,  'Theo dõi cây, nhiệt độ quá thấp có thể ảnh hưởng sinh trưởng'],
            ['warning', 'Nhiệt độ hơi thấp', null,      null,  'Theo dõi thêm'],
            [null],
            ['warning', 'Nhiệt độ hơi cao',  'quatgio', 'ON',  'Bật quạt để hạ nhiệt'],
            ['danger',  'Nhiệt độ quá cao',  'quatgio', 'ON',  'Nhiệt độ nguy hiểm cho cây, bật quạt gấp'],
        ],
        'do_am' => [
            ['danger',  'Độ ẩm quá thấp',  null,      null, 'Nguy cơ cây mất nước nhanh'],
            ['warning', 'Độ ẩm hơi thấp',  null,      null, 'Theo dõi thêm'],
            [null],
            ['warning', 'Độ ẩm cao',       'quatgio', 'ON', 'Bật quạt giảm ẩm'],
            ['danger',  'Độ ẩm nguy hiểm', 'quatgio', 'ON', 'Nguy cơ nấm mốc, bật quạt gấp'],
        ],
        'anh_sang' => [
            ['danger',  'Ánh sáng quá yếu',  'denled', 'ON', 'Bật đèn LED bổ sung ánh sáng'],
            ['warning', 'Ánh sáng thiếu',    'denled', 'ON', 'Bật đèn LED hỗ trợ'],
            [null],
            ['warning', 'Ánh sáng quá mạnh', null,     null, 'Theo dõi, cảnh báo cháy lá'],
            ['danger',  'Ánh sáng cực mạnh', null,     null, 'Nguy cơ cháy lá nghiêm trọng'],
        ],
        'muc_nuoc' => [
            ['danger',  'Mức nước nguy hiểm', 'maybom', 'OFF', 'Tắt bơm để bảo vệ thiết bị, cần bổ sung nước ngay'],
            ['warning', 'Mức nước thấp',      null,     null,  'Cần thêm nước sớm'],
            [null],
            ['warning', 'Mức nước gần đầy',   null,     null,  'Theo dõi, gần tràn bể'],
            ['danger',  'Nguy cơ tràn bể',    'maybom', 'OFF', 'Tắt bơm tự động'],
        ],
    ];

    // Ưu tiên khi nhiều cảm biến cùng bất thường: an toàn thiết bị (mực nước)
    // > nhiệt độ > độ ẩm > ánh sáng. Chỉ 1 action_taken được ghi chính thức.
    $priority = ['muc_nuoc', 'nhiet_do', 'do_am', 'anh_sang'];

    $matches = [];
    foreach ($mucTheoCamBien as $key => $mucs) {
        if (!isset($sv[$key]) || $sv[$key] === null || $sv[$key] === '') continue;
        $val = (float) $sv[$key];

        // chỉ số mức = số mốc mà giá trị đã vượt/bằng (0..4)
        $idx = 0;
        foreach ($cfg[$key]['breaks'] as $b) {
            if ($val >= $b) $idx++;
        }

        $muc = $mucs[$idx];
        if ($muc[0] === null) continue; // bình thường

        $matches[$key] = [
            'severity' => $muc[0], 'title' => $muc[1], 'device' => $muc[2],
            'command'  => $muc[3], 'reason' => $muc[4],
            'value'    => $val, 'label' => $cfg[$key]['label'], 'unit' => $cfg[$key]['unit'],
            'level'    => ['danger_low', 'warning_low', 'normal', 'warning_high', 'danger_high'][$idx],
        ];
    }

    // ── Không có gì bất thường ──
    if (empty($matches)) {
        return [
            'anomaly_type'     => 'none',
            'severity'         => 'normal',
            'confidence'       => 'certain',
            'abnormal_sensors' => [],
            'abnormal_levels'  => [],
            'summary'          => 'Tất cả chỉ số đang trong ngưỡng bình thường.',
            'action_taken'     => null,
            'recommendation'   => ['action' => 'NONE', 'message' => 'Không cần can thiệp, hệ thống đang ổn định.'],
        ];
    }

    // ── Có bất thường ──
    $primaryKey = null;
    foreach ($priority as $k) {
        if (isset($matches[$k])) { $primaryKey = $k; break; }
    }
    $primary = $matches[$primaryKey];

    $overallSeverity = 'warning';
    foreach ($matches as $m) {
        if ($m['severity'] === 'danger') { $overallSeverity = 'danger'; break; }
    }

    $summaryParts = [];
    $levels = [];
    foreach ($matches as $k => $m) {
        $summaryParts[] = sprintf('%s: %.1f%s (%s)', $m['label'], $m['value'], $m['unit'], $m['title']);
        $levels[$k] = $m['level'];
    }

    $actionTaken = null;
    if ($primary['device'] && $primary['command']) {
        $actionTaken = [
            'device'  => $primary['device'],
            'command' => $primary['command'],
            'reason'  => $primary['reason'],
        ];
    }

    return [
        'anomaly_type'     => 'environment',
        'severity'         => $overallSeverity,
        'confidence'       => 'certain',
        'abnormal_sensors' => array_keys($matches),
        'abnormal_levels'  => $levels,
        'summary'          => implode(' | ', $summaryParts),
        'action_taken'     => $actionTaken,
        'recommendation'   => [
            'action'  => $actionTaken ? 'AUTO_CONTROL' : 'CHECK_ENVIRONMENT',
            'message' => $primary['reason'],
        ],
    ];
}