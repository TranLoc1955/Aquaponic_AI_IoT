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
              LEFT JOIN cambien cb ON ch.idcambien = cb.idcambien AND ch.idthietbi = cb.idthietbi
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
        
        $stmt->execute([':did' => $device_id]);
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($configs as &$c) {
        $c['nguongtren'] = isset($c['nguongtren']) ? $c['nguongtren'] : (isset($c['nguong_tren']) ? $c['nguong_tren'] : null);
        $c['nguongduoi'] = isset($c['nguongduoi']) ? $c['nguongduoi'] : (isset($c['nguong_duoi']) ? $c['nguong_duoi'] : null);
        if (!isset($c['donvido']) || $c['donvido'] === null) $c['donvido'] = '';
        if (!isset($c['loaicambien']) || $c['loaicambien'] === null) $c['loaicambien'] = '';
    }
    unset($c);

    echo json_encode(["status" => "success", "data" => $configs]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>