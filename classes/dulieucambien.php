<?php
class DuLieuCamBien {
    private $conn;
    private $table = 'dulieucambien';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function luuDuLieuTuDong($data) {
        if (empty($data->id)) return false; 
        $sql = "INSERT INTO " . $this->table . " (idthietbi, idcambien, giatri, thoigian) VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);

        $stmt->execute([$data->id, 1, (int)$data->nhietdo]);

        $stmt->execute([$data->id, 2, (int)$data->do_am]);

        $stmt->execute([$data->id, 4, (int)$data->anhsang]);

        $stmt->execute([$data->id, 3, (int)$data->pH]);

        return true;
    }
}
?>
        