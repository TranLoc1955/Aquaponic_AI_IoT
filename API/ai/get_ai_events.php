<?php
/**
 * API: get_ai_events.php
 * Lấy danh sách sự kiện bất thường AI có lọc đa chiều, thống kê và phân trang.
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/../../config/connect.php';

try {
    $user_id    = !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null;
    $time_range = $_GET['time_range'] ?? 'all';
    $from_date  = $_GET['from_date'] ?? null;
    $to_date    = $_GET['to_date'] ?? null;
    $severity   = strtolower($_GET['severity'] ?? 'all');
    $sensor     = strtolower($_GET['sensor'] ?? 'all');
    $status     = $_GET['status'] ?? 'all';
    $source     = strtolower($_GET['source'] ?? 'all');
    $page       = max(1, (int) ($_GET['page'] ?? 1));
    $limit      = max(1, min(100, (int) ($_GET['limit'] ?? 10)));
    $offset     = ($page - 1) * $limit;

    $database = new Database();
    $db = $database->connect();

    // 1. Lấy ID thiết bị của người dùng (fallback thiết bị có dữ liệu nếu chưa gắn hoặc thiết bị của user chưa có dữ liệu AI)
    $device = null;
    if ($user_id) {
        // Ưu tiên thiết bị của user này mà CÓ dữ liệu trong ai_canhbao
        $stmt_user_ai = $db->prepare("
            SELECT t.idthietbi 
            FROM thietbi t 
            INNER JOIN ai_canhbao c ON t.idthietbi = c.idthietbi 
            WHERE t.id = :uid 
            GROUP BY t.idthietbi 
            ORDER BY COUNT(*) DESC 
            LIMIT 1
        ");
        $stmt_user_ai->execute([':uid' => $user_id]);
        $device = $stmt_user_ai->fetch(PDO::FETCH_ASSOC);

        if (!$device) {
            $stmt_find = $db->prepare("SELECT idthietbi FROM thietbi WHERE id = :uid LIMIT 1");
            $stmt_find->execute([':uid' => $user_id]);
            $device = $stmt_find->fetch(PDO::FETCH_ASSOC);
        }
    }

    // Nếu thiết bị được chọn hoàn toàn không có bản ghi ai_canhbao nào:
    // Tự động fallback sang thiết bị có dữ liệu AI thực tế trong hệ thống (như thiết bị #18)
    if ($device) {
        $stmt_chk = $db->prepare("SELECT COUNT(*) FROM ai_canhbao WHERE idthietbi = :did");
        $stmt_chk->execute([':did' => $device['idthietbi']]);
        if ((int)$stmt_chk->fetchColumn() === 0) {
            $device = null;
        }
    }

    if (!$device) {
        // Ưu tiên thiết bị có dữ liệu trong ai_canhbao
        $stmt_active = $db->query("SELECT idthietbi FROM ai_canhbao GROUP BY idthietbi ORDER BY COUNT(*) DESC LIMIT 1");
        $device = $stmt_active->fetch(PDO::FETCH_ASSOC);
    }

    if (!$device) {
        $stmt_def = $db->query("SELECT idthietbi FROM thietbi ORDER BY idthietbi DESC LIMIT 1");
        $device = $stmt_def->fetch(PDO::FETCH_ASSOC);
    }

    if (!$device) {
        echo json_encode([
            "status"     => "success",
            "stats"      => ["total" => 0, "warning" => 0, "danger" => 0, "unresolved" => 0, "resolved" => 0],
            "pagination" => ["page" => 1, "limit" => $limit, "total_records" => 0, "total_pages" => 0],
            "data"       => []
        ]);
        exit();
    }
    $device_id = (int) $device['idthietbi'];

    // 2. Thống kê tổng quan (không phụ thuộc vào bộ lọc đang chọn để người dùng luôn thấy bức tranh chung)
    $stmt_stats = $db->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN mucdo = 'danger' THEN 1 ELSE 0 END) AS danger_count,
            SUM(CASE WHEN mucdo = 'warning' THEN 1 ELSE 0 END) AS warning_count,
            SUM(CASE WHEN trangthai = 0 THEN 1 ELSE 0 END) AS unresolved_count,
            SUM(CASE WHEN trangthai = 1 THEN 1 ELSE 0 END) AS resolved_count
        FROM ai_canhbao 
        WHERE idthietbi = :did
    ");
    $stmt_stats->execute([':did' => $device_id]);
    $stats_row = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    $stats = [
        "total"      => (int) ($stats_row['total'] ?? 0),
        "danger"     => (int) ($stats_row['danger_count'] ?? 0),
        "warning"    => (int) ($stats_row['warning_count'] ?? 0),
        "unresolved" => (int) ($stats_row['unresolved_count'] ?? 0),
        "resolved"   => (int) ($stats_row['resolved_count'] ?? 0),
    ];

    // 3. Xây dựng câu truy vấn có điều kiện lọc
    $whereConditions = ["c.idthietbi = :did"];
    $params = [':did' => $device_id];

    // Lọc thời gian
    if ($time_range === 'today') {
        $whereConditions[] = "DATE(c.thoigian) = CURDATE()";
    } elseif ($time_range === '7days') {
        $whereConditions[] = "c.thoigian >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($time_range === '30days') {
        $whereConditions[] = "c.thoigian >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    } elseif ($time_range === 'custom' && $from_date && $to_date) {
        $whereConditions[] = "DATE(c.thoigian) BETWEEN :from_date AND :to_date";
        $params[':from_date'] = $from_date;
        $params[':to_date']   = $to_date;
    }

    // Lọc mức độ
    if ($severity === 'warning' || $severity === 'danger') {
        $whereConditions[] = "c.mucdo = :severity";
        $params[':severity'] = $severity;
    }

    // Lọc trạng thái xử lý
    if ($status === '0' || $status === '1') {
        $whereConditions[] = "c.trangthai = :status";
        $params[':status'] = (int) $status;
    }

    // Lọc loại cảm biến
    if ($sensor !== 'all') {
        if ($sensor === 'nhiet_do') {
            $whereConditions[] = "(c.cam_bien LIKE '%nhiet_do%' OR c.cam_bien LIKE '%nhietdo%' OR c.noidung LIKE '%Nhiệt độ%')";
        } elseif ($sensor === 'do_am') {
            $whereConditions[] = "(c.cam_bien LIKE '%do_am%' OR c.noidung LIKE '%Độ ẩm%')";
        } elseif ($sensor === 'anh_sang') {
            $whereConditions[] = "(c.cam_bien LIKE '%anh_sang%' OR c.cam_bien LIKE '%anhsang%' OR c.noidung LIKE '%Ánh sáng%')";
        } elseif ($sensor === 'muc_nuoc') {
            $whereConditions[] = "(c.cam_bien LIKE '%muc_nuoc%' OR c.noidung LIKE '%Mực nước%')";
        }
    }

    // Lọc theo Nguồn AI (Edge IoT vs Web)
    if ($source === 'edge') {
        $whereConditions[] = "(k.engine LIKE 'edge%' OR k.engine IS NULL)";
    } elseif ($source === 'web') {
        $whereConditions[] = "k.engine LIKE 'web%'";
    }

    $whereSql = implode(" AND ", $whereConditions);

    // 4. Đếm số bản ghi khớp bộ lọc (join ai_ket_qua k để lọc được theo engine)
    $stmt_count = $db->prepare("SELECT COUNT(*) AS total_filtered FROM ai_canhbao c LEFT JOIN ai_ket_qua k ON c.id_ketqua = k.id WHERE $whereSql");
    $stmt_count->execute($params);
    $total_filtered = (int) $stmt_count->fetch(PDO::FETCH_ASSOC)['total_filtered'];
    $total_pages    = $total_filtered > 0 ? (int) ceil($total_filtered / $limit) : 0;

    // 5. Lấy danh sách bản ghi theo trang
    $sql_data = "
        SELECT 
            c.id,
            c.idthietbi,
            c.anomaly_type,
            c.anomaly_type AS loai_canhbao,
            c.cam_bien,
            c.gia_tri_cambien,
            c.nguong_apdung,
            c.mucdo,
            c.mucdo AS muc_do,
            c.action_device,
            c.action_command,
            c.trangthai,
            c.ghi_chu,
            c.so_lan_lap,
            c.thoigian,
            c.thoigian_capnhat,
            c.noidung,
            k.nhietdo,
            k.do_am,
            k.anhsang,
            k.muc_nuoc,
            k.mode,
            k.time_window,
            k.engine,
            k.confidence,
            k.analysis_summary,
            k.action_reason,
            k.recommendation_action,
            k.recommendation_message
        FROM ai_canhbao c
        LEFT JOIN ai_ket_qua k ON c.id_ketqua = k.id
        WHERE $whereSql
        ORDER BY c.thoigian DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt_data = $db->prepare($sql_data);
    foreach ($params as $key => $val) {
        $stmt_data->bindValue($key, $val);
    }
    $stmt_data->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt_data->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_data->execute();
    $rows = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"     => "success",
        "device_id"  => $device_id,
        "stats"      => $stats,
        "pagination" => [
            "page"          => $page,
            "limit"         => $limit,
            "total_records" => $total_filtered,
            "total_pages"   => $total_pages
        ],
        "data"       => $rows
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
