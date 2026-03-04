<?php
// File: Test/API/auth/reset_password.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../config/connect.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->email) || !isset($data->phone) || !isset($data->new_pass)) {
        throw new Exception("Vui lòng nhập đủ: Email, SĐT và Mật khẩu mới");
    }

    $database = new Database();
    $db = $database->connect();
    $query = "SELECT id FROM nguoidung WHERE email = :email AND sodienthoai = :phone LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':email' => $data->email,
        ':phone' => $data->phone
    ]);

    if ($stmt->rowCount() == 0) {
        throw new Exception("Email hoặc Số điện thoại không chính xác!");
    }

    $new_pass_hash = password_hash($data->new_pass, PASSWORD_DEFAULT);
    
    $query_upd = "UPDATE nguoidung SET matkhau = :pass WHERE email = :email";
    
    $stmt_upd = $db->prepare($query_upd);
    
    if($stmt_upd->execute([':pass' => $new_pass_hash, ':email' => $data->email])) {
        echo json_encode(["status" => "success", "message" => "Đổi mật khẩu thành công! Hãy đăng nhập lại."]);
    } else {
        throw new Exception("Lỗi hệ thống, không thể cập nhật.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>