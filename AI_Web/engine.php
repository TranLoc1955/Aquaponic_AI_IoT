<?php
/**
 * AI_Web/engine.php — ĐỒNG BỘ 100% LOGIC VỚI IOT ESP32 VÀ MAIN.PY
 * ------------------------------------------------------------------------------------------
 * Chu trình phân tích thống nhất:
 *   1. Kiểm tra phạm vi vật lý hợp lệ (checkPhysicalRange): bắt lỗi cảm biến phần cứng (sensor_fault, danger).
 *   2. Kiểm tra ngưỡng vận hành cây trồng (Agronomic Breaks): phân loại Thấp/Bình thường/Cao.
 *      - Lệch ngưỡng: báo bất thường môi trường (environment, warning/danger), tóm tắt rõ cảm biến,
 *        tự động kích hoạt hành động thiết bị (Quạt/Bơm/Đèn) và khuyến nghị xử lý.
 *   3. Kiểm tra bổ trợ thống kê 3-Sigma & Delta (theo Day/Night).
 *   4. Khi tất cả chỉ số tối ưu: báo bình thường (normal, none), không phát sinh hành động thừa.
 */

const WEB_AI_SENSORS = ['nhiet_do', 'do_am', 'anh_sang', 'muc_nuoc'];

/** Đọc thresholds.json (cache static). */
function layNguongWebAI(): array
{
    static $cache = null;
    if ($cache === null) {
        $path  = __DIR__ . '/thresholds.json';
        $raw   = @file_get_contents($path);
        $cache = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($cache) || !isset($cache['physical_ranges'], $cache['sensors'], $cache['phan_tich_mac_dinh'])) {
            throw new RuntimeException("Không đọc được file ngưỡng hợp lệ: {$path}");
        }
    }
    return $cache;
}

/**
 * Kiểm tra phạm vi vật lý hợp lý của cảm biến (khớp checkPhysicalRange() trong ESP32 & main.py)
 */
function kiemTraPhamViVatLy(float $t, float $h, float $water, float $light, array $ranges): bool
{
    if ($t < $ranges['nhiet_do']['min'] || $t > $ranges['nhiet_do']['max']) return false;
    if ($h < $ranges['do_am']['min'] || $h > $ranges['do_am']['max']) return false;
    if ($light < $ranges['anh_sang']['min'] || $light > $ranges['anh_sang']['max']) return false;
    // Bỏ qua min của mực nước để không xung đột với ngưỡng bơm cạn (y như ESP32)
    if ($water > $ranges['muc_nuoc']['max']) return false;
    // Cảm biến nhiệt độ, độ ẩm phải > 0
    if ($t <= 0 || $h <= 0) return false;

    return true;
}

/**
 * Phân loại mức giá trị cảm biến: low | mid | high | fault
 */
function xacDinhMuc(array $sensorCfg, float $val): string
{
    if ($val <= $sensorCfg['fault_at_or_below']) return 'fault';
    $idx = 0;
    foreach ($sensorCfg['breaks'] as $b) {
        if ($val >= $b) $idx++;
    }
    return ['low', 'mid', 'high'][$idx] ?? 'mid';
}

/**
 * Tính toán Thâm hụt áp suất hơi nước VPD (Tetens Equation)
 */
function tinhVPD(float $t, float $h): float
{
    $svp = 0.61078 * exp((17.27 * $t) / ($t + 237.3));
    $avp = $svp * ($h / 100.0);
    $vpd = $svp - $avp;
    return max(0.0, $vpd);
}

/**
 * Phân tích bộ giá trị cảm biến — Đồng bộ 100% với AI IoT (Edge ESP32 / main.py)
 *
 * @param array $sv ['nhiet_do'=>..,'do_am'=>..,'anh_sang'=>..,'muc_nuoc'=>..]
 */
