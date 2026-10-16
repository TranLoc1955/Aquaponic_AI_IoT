<?php
/**
 * {
 *   "device_id": "18", "timestamp": "...", "mode": "AUTO", "auto_revert_in_seconds": 0,
 *   "sensor_values": {"nhietdo":32.5, "do_am":68, "muc_nuoc":1450, "anhsang":620},
 *   "ai_result": {"engine":"edge_threshold_v1","time_window":"day","anomaly_type":"environment","severity":"warning","confidence":"certain"},
 *   "analysis": {"summary":"...", "abnormal_sensors":["nhietdo"]},
 *   "action_taken": {"device":"quatgio","command":"ON","reason":"..."},   // TÙY CHỌN
 *   "recommendation": {"action":"CHECK_ENVIRONMENT","message":"..."}
 * }
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once __DIR__ . '/../../config/connect.php';

function jsonErr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(["status" => "error", "message" => $msg]);
    exit;
}

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

// Nhãn tiếng Việt cho anomaly_type, dùng khi build nội dung cảnh báo
function nhanAnomalyType($type) {
    return [
        'environment'  => 'Bất thường môi trường',
        'sensor_fault' => 'Lỗi cảm biến',
    ][$type] ?? $type;
}

function nhanDevice($device) {
    return [
        'maybom' => 'Máy Bơm', 'denled' => 'Đèn', 'quatgio' => 'Quạt Gió',
    ][$device] ?? $device;
}

// ---------------------------------------------------------------- //
$raw  = file_get_contents("php://input");
$data = json_decode($raw);
if (!$data) {
    jsonErr("Payload rỗng hoặc JSON không hợp lệ.");
}
if (!isset($data->device_id)) {
    jsonErr("Thiếu device_id.");
}

$idthietbi = (int) $data->device_id;
$sv        = $data->sensor_values ?? null;
$aiResult  = $data->ai_result ?? null;
$analysis  = $data->analysis ?? null;
$action    = $data->action_taken ?? null;   
$reco      = $data->recommendation ?? null;

if (!$aiResult) {
    jsonErr("Thiếu ai_result.");
}

$mode         = $data->mode ?? 'AUTO';
$timeWindow   = $aiResult->time_window ?? null;
$engine       = $aiResult->engine ?? null;
$anomalyType  = $aiResult->anomaly_type ?? 'none';
$severity     = $aiResult->severity ?? 'normal';
$confidence   = $aiResult->confidence ?? null;
$isAnomaly    = ($anomalyType !== 'none') ? 1 : 0;

$analysisSummary  = $analysis->summary ?? null;
$abnormalSensors  = isset($analysis->abnormal_sensors) && is_array($analysis->abnormal_sensors)
    ? implode(',', $analysis->abnormal_sensors)
    : null;

$actionDevice  = $action->device  ?? null;
$actionCommand = $action->command ?? null;
$actionReason  = $action->reason  ?? null;

$recoAction  = $reco->action  ?? null;
$recoMessage = $reco->message ?? null;

if ($anomalyType === 'sensor_fault' || $confidence === 'uncertain') {
    $actionDevice = null;
    $actionCommand = null;
    $actionReason = null;
}

try {
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    jsonErr("Kết nối DB lỗi: " . $e->getMessage(), 500);
}

try {
    // ---- 1. Luôn ghi vào ai_ket_qua (lịch sử đầy đủ mọi lần phân tích) ----
    $stmt = $db->prepare(
        "INSERT INTO ai_ket_qua
            (idthietbi, nhietdo, do_am, muc_nuoc, anhsang, mode, time_window, engine,
             anomaly_type, is_anomaly, severity, confidence, analysis_summary, abnormal_sensors,
             action_device, action_command, action_reason,
             recommendation_action, recommendation_message, thoigian)
         VALUES
            (:id, :nhietdo, :do_am, :muc_nuoc, :anhsang, :mode, :time_window, :engine,
             :anomaly_type, :is_anomaly, :severity, :confidence, :analysis_summary, :abnormal_sensors,
             :action_device, :action_command, :action_reason,
             :recommendation_action, :recommendation_message, NOW())"
    );
    $stmt->execute([
        ':id'                     => $idthietbi,
        ':nhietdo'                => $sv->nhietdo  ?? null,
        ':do_am'                  => $sv->do_am    ?? null,
        ':muc_nuoc'               => $sv->muc_nuoc ?? null,
        ':anhsang'                => $sv->anhsang  ?? null,
        ':mode'                   => $mode,
        ':time_window'            => $timeWindow,
        ':engine'                 => $engine,
        ':anomaly_type'           => $anomalyType,
        ':is_anomaly'             => $isAnomaly,
        ':severity'               => $severity,
        ':confidence'             => $confidence,
        ':analysis_summary'       => $analysisSummary,
        ':abnormal_sensors'       => $abnormalSensors,
        ':action_device'          => $actionDevice,
        ':action_command'         => $actionCommand,
        ':action_reason'          => $actionReason,
        ':recommendation_action'  => $recoAction,
        ':recommendation_message' => $recoMessage,
    ]);

    $lastKqId = (int)$db->lastInsertId();

    // ---- 2. Ghi vào ai_canhbao (chỉ lưu WARNING/DANGER hoặc có action, áp dụng chống trùng lặp) ----
    $isNotable = $isAnomaly || ($severity === 'warning' || $severity === 'danger') || $actionDevice !== null;

    if ($isNotable) {
        // Chuỗi tóm tắt giá trị cảm biến tại thời điểm phát hiện
        $sensorValsSummary = [];
        if (isset($sv->nhietdo) && $sv->nhietdo !== null) $sensorValsSummary[] = "Nhiệt độ: " . round((float)$sv->nhietdo, 1) . "°C";
        if (isset($sv->do_am) && $sv->do_am !== null) $sensorValsSummary[] = "Độ ẩm: " . round((float)$sv->do_am, 1) . "%";
        if (isset($sv->anhsang) && $sv->anhsang !== null) $sensorValsSummary[] = "Ánh sáng: " . round((float)$sv->anhsang, 1) . " lux";
        if (isset($sv->muc_nuoc) && $sv->muc_nuoc !== null) $sensorValsSummary[] = "Mực nước: " . round((float)$sv->muc_nuoc, 1);
        $giaTriCamBienStr = implode(' | ', $sensorValsSummary);

        $noidung = $isAnomaly
            ? "🤖 " . nhanAnomalyType($anomalyType) . " tại thiết bị #{$idthietbi} (mức: {$severity})."
            : "🤖 AI vừa tự động điều khiển thiết bị #{$idthietbi}.";

        $noidung .= "\n" . ($analysisSummary ?? '');

        if ($actionDevice) {
            $lenh = $actionCommand === 'ON' ? 'BẬT' : 'TẮT';
            $noidung .= "\n⚡ Đã tự động {$lenh} " . nhanDevice($actionDevice) . ($actionReason ? " — {$actionReason}" : '');
        }
        if ($recoMessage && $recoAction !== 'NONE') {
            $noidung .= "\n💡 Khuyến nghị: {$recoMessage}";
        }

        $alertAnomalyType = ($anomalyType === 'none') ? 'action' : $anomalyType;

        // Deduplication: Kiểm tra sự kiện cùng loại vẫn đang mở (trangthai = 0) trong vòng 90 giây
        $stmtCheckOpen = $db->prepare(
            "SELECT id, mucdo, so_lan_lap FROM ai_canhbao 
             WHERE idthietbi = :id 
               AND anomaly_type = :atype 
               AND trangthai = 0 
               AND TIMESTAMPDIFF(SECOND, COALESCE(thoigian_capnhat, thoigian), NOW()) <= 90
             ORDER BY id DESC LIMIT 1"
        );
        $stmtCheckOpen->execute([
            ':id'    => $idthietbi,
            ':atype' => $alertAnomalyType
        ]);
        $openAlert = $stmtCheckOpen->fetch(PDO::FETCH_ASSOC);

        if ($openAlert) {
            // Đang có sự kiện cùng loại tiếp diễn -> Cập nhật thời gian và nội dung mới nhất, không tạo trùng lặp
            $newMucdo = ($severity === 'danger' || $openAlert['mucdo'] === 'danger') ? 'danger' : $severity;
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
                ':alert_id'       => $openAlert['id']
            ]);
        } else {
            // Sự kiện mới xuất hiện -> Tạo bản ghi mới
            $stmtAlert = $db->prepare(
                "INSERT INTO ai_canhbao 
                    (idthietbi, noidung, anomaly_type, cam_bien, gia_tri_cambien, mucdo, action_device, action_command, trangthai, thoigian, thoigian_capnhat, so_lan_lap, id_ketqua)
                 VALUES 
                    (:id, :noidung, :anomaly_type, :cam_bien, :gia_tri, :mucdo, :action_device, :action_command, 0, NOW(), NOW(), 1, :id_ketqua)"
            );
            $stmtAlert->execute([
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

            // ---- 3. Telegram: CHỈ gửi khi tạo sự kiện mới mà severity=danger HOẶC có action_taken ----
            $shouldSendTelegram = ($severity === 'danger') || ($actionDevice !== null);
            if ($shouldSendTelegram) {
                $nguonAI = (strpos((string)$engine, 'web_') === 0) ? 'Web' : 'Edge';
                guiTelegramAI($db, $idthietbi, "🤖 <b>Cảnh báo AI ({$nguonAI})</b>\n\n{$noidung}");
            }
        }
    }

    http_response_code(200);
    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    jsonErr("Lỗi khi ghi DB: " . $e->getMessage(), 500);
}
?>