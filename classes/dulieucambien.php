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

        $stmt->execute([$id, 1, isset($data->nhiet_do) ? (int)$data->nhiet_do : 0]);
        $stmt->execute([$id, 2, isset($data->do_am) ? (int)$data->do_am : 0]);
        $stmt->execute([$id, 4, isset($data->anh_sang) ? (int)$data->anh_sang : 0]);
        $stmt->execute([$id, 3, isset($data->muc_nuoc) ? (float)$data->muc_nuoc : 0]);

        return true;
    }
}
?>
        