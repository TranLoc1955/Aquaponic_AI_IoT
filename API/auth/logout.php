<?php
// api/auth/dangxuat.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

session_destroy();

echo json_encode([
    "thanh_cong" => true,
    "thong_bao" => "Đăng xuất thành công"
]);
?>