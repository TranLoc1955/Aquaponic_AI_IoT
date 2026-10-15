<?php
/**
 * AI_Web/engine.php — LỚP 1 của AI Web (ngưỡng mặc định trong code).
 * ------------------------------------------------------------
 * Nhiệm vụ:
 *   1. Quy từng giá trị cảm biến về mức: low | mid | high | fault (theo thresholds.json).
 *   2. Với mức lệch khỏi 'mid', tra phan_tich_mac_dinh (thresholds.json) để lấy
 *      phân tích + hành động mặc định.
 *   3. Quyết định có cần chuyển sang LỚP 2 (Python, tra luật học từ dữ liệu) không:
 *        - từ min_abnormal_sensors cảm biến trở lên cùng lệch khỏi 'mid', HOẶC
 *        - có cảm biến lệch mà ngưỡng mặc định KHÔNG có phân tích cho mức đó (null).
 *
 * NGƯỠNG chỉ nằm ở AI_Web/thresholds.json (dùng chung với python/).
 * Severity: 'normal' | 'warning' | 'danger' (khớp Frontend/js/mqtt.js).
 */

const WEB_AI_SENSORS = ['nhiet_do', 'do_am', 'anh_sang', 'muc_nuoc'];

/** Đọc thresholds.json (cache static, chỉ đọc đĩa 1 lần). */
function layNguongWebAI(): array
{
    static $cache = null;
    if ($cache === null) {
        $path  = __DIR__ . '/thresholds.json';
        $raw   = @file_get_contents($path);
        $cache = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($cache) || !isset($cache['sensors'], $cache['phan_tich_mac_dinh'])) {
            throw new RuntimeException("Không đọc được file ngưỡng hợp lệ: {$path}");
        }
    }
    return $cache;
}

/** Quy 1 giá trị về mức: 'fault' | 'low' | 'mid' | 'high' (giống python/core.py). */
function xacDinhMuc(array $sensorCfg, float $val): string
{
    if ($val <= $sensorCfg['fault_at_or_below']) return 'fault';
    $idx = 0;
    foreach ($sensorCfg['breaks'] as $b) {
        if ($val >= $b) $idx++;
    }
    return ['low', 'mid', 'high'][$idx];
}

/**
 * Phân tích 1 bộ giá trị cảm biến.
 * Trả về cấu trúc khớp JSON contract của API/ai/nhan_ket_qua.php, cộng thêm:
 *   'levels'        => [sensor => mức]
 *   'abnormal_levels' => chỉ các cảm biến lệch khỏi 'mid'
 *   'commands'      => [device => 'ON'|'OFF'] lệnh mặc định của lớp 1
 *   'can_case_search' + 'ly_do_case_search' => có cần gọi lớp 2 không và vì sao
 *
 * @param array $sv ['nhiet_do'=>..,'do_am'=>..,'anh_sang'=>..,'muc_nuoc'=>..]
 */
