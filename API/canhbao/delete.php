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
    
    $id_canhbao = isset($data['id_canhbao']) ? $data['id_canhbao'] : (isset($data['idcanhbao']) ? $data['idcanhbao'] : null);
    if (!$data || $id_canhbao === null) {
        echo json_encode([
            "status" => "error", 
            "message" => "Thiếu ID cảnh báo"
        ]);
        exit();
    }
    
    $database = new Database();
    $db = $database->connect();
    
    $id_canhbao = intval($id_canhbao);

    $stmt = $db->prepare("DELETE FROM canhbao WHERE id_canhbao = ?");
    
    if ($stmt->execute([$id_canhbao])) {
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
