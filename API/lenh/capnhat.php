<?php
// api/lenh/capnhat.php - ESP32 cập nhật trạng thái lệnh sau khi thực thi
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key");

require_once '../../classes/lenhdieukhien.php';
require_once '../../classes/connect.php';

// API Key để xác thực ESP32
define('ESP32_API_KEY', 'your_secret_key_12345');

// Kiểm tra API Key
$headers = getallheaders();
$api_key = $headers['X-API-Key'] ?? $_GET['api_key'] ?? '';

if ($api_key !== ESP32_API_KEY) {
    http_response_code(401);
    echo json_encode(["thanh_cong" => false, "thong_bao" => "API Key không hợp lệ"]);
    exit;
}

$database = new Database();
$db = $database->connect();
$lenh = new LenhDieuKhien($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->idlenh) && !empty($data->trangthai)) {
    
    if ($lenh->capnhatTT($data->idlenh, $data->trangthai)) {
        echo json_encode([
            "thanh_cong" => true,
            "thong_bao" => "Cập nhật trạng thái thành công"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["thanh_cong" => false, "thong_bao" => "Cập nhật thất bại"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["thanh_cong" => false, "thong_bao" => "Thiếu thông tin (idlenh, trangthai)"]);
}

?>