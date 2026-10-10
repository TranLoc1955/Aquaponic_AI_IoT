<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/connect.php';

try {
    if (!isset($_GET['user_id'])) throw new Exception("Thiếu User ID");
    $user_id = $_GET['user_id'];
    $limit   = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 30;

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

    // 2. Đếm số chưa đọc
    $stmt_unread = $db->prepare("SELECT COUNT(*) AS unread FROM ai_canhbao WHERE idthietbi = :did AND trangthai = 0");
    $stmt_unread->execute([':did' => $device_id]);
    $unread = (int) $stmt_unread->fetch(PDO::FETCH_ASSOC)['unread'];

    // 3. Lấy danh sách cảnh báo AI gần nhất
    $stmt = $db->prepare(
        "SELECT id, idthietbi, noidung, anomaly_type, mucdo, action_device, action_command, trangthai, thoigian
         FROM ai_canhbao
         WHERE idthietbi = :did
         ORDER BY thoigian DESC
         LIMIT $limit"
    );
    $stmt->execute([':did' => $device_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"    => "success",
        "device_id" => $device_id,
        "unread"    => $unread,
        "data"      => $rows
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>