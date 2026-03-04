<?php
// 1. Cấu hình Header
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. Import file Model ở Bước 1 vào
include_once '../../config/connect.php';
include_once '../../classes/dulieucambien.php';
// 3. Kết nối Database
try {
    $database = new Database();
    $db = $database->connect();


    // 4. Gọi Class ra làm việc
    $sensor = new DuLieuCamBien($db);

    // 5. Nhận dữ liệu từ IoT
    $data = json_decode(file_get_contents("php://input"));

    if (!$data) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Du lieu rong"]);
        exit();
    }

    if ($sensor->luuDuLieuTuDong($data)) {
        http_response_code(200); // Trả về 200 OK cho IoT vui
        echo json_encode(["status" => "success", "message" => "Da luu thanh cong"]);
    } else {
        // Nếu lỗi (do không tìm thấy cảm biến hoặc lỗi mạng)
        http_response_code(503); 
        echo json_encode(["status" => "error", "message" => "Loi: Khong tim thay cam bien Nuoc cua User " . $data->id]);
    }
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
?>
