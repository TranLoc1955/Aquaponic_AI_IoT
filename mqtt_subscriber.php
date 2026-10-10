<?php
require __DIR__ . '/vendor/autoload.php';
include_once 'C:/xampp/htdocs/Test/config/connect.php';
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// ── Cấu hình HiveMQ ──────────────────────────────────────
$host     = '56865ed923c247919013bdef6430f5c6.s1.eu.hivemq.cloud';
$port     = 8883;
$clientId = 'php_subscriber_' . uniqid();
$username = 'esp32_user';   
$password = '123456Esp32@';   
// ─────────────────────────────────────────────────────────

$db = (new Database())->connect();

$settings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setUseTls(true)
    ->setTlsSelfSignedAllowed(true)
    ->setKeepAliveInterval(60);

$client = new MqttClient($host, $port, $clientId);

try {
    $client->connect($settings, true);
    echo "[" . date('H:i:s') . "] ✅ Đã kết nối HiveMQ thành công!\n";
} catch (Exception $e) {
    echo "❌ Lỗi kết nối: " . $e->getMessage() . "\n";
    exit(1);
}

// ── Subscribe sensor data ─────────────────────────────────
$client->subscribe('aquaponic/sensor/data', function($topic, $message) {
    $data = json_decode($message, true);
    if (!$data) return;

    // Chuẩn hóa tên field
    $normalized = [
    'idthietbi' => 18,
    'nhiet_do'   => $data['nhiet_do'] ?? null,
    'do_am'     => $data['do_am']    ?? null,
    'muc_nuoc'  => $data['muc_nuoc'] ?? null,
    'anh_sang'   => $data['anh_sang']  ?? null,
];
$normalized = array_filter($normalized, fn($v) => $v !== null);
    // Xóa field null
    $normalized = array_filter($normalized, fn($v) => $v !== null);

    $url     = 'http://localhost/Test/API/data_sensor/input.php';
    $options = ['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'content' => json_encode($normalized),
        'timeout' => 5
    ]];
   $res = @file_get_contents($url, false, stream_context_create($options));

echo "\n=========================\n";
echo "Response API:\n";

if ($res === false) {
    echo "Không gọi được API!\n";

    $error = error_get_last();
    print_r($error);
} else {
    echo $res . "\n";
}

echo "=========================\n";
}, 0);  
echo "[" . date('H:i:s') . "] 🚀 MQTT Subscriber đang chạy... (Ctrl+C để dừng)\n";

// Chạy vòng lặp liên tục
$client->loop(true);
?>