function phanTichWebAI(array $sv): array
{
    $cfg = layNguongWebAI();

    $levels = [];            // mọi cảm biến có dữ liệu
    $matches = [];           // cảm biến lệch khỏi 'mid'
    $khongCoPhanTich = [];   // cảm biến lệch nhưng ngưỡng mặc định không có phân tích

    foreach (WEB_AI_SENSORS as $key) {
        if (!isset($sv[$key]) || $sv[$key] === null || $sv[$key] === '') continue;
        $val = (float) $sv[$key];
        $muc = xacDinhMuc($cfg['sensors'][$key], $val);
        $levels[$key] = $muc;
        if ($muc === 'mid') continue;

        $pt = $cfg['phan_tich_mac_dinh'][$key][$muc] ?? null;
        $matches[$key] = [
            'level' => $muc, 'value' => $val, 'pt' => $pt,
            'label' => $cfg['sensors'][$key]['label'], 'unit' => $cfg['sensors'][$key]['unit'],
        ];
        if ($pt === null) $khongCoPhanTich[] = $key;
    }

    // ── Không có gì bất thường ──
    if (empty($matches)) {
        return [
            'anomaly_type' => 'none', 'severity' => 'normal', 'confidence' => 'certain',
            'abnormal_sensors' => [], 'abnormal_levels' => [], 'levels' => $levels,
            'commands' => [], 'can_case_search' => false, 'ly_do_case_search' => null,
            'summary' => 'Tất cả chỉ số đang trong ngưỡng bình thường.',
            'action_taken' => null,
            'recommendation' => ['action' => 'NONE', 'message' => 'Không cần can thiệp, hệ thống đang ổn định.'],
        ];
    }

    // ── Tóm tắt + lệnh mặc định ──
    $summaryParts = [];
    $levelsAbn    = [];
    $commands     = [];       // device => ['command'=>, 'reason'=>, 'prio'=>]
    $severity     = 'warning';
    $coLoi        = false;
    foreach ($matches as $key => $m) {
        $levelsAbn[$key] = $m['level'];
        $tenMuc = $m['pt']['title'] ?? ['low' => 'Thấp', 'high' => 'Cao'][$m['level']] ?? $m['level'];
        $summaryParts[] = sprintf('%s: %s%s (%s)', $m['label'], rtrim(rtrim(number_format($m['value'], 1, '.', ''), '0'), '.'), $m['unit'], $tenMuc);
        if ($m['level'] === 'fault') $coLoi = true;
        if ($m['pt']) {
            if ($m['pt']['severity'] === 'danger') $severity = 'danger';
            if ($m['pt']['device'] && $m['pt']['command']) {
                // 'fault' ưu tiên cao nhất (an toàn), rồi theo thứ tự cảm biến
                $prio = $m['level'] === 'fault' ? 2 : 1;
                $dev  = $m['pt']['device'];
                if (!isset($commands[$dev]) || $prio > $commands[$dev]['prio']) {
                    $commands[$dev] = ['command' => $m['pt']['command'], 'reason' => $m['pt']['reason'], 'prio' => $prio];
                }
            }
        }
    }

    // action_taken chính thức (DB chỉ lưu 1 dòng): ưu tiên lệnh an toàn/mực nước trước
    $actionTaken = null;
    foreach (['maybom', 'quatgio', 'denled'] as $dev) {
        if (isset($commands[$dev])) {
            $actionTaken = ['device' => $dev, 'command' => $commands[$dev]['command'], 'reason' => $commands[$dev]['reason']];
            break;
        }
    }
    $lenh = [];
    foreach ($commands as $dev => $c) $lenh[$dev] = $c['command'];

    // ── Có cần lớp 2 không? ──
    $nAbn = count($matches);
    $minAbn = (int) ($cfg['case_trigger']['min_abnormal_sensors'] ?? 2);
    $canCase = false; $lyDo = null;
    if ($nAbn >= $minAbn) {
        $canCase = true;
        $lyDo = "{$nAbn} cảm biến cùng lệch ngưỡng";
    } elseif (!empty($khongCoPhanTich)) {
        $canCase = true;
        $lyDo = 'ngưỡng mặc định chưa có phân tích cho: ' . implode(', ', $khongCoPhanTich);
    }

    return [
        'anomaly_type'     => $coLoi ? 'sensor_fault' : 'environment',
        'severity'         => $severity,
        'confidence'       => 'certain',
        'abnormal_sensors' => array_keys($matches),
        'abnormal_levels'  => $levelsAbn,
        'levels'           => $levels,
        'commands'         => $lenh,
        'can_case_search'  => $canCase,
        'ly_do_case_search'=> $lyDo,
        'summary'          => implode(' | ', $summaryParts),
        'action_taken'     => $actionTaken,
        'recommendation'   => [
            'action'  => $actionTaken ? 'AUTO_CONTROL' : ($coLoi ? 'CHECK_SENSOR' : 'CHECK_ENVIRONMENT'),
            'message' => $actionTaken['reason'] ?? 'Theo dõi thêm các chỉ số đang lệch ngưỡng.',
        ],
    ];
}
