<?php
// File: Test/API/cauhinh/get_config.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/connect.php';

try {
    if (!isset($_GET['user_id'])) throw new Exception("Thiếu User ID");
    $user_id = $_GET['user_id'];

    $database = new Database();
    $db = $database->connect();


    $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
    $stmt_find->execute([':uid' => $user_id]);
    $device = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if (!$device) throw new Exception("Tài khoản chưa kết nối thiết bị");
    $device_id = $device['idthietbi'];

    $query = "SELECT ch.*, cb.loaicambien, cb.donvido 
              FROM cauhinh_canhbao ch
              JOIN cambien cb ON ch.idcambien = cb.idcambien
              WHERE ch.idthietbi = :did 
              ORDER BY ch.idcambien ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':did' => $device_id]);
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($configs) == 0) {
        $defaults = [1, 2, 3, 4]; 
        $stmt_ins = $db->prepare("INSERT INTO cauhinh_canhbao (idthietbi, idcambien, trangthai) VALUES (?, ?, 1)");
        
        foreach ($defaults as $id_cam) {
            $stmt_ins->execute([$device_id, $id_cam]);
        }
        
        echo file_get_contents("http://localhost/Test/API/cauhinh/get_config.php?user_id=" . $user_id);
        exit();
    }

    echo json_encode(["status" => "success", "data" => $configs]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>