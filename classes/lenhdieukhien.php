<?php
class LenhDieuKhien {
    private $conn;
    private $table = "lenhdieukhien";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table;
        $command = $this->conn->prepare($query);
        $command->execute();
        return $command->fetchAll();
    }

    // Lấy theo ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE idlenh = ?";
        $command = $this->conn->prepare($query);
        $command->execute([$id]);
        return $command->fetch();
    }

    // Lấy lệnh theo thiết bị
    public function getByThietBi($idthietbi) {
        $query = "SELECT * FROM " . $this->table . " WHERE idthietbi = ? ORDER BY thoigian DESC";
        $command = $this->conn->prepare($query);
        $command->execute([$idthietbi]);
        return $command->fetchAll();
    }

    // Lấy lệnh theo trạng thái
    public function getByTrangThai($trangthai) {
        $query = "SELECT * FROM " . $this->table . " WHERE trangthai = ?";
        $command = $this->conn->prepare($query);
        $command->execute([$trangthai]);
        return $command->fetchAll();
    }

    // Thêm mới
    public function themmoi($data) {
        $query = "INSERT INTO " . $this->table . " (idthietbi, lenh, trangthai, thoigian) VALUES (?, ?, ?, ?)";
        $command = $this->conn->prepare($query);
        return $command->execute([$data['idthietbi'], $data['lenh'], $data['trangthai'], $data['thoigian']]);
    }

    // Cập nhật
    public function capnhat($id, $data) {
        $query = "UPDATE " . $this->table . " SET idthietbi = ?, lenh = ?, trangthai = ?, thoigian = ? WHERE idlenh = ?";
        $command = $this->conn->prepare($query);
        return $command->execute([$data['idthietbi'], $data['lenh'], $data['trangthai'], $data['thoigian'], $id]);
    }

    // Cập nhật trạng thái
    public function capnhatTT($id, $trangthai) {
        $query = "UPDATE " . $this->table . " SET trangthai = ? WHERE idlenh = ?";
        $command = $this->conn->prepare($query);
        return $command->execute([$trangthai, $id]);
    }

    // Xóa
    public function xoa($id) {
        $query = "DELETE FROM " . $this->table . " WHERE idlenh = ?";
        $command = $this->conn->prepare($query);
        return $command->execute([$id]);
    }
}

?>