<?php

class Database{
    private $host = "localhost";
    private $name = "demothuycanh";
    private $user = "root";
    private $password = "";
    private $conn;

    public  function connect(){
        $this ->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->name . ";charset=utf8mb4",
                $this->user,
                $this->password
            );
             $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
             $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            echo "Lỗi kết nối: " . $e->getMessage();
            die();
        }
        return $this->conn;
    }
}
?>