<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/connect.php';
include_once '../../classes/dulieucambien.php';
include_once '../../classes/canhbao.php';
include_once '../../AI_Web/engine.php';

function jsonErr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(["status" => "error", "message" => $msg]);
    exit;
}

// Hàm gửi Telegram cho Cảnh báo truyền thống — lấy token/chatid từ DB
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

    $lastSent[$key] = $now;
    $updateStmt = $db->prepare("UPDATE cauhinh_telegram SET last_sent = ? WHERE id = ?");
    $updateStmt->execute([json_encode($lastSent), $cfg['id']]);
}

// Hàm gửi Telegram cho AI Web
if (!function_exists('guiTelegramAI')) {
    function guiTelegramAI($db, $idthietbi, $noidung) {
        $stmt = $db->prepare("SELECT id, bot_token, chat_id FROM cauhinh_telegram WHERE idthietbi = ? AND trangthai = 1 LIMIT 1");
        $stmt->execute([$idthietbi]);
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cfg) return;

        $url  = "https://api.telegram.org/bot{$cfg['bot_token']}/sendMessage";
        $data = http_build_query([
            'chat_id'    => $cfg['chat_id'],
            'text'       => $noidung,
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
    }
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

// 1. Lưu dữ liệu cảm biến vào bảng dulieucambien
try {
    if (!$sensor->luuDuLieuTuDong($data)) {
        jsonErr("Lỗi khi lưu dữ liệu cảm biến.", 503);
    }
} catch (Exception $e) {
    jsonErr("Lỗi DB khi lưu dulieucambien: " . $e->getMessage(), 500);
}

// 2. Kiểm tra ngưỡng cảnh báo thông thường (bảng cauhinh_canhbao)
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
} catch (Exception $e) {
    error_log("Lỗi kiểm tra cảnh báo cấu hình: " . $e->getMessage());
}

// =========================================================================
// 3. TÍCH HỢP TỰ ĐỘNG CHẠY AI WEB (KHÔNG CẦN CHẠY RUN_AI_WEB.BAT)
// =========================================================================
$aiWebPayload = null;
try {
    $sv = [
        'nhiet_do' => isset($data->nhiet_do) ? (float)$data->nhiet_do : null,
        'do_am'    => isset($data->do_am) ? (float)$data->do_am : null,
        'muc_nuoc' => isset($data->muc_nuoc) ? (float)$data->muc_nuoc : null,
        'anh_sang' => isset($data->anh_sang) ? (float)$data->anh_sang : null,
    ];

    $t0 = microtime(true);
    $aiKetQua = phanTichWebAI($sv);
    $processingMs = round((microtime(true) - $t0) * 1000, 2);

    $hour = (int) date('H');
    $timeWindow = ($hour >= 6 && $hour < 18) ? 'day' : 'night';
    $engineName = 'web_rule_v1';
    $anomalyType = $aiKetQua['anomaly_type'] ?? 'none';
    $severity = $aiKetQua['severity'] ?? 'normal';
    $confidence = $aiKetQua['confidence'] ?? 'certain';
    $isAnomaly = ($anomalyType !== 'none') ? 1 : 0;
    $summary = $aiKetQua['summary'] ?? '';
    $summaryWithLatency = $summary . sprintf(' [Web AI xử lý: %.1fms]', $processingMs);
    $abnormalSensors = !empty($aiKetQua['abnormal_sensors']) ? implode(',', $aiKetQua['abnormal_sensors']) : null;
    $actionTaken = $aiKetQua['action_taken'] ?? null;
    $actionDevice = $actionTaken['device'] ?? null;
    $actionCommand = $actionTaken['command'] ?? null;
    $actionReason = $actionTaken['reason'] ?? null;
    $recommendation = $aiKetQua['recommendation'] ?? null;
    $recoAction = $recommendation['action'] ?? 'NONE';
    $recoMessage = $recommendation['message'] ?? '';

    // A. Ghi vào bảng ai_ket_qua
    $stmtAiKq = $db->prepare(
        "INSERT INTO ai_ket_qua
            (idthietbi, nhietdo, do_am, muc_nuoc, anhsang, mode, time_window, engine,
             anomaly_type, is_anomaly, severity, confidence, analysis_summary, abnormal_sensors,
             action_device, action_command, action_reason,
             recommendation_action, recommendation_message, thoigian)
         VALUES
            (:id, :nhietdo, :do_am, :muc_nuoc, :anhsang, 'AUTO', :time_window, :engine,
             :anomaly_type, :is_anomaly, :severity, :confidence, :analysis_summary, :abnormal_sensors,
             :action_device, :action_command, :action_reason,
             :recommendation_action, :recommendation_message, NOW())"
    );
    $stmtAiKq->execute([
        ':id'                     => $idthietbi,
        ':nhietdo'                => $sv['nhiet_do'],
        ':do_am'                  => $sv['do_am'],
        ':muc_nuoc'               => $sv['muc_nuoc'],
        ':anhsang'                => $sv['anh_sang'],
        ':time_window'            => $timeWindow,
        ':engine'                 => $engineName,
        ':anomaly_type'           => $anomalyType,
        ':is_anomaly'             => $isAnomaly,
        ':severity'               => $severity,
        ':confidence'             => $confidence,
        ':analysis_summary'       => $summaryWithLatency,
        ':abnormal_sensors'       => $abnormalSensors,
        ':action_device'          => $actionDevice,
        ':action_command'         => $actionCommand,
        ':action_reason'          => $actionReason,
        ':recommendation_action'  => $recoAction,
        ':recommendation_message' => $recoMessage,
    ]);
    $lastKqId = (int)$db->lastInsertId();

    // B. Ghi vào bảng ai_canhbao & Gửi Telegram (chống lặp 90s cho nguồn Web)
    $isNotable = $isAnomaly || ($severity === 'warning' || $severity === 'danger') || $actionDevice !== null;
    if ($isNotable) {
        $alertAnomalyType = ($anomalyType === 'none') ? 'action' : $anomalyType;

        $sensorValsSummary = [];
        if ($sv['nhiet_do'] !== null) $sensorValsSummary[] = "Nhiệt độ: " . round($sv['nhiet_do'], 1) . "°C";
        if ($sv['do_am'] !== null) $sensorValsSummary[] = "Độ ẩm: " . round($sv['do_am'], 1) . "%";
        if ($sv['anh_sang'] !== null) $sensorValsSummary[] = "Ánh sáng: " . round($sv['anh_sang'], 1) . " lux";
        if ($sv['muc_nuoc'] !== null) $sensorValsSummary[] = "Mực nước: " . round($sv['muc_nuoc'], 1);
        $giaTriCamBienStr = implode(' | ', $sensorValsSummary);

        $noidung = $isAnomaly
            ? "🤖 " . ($anomalyType === 'sensor_fault' ? 'Lỗi cảm biến' : 'Bất thường môi trường') . " tại thiết bị #{$idthietbi} (mức: {$severity})."
            : "🤖 AI vừa tự động điều khiển thiết bị #{$idthietbi}.";
        $noidung .= "\n" . $summaryWithLatency;
        if ($actionDevice) {
            $lenh = $actionCommand === 'ON' ? 'BẬT' : 'TẮT';
            $devName = ['maybom'=>'Máy Bơm', 'denled'=>'Đèn', 'quatgio'=>'Quạt Gió'][$actionDevice] ?? $actionDevice;
            $noidung .= "\n⚡ Đã tự động {$lenh} {$devName}" . ($actionReason ? " — {$actionReason}" : '');
        }
        if ($recoMessage && $recoAction !== 'NONE') {
            $noidung .= "\n💡 Khuyến nghị: {$recoMessage}";
        }

        $stmtCheckWebAlert = $db->prepare(
            "SELECT c.id, c.mucdo, c.so_lan_lap FROM ai_canhbao c
             LEFT JOIN ai_ket_qua k ON c.id_ketqua = k.id
             WHERE c.idthietbi = :id 
               AND c.anomaly_type = :atype 
               AND c.trangthai = 0 
               AND k.engine LIKE 'web%'
               AND TIMESTAMPDIFF(SECOND, COALESCE(c.thoigian_capnhat, c.thoigian), NOW()) <= 90
             ORDER BY c.id DESC LIMIT 1"
        );
        $stmtCheckWebAlert->execute([
            ':id'    => $idthietbi,
            ':atype' => $alertAnomalyType
        ]);
        $openWebAlert = $stmtCheckWebAlert->fetch(PDO::FETCH_ASSOC);

        if ($openWebAlert) {
            $newMucdo = ($severity === 'danger' || $openWebAlert['mucdo'] === 'danger') ? 'danger' : $severity;
            $stmtUpd = $db->prepare(
                "UPDATE ai_canhbao 
                 SET noidung = :noidung,
                     mucdo = :mucdo,
                     cam_bien = :cam_bien,
                     gia_tri_cambien = :gia_tri,
                     action_device = COALESCE(:action_device, action_device),
                     action_command = COALESCE(:action_command, action_command),
                     thoigian_capnhat = NOW(),
                     so_lan_lap = so_lan_lap + 1,
                     id_ketqua = :id_ketqua
                 WHERE id = :alert_id"
            );
            $stmtUpd->execute([
                ':noidung'        => $noidung,
                ':mucdo'          => $newMucdo,
                ':cam_bien'       => $abnormalSensors,
                ':gia_tri'        => $giaTriCamBienStr,
                ':action_device'  => $actionDevice,
                ':action_command' => $actionCommand,
                ':id_ketqua'      => $lastKqId,
                ':alert_id'       => $openWebAlert['id']
            ]);
        } else {
            $stmtInsAlert = $db->prepare(
                "INSERT INTO ai_canhbao 
                    (idthietbi, noidung, anomaly_type, cam_bien, gia_tri_cambien, mucdo, action_device, action_command, trangthai, thoigian, thoigian_capnhat, so_lan_lap, id_ketqua)
                 VALUES 
                    (:id, :noidung, :anomaly_type, :cam_bien, :gia_tri, :mucdo, :action_device, :action_command, 0, NOW(), NOW(), 1, :id_ketqua)"
            );
            $stmtInsAlert->execute([
                ':id'             => $idthietbi,
                ':noidung'        => $noidung,
                ':anomaly_type'   => $alertAnomalyType,
                ':cam_bien'       => $abnormalSensors,
                ':gia_tri'        => $giaTriCamBienStr,
                ':mucdo'          => $severity,
                ':action_device'  => $actionDevice,
                ':action_command' => $actionCommand,
                ':id_ketqua'      => $lastKqId,
            ]);

            // Gửi Telegram AI Web khi severity = danger HOẶC có action_taken
            if ($severity === 'danger' || $actionDevice !== null) {
                guiTelegramAI($db, $idthietbi, "🤖 <b>Cảnh báo AI (Web)</b>\n\n{$noidung}");
            }
        }
    }

    $aiWebPayload = [
        'device_id'     => (string)$idthietbi,
        'timestamp'     => date('c'),
        'mode'          => 'AUTO',
        'sensor_values' => [
            'nhietdo'  => $sv['nhiet_do'],
            'do_am'    => $sv['do_am'],
            'muc_nuoc' => $sv['muc_nuoc'],
            'anhsang'  => $sv['anh_sang'],
        ],
        'ai_result' => [
            'engine'       => $engineName,
            'time_window'  => $timeWindow,
            'anomaly_type' => $anomalyType,
            'severity'     => $severity,
            'confidence'   => $confidence,
        ],
        'analysis' => [
            'summary'          => $summaryWithLatency,
            'abnormal_sensors' => $aiKetQua['abnormal_sensors'] ?? [],
        ],
        'recommendation' => $recommendation,
        'action_taken'   => $actionTaken,
    ];
} catch (Throwable $e) {
    error_log("Lỗi chạy Web AI trong input.php: " . $e->getMessage());
}

http_response_code(200);
$resData = ["status" => "success", "message" => "Đã lưu dữ liệu thành công"];
if ($aiWebPayload !== null) {
    $resData["ai_web"] = $aiWebPayload;
}
echo json_encode($resData);
?>
