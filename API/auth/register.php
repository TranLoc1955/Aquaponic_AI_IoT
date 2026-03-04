<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. THÊM ĐƯỜNG DẪN FILE THIETBI.PHP
$path_connect = __DIR__ . '/../../config/connect.php';
$path_user    = __DIR__ . '/../../classes/nguoidung.php';
$path_device  = __DIR__ . '/../../classes/thietbi.php'; 

if (!file_exists($path_connect) || !file_exists($path_user) || !file_exists($path_device)) {
    echo json_encode([
        "thanh_cong" => false, 
        "thong_bao" => "Lỗi: Không tìm thấy file hệ thống cần thiết"
    ]);
    exit();
}

require_once $path_connect;
require_once $path_user;
require_once $path_device;

try {
    $database = new Database();
    $db = $database->connect();
    
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    if (!$data || !isset($data['email']) || !isset($data['matkhau']) || !isset($data['hoten'])) {
        echo json_encode([
            'thanh_cong' => false,
            'thong_bao' => 'Thiếu thông tin bắt buộc'
        ]);
        exit();
    }
    
    // Validate số điện thoại nếu có
    if (isset($data['sodienthoai']) && !empty($data['sodienthoai'])) {
        $sodienthoai = preg_replace('/[^0-9]/', '', $data['sodienthoai']); // Chỉ lấy số
        if (strlen($sodienthoai) < 10 || strlen($sodienthoai) > 11) {
            echo json_encode([
                'thanh_cong' => false,
                'thong_bao' => 'Số điện thoại không hợp lệ'
            ]);
            exit();
        }
    }

    $nguoidung = new Nguoidung($db);
    $thietbi   = new Thietbi($db); 
    
    $user_cu = $nguoidung->getByEmail($data['email']);
    if ($user_cu) {
        echo json_encode([
            'thanh_cong' => false,
            'thong_bao' => 'Email đã được sử dụng'
        ]);
        exit();
    }
    
    $du_lieu = [
        'hoten' => $data['hoten'],
        'email' => $data['email'],        
        'matkhau' => $data['matkhau'],
        'sodienthoai' => isset($data['sodienthoai']) ? $data['sodienthoai'] : null,
        'vaitro' => $data['vaitro'] ?? 'user'
    ];
    
    $new_user_id = $nguoidung->themmoi($du_lieu);
    
    if ($new_user_id) {
    
        $serial_code = $thietbi->taoThietBiMacDinh($new_user_id);

        if ($serial_code) {
            http_response_code(201);
            echo json_encode([
                'thanh_cong' => true,
                'thong_bao' => 'Đăng ký thành công! Đã cấp thiết bị mã: ' . $serial_code,
                'ma_serial' => $serial_code 
            ]);
        } else {
            
            http_response_code(201);
            echo json_encode([
                'thanh_cong' => true,
                'thong_bao' => 'Đăng ký thành công (Lỗi tạo thiết bị tự động)'
            ]);
        }

    } else {
        http_response_code(500);
        echo json_encode([
            'thanh_cong' => false,
            'thong_bao' => 'Đăng ký thất bại'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'thanh_cong' => false,
        'thong_bao' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>