<?php
/**
 * AI_Web/web_ai_subscriber.php
 * Luồng xử lý:
 *
 *   ESP32 đo cảm biến --MQTT--> HiveMQ --subscribe--> [FILE NÀY]
 *        --phân tích rule-based (engine.php)--> quyết định
 *        --publish MQTT--> topic "aquaponic/control" --> ESP32 bật/tắt thiết bị
 *        --HTTP POST--> API/ai/nhan_ket_qua.php (lưu DB, engine="web_rule_v1")
 * CÁCH CHẠY (phải chạy song song, không tắt, trong lúc demo/thu thập số liệu):
 *   Windows (XAMPP)  : mở CMD riêng, chạy  php AI_Web\web_ai_subscriber.php
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
// false = chỉ phân tích + ghi log/DB, không đụng thiết bị (bật lại nếu muốn tách biệt an toàn với Edge AI khi demo)
const CHO_PHEP_DIEU_KHIEN_THAT = true;

// ── Lớp 2: Python case search (xem AI_Web/python/case_service.py) ──
// Chỉ được gọi khi có từ CASE_TRIGGER_MIN_SENSORS cảm biến trở lên đồng thời bất thường
// (tình huống KẾT HỢP mà luật đơn lẻ của engine.php không hiểu được, vd nóng + ẩm).
// Service Python chưa chạy / lỗi / quá chậm -> tự bỏ qua, hệ thống vẫn chạy bằng luật như cũ.
const BAT_LOP_CASE_SEARCH      = true;
const CASE_SERVICE_URL         = 'http://127.0.0.1:5001/analyze';
const CASE_TRIGGER_MIN_SENSORS = 2;
const CASE_TIMEOUT_MS          = 1500;

$settings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setUseTls(true)
    ->setTlsSelfSignedAllowed(true)
    ->setKeepAliveInterval(60);

$client = new MqttClient($host, $port, $clientId);

try {
    $client->connect($settings, true);
    echo "[" . date('H:i:s') . "] ✅ AI Web đã kết nối HiveMQ!\n";
} catch (Exception $e) {
    echo "❌ Lỗi kết nối: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Publish lệnh điều khiển xuống ESP32 — dùng ĐÚNG topic/format mà dashboard
 * (Frontend/js/mqtt.js -> mqttPublishControl) đang dùng, nên ESP32 không cần
 * sửa firmware gì thêm, vẫn nhận lệnh y như khi người dùng bấm nút trên web.
 */
function publishDieuKhien(MqttClient $client, string $device, string $command): void
{
    $payload = json_encode([$device => ($command === 'ON' ? 1 : 0)]);
    $client->publish('aquaponic/control', $payload, 0);
    echo "[" . date('H:i:s') . "] ⚡ AI Web đã publish điều khiển: {$payload}\n";
}

/**
 * Publish kết quả phân tích lên MQTT topic "aquaponic/ai/result/<device_id>"
 * — ĐÚNG topic mà trình duyệt (Frontend/js/mqtt.js) đang subscribe để cập
 * nhật LIVE khối tóm tắt AI + bảng "Lịch sử phân tích" trên Dashboard (xem
 * hàm renderAIInsight()). Đây chính là phần còn thiếu khiến Dashboard trước
 * đây không tự nhảy realtime với AI Web — trước giờ chỉ có AI Edge (ESP32)
 * publish lên topic này, còn AI Web chỉ âm thầm lưu DB qua HTTP nên trình
 * duyệt không hề hay biết có kết quả mới cho tới khi F5 lại trang.
 */
function publishKetQuaAILenMQTT(MqttClient $client, array $payload): void
{
    $topic = 'aquaponic/ai/result/' . DEVICE_ID;
    $client->publish($topic, json_encode($payload, JSON_UNESCAPED_UNICODE), 0);
}

/**
 * Xác định khung giờ Ban ngày/Ban đêm theo giờ hệ thống hiện tại — để khớp
 * với AI_TIME_WINDOW_LABELS ở Frontend/js/mqtt.js (chỉ hiểu 'day'/'night',
 * trước đây gửi 'realtime' nên frontend không map được, hiện "--").
 */
function xacDinhKhungGio(): string
{
    $gio = (int) date('H');
    return ($gio >= 6 && $gio < 18) ? 'day' : 'night';
}

