<?php
/**
 * AI_Web/web_ai_subscriber.php — AI Web kiểu HYBRID
 *
 *   ESP32 --MQTT--> HiveMQ --subscribe--> [FILE NÀY]
 *     Lớp 1: so ngưỡng mặc định (engine.php + thresholds.json) -> lệnh điều khiển mặc định
 *     Nếu có cảm biến lệch ngưỡng mà ngưỡng mặc định không phân tích được
 *       (nhiều cảm biến cùng lệch, hoặc mức không có phân tích) ->
 *     Lớp 2: POST 127.0.0.1:5001/analyze (python/case_service.py) -> tra LUẬT học từ
 *            bộ dữ liệu của hệ thống -> trả PHÂN TÍCH để hiển thị.
 *     --publish MQTT--> "aquaponic/control" (lệnh lớp 1)
 *     --publish MQTT--> "aquaponic/ai/result/<id>" (Dashboard cập nhật realtime)
 *     --HTTP POST--> API/ai/nhan_ket_qua.php (lưu DB; engine = web_rule_v1 | web_hybrid_v1)
 *
 * CÁCH CHẠY (chạy song song, không tắt):
 *   1) cd AI_Web/python && python case_service.py
 *   2) php AI_Web\web_ai_subscriber.php
 * ------------------------------------------------------------
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/engine.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// ── Cấu hình HiveMQ — giống hệt mqtt_subscriber.php / ai_mqtt_subscriber.php ──
$host     = '56865ed923c247919013bdef6430f5c6.s1.eu.hivemq.cloud';
$port     = 8883;
$clientId = 'php_web_ai_' . uniqid();
$username = 'esp32_user';
$password = '123456Esp32@';

// ID thiết bị mặc định — giống quy ước có sẵn trong mqtt_subscriber.php (idthietbi=18).
// Nếu hệ thống sau này có nhiều thiết bị, đổi chỗ này để lấy device_id theo dữ liệu MQTT thực tế.
const DEVICE_ID = 18;

// URL gọi tới API lưu kết quả AI — đổi nếu domain/host khác localhost.
const API_NHAN_KET_QUA = 'http://localhost/Test/API/ai/nhan_ket_qua.php';

// true  = AI Web được publish lệnh MQTT điều khiển thiết bị THẬT (đúng yêu cầu ban đầu)
// false = chỉ phân tích + ghi log/DB, không đụng thiết bị (an toàn đối chứng với Edge AI)
const CHO_PHEP_DIEU_KHIEN_THAT = true;

// ── Lớp 2: Python (tra luật học từ dữ liệu — xem python/case_service.py) ──
// Điều kiện gọi lớp 2 nằm trong thresholds.json ("case_trigger" + "phan_tich_mac_dinh").
// Service chưa chạy / lỗi / quá chậm -> tự bỏ qua, hệ thống vẫn chạy bằng lớp 1.
const BAT_LOP_CASE_SEARCH = true;
const CASE_SERVICE_URL    = 'http://127.0.0.1:5001/analyze';
const CASE_TIMEOUT_MS     = 1500;

// Mặc định false: lớp 2 CHỈ hiển thị phân tích + gợi ý thiết bị, KHÔNG tự điều khiển.
// true = lệnh khuyến nghị của lớp 2 sẽ thay lệnh mặc định của lớp 1 (chỉ bật khi đã kiểm chứng).
const CHO_PHEP_LOP2_DIEU_KHIEN = true;

$settings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setUseTls(true)
    ->setTlsSelfSignedAllowed(true)
    ->setKeepAliveInterval(60);

$client = new MqttClient($host, $port, $clientId);

$maxRetries = 5;
$retryDelay = 3;
$connected  = false;

for ($lanThu = 1; $lanThu <= $maxRetries; $lanThu++) {
    try {
        echo "[" . date('H:i:s') . "] 🔄 Đang kết nối HiveMQ Cloud (lần {$lanThu}/{$maxRetries})...\n";
        $client->connect($settings, true);
        echo "[" . date('H:i:s') . "] ✅ AI Web đã kết nối HiveMQ thành công!\n";
        $connected = true;
        break;
    } catch (Exception $e) {
        echo "[" . date('H:i:s') . "] ⚠️ Lỗi kết nối (lần {$lanThu}/{$maxRetries}): " . $e->getMessage() . "\n";
        if ($lanThu < $maxRetries) {
            echo "[" . date('H:i:s') . "] ⏳ Thử lại sau {$retryDelay}s...\n";
            sleep($retryDelay);
        }
    }
}

if (!$connected) {
    echo "❌ Không thể kết nối HiveMQ sau {$maxRetries} lần thử.\n";
    echo "👉 Gợi ý: Hãy kiểm tra mạng Internet hoặc DNS máy tính.\n";
    exit(1);
}

/**
 * Publish lệnh điều khiển xuống ESP32 — dùng ĐÚNG topic/format mà dashboard
 * (Frontend/js/mqtt.js -> mqttPublishControl) đang dùng.
 */
