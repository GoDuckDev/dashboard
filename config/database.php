<?php
/**
 * Database Configuration และ Connection Class
 * ใช้สำหรับจัดการการเชื่อมต่อฐานข้อมูลอย่างปลอดภัย
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // การตั้งค่าฐานข้อมูล - ควรเก็บใน environment variables ในสภาพแวดล้อมจริง
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'secure_login_system';
    private const DB_USER = 'your_db_user';
    private const DB_PASS = 'your_strong_password';
    private const DB_CHARSET = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // ป้องกัน SQL Injection
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    // Singleton pattern เพื่อใช้ connection เดียวกัน
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // ป้องกัน clone
    private function __clone() {}
    
    // ป้องกัน unserialize
    private function __wakeup() {}
}

/**
 * Base Model Class
 */
abstract class BaseModel {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Execute prepared statement อย่างปลอดภัย
     */
    protected function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * ดึงข้อมูลแถวเดียว
     */
    protected function fetchOne($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * ดึงข้อมูลหลายแถว
     */
    protected function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute query แบบไม่ return ข้อมูล
     */
    protected function execute($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * ดึง last insert id
     */
    protected function getLastInsertId() {
        return $this->db->lastInsertId();
    }
    
    /**
     * เริ่ม transaction
     */
    protected function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    protected function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback transaction
     */
    protected function rollback() {
        return $this->db->rollback();
    }
}