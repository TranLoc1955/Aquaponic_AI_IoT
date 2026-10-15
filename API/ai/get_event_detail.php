<?php
/**
 * API: get_event_detail.php
 * Lấy chi tiết toàn diện của một sự kiện cảnh báo AI.
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/../../config/connect.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception("Thiếu tham số id");
    }

    $id = (int) $_GET['id'];
    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("
        SELECT 
            c.*,
            k.nhietdo,
            k.do_am,
            k.anhsang,
            k.muc_nuoc,
            k.mode,
            k.time_window,
            k.engine,
            k.confidence,
            k.analysis_summary,
            k.abnormal_sensors,
            k.action_reason,
            k.recommendation_action,
            k.recommendation_message,
            k.thoigian AS thoigian_ketqua
        FROM ai_canhbao c
        LEFT JOIN ai_ket_qua k ON c.id_ketqua = k.id
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception("Không tìm thấy sự kiện #$id");
    }

    echo json_encode([
        "status" => "success",
        "data"   => $event
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