function publishDieuKhien(MqttClient $client, string $device, string $command): void
{
    $payload = json_encode([$device => ($command === 'ON' ? 1 : 0)]);
    $client->publish('aquaponic/control', $payload, 0);
    echo "[" . date('H:i:s') . "] ⚡ AI Web đã publish điều khiển: {$payload}\n";
}

/**
 * Publish kết quả phân tích lên MQTT topic RIÊNG "aquaponic/ai/web/result/<device_id>"
 * để KHÔNG bị ghi đè hay lẫn lộn với kết quả của AI IoT (Edge ESP32) trên topic "aquaponic/ai/result/<device_id>".
 */
function publishKetQuaAILenMQTT(MqttClient $client, array $payload): void
{
    $topic = 'aquaponic/ai/web/result/' . DEVICE_ID;
    $client->publish($topic, json_encode($payload, JSON_UNESCAPED_UNICODE), 0);
}

/**
 * Xác định khung giờ Ban ngày/Ban đêm theo giờ hệ thống hiện tại.
 */
function xacDinhKhungGio(): string
{
    $gio = (int) date('H');
    return ($gio >= 6 && $gio < 18) ? 'day' : 'night';
}

/**
 * Gửi kết quả phân tích về API/ai/nhan_ket_qua.php để lưu DB.
 */
function guiKetQuaAI(array $payload): void
{
    $ch = curl_init(API_NHAN_KET_QUA);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        echo "[" . date('H:i:s') . "] ❌ Lỗi gọi nhan_ket_qua.php: {$err}\n";
        return;
    }
    $json = json_decode($res, true);
    if ($json === null) {
        echo "[" . date('H:i:s') . "] ⚠️ API trả về không phải JSON: " . substr($res, 0, 200) . "\n";
        return;
    }
    $status = $json['status'] ?? 'unknown';
    echo "[" . date('H:i:s') . "] 💾 Lưu kết quả AI Web: {$status}"
        . ($status === 'error' ? ' -> ' . ($json['message'] ?? '') : '') . "\n";
}

/**
 * Gọi service Python tìm tình huống tương tự.
 */
function goiCaseSearch(array $sv): ?array
{
    $ch = curl_init(CASE_SERVICE_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST              => true,
        CURLOPT_POSTFIELDS        => json_encode($sv),
        CURLOPT_HTTPHEADER        => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER    => true,
        CURLOPT_TIMEOUT_MS        => CASE_TIMEOUT_MS,
        CURLOPT_CONNECTTIMEOUT_MS => 500,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        // Rút gọn thông báo nếu không mở service Lớp 2
        static $lastCaseErrNotice = 0;
        if (time() - $lastCaseErrNotice >= 30) {
            echo "[" . date('H:i:s') . "] ℹ️ Lớp 2 Case Search chưa mở (127.0.0.1:5001) -> chạy chuẩn Lớp 1 (Rule-based).\n";
            $lastCaseErrNotice = time();
        }
        return null;
    }
    $json = json_decode($res, true);
    return (is_array($json) && ($json['status'] ?? '') === 'success') ? $json : null;
}

// Trạng thái Bật/Tắt từ Dashboard (mặc định bật khi khởi chạy script)
$aiWebEnabled = true;

// 🎛️ Lắng nghe lệnh Bật/Tắt từ giao diện Dashboard (topic: aquaponic/ai/web/enable)
$client->subscribe('aquaponic/ai/web/enable', function ($topic, $message) use (&$aiWebEnabled) {
    $d = json_decode($message, true);
    if (isset($d['enabled'])) {
        $aiWebEnabled = (bool)$d['enabled'];
        $trangThai = $aiWebEnabled ? 'ĐƯỢC BẬT (Đang phân tích & gửi đối chứng)' : 'ĐÃ TẮT (Tạm dừng, chỉ chạy AI IoT)';
        echo "[" . date('H:i:s') . "] 🎛️ Công tắc từ Web: AI Web -> {$trangThai}\n";
    }
}, 0);

