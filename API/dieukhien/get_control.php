<?php
// File: Test/API/dieukhien/get_control_log.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/connect.php';

try {
    if (!isset($_GET['user_id'])) throw new Exception("Thiếu User ID");
    $user_id = $_GET['user_id'];

    $database = new Database();
    $db = $database->connect();

    // 1. Lấy ID thiết bị của User này
    $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
    $stmt_find->execute([':uid' => $user_id]);
    $dev = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if (!$dev) { 
        // Trả về mảng rỗng nếu không thấy thiết bị (để JS không bị lỗi)
        echo json_encode(["status" => "success", "data" => []]); 
        exit(); 
    }

    // 2. Lấy dữ liệu từ bảng lenhdieukhien
    // Sắp xếp giảm dần theo thời gian (Mới nhất lên đầu)
    $query = "SELECT * FROM lenhdieukhien WHERE idthietbi = :did ORDER BY thoigian DESC LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':did' => $dev['idthietbi']]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $result]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>