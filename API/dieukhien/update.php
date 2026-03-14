<?php
// File: Test/API/dieukhien/update.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../config/connect.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    // Kiểm tra dữ liệu đầu vào
    if (!isset($data->user_id) || !isset($data->field) || !isset($data->value)) {
        throw new Exception("Thiếu dữ liệu đầu vào");
    }

    // DANH SÁCH CỘT ĐƯỢC PHÉP SỬA (Bảo mật)
    // Chỉ cho phép sửa 3 cột này, tránh hack sửa cột lung tung
    $allowed_fields = ['maybom', 'denled', 'quatgio'];
    
    if (!in_array($data->field, $allowed_fields)) {
        throw new Exception("Tên thiết bị không hợp lệ (Phải là: maybom, denled, quatgio)");
    }

    $database = new Database();
    $db = $database->connect();

    
    $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
    $stmt_find->execute([':uid' => $data->user_id]);
    $device = $stmt_find->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) throw new Exception("Không tìm thấy thiết bị");
    $device_id = $device['idthietbi'];

    $column_name = $data->field; 
    
    // Dùng INSERT...ON DUPLICATE KEY UPDATE để tự tạo row nếu chưa có
    // Giả sử bảng dieukhien có UNIQUE KEY trên cột idthietbi
    $query = "INSERT INTO dieukhien (idthietbi, $column_name, thoigian) 
              VALUES (:did, :val, NOW())
              ON DUPLICATE KEY UPDATE $column_name = :val2, thoigian = NOW()";
    $stmt_upd = $db->prepare($query);
    $stmt_upd->execute([
        ':did'  => $device_id,
        ':val'  => $data->value,
        ':val2' => $data->value
    ]);

    
    $map_name = [
        'maybom'  => 'Máy Bơm',
        'denled'  => 'Đèn Grow',
        'quatgio' => 'Quạt Gió'
    ];
    
    $ten_viet = $map_name[$data->field]; 
    $hanh_dong = ($data->value == 1) ? "Bật" : "Tắt";
    $noi_dung_lenh = "$hanh_dong $ten_viet"; 

    $stmt_log = $db->prepare("INSERT INTO lenhdieukhien (idthietbi, lenh, trangthai, thoigian) VALUES (:did, :msg, 'Da_gui', NOW())");
    $stmt_log->execute([
        ':did' => $device_id,
        ':msg' => $noi_dung_lenh
    ]);

    echo json_encode(["status" => "success", "message" => "Đã cập nhật $ten_viet thành công"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>