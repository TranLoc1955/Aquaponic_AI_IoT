<?php
class DuLieuCamBien {
    private $conn;
    private $table = 'dulieucambien';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function luuDuLieuTuDong($data) {
        $id = isset($data->idthietbi) ? $data->idthietbi : (isset($data->id) ? $data->id : null);
        if ($id === null || $id === '') return false;
        $id = (int) $id;

        $sql = "INSERT INTO " . $this->table . " (idthietbi, idcambien, giatri, thoigian) VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);

        $stmt->execute([$id, 1, isset($data->nhietdo) ? (int)$data->nhietdo : 0]);
        $stmt->execute([$id, 2, isset($data->do_am) ? (int)$data->do_am : 0]);
        $stmt->execute([$id, 4, isset($data->anhsang) ? (int)$data->anhsang : 0]);
        $stmt->execute([$id, 3, isset($data->pH) ? (float)$data->pH : 0]);

        return true;
    }
}
?>
        