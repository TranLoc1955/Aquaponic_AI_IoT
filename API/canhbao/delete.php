<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/connect.php';

try {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['idcanhbao'])) {
        echo json_encode([
            "status" => "error", 
            "message" => "Thiếu ID cảnh báo"
        ]);
        exit();
    }
    
    $database = new Database();
    $db = $database->connect();
    
    $idcanhbao = intval($data['idcanhbao']);
    

    $stmt = $db->prepare("DELETE FROM canhbao WHERE idcanhbao = ?");
    
    if ($stmt->execute([$idcanhbao])) {
        echo json_encode([
            "status" => "success",
            "message" => "Đã xóa cảnh báo thành công"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Không thể xóa cảnh báo"
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi: " . $e->getMessage()
    ]);
}
?>
