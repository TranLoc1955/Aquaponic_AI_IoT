<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Đường dẫn file
$path_connect = __DIR__ . '/../../config/connect.php';
$path_user    = __DIR__ . '/../../classes/nguoidung.php';

if (!file_exists($path_connect) || !file_exists($path_user)) {
    echo json_encode(["thanh_cong" => false, "thong_bao" => "Lỗi: Không tìm thấy file hệ thống"]);
    exit();
}

require_once $path_connect;
require_once $path_user;

try {
    // Kết nối database
    $database = new Database();
    $db = $database->connect();
    
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['email']) || !isset($data['matkhau'])) {
        echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu email hoặc mật khẩu']);
        exit();
    }
    
    // 1. Tìm user theo email
    $query = "SELECT * FROM nguoidung WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data['email']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Kiểm tra mật khẩu
    if ($user && password_verify($data['matkhau'], $user['matkhau'])) {
        
        // 3. Chuẩn bị dữ liệu trả về (Bắt buộc phải có 'id')
        $user_data = [
            'id' => $user['id'],
            'hoten' => $user['hoten'],
            'email' => $user['email'],
            'vaitro' => $user['vaitro']
        ];

        http_response_code(200);
        echo json_encode([
            'thanh_cong' => true,
            'thong_bao' => 'Đăng nhập thành công',
            'data' => $user_data 
        ]);
        
    } else {
        http_response_code(401);
        echo json_encode([
            'thanh_cong' => false,
            'thong_bao' => 'Email hoặc mật khẩu không đúng'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'thanh_cong' => false,
        'thong_bao' => 'Lỗi server: ' . $e->getMessage()
    ]);
}
?>