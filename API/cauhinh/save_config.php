<?php
// File: Test/API/cauhinh/save_config.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../config/connect.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->user_id) || !isset($data->configs)) {
        throw new Exception("Dữ liệu không hợp lệ");
    }

    $database = new Database();
    $db = $database->connect();

    $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
    $stmt_find->execute([':uid' => $data->user_id]);
    $device = $stmt_find->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) throw new Exception("Không tìm thấy thiết bị");
    $device_id = $device['idthietbi'];

    $query = "UPDATE cauhinh_canhbao 
              SET nguong_tren = :max, nguong_duoi = :min, trangthai = :stt 
              WHERE id = :id AND idthietbi = :did";
    
    $stmt = $db->prepare($query);

    foreach ($data->configs as $cfg) {
        $max = ($cfg->nguong_tren === "" || $cfg->nguong_tren === null) ? null : $cfg->nguong_tren;
        $min = ($cfg->nguong_duoi === "" || $cfg->nguong_duoi === null) ? null : $cfg->nguong_duoi;
        
        $stmt->execute([
            ':max' => $max,
            ':min' => $min,
            ':stt' => $cfg->trangthai,
            ':id'  => $cfg->id,
            ':did' => $device_id
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Đã lưu cấu hình thành công!"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>