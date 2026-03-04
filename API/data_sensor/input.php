<?php
// 1. Cấu hình Header
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. Import file cấu hình và Model
// Lưu ý: Đếm số dấu ../ cho đúng với thư mục bạn đặt file này
include_once '../../config/connect.php';
include_once '../../classes/dulieucambien.php';
// 3. Kết nối Database
try {
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    http_response_code(500);
    exit();
}

$sensor = new DuLieuCamBien($db);

// 5. Nhận dữ liệu từ IoT
$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Dữ liệu rỗng"]);
    exit();
}

if ($sensor->luuDuLieuTuDong($data)) {
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Đã lưu dữ liệu thành công"]);
} else {
    http_response_code(503);
    echo json_encode(["status" => "error", "message" => "Lỗi: Không tìm thấy cảm biến phù hợp cho User " . ($data->id ?? 'Unknown')]);
}
?>