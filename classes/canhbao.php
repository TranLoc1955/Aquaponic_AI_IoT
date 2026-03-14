<?php

class CanhBao
{
    private $conn;
    private $table = "canhbao";
    public function __construct($db)
    {
        $this->conn = $db;
    }

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
