<?php
/**
 * Security Helper Class
 * จัดการเรื่องความปลอดภัยต่างๆ เช่น encryption, hashing, CSRF protection
 */

class SecurityHelper {
    
    // CSRF Token management
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    public static function validateCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // ตรวจสอบว่า token มีอยู่และไม่หมดอายุ (5 นาที)
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) ||
            (time() - $_SESSION['csrf_token_time']) > 300) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Password hashing และ verification
    public static function hashPassword($password, $salt = null) {
        if ($salt === null) {
            $salt = bin2hex(random_bytes(16));
        }
        
        // ใช้ PBKDF2 กับ SHA-256
        $hash = hash_pbkdf2('sha256', $password, $salt, 10000, 64);
        
        return [
            'hash' => $hash,
            'salt' => $salt
        ];
    }
    
    public static function verifyPassword($password, $hash, $salt) {
        $computedHash = hash_pbkdf2('sha256', $password, $salt, 10000, 64);
        return hash_equals($hash, $computedHash);
    }
    
    // Input validation และ sanitization
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        // ตัดช่องว่างและแปลง special characters
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateUsername($username) {
        // Username: 3-50 ตัวอักษร, อนุญาตเฉพาะ a-z, A-Z, 0-9, underscore, dash
        return preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username);
    }
    
    public static function validatePassword($password) {
        // Password: อย่างน้อย 8 ตัว, มีตัวพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข และสัญลักษณ์
        return strlen($password) >= 8 && 
               preg_match('/[a-z]/', $password) &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^a-zA-Z0-9]/', $password);
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Rate limiting
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . $identifier;
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        // ลบ attempts ที่หมดอายุ
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // ตรวจสอบว่าเกิน limit หรือไม่
        if (count($_SESSION[$key]) >= $maxAttempts) {
            return false;
        }
        
        // เพิ่ม attempt ใหม่
        $_SESSION[$key][] = $now;
        return true;
    }
    
    // IP Address validation
    public static function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Session security
    public static function generateSecureSessionId() {
        return bin2hex(random_bytes(32));
    }
    
    public static function regenerateSessionId() {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    // XSS Protection
    public static function escapeOutput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escapeOutput'], $data);
        }
        
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    // Generate random token
    public static function generateRandomToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Time-safe string comparison
    public static function timeSafeCompare($known, $provided) {
        return hash_equals($known, $provided);
    }
    
    // Log security events
    public static function logSecurityEvent($event, $details = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'event' => $event,
            'details' => $details
        ];
        
        error_log("SECURITY_EVENT: " . json_encode($logData));
    }
}