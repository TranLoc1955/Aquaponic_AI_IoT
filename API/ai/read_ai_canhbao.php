<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../config/connect.php';

try {   
    $data = json_decode(file_get_contents("php://input"));
    $user_id = $data->user_id ?? null;
    if (!$user_id) throw new Exception("Thiếu user_id");

    $database = new Database();
    $db = $database->connect();

    $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
    $stmt_find->execute([':uid' => $user_id]);
    $device = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if ($device) {
        $stmt_upd = $db->prepare("UPDATE ai_canhbao SET trangthai = 1 WHERE idthietbi = ? AND trangthai = 0");
        $stmt_upd->execute([$device['idthietbi']]);
    }

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>