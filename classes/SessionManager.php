<?php
require_once '../config/database.php';
require_once '../classes/SecurityHelper.php';

/**
 * Session Manager Class
 * จัดการ session อย่างปลอดภัยและเก็บข้อมูลในฐานข้อมูล
 */
class SessionManager extends BaseModel {
    
    private const SESSION_LIFETIME = 3600; // 1 ชั่วโมง
    private const CLEANUP_PROBABILITY = 1; // % โอกาสที่จะทำความสะอาด session หมดอายุ
    
    public function __construct() {
        parent::__construct();
        $this->configureSession();
        $this->startSession();
        $this->cleanupExpiredSessions();
    }
    
    /**
     * ตั้งค่า session
     */
    private function configureSession() {
        // ตั้งค่าความปลอดภัยของ session
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', self::SESSION_LIFETIME);
        
        // กำหนดชื่อ session cookie
        session_name('SECURE_SESSION_ID');
    }
    
    /**
     * เริ่ม session
     */
    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
            
            // ตรวจสอบและต่ออายุ session
            if (isset($_SESSION['user_id'])) {
                $this->validateAndRenewSession();
            }
        }
    }
    
    /**
     * สร้าง session สำหรับผู้ใช้
     */
    public function createUserSession($userId, $userData) {
        try {
            // Regenerate session ID เพื่อป้องกัน session fixation
            SecurityHelper::regenerateSessionId();
            
            $sessionId = session_id();
            $expiresAt = date('Y-m-d H:i:s', time() + self::SESSION_LIFETIME);
            
            // เก็บข้อมูล session ในฐานข้อมูล
            $sql = "INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, expires_at)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    last_activity = NOW(), expires_at = ?";
            
            $params = [
                $sessionId,
                $userId,
                SecurityHelper::getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                $expiresAt,
                $expiresAt
            ];
            
            $this->execute($sql, $params);
            
            // เก็บข้อมูลใน PHP session
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $userData['username'];
            $_SESSION['role_id'] = $userData['role_id'];
            $_SESSION['session_start'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = SecurityHelper::getClientIP();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            return true;
            
        } catch (Exception $e) {
            error_log("Session creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ตรวจสอบและต่ออายุ session
     */
    private function validateAndRenewSession() {
        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            $this->destroySession();
            return false;
        }
        
        // ตรวจสอบ session ในฐานข้อมูล
        $sql = "SELECT * FROM user_sessions 
                WHERE session_id = ? AND user_id = ? AND expires_at > NOW()";
        
        $session = $this->fetchOne($sql, [$sessionId, $userId]);
        
        if (!$session) {
            $this->destroySession();
            return false;
        }
        
        // ตรวจสอบ IP และ User Agent เพื่อป้องกัน session hijacking
        if ($session['ip_address'] !== SecurityHelper::getClientIP() ||
            $session['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown')) {
            
            SecurityHelper::logSecurityEvent('session_hijack_attempt', [
                'user_id' => $userId,
                'session_ip' => $session['ip_address'],
                'current_ip' => SecurityHelper::getClientIP()
            ]);
            
            $this->destroySession();
            return false;
        }
        
        // ต่ออายุ session ถ้าใช้งานมาแล้วครึ่งหนึ่งของอายุ
        $timeSinceStart = time() - $_SESSION['session_start'];
        if ($timeSinceStart > (self::SESSION_LIFETIME / 2)) {
            $this->renewSession();
        }
        
        // อัปเดตเวลากิจกรรมล่าสุด
        $this->updateLastActivity($sessionId);
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * ต่ออายุ session
     */
    private function renewSession() {
        SecurityHelper::regenerateSessionId();
        
        $newSessionId = session_id();
        $oldSessionId = $_SESSION['old_session_id'] ?? '';
        $userId = $_SESSION['user_id'];
        
        // อัปเดต session ID ในฐานข้อมูล
        $sql = "UPDATE user_sessions 
                SET session_id = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
                WHERE user_id = ? AND expires_at > NOW()";
        
        $this->execute($sql, [$newSessionId, self::SESSION_LIFETIME, $userId]);
        
        $_SESSION['session_start'] = time();
    }
    
    /**
     * อัปเดตเวลากิจกรรมล่าสุด
     */
    private function updateLastActivity($sessionId) {
        $sql = "UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?";
        $this->execute($sql, [$sessionId]);
    }
    
    /**
     * ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $this->validateSession();
    }
    
    /**
     * ตรวจสอบความถูกต้องของ session
     */
    private function validateSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // ตรวจสอบว่า session หมดอายุหรือไม่
        if ((time() - $_SESSION['last_activity']) > self::SESSION_LIFETIME) {
            $this->destroySession();
            return false;
        }
        
        return true;
    }
    
    /**
     * ดึงข้อมูลผู้ใช้จาก session
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role_id' => $_SESSION['role_id']
        ];
    }
    
    /**
     * ทำลาย session
     */
    public function destroySession() {
        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;
        
        // ลบ session จากฐานข้อมูล
        if ($sessionId) {
            $sql = "DELETE FROM user_sessions WHERE session_id = ?";
            $this->execute($sql, [$sessionId]);
        }
        
        // ลบข้อมูลทั้งหมดใน session
        $_SESSION = [];
        
        // ทำลาย session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // ทำลาย session
        session_destroy();
        
        if ($userId) {
            SecurityHelper::logSecurityEvent('user_logout', ['user_id' => $userId]);
        }
    }
    
    /**
     * ลบ session ทั้งหมดของผู้ใช้ (เช่น เมื่อเปลี่ยนรหัสผ่าน)
     */
    public function destroyAllUserSessions($userId) {
        $sql = "DELETE FROM user_sessions WHERE user_id = ?";
        $this->execute($sql, [$userId]);
        
        SecurityHelper::logSecurityEvent('all_sessions_destroyed', ['user_id' => $userId]);
    }
    
    /**
     * ทำความสะอาด session หมดอายุ
     */
    private function cleanupExpiredSessions() {
        // ทำความสะอาดตามความน่าจะเป็นที่กำหนด
        if (rand(1, 100) <= self::CLEANUP_PROBABILITY) {
            $sql = "DELETE FROM user_sessions WHERE expires_at < NOW()";
            $deletedCount = $this->execute($sql);
            
            if ($deletedCount > 0) {
                error_log("Cleaned up {$deletedCount} expired sessions");
            }
        }
    }
    
    /**
     * ดึงรายการ session ที่ active ของผู้ใช้
     */
    public function getActiveSessions($userId) {
        $sql = "SELECT session_id, ip_address, user_agent, created_at, last_activity
                FROM user_sessions 
                WHERE user_id = ? AND expires_at > NOW()
                ORDER BY last_activity DESC";
        
        return $this->fetchAll($sql, [$userId]);
    }
    
    /**
     * ลบ session ที่เฉพาะเจาะจง
     */
    public function destroySpecificSession($userId, $sessionId) {
        $sql = "DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?";
        return $this->execute($sql, [$userId, $sessionId]) > 0;
    }
}