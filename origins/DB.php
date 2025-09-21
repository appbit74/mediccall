<?php

class DB {
    private $host = 'localhost';
    private $dbname = 'mediccall'; // ใส่ชื่อฐานข้อมูลของคุณที่นี่
    private $user = 'root';       // ใส่ชื่อผู้ใช้ฐานข้อมูลของคุณที่นี่
    private $pass = '';       // ใส่รหัสผ่านฐานข้อมูลของคุณที่นี่
    private $conn;

    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // Log the error or display a user-friendly message
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}