/**
 * Gửi kết quả phân tích về API/ai/nhan_ket_qua.php để lưu DB — dùng CHUNG
 * bảng ai_ket_qua/ai_canhbao với AI Edge, chỉ khác giá trị "engine", nên
 * dashboard/trang lịch sử có thể lọc & so sánh 2 nguồn cạnh nhau.
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
 * Gọi service Python tìm tình huống tương tự. Trả về mảng kết quả hoặc null
 * nếu service không chạy / lỗi / quá hạn — KHÔNG bao giờ ném lỗi, để lớp 1
 * (luật) vẫn chạy bình thường khi lớp 2 có vấn đề.
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
        echo "[" . date('H:i:s') . "] ⚠️ Case search không phản hồi ({$err}) — dùng kết quả luật.\n";
        return null;
    }
    $json = json_decode($res, true);
    return (is_array($json) && ($json['status'] ?? '') === 'success') ? $json : null;
}

$client->subscribe('aquaponic/sensor/data', function ($topic, $message) use ($client) {
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

    // ── Lớp 1: luật đơn lẻ (engine.php) — nhanh, tất định ──
    $ketQua = phanTichWebAI($sv);
    $processingMs = round((microtime(true) - $t0) * 1000, 2);

    // ── Lớp 2: tình huống KẾT HỢP nhiều cảm biến -> tìm case tương tự (Python/KNN) ──
    $caseInfo = null;
    $caseMs   = null;
    if (BAT_LOP_CASE_SEARCH && count($ketQua['abnormal_sensors']) >= CASE_TRIGGER_MIN_SENSORS) {
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
    $lenh           = []; // device => 'ON'|'OFF' — tất cả lệnh sẽ publish xuống ESP32
    $caseGhiChu     = '';
    if ($actionTaken) {
        $lenh[$actionTaken['device']] = $actionTaken['command'];
    }

    if ($caseInfo && in_array($caseInfo['mode'], ['auto', 'advise'], true)) {
        $engineName = 'web_case_v1';
        $summary    = 'Tình huống kết hợp: ' . $caseInfo['label'] . ' | Số liệu: ' . $ketQua['summary'];
        if ($caseInfo['severity'] === 'danger') $severity = 'danger';

        $caseGhiChu = sprintf(
            ' [Case search: tin cậy %d%%, %s, %.1fms]',
            round($caseInfo['confidence'] * 100),
            $caseInfo['mode'] === 'auto' ? 'tự động' : 'chỉ cảnh báo',
            $caseInfo['latency_ms']
        );

        if ($caseInfo['mode'] === 'auto') {
            // Rất giống 1 case đã biết -> được phép tự điều khiển, thay thế lệnh của lớp 1
            // (lệnh của case là tập cha: đã gồm cả luật đơn lẻ + luật kết hợp + an toàn bơm)
            $lenh = [];
            foreach (['quatgio', 'denled', 'maybom'] as $dev) {
                $v = $caseInfo['commands'][$dev] ?? -1;
                if ($v === 1 || $v === 0) $lenh[$dev] = ($v === 1 ? 'ON' : 'OFF');
            }
            $actionTaken = null;
            if ($lenh) {
                // DB (ai_ket_qua) chỉ lưu 1 action/dòng: ghi lệnh đầu tiên, các lệnh còn lại nằm trong summary
                $dev0 = array_key_first($lenh);
                $actionTaken = [
                    'device'  => $dev0,
                    'command' => $lenh[$dev0],
                    'reason'  => mb_substr($caseInfo['label'], 0, 120),
                ];
                if (count($lenh) > 1) {
                    $ds = [];
                    foreach ($lenh as $d => $c) $ds[] = "{$d}={$c}";
                    $caseGhiChu .= ' [Đã gửi lệnh: ' . implode(', ', $ds) . ']';
                }
            }
            $recommendation = ['action' => 'AUTO_CONTROL', 'message' => mb_substr($caseInfo['label'], 0, 200)];
        } else {
            // Giống tương đối -> CHỈ cảnh báo. Lệnh của lớp 1 (luật đơn lẻ) vẫn giữ nguyên.
            $recommendation = [
                'action'  => 'ADVISE_CASE',
                'message' => 'Gợi ý (chưa tự thực hiện): ' . mb_substr($caseInfo['label'], 0, 180),
            ];
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
        . ($caseMs !== null ? ", case search: {$caseMs}ms" . ($caseInfo ? " (mode={$caseInfo['mode']}, tin cậy " . round($caseInfo['confidence'] * 100) . "%)" : " (lỗi)") : "")
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
            'engine'       => $engineName, // web_rule_v1 (luật) hoặc web_case_v1 (có case search) — khác "edge_threshold_v1"
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