$client->subscribe('aquaponic/sensor/data', function ($topic, $message) use ($client, &$aiWebEnabled) {
    if (!$aiWebEnabled) {
        // AI Web đang tạm dừng theo lệnh từ Dashboard -> thông báo ra console mỗi 15s để người dùng biết
        static $lastPauseNotice = 0;
        if (time() - $lastPauseNotice >= 15) {
            echo "[" . date('H:i:s') . "] ⏸️ ĐANG TẠM DỪNG: Công tắc AI Web trên Dashboard đang TẮT. Hãy gạt sang BẬT trên giao diện Web Dashboard để kích hoạt phân tích!\n";
            $lastPauseNotice = time();
        }
        return;
    }

    // t0: thời điểm AI Web NHẬN được dữ liệu (đã đi 1 vòng ESP32 -> broker -> đây)
    $t0 = microtime(true);

    $data = json_decode($message, true);
    if (!$data) return;

    $sv = [
        'nhiet_do' => $data['nhiet_do'] ?? null,
        'do_am'    => $data['do_am']    ?? null,
        'anh_sang' => $data['anh_sang'] ?? null,
        'muc_nuoc' => $data['muc_nuoc'] ?? null,
    ];

    // ── Lớp 1: so ngưỡng mặc định (engine.php) ──
    try {
        $ketQua = phanTichWebAI($sv);
    } catch (Throwable $e) {
        echo "[" . date('H:i:s') . "] ❌ Lỗi lớp 1: " . $e->getMessage() . "\n";
        return; // không để 1 gói lỗi làm chết daemon
    }
    $processingMs = round((microtime(true) - $t0) * 1000, 2);

    // ── Lớp 2: chỉ khi lớp 1 báo "ngoài phân tích mặc định" ──
    $caseInfo = null;
    $caseMs   = null;
    if (BAT_LOP_CASE_SEARCH && $ketQua['can_case_search']) {
        $tc = microtime(true);
        $caseInfo = goiCaseSearch($sv);
        $caseMs = round((microtime(true) - $tc) * 1000, 2);
    }

    // Mặc định: giữ nguyên quyết định của lớp 1
    $engineName     = 'web_rule_v1';
    $severity       = $ketQua['severity'];
    $summary        = $ketQua['summary'];
    $recommendation = $ketQua['recommendation'];
    $actionTaken    = $ketQua['action_taken'];
    $lenh           = $ketQua['commands']; // device => 'ON'|'OFF' — lệnh sẽ publish xuống ESP32
    $caseGhiChu     = '';

    if ($caseInfo) {
        $engineName = 'web_hybrid_v1';
        $summary    = 'Phân tích tình huống: ' . $caseInfo['label'] . ' | Số liệu: ' . $ketQua['summary'];

        $doTinCay = ['cao' => 'cao', 'trung_binh' => 'trung bình (sát ngưỡng)', 'thap' => 'thấp (ít dữ liệu mẫu)'];
        $caseGhiChu = sprintf(
            ' [Luật học từ dữ liệu: độ tin cậy %s, %d mẫu, %.1fms]',
            $doTinCay[$caseInfo['confidence']['muc']] ?? '?',
            $caseInfo['confidence']['ho_tro'],
            $caseInfo['latency_ms']
        );

        // Gợi ý thiết bị của lớp 2 so với lệnh mặc định của lớp 1
        $khuyenNghi = [];
        $xungDot    = [];
        foreach (['quatgio' => 'Quạt', 'denled' => 'Đèn', 'maybom' => 'Bơm'] as $dev => $ten) {
            $v = $caseInfo['commands'][$dev] ?? null;
            if ($v !== 0 && $v !== 1) continue;
            $khuyenNghi[] = $ten . ' ' . ($v === 1 ? 'BẬT' : 'TẮT');
            $l1 = $lenh[$dev] ?? null;
            if ($l1 !== null && (($l1 === 'ON') !== ($v === 1))) {
                $xungDot[] = "{$ten}: mặc định " . ($l1 === 'ON' ? 'BẬT' : 'TẮT') . " nhưng tình huống này khuyến nghị " . ($v === 1 ? 'BẬT' : 'TẮT');
            }
        }
        $caseGhiChu .= ' [Khuyến nghị thiết bị: ' . implode(', ', $khuyenNghi) . ']';
        if ($xungDot) $caseGhiChu .= ' [⚠️ Khác lệnh mặc định: ' . implode('; ', $xungDot) . ']';

        $recommendation = [
            'action'  => $ketQua['anomaly_type'] === 'sensor_fault' ? 'CHECK_SENSOR' : 'CHECK_ENVIRONMENT',
            'message' => mb_substr('Tình huống: ' . $caseInfo['label'], 0, 200),
        ];

        if (CHO_PHEP_LOP2_DIEU_KHIEN) {
            $lenh = [];
            foreach (['quatgio', 'denled', 'maybom'] as $dev) {
                $v = $caseInfo['commands'][$dev] ?? -1;
                if ($v === 1 || $v === 0) $lenh[$dev] = ($v === 1 ? 'ON' : 'OFF');
            }
            $actionTaken = null;
            if ($lenh) {
                $dev0 = array_key_first($lenh);
                $actionTaken = ['device' => $dev0, 'command' => $lenh[$dev0], 'reason' => mb_substr($caseInfo['label'], 0, 120)];
            }
        }
    }

    $dieuKhienMs = null;
    if ($lenh && CHO_PHEP_DIEU_KHIEN_THAT) {
        foreach ($lenh as $dev => $cmd) {
            publishDieuKhien($client, $dev, $cmd);
        }
        $dieuKhienMs = round((microtime(true) - $t0) * 1000, 2); // tính từ t0 tới lúc publish lệnh xong
    }

    $totalMs = round((microtime(true) - $t0) * 1000, 2);
    echo "[" . date('H:i:s') . "] 🧠 [{$engineName}] luật: {$processingMs}ms"
        . ($caseMs !== null ? ", case search: {$caseMs}ms" . ($caseInfo ? " (tin cậy {$caseInfo['confidence']['muc']}, {$caseInfo['confidence']['ho_tro']} mẫu)" : " (lỗi)") : "")
        . ($dieuKhienMs !== null ? ", gửi lệnh xong lúc +{$dieuKhienMs}ms" : "")
        . " (tổng {$totalMs}ms)\n";

    // Nhúng số liệu độ trễ vào summary (không cần sửa schema DB) để xem trực
    // tiếp trên Dashboard / khi trích xuất báo cáo, khỏi phải soi log riêng.
    $summaryVoiTre = $summary . $caseGhiChu . sprintf(
        ' [Web AI xử lý: %.1fms%s]',
        $processingMs,
        $dieuKhienMs !== null ? sprintf(', gửi lệnh: %.1fms', $dieuKhienMs) : ''
    );

    $payload = [
        'device_id' => (string) DEVICE_ID,
        'timestamp' => date('c'),
        'mode'      => 'AUTO',
        'sensor_values' => [
            'nhietdo'  => $sv['nhiet_do'],
            'do_am'    => $sv['do_am'],
            'muc_nuoc' => $sv['muc_nuoc'],
            'anhsang'  => $sv['anh_sang'],
        ],
        'ai_result' => [
            'engine'       => $engineName, // web_rule_v1 (chỉ ngưỡng) hoặc web_hybrid_v1 (có lớp 2) — khác "edge_threshold_v1"
            'time_window'  => xacDinhKhungGio(),
            'anomaly_type' => $ketQua['anomaly_type'],
            'severity'     => $severity,
            'confidence'   => $ketQua['confidence'],
        ],
        'analysis' => [
            'summary'          => $summaryVoiTre,
            'abnormal_sensors' => $ketQua['abnormal_sensors'],
        ],
        'recommendation' => $recommendation,
    ];

    if ($actionTaken && CHO_PHEP_DIEU_KHIEN_THAT) {
        $payload['action_taken'] = $actionTaken;
    }

    // Publish lên MQTT TRƯỚC (để Dashboard nhảy realtime ngay lập tức),
    // rồi mới lưu DB qua HTTP (lưu trữ lâu dài, không cần user chờ bước này).
    publishKetQuaAILenMQTT($client, $payload);
    guiKetQuaAI($payload);
}, 0);

echo "[" . date('H:i:s') . "] 🚀 AI Web Subscriber đang chạy... (Ctrl+C để dừng)\n";
$client->loop(true);