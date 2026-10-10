<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../config/connect.php';
$db = (new Database())->connect();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $user_id = $_GET['user_id'] ?? null;
    if (!$user_id) { echo json_encode(['status'=>'error','message'=>'Thiếu user_id']); exit; }

    $stmt = $db->prepare("SELECT ct.bot_token, ct.chat_id, ct.trangthai 
                          FROM cauhinh_telegram ct 
                          JOIN thietbi t ON ct.idthietbi = t.idthietbi 
                          WHERE t.id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'success', 'data'=>$data ?: null]);
}

if ($method === 'POST') {
    $raw = json_decode(file_get_contents('php://input'));
    $user_id   = $raw->user_id   ?? null;
    $token     = $raw->bot_token ?? null;
    $chat_id   = $raw->chat_id  ?? null;
    $trangthai = $raw->trangthai ?? 1;

    if (!$user_id || !$token || !$chat_id) {
        echo json_encode(['status'=>'error','message'=>'Thiếu thông tin']); exit;
    }

    $stmt = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$device) { echo json_encode(['status'=>'error','message'=>'Không tìm thấy thiết bị']); exit; }

    $idthietbi = $device['idthietbi'];

    $check = $db->prepare("SELECT id FROM cauhinh_telegram WHERE idthietbi = ?");
    $check->execute([$idthietbi]);

    if ($check->fetch()) {
        $stmt = $db->prepare("UPDATE cauhinh_telegram SET bot_token=?, chat_id=?, trangthai=? WHERE idthietbi=?");
        $stmt->execute([$token, $chat_id, $trangthai, $idthietbi]);
    } else {
        $stmt = $db->prepare("INSERT INTO cauhinh_telegram (idthietbi, bot_token, chat_id, trangthai) VALUES (?,?,?,?)");
        $stmt->execute([$idthietbi, $token, $chat_id, $trangthai]);
    }

    echo json_encode(['status'=>'success','message'=>'Đã lưu cấu hình Telegram']);
}
?>