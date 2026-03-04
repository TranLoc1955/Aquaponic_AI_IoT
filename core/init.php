<?php

// ===== HEADERS =====
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Fix preflight (quan trọng cho FE)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===== SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME ?? "AQUAPONIC_AI");
    session_start();
}

// ===== LOAD CONFIG =====
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/connect.php";

// ===== DATABASE =====
$database = new Database();
$db = $database->connect();

// ===== AUTOLOAD CLASS =====
spl_autoload_register(function ($class) {
    $file = __DIR__ . "/../classes/" . strtolower($class) . ".php";
    if (file_exists($file)) {
        require_once $file;
    }
});

// ===== RESPONSE HELPER (nhẹ) =====
function jsonResponse($success, $data = null, $message = "", $code = 200) {
    http_response_code($code);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}
