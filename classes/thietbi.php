<?php
class Thietbi {
    private $conn;
    private $table = "thietbi"; 

    public function __construct($db) {
        $this->conn = $db;
    }
    public function taoThietBiMacDinh($user_id) {
        $serial = "UNO_" . rand(10000, 99999);

        $query = "INSERT INTO " . $this->table . " 
                  (tenthietbi, ma_serial, id, vitri, mota) 
                  VALUES (:ten, :serial, :uid, :vitri, :mota)";
        
        $stmt = $this->conn->prepare($query);
        
        $ten = "Vuon thuy canh AI";
        $vitri = "Mặc định";
        $mota = "Thiết bị tự động tạo khi đăng ký";

        $stmt->bindParam(':ten', $ten);
        $stmt->bindParam(':serial', $serial);
        $stmt->bindParam(':uid', $user_id); 
        $stmt->bindParam(':vitri', $vitri);
        $stmt->bindParam(':mota', $mota);

        if($stmt->execute()) {
            $device_id = $this->conn->lastInsertId();

            $query_ctrl = "INSERT INTO dieukhien (idthietbi, maybom, denled, quatgio) 
                           VALUES (:did, 0, 0, 0)";
            
            $stmt_ctrl = $this->conn->prepare($query_ctrl);
            $stmt_ctrl->bindParam(':did', $device_id);
            
            if($stmt_ctrl->execute()) {
                return $serial; 
            }
        }
        return false;
    }
}
?>