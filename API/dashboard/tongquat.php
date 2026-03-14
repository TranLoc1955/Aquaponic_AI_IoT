<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if (file_exists('../../config/connect.php')) {
    include_once '../../config/connect.php';
} else {
    die(json_encode(["status" => "error", "message" => "Lỗi đường dẫn connect.php"]));
}

try {
    
    if (!isset($_GET['user_id'])) {
        throw new Exception("Chưa đăng nhập (Thiếu User ID)");
    }
    $user_id = $_GET['user_id'];

    $database = new Database();
    $db = $database->connect();

    $query_find = "SELECT idthietbi, ma_serial FROM thietbi WHERE id = :uid LIMIT 1";
    $stmt_find = $db->prepare($query_find);
    $stmt_find->bindParam(':uid', $user_id);
    $stmt_find->execute();
    $device_info = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if (!$device_info) {
        throw new Exception("Tài khoản này chưa kích hoạt thiết bị nào");
    }

    $device_id = $device_info['idthietbi'];

    $stmt_btn = $db->prepare("SELECT maybom, denled, quatgio FROM dieukhien WHERE idthietbi = :did LIMIT 1");
    $stmt_btn->execute([':did' => $device_id]);
    $buttons = $stmt_btn->fetch(PDO::FETCH_ASSOC);
    // Nếu chưa có row trong dieukhien, trả về mặc định tắt hết
    if (!$buttons) {
        $buttons = ['maybom' => 0, 'denled' => 0, 'quatgio' => 0];
    }

    $query_sensor = "SELECT idcambien, giatri FROM dulieucambien 
                     WHERE idthietbi = :did 
                     ORDER BY thoigian DESC LIMIT 20";
    $stmt_sensor = $db->prepare($query_sensor);
    $stmt_sensor->execute([':did' => $device_id]);
    $raw_data = $stmt_sensor->fetchAll(PDO::FETCH_ASSOC);

    $sensor_final = [
        "nhiet_do" => "--",
        "do_am"    => "--",
        "ph"       => "--",
        "anh_sang" => "--"
    ];

    foreach ($raw_data as $row) {
        $id = $row['idcambien'];
        $val = $row['giatri'];

        if ($id == 1 && $sensor_final["nhiet_do"] == "--") $sensor_final["nhiet_do"] = $val;
        if ($id == 2 && $sensor_final["do_am"] == "--")    $sensor_final["do_am"] = $val;
        if ($id == 3 && $sensor_final["ph"] == "--")       $sensor_final["ph"] = $val;
        if ($id == 4 && $sensor_final["anh_sang"] == "--") $sensor_final["anh_sang"] = $val;
    }

    $history = [];
    try {
        $stmt_log = $db->prepare("SELECT * FROM lenhdieukhien ORDER BY idlenh DESC LIMIT 5");
        $stmt_log->execute();
        $history = $stmt_log->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { }

    echo json_encode([
        "status" => "success",
        "data" => [
            "device_id" => $device_id, 
            "device" => $buttons,      
            "sensor" => $sensor_final, 
            "history" => $history,
            "server_time" => date("H:i:s d/m/Y")
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>