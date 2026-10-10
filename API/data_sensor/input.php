<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/connect.php';
include_once '../../classes/dulieucambien.php';
include_once '../../classes/canhbao.php';

function jsonErr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(["status" => "error", "message" => $msg]);
    exit;
}

// Hàm gửi Telegram — lấy token/chatid từ DB
function guiTelegram($db, $idthietbi, $idcambien, $message) {
    $stmt = $db->prepare("SELECT id, bot_token, chat_id, last_sent FROM cauhinh_telegram WHERE idthietbi = ? AND trangthai = 1 LIMIT 1");
    $stmt->execute([$idthietbi]);
    $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cfg) return;

    // Kiểm tra 3 phút / cảm biến
    $lastSent = json_decode($cfg['last_sent'], true) ?: [];
    $key = "cb_{$idcambien}";
    $now = time();

    if (isset($lastSent[$key]) && ($now - $lastSent[$key]) < 180) {
        return; 
    }

    // Gửi Telegram
    $url  = "https://api.telegram.org/bot{$cfg['bot_token']}/sendMessage";
    $data = http_build_query([
        'chat_id'    => $cfg['chat_id'],
        'text'       => $message,
        'parse_mode' => 'HTML'
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    curl_close($ch);

    // Cập nhật thời gian gửi cuối
    $lastSent[$key] = $now;
    $updateStmt = $db->prepare("UPDATE cauhinh_telegram SET last_sent = ? WHERE id = ?");
    $updateStmt->execute([json_encode($lastSent), $cfg['id']]);
}

try {
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    jsonErr("Kết nối DB lỗi: " . $e->getMessage(), 500);
}

$sensor = new DuLieuCamBien($db);
$canhBao = new CanhBao($db);

$raw = file_get_contents("php://input");
$data = json_decode($raw);

if (!$data) {
    jsonErr("Dữ liệu rỗng hoặc JSON không hợp lệ. Raw: " . substr($raw, 0, 200));
}

if (!isset($data->id) && !isset($data->idthietbi)) {
    jsonErr("Thiếu id (idthietbi) thiết bị.");
}

$idthietbi = (int) (isset($data->id) ? $data->id : $data->idthietbi);
if ($idthietbi <= 0) {
    jsonErr("id thiết bị phải là số dương.");
}

try {
    $check = $db->prepare("SELECT 1 FROM thietbi WHERE idthietbi = ? LIMIT 1");
    $check->execute([$idthietbi]);
    if (!$check->fetch()) {
        jsonErr("Không tìm thấy thiết bị có idthietbi = {$idthietbi}.", 404);
    }
} catch (Exception $e) {
    jsonErr("Lỗi kiểm tra thiết bị: " . $e->getMessage(), 500);
}

try {
    if (!$sensor->luuDuLieuTuDong($data)) {
        jsonErr("Lỗi khi lưu dữ liệu cảm biến.", 503);
    }
} catch (Exception $e) {
    jsonErr("Lỗi DB khi lưu dulieucambien: " . $e->getMessage(), 500);
}

$valuesBySensor = [
    1 => isset($data->nhiet_do)  ? (float) $data->nhiet_do  :  null,
    2 => isset($data->do_am)    ? (float) $data->do_am    : null,
    3 => isset($data->muc_nuoc) ? (float) $data->muc_nuoc : null,
    4 => isset($data->anh_sang)  ? (float) $data->anh_sang  : null,
];

try {
    $stmt = $db->prepare("SELECT idcambien, nguong_tren, nguong_duoi FROM cauhinh_canhbao WHERE idthietbi = ? AND trangthai = 1");
    $stmt->execute([$idthietbi]);
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tenCamBien = [
        1 => 'Nhiệt độ',
        2 => 'Độ ẩm',
        3 => 'Mức nước',
        4 => 'Ánh sáng'
    ];

    foreach ($configs as $cfg) {
        $idcambien = (int) $cfg['idcambien'];
        $val = isset($valuesBySensor[$idcambien]) ? $valuesBySensor[$idcambien] : null;
        if ($val === null) continue;

        $ten = isset($tenCamBien[$idcambien]) ? $tenCamBien[$idcambien] : "Cảm biến $idcambien";
        $nguong_tren = ($cfg['nguong_tren'] !== null && $cfg['nguong_tren'] !== '') ? (float)$cfg['nguong_tren'] : null;
        $nguong_duoi = ($cfg['nguong_duoi'] !== null && $cfg['nguong_duoi'] !== '') ? (float)$cfg['nguong_duoi'] : null;

        if ($nguong_tren !== null && $val > $nguong_tren) {
    $noidung = "⚠️ {$ten} đang quá cao: {$val} (ngưỡng tối đa: {$nguong_tren})";
    $canhBao->themCanhBaoFull($idthietbi, $idcambien, $val, $noidung, 'danger');
    guiTelegram($db, $idthietbi, $idcambien, "🔴 <b>HydroSmart Cảnh Báo</b>\n\n{$noidung}");
}

if ($nguong_duoi !== null && $val < $nguong_duoi) {
    $noidung = "🔵 {$ten} đang quá thấp: {$val} (ngưỡng tối thiểu: {$nguong_duoi})";
    $canhBao->themCanhBaoFull($idthietbi, $idcambien, $val, $noidung, 'warning');
    guiTelegram($db, $idthietbi, $idcambien, "🟡 <b>HydroSmart Cảnh Báo</b>\n\n{$noidung}");
}
    }

    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Đã lưu dữ liệu thành công"]);
} catch (Exception $e) {
    jsonErr("Lỗi khi kiểm tra ngưỡng/tạo cảnh báo: " . $e->getMessage(), 500);
}
?>