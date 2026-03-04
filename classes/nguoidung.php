<?php
class Nguoidung {
    private $conn;
    private $table = "nguoidung"; 

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
    
        $query = "SELECT id, hoten, email, role, created_at FROM " . $this->table;
        $command = $this->conn->prepare($query);
        $command->execute();
        return $command->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getId($id) {
        $query = "SELECT id, hoten, email, role, created_at FROM " . $this->table . " WHERE id = ?";
        $command = $this->conn->prepare($query);
        $command->execute([$id]);
        return $command->fetch(PDO::FETCH_ASSOC);
    }

    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = ?";
        $command = $this->conn->prepare($query);
        $command->execute([$email]);
        return $command->fetch(PDO::FETCH_ASSOC);
    }

    public function themmoi($data) {
        $query = "INSERT INTO " . $this->table . " (hoten, email, matkhau, sodienthoai, vaitro) VALUES (?, ?, ?, ?, ?)";
        $command = $this->conn->prepare($query);
        $khoamatkhau = password_hash($data['matkhau'], PASSWORD_DEFAULT);
        $sodienthoai = isset($data['sodienthoai']) ? $data['sodienthoai'] : null;
        
        if ($command->execute([
            $data['hoten'], 
            $data['email'],    
            $khoamatkhau,
            $sodienthoai,
            $data['vaitro']
        ])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function capnhat($id, $data) {
        $query = "UPDATE " . $this->table . " SET hoten = ?, email = ?, vaitro = ? WHERE id = ?";
        $command = $this->conn->prepare($query);
        
        return $command->execute([
            $data['hoten'], 
            $data['email'],    
            $data['vaitro'], 
            $id
        ]);
    }

    public function capnhatPassword($id, $newPassword) {
        $query = "UPDATE " . $this->table . " SET matkhau = ? WHERE id = ?";
        $command = $this->conn->prepare($query);
        $khoamatkhau = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $command->execute([$khoamatkhau, $id]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function login($email, $password) {
        $user = $this->getByEmail($email);

        if ($user) {
            
            if (password_verify($password, $user['matkhau'])) {
                unset($user['matkhau']); 
                return $user;
            }
        }
        return false;
    }
}
?>