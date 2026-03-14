<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/connect.php';

try {
    if (!isset($_GET['user_id'])) throw new Exception("Thiếu User ID");
    $user_id = $_GET['user_id'];
    
    // Nhận tham số lọc
    $sensor_id = isset($_GET['sensor_id']) ? $_GET['sensor_id'] : 0;
    
    // NHẬN THÊM THAM SỐ NGÀY (Start - End)
    $start_date = isset($_GET['start']) ? $_GET['start'] : null; // YYYY-MM-DD
    $end_date   = isset($_GET['end']) ? $_GET['end'] : null;     // YYYY-MM-DD
    $limit      = isset($_GET['limit']) ? $_GET['limit'] : 100;

    $database = new Database();
    $db = $database->connect();

    // 1. Tìm thiết bị
    $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
    $stmt_find->execute([':uid' => $user_id]);
    $device = $stmt_find->fetch(PDO::FETCH_ASSOC);
    if (!$device) throw new Exception("Không tìm thấy thiết bị");
    $device_id = $device['idthietbi'];

    // 2. Xây dựng câu truy vấn
    $sql = "SELECT d.giatri, d.thoigian, c.loaicambien, c.donvido 
            FROM dulieucambien d
            JOIN cambien c ON d.idcambien = c.idcambien AND c.idthietbi = :did2
            WHERE d.idthietbi = :did";

    // Lọc theo loại cảm biến
    if ($sensor_id > 0) {
        $sql .= " AND d.idcambien = :sid";
    }

    // LOGIC LỌC NGÀY GIỜ (Quan trọng)
    if ($start_date && $end_date) {
        // Nếu chọn ngày -> Lấy trong khoảng đó (Không dùng Limit)
        $sql .= " AND d.thoigian >= :start AND d.thoigian <= :end ORDER BY d.thoigian DESC";
    } else {
        // Nếu không chọn ngày -> Lấy mặc định mới nhất (Dùng Limit)
        $sql .= " ORDER BY d.thoigian DESC LIMIT :lim";
    }

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':did', $device_id);
    $stmt->bindParam(':did2', $device_id);
    if ($sensor_id > 0) $stmt->bindParam(':sid', $sensor_id);

    // Bind tham số ngày hoặc limit
    if ($start_date && $end_date) {
        // Thêm giờ vào để lấy trọn vẹn ngày (00:00:00 đến 23:59:59)
        $s = $start_date . " 00:00:00";
        $e = $end_date . " 23:59:59";
        $stmt->bindParam(':start', $s);
        $stmt->bindParam(':end', $e);
    } else {
        $stmt->bindParam(':lim', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Đảo ngược mảng để vẽ biểu đồ (Cũ -> Mới)
    $chart_data = array_reverse($data);

    echo json_encode(["status" => "success", "data" => $chart_data]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>