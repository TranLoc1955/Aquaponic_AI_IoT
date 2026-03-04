<?php
class CamBien {
    private $conn;
    private $table = "cambien";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT * FROM " . $this->table;
        $command = $this->conn->prepare($query);
        $command->execute();
        return $command->fetchAll();
    }
    
    public function getId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE idcambien = ?";
        $command = $this->conn->prepare($query);
        $command->execute([$id]);
        return $command->fetch();
    }
    
    public function themmoi($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (idthietbi, loaicambien, donvido, trangthai) 
                  VALUES (?, ?, ?, ?)";
        
        $command = $this->conn->prepare($query);
        return $command->execute([
            $data['idthietbi'],
            $data['loaicambien'],
            $data['donvido'],
            $data['trangthai']
        ]);
    }
    
    public function capnhat($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET idthietbi = ?, loaicambien = ?, donvido = ?, trangthai = ? 
                  WHERE idcambien = ?";
        
        $command = $this->conn->prepare($query);
        return $command->execute([
            $data['idthietbi'],
            $data['loaicambien'],
            $data['donvido'],
            $data['trangthai'],
            $id
        ]);
    }
    
    public function xoa($id) {
        $query = "DELETE FROM " . $this->table . " WHERE idcambien = ?";
        $command = $this->conn->prepare($query);
        return $command->execute([$id]);
    }
}
?>