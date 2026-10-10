<?php
require __DIR__ . '/vendor/autoload.php';
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// ── Cấu hình HiveMQ (giống hệt mqtt_subscriber.php) ─────────
$host     = '56865ed923c247919013bdef6430f5c6.s1.eu.hivemq.cloud';
$port     = 8883;
$clientId = 'php_ai_subscriber_' . uniqid();
$username = 'esp32_user';
$password = '123456Esp32@';
// ─────────────────────────────────────────────────────────

$settings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setUseTls(true)
    ->setTlsSelfSignedAllowed(true)
    ->setKeepAliveInterval(60);

$client = new MqttClient($host, $port, $clientId);

try {
    $client->connect($settings, true);
    echo "[" . date('H:i:s') . "] ✅ AI Subscriber đã kết nối HiveMQ!\n";
} catch (Exception $e) {
    echo "❌ Lỗi kết nối: " . $e->getMessage() . "\n";
    exit(1);
}

function guiKetQuaVeAPI($message) {
    $url = 'http://localhost/Test/API/ai/nhan_ket_qua.php';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $message,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $res      = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false) {
        // Gọi API thất bại hẳn (không kết nối được, sai URL, Apache chưa bật...)
        echo "[" . date('H:i:s') . "] ❌ Lỗi gọi API nhan_ket_qua.php: {$curlErr}\n";
        return;
    }

    $json = json_decode($res, true);
    if ($json === null) {
        // Kết nối được nhưng nội dung trả về không phải JSON hợp lệ -> in ra
        // nguyên văn để dễ soi lỗi (ví dụ PHP báo Fatal error dạng HTML).
        echo "[" . date('H:i:s') . "] ⚠️ API trả về không phải JSON (HTTP {$httpCode}): "
           . substr($res, 0, 200) . "\n";
        return;
    }

    $status = $json['status'] ?? 'unknown';
    if ($status === 'error') {
        // In rõ lý do lỗi thật (VD: "Thiếu device_id.", "Lỗi khi ghi DB: ...")
        // thay vì chỉ in mỗi chữ "error" chung chung, để dễ soi tiếp.
        echo "[" . date('H:i:s') . "] 🤖 Lưu kết quả AI: error -> " . ($json['message'] ?? '(không rõ lý do)') . "\n";
    } else {
        echo "[" . date('H:i:s') . "] 🤖 Lưu kết quả AI: {$status}\n";
    }
}

$client->subscribe('aquaponic/ai/result/#', function ($topic, $message) {
    guiKetQuaVeAPI($message);
}, 0);

$client->subscribe('aquaponic/ai/alert', function ($topic, $message) {
    echo "[" . date('H:i:s') . "] 🚨 CẢNH BÁO AI: {$message}\n";
}, 0);

echo "[" . date('H:i:s') . "] 🚀 AI MQTT Subscriber đang chạy... (Ctrl+C để dừng)\n";
$client->loop(true);

?>