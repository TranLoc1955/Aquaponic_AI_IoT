<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/connect.php';

try {
    if (!isset($_GET['user_id'])) throw new Exception("Thiếu User ID");
    $user_id = $_GET['user_id'];
    $limit   = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 15;

    $database = new Database();
    $db = $database->connect();

    // 1. Lấy ID thiết bị
    $device = null;
    if ($user_id > 0) {
        $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
        $stmt_find->execute([':uid' => $user_id]);
        $device = $stmt_find->fetch(PDO::FETCH_ASSOC);
    }

    if (!$device) {
        $stmt_active = $db->query("SELECT idthietbi FROM ai_ket_qua GROUP BY idthietbi ORDER BY COUNT(*) DESC LIMIT 1");
        $device = $stmt_active->fetch(PDO::FETCH_ASSOC);
    }

    if (!$device) {
        echo json_encode(["status" => "success", "device_id" => null, "data" => []]);
        exit();
    }
    $device_id = $device['idthietbi'];

    // 2. Lấy N bản ghi AI gần nhất của thiết bị đó
    $stmt = $db->prepare(
        "SELECT idthietbi, nhietdo, do_am, muc_nuoc, anhsang,
                mode, time_window, engine, anomaly_type, is_anomaly, severity, confidence,
                analysis_summary, abnormal_sensors,
                action_device, action_command, action_reason,
                recommendation_action, recommendation_message, thoigian
         FROM ai_ket_qua
         WHERE idthietbi = :did
         ORDER BY thoigian DESC
         LIMIT $limit"
    );
    $stmt->execute([':did' => $device_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"    => "success",
        "device_id" => $device_id,
        "data"      => $rows
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>