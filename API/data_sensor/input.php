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
    jsonErr("Thiếu id (idthietbi) thiết bị. Gửi JSON có trường: id hoặc idthietbi (số).");
}

$idthietbi = (int) (isset($data->id) ? $data->id : $data->idthietbi);
if ($idthietbi <= 0) {
    jsonErr("id thiết bị phải là số dương. Hiện tại: " . (isset($data->id) ? $data->id : $data->idthietbi));
}

try {
    $check = $db->prepare("SELECT 1 FROM thietbi WHERE idthietbi = ? LIMIT 1");
    $check->execute([$idthietbi]);
    if (!$check->fetch()) {
        jsonErr("Không tìm thấy thiết bị có idthietbi = {$idthietbi}. Kiểm tra bảng thietbi hoặc dùng id từ Bước 1 (data[].idthietbi).", 404);
    }
} catch (Exception $e) {
    jsonErr("Lỗi kiểm tra thiết bị: " . $e->getMessage(), 500);
}

try {
    if (!$sensor->luuDuLieuTuDong($data)) {
        jsonErr("Lỗi khi lưu dữ liệu cảm biến. Kiểm tra id (idthietbi) và các trường: nhietdo, do_am, pH, anhsang.", 503);
    }
} catch (Exception $e) {
    jsonErr("Lỗi DB khi lưu dulieucambien: " . $e->getMessage() . ". Có thể idthietbi không tồn tại hoặc bảng dulieucambien thiếu cột/khớp khóa ngoại.", 500);
}

$valuesBySensor = [
    1 => isset($data->nhiet_do) ? (float) $data->nhiet_do : null,
    2 => isset($data->do_am)  ? (float) $data->do_am  : null,
    3 => isset($data->muc_nuoc)     ? (float) $data->muc_nuoc   : null,
    4 => isset($data->anh_sang) ? (float) $data->anh_sang : null,
];

try {
    $stmt = $db->prepare("SELECT idcambien, nguong_tren, nguong_duoi FROM cauhinh_canhbao WHERE idthietbi = ? AND trangthai = 1");
    $stmt->execute([$idthietbi]);
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($configs as $cfg) {
        $idcambien = (int) $cfg['idcambien'];
        $val = isset($valuesBySensor[$idcambien]) ? $valuesBySensor[$idcambien] : null;
        if ($val === null) continue;

        $gt = isset($cfg['nguongtren']) ? $cfg['nguongtren'] : (isset($cfg['nguong_tren']) ? $cfg['nguong_tren'] : null);
        $gd = isset($cfg['nguongduoi']) ? $cfg['nguongduoi'] : (isset($cfg['nguong_duoi']) ? $cfg['nguong_duoi'] : null);
        $nguong_tren = ($gt !== null && $gt !== '') ? (float) $gt : null;
        $nguong_duoi = ($gd !== null && $gd !== '') ? (float) $gd : null;

        if ($nguong_tren !== null && $val > $nguong_tren) {
            $canhBao->themCanhBaoFull($idthietbi, $idcambien, $val, "Giá trị vượt ngưỡng trên ({$val} > {$nguong_tren})", 'danger');
        }
        if ($nguong_duoi !== null && $val < $nguong_duoi) {
            $canhBao->themCanhBaoFull($idthietbi, $idcambien, $val, "Giá trị dưới ngưỡng dưới ({$val} < {$nguong_duoi})", 'warning');
        }
    }

    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Đã lưu dữ liệu thành công"]);
} catch (Exception $e) {
    jsonErr("Lỗi khi kiểm tra ngưỡng/tạo cảnh báo: " . $e->getMessage(), 500);
}
?>