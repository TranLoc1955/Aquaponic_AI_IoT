<?php
/**
 * API: update_event_status.php
 * Cập nhật trạng thái xử lý sự kiện AI (0: Chưa xử lý, 1: Đã xử lý, 2: Đang theo dõi)
 * Kèm ghi chú của người dùng. Không xóa lịch sử.
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once __DIR__ . '/../../config/connect.php';

try {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data) {
        throw new Exception("Dữ liệu gửi lên không hợp lệ.");
    }

    $eventId = (int) ($data['event_id'] ?? 0);
    $status  = isset($data['status']) ? (int) $data['status'] : null;
    $note    = trim($data['note'] ?? '');

    if ($eventId <= 0 || $status === null) {
        throw new Exception("Thiếu event_id hoặc status.");
    }

    $database = new Database();
    $db = $database->connect();

    // Kiểm tra sự kiện có tồn tại không
    $stmtCheck = $db->prepare("SELECT id, trangthai, ghi_chu FROM ai_canhbao WHERE id = :id LIMIT 1");
    $stmtCheck->execute([':id' => $eventId]);
    $event = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception("Không tìm thấy sự kiện AI với ID #$eventId");
    }

    // Ghép ghi chú mới nếu có
    $finalNote = $note !== '' ? $note : ($event['ghi_chu'] ?? null);

    $stmtUpd = $db->prepare("
        UPDATE ai_canhbao 
        SET trangthai = :st, 
            ghi_chu = :note,
            thoigian_capnhat = NOW()
        WHERE id = :id
    ");
    $stmtUpd->execute([
        ':st'   => $status,
        ':note' => $finalNote,
        ':id'   => $eventId
    ]);

    echo json_encode([
        "status"   => "success",
        "message"  => "Cập nhật trạng thái sự kiện thành công.",
        "event_id" => $eventId,
        "new_status" => $status,
        "note"     => $finalNote
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
