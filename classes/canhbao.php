<?php

class CanhBao
{
    private $conn;
    private $table = "canhbao";

    // Constructor nhận connection
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // 1️⃣ Thêm cảnh báo mới
    public function themCanhBao($id_cambien, $giatri, $noidung, $mucdo)
    {
        $sql = "INSERT INTO {$this->table}
                (id_cambien, giatri, noidung, mucdo, trangthai, thoigian)
                VALUES
                (:id_cambien, :giatri, :noidung, :mucdo, 'MOI', NOW())";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id_cambien' => $id_cambien,
            ':giatri'     => $giatri,
            ':noidung'    => $noidung,
            ':mucdo'      => $mucdo
        ]);
    }

    // 2️⃣ Lấy danh sách cảnh báo MỚI
    public function layCanhBaoMoi()
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE trangthai = 'MOI'
                ORDER BY thoigian DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    // 3️⃣ Lấy cảnh báo theo cảm biến
    public function layCanhBaoTheoCamBien($id_cambien)
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE id_cambien = :id_cambien
                ORDER BY thoigian DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_cambien', $id_cambien);
        $stmt->execute();

        return $stmt;
    }

    // 4️⃣ Cập nhật trạng thái cảnh báo
    public function capNhatTrangThai($id_canhbao, $trangthai)
    {
        $sql = "UPDATE {$this->table}
                SET trangthai = :trangthai
                WHERE id_canhbao = :id_canhbao";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':trangthai'   => $trangthai,
            ':id_canhbao'  => $id_canhbao
        ]);
    }

    // 5️⃣ Lấy tất cả cảnh báo (admin)
    public function layTatCa()
    {
        $sql = "SELECT *
                FROM {$this->table}
                ORDER BY thoigian DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }
}
