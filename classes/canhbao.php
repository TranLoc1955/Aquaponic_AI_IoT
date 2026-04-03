<?php

class CanhBao
{
    private $conn;
    private $table = "canhbao";
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     
     * @param int $idthietbi ID thiết bị
     * @param int $idcambien ID cảm biến
     * @param mixed $giatri Giá trị vượt ngưỡng
     * @param string $noidung Nội dung cảnh báo
     * @param string $mucdo danger | warning
     */
    public function themCanhBaoFull($idthietbi, $idcambien, $giatri, $noidung, $mucdo)
    {
        $sql = "INSERT INTO {$this->table}
                (idthietbi, idcambien, giatri, noidung, mucdo, trangthai, thoigian)
                VALUES
                (:idthietbi, :idcambien, :giatri, :noidung, :mucdo, 0, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':idthietbi' => $idthietbi,
            ':idcambien' => $idcambien,
            ':giatri'    => $giatri,
            ':noidung'   => $noidung,
            ':mucdo'     => $mucdo
        ]);
    }

    public function themCanhBao($id_cambien, $giatri, $noidung, $mucdo)
    {
        $sql = "INSERT INTO {$this->table}
                (idcambien, giatri, noidung, mucdo, trangthai, thoigian)
                VALUES
                (:idcambien, :giatri, :noidung, :mucdo, 0, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':idcambien' => $id_cambien,
            ':giatri'    => $giatri,
            ':noidung'   => $noidung,
            ':mucdo'     => $mucdo
        ]);
    }

    public function layCanhBaoMoi()
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE trangthai = 0
                ORDER BY thoigian DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    public function layCanhBaoTheoCamBien($id_cambien)
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE idcambien = :idcambien
                ORDER BY thoigian DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idcambien', $id_cambien);
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
