<?php
// File: Test/API/canhbao/get_alerts.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/connect.php';

try {
    if (!isset($_GET['user_id'])) throw new Exception("Thiếu User ID");
    $user_id = $_GET['user_id'];

    $database = new Database();
    $db = $database->connect();

    // 1. Lấy ID thiết bị
    $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
    $stmt_find->execute([':uid' => $user_id]);
    $device = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if (!$device) {
        echo json_encode(["status" => "success", "unread" => 0, "data" => []]);
        exit();
    }
    $device_id = $device['idthietbi'];

    // 2. Đếm số lượng chưa đọc
    $stmt_count = $db->prepare("SELECT COUNT(*) as total FROM canhbao WHERE idthietbi = ? AND trangthai = 0");
    $stmt_count->execute([$device_id]);
    $unread = (int) $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. Lấy 10 thông báo mới nhất (Kèm tên cảm biến)
    $query = "SELECT c.*, cb.loaicambien, cb.donvido 
              FROM canhbao c
              LEFT JOIN cambien cb ON c.idcambien = cb.idcambien AND c.idthietbi = cb.idthietbi
              WHERE c.idthietbi = :did 
              ORDER BY c.thoigian DESC LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':did' => $device_id]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success", 
        "unread" => $unread, 
        "data" => $alerts
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>