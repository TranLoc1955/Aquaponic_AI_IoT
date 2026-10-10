<?php
class DuLieuCamBien {
    private $conn;
    private $table = 'dulieucambien';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function luuDuLieuTuDong($data) {
    try {
        $id = isset($data->idthietbi) ? $data->idthietbi : (isset($data->id) ? $data->id : null);
        if ($id === null || $id === '') return false;
        $id = (int)$id;

        $sql = "INSERT INTO " . $this->table . " (idthietbi, idcambien, giatri, thoigian)
                VALUES (?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([$id, 1, (float)($data->nhiet_do ?? 0)]);
        $stmt->execute([$id, 2, (float)($data->do_am ?? 0)]);
        $stmt->execute([$id, 4, (float)($data->anh_sang ?? 0)]);
        $stmt->execute([$id, 3, (float)($data->muc_nuoc ?? 0)]);

        return true;

    } catch (PDOException $e) {
        error_log("Lỗi lưu dữ liệu cảm biến: " . $e->getMessage());
        return false;
    }
}
}
?>
        