function phanTichWebAI(array $sv): array
{
    $cfg = layNguongWebAI();
    $ranges = $cfg['physical_ranges'];
    $sensorsCfg = $cfg['sensors'];
    $defaultAnalysis = $cfg['phan_tich_mac_dinh'];

    // Theo dõi mẫu trước đó để tính delta (giống ESP32 hasPreviousSample)
    static $prevSamples = [];

    // Kiểm tra cảm biến đọc lỗi (null, rỗng hoặc âm)
    $sensorReadFault = false;
    foreach (WEB_AI_SENSORS as $k) {
        if (!isset($sv[$k]) || $sv[$k] === null || $sv[$k] === '' || (float)$sv[$k] < 0) {
            $sensorReadFault = true;
            break;
        }
    }

    $t     = (float) ($sv['nhiet_do'] ?? 0);
    $h     = (float) ($sv['do_am'] ?? 0);
    $light = (float) ($sv['anh_sang'] ?? 0);
    $water = (float) ($sv['muc_nuoc'] ?? 0);

    $rangeError    = !kiemTraPhamViVatLy($t, $h, $water, $light, $ranges);
    $physicalError = $rangeError || $sensorReadFault;

    // Bản đồ tên trường chuẩn giữa Web và IoT
    $fieldMap = [
        'nhiet_do' => 'nhietdo',
        'do_am'    => 'do_am',
        'anh_sang' => 'anhsang',
        'muc_nuoc' => 'muc_nuoc',
    ];

    // =========================================================================
    // 1. TRƯỜNG HỢP LỖI CẢM BIẾN / VƯỢT PHẠM VI VẬT LÝ (KHỚP ESP32 AI_SENSOR_ERROR)
    // =========================================================================
    if ($physicalError) {
        $abnormalSensors = [];
        if ($t < $ranges['nhiet_do']['min'] || $t > $ranges['nhiet_do']['max'] || $t <= 0) $abnormalSensors[] = 'nhietdo';
        if ($h < $ranges['do_am']['min'] || $h > $ranges['do_am']['max'] || $h <= 0) $abnormalSensors[] = 'do_am';
        if ($light < $ranges['anh_sang']['min'] || $light > $ranges['anh_sang']['max']) $abnormalSensors[] = 'anhsang';
        if ($water > $ranges['muc_nuoc']['max']) $abnormalSensors[] = 'muc_nuoc';

        $faultLevels = [];
        foreach (WEB_AI_SENSORS as $k) {
            $faultLevels[$k] = in_array($fieldMap[$k], $abnormalSensors) ? 'fault' : 'mid';
        }

        return [
            'anomaly_type'     => 'sensor_fault',
            'severity'         => 'danger',
            'confidence'       => 'certain',
            'abnormal_sensors' => $abnormalSensors,
            'abnormal_levels'  => $faultLevels,
            'levels'           => $faultLevels,
            'commands'         => [],
            'can_case_search'  => false,
            'ly_do_case_search'=> null,
            'summary'          => $sensorReadFault
                ? 'Khong doc duoc cam bien (DHT22 hoac BH1750), nghi ngo cam bien loi.'
                : 'Gia tri cam bien vuot pham vi vat ly cho phep, nghi ngo cam bien loi.',
            'action_taken'     => null,
            'recommendation'   => [
                'action'  => 'CHECK_SENSOR',
                'message' => 'Nen kiem tra day ket noi va nguon cap cua cam bien.',
            ],
        ];
    }

    // =========================================================================
    // 2. KIỂM TRA THEO NGƯỠNG VẬN HÀNH NÔNG HỌC (BREAKS — ĐỒNG BỘ MAIN.PY & ESP32)
    // =========================================================================
    $levels = [];
    $abnormalLevels = [];
    $summaryParts = [];
    $abnormalSensors = [];
    $cmds = [];
    $reasons = [];
    $severity = 'normal';

    foreach (WEB_AI_SENSORS as $k) {
        $val = (float) ($sv[$k] ?? 0);
        $sc  = $sensorsCfg[$k];
        $lv  = xacDinhMuc($sc, $val);
        $levels[$k] = $lv;

        if ($lv === 'mid') {
            continue;
        }

        $pt = $defaultAnalysis[$k][$lv] ?? null;
        $title = $pt['title'] ?? ($lv === 'low' ? 'Thấp' : 'Cao');
        $valFmt = (round($val) == $val) ? (int)$val : round($val, 1);
        $summaryParts[] = "{$sc['label']}: {$valFmt}{$sc['unit']} ({$title})";
        $abnormalLevels[$k] = $lv;
        $abnormalSensors[] = $fieldMap[$k];

        $ptSev = $pt['severity'] ?? 'warning';
        if ($ptSev === 'danger' || $severity === 'danger') {
            $severity = 'danger';
        } else {
            $severity = 'warning';
        }

        if ($pt && !empty($pt['device']) && !empty($pt['command'])) {
            $dev = $pt['device'];
            $cmds[$dev] = $pt['command'];
            $reasons[$dev] = $pt['reason'];
        }
    }

    // Xác định action_taken từ lệnh điều khiển
    $actionTaken = null;
    foreach (['quatgio', 'maybom', 'denled'] as $d) {
        if (isset($cmds[$d])) {
            $actionTaken = ['device' => $d, 'command' => $cmds[$d], 'reason' => $reasons[$d]];
            break;
        }
    }

    // =========================================================================
    // 3. NẾU CÓ BẤT THƯỜNG MÔI TRƯỜNG THEO NGƯỠNG
    // =========================================================================
    if (!empty($abnormalLevels)) {
        if (in_array('muc_nuoc', $abnormalSensors)) {
            $recoAction = 'CHECK_WATER_LEVEL';
            $recoMsg    = $reasons['maybom'] ?? 'Nên kiểm tra bồn chứa và hệ thống cấp nước.';
        } elseif (in_array('nhietdo', $abnormalSensors) || in_array('do_am', $abnormalSensors)) {
            $recoAction = 'CHECK_ENVIRONMENT';
            $recoMsg    = $reasons['quatgio'] ?? 'Nên kiểm tra môi trường khu vực trồng (quạt, mái che).';
        } elseif (in_array('anhsang', $abnormalSensors)) {
            $recoAction = 'CHECK_LIGHTING';
            $recoMsg    = $reasons['denled'] ?? 'Nên kiểm tra đèn chiếu sáng hoặc vị trí cảm biến ánh sáng.';
        } else {
            $recoAction = 'CHECK_ENVIRONMENT';
            $recoMsg    = 'Theo dõi thêm các chỉ số đang lệch ngưỡng.';
        }

        return [
            'anomaly_type'     => 'environment',
            'severity'         => $severity,
            'confidence'       => 'certain',
            'abnormal_sensors' => $abnormalSensors,
            'abnormal_levels'  => $abnormalLevels,
            'levels'           => $levels,
            'commands'         => $cmds,
            'can_case_search'  => count($abnormalSensors) >= 2,
            'ly_do_case_search'=> count($abnormalSensors) . ' cảm biến lệch ngưỡng vận hành',
            'summary'          => implode(' | ', $summaryParts),
            'action_taken'     => $actionTaken,
            'recommendation'   => [
                'action'  => $recoAction,
                'message' => $recoMsg,
            ],
        ];
    }

    // =========================================================================
    // 4. KIỂM TRA BỔ TRỢ THỐNG KÊ (3-SIGMA & DELTA NẾU CÓ)
    // =========================================================================
    $hour = (int) date('H');
    $window = ($hour >= 6 && $hour < 18) ? 'day' : 'night';
    $wThresh = $cfg['statistical_thresholds'][$window] ?? null;

    $statAbn = [];
    if ($wThresh) {
        $mapVals = [
            'nhiet_do' => ['field' => 'nhietdo',  'label' => 'nhiệt độ',  'val' => $t],
            'do_am'    => ['field' => 'do_am',    'label' => 'độ ẩm',     'val' => $h],
            'muc_nuoc' => ['field' => 'muc_nuoc', 'label' => 'mực nước',  'val' => $water],
            'anh_sang' => ['field' => 'anhsang',  'label' => 'ánh sáng',  'val' => $light],
        ];
        foreach ($mapVals as $k => $m) {
            if (!isset($wThresh[$k]) || ($wThresh[$k]['sample_count'] ?? 1) == 0) continue;
            $th = $wThresh[$k];
            $val = $m['val'];
            $lower = $th['mean'] - 3.0 * $th['std'];
            $upper = $th['mean'] + 3.0 * $th['std'];
            if ($val < $lower || $val > $upper) {
                $statAbn[] = $k;
            }
            if (isset($prevSamples[$k])) {
                $delta = $val - $prevSamples[$k];
                $dLower = $th['delta_mean'] - 3.0 * $th['delta_std'];
                $dUpper = $th['delta_mean'] + 3.0 * $th['delta_std'];
                if ($delta < $dLower || $delta > $dUpper) {
                    $statAbn[] = $k;
                }
            }
            $prevSamples[$k] = $val;
        }
    }

    if (!empty($statAbn)) {
        $statAbn = array_unique($statAbn);
        $abnLabels = [];
        $abnFields = [];
        foreach ($statAbn as $k) {
            $abnLabels[] = $sensorsCfg[$k]['label'];
            $abnFields[] = $fieldMap[$k];
        }
        return [
            'anomaly_type'     => 'environment',
            'severity'         => 'warning',
            'confidence'       => 'uncertain',
            'abnormal_sensors' => $abnFields,
            'abnormal_levels'  => $levels,
            'levels'           => $levels,
            'commands'         => [],
            'can_case_search'  => true,
            'ly_do_case_search'=> 'Lệch ngưỡng thống kê 3-sigma (' . implode(', ', $abnLabels) . ')',
            'summary'          => 'Dữ liệu dao động đáng kể so với thống kê (' . implode(', ', $abnLabels) . ').',
            'action_taken'     => null,
            'recommendation'   => [
                'action'  => 'CHECK_ENVIRONMENT',
                'message' => 'Nên theo dõi khu vực trồng, có biến động nhỏ so với dữ liệu học.',
            ],
        ];
    }

    // =========================================================================
    // 5. TRƯỜNG HỢP MỌI CHỈ SỐ BÌNH THƯỜNG (NORMAL)
    // =========================================================================
    return [
        'anomaly_type'     => 'none',
        'severity'         => 'normal',
        'confidence'       => 'certain',
        'abnormal_sensors' => [],
        'abnormal_levels'  => [],
        'levels'           => $levels,
        'commands'         => [],
        'can_case_search'  => false,
        'ly_do_case_search'=> null,
        'summary'          => 'Tất cả chỉ số đang trong ngưỡng bình thường.',
        'action_taken'     => null,
        'recommendation'   => [
            'action'  => 'NONE',
            'message' => 'Không cần can thiệp, hệ thống đang ổn định.',
        ],
    ];
}
