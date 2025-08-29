<?php
require_once '../config/database.php';
require_once '../classes/SecurityHelper.php';

/**
 * User Model Class
 * จัดการข้อมูลผู้ใช้และการตรวจสอบสิทธิ์
 */
class UserModel extends BaseModel {
    
    /**
     * ตรวจสอบ username และ password
     */
    public function authenticateUser($username, $password) {
        try {
            // ดึงข้อมูลผู้ใช้
            $sql = "SELECT id, username, password_hash, salt, role_id, status, 
                           failed_attempts, account_locked_until 
                    FROM users 
                    WHERE username = ? AND status = 'active'";
            
            $user = $this->fetchOne($sql, [$username]);
            
            if (!$user) {
                $this->logFailedAttempt($username, 'User not found');
                return false;
            }
            
            // ตรวจสอบว่าบัญชีถูกล็อคหรือไม่
            if ($this->isAccountLocked($user)) {
                $this->logFailedAttempt($username, 'Account locked', $user['id']);
                return ['error' => 'Account is temporarily locked. Please try again later.'];
            }
            
            // ตรวจสอบรหัสผ่าน
            if (!SecurityHelper::verifyPassword($password, $user['password_hash'], $user['salt'])) {
                $this->handleFailedLogin($user['id'], $username);
                return false;
            }
            
            // ล็อกอินสำเร็จ - รีเซ็ตจำนวนครั้งที่ล็อกอินไม่สำเร็จ
            $this->resetFailedAttempts($user['id']);
            $this->updateLastLogin($user['id']);
            $this->logSuccessfulLogin($user['id'], $username);
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * สร้างผู้ใช้ใหม่
     */
    public function createUser($username, $email, $password, $roleId = 3) {
        try {
            // ตรวจสอบว่า username หรือ email ซ้ำหรือไม่
            if ($this->isUsernameExists($username)) {
                return ['error' => 'Username already exists'];
            }
            
            if ($this->isEmailExists($email)) {
                return ['error' => 'Email already exists'];
            }
            
            // Hash password
            $passwordData = SecurityHelper::hashPassword($password);
            
            $sql = "INSERT INTO users (username, email, password_hash, salt, role_id) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $params = [
                $username,
                $email,
                $passwordData['hash'],
                $passwordData['salt'],
                $roleId
            ];
            
            $this->execute($sql, $params);
            
            return ['success' => true, 'user_id' => $this->getLastInsertId()];
            
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            return ['error' => 'Failed to create user'];
        }
    }
    
    /**
     * ดึงข้อมูลผู้ใช้พร้อมสิทธิ์
     */
    public function getUserWithPermissions($userId) {
        $sql = "SELECT u.id, u.username, u.email, r.role_name, u.status,
                       GROUP_CONCAT(p.permission_name) as permissions
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN role_permissions rp ON r.id = rp.role_id
                LEFT JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = ? AND u.status = 'active'
                GROUP BY u.id";
        
        $user = $this->fetchOne($sql, [$userId]);
        
        if ($user && $user['permissions']) {
            $user['permissions'] = explode(',', $user['permissions']);
        } else {
            $user['permissions'] = [];
        }
        
        return $user;
    }
    
    /**
     * ตรวจสอบสิทธิ์ของผู้ใช้
     */
    public function hasPermission($userId, $permission) {
        $sql = "SELECT COUNT(*) as count
                FROM users u
                JOIN roles r ON u.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = ? AND p.permission_name = ? AND u.status = 'active'";
        
        $result = $this->fetchOne($sql, [$userId, $permission]);
        return $result['count'] > 0;
    }
    
    /**
     * ตรวจสอบว่า username มีอยู่แล้วหรือไม่
     */
    public function isUsernameExists($username) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $result = $this->fetchOne($sql, [$username]);
        return $result['count'] > 0;
    }
    
    /**
     * ตรวจสอบว่า email มีอยู่แล้วหรือไม่
     */
    public function isEmailExists($email) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $result = $this->fetchOne($sql, [$email]);
        return $result['count'] > 0;
    }
    
    /**
     * ตรวจสอบว่าบัญชีถูกล็อคหรือไม่
     */
    private function isAccountLocked($user) {
        if ($user['account_locked_until'] === null) {
            return false;
        }
        
        $lockUntil = strtotime($user['account_locked_until']);
        return time() < $lockUntil;
    }
    
    /**
     * จัดการเมื่อล็อกอินไม่สำเร็จ
     */
    private function handleFailedLogin($userId, $username) {
        $failedAttempts = $this->incrementFailedAttempts($userId);
        $this->logFailedAttempt($username, 'Invalid password', $userId);
        
        // ล็อคบัญชีถ้าพยายามล็อกอินผิดเกิน 5 ครั้งใน 15 นาที
        if ($failedAttempts >= 5) {
            $this->lockAccount($userId, 15); // ล็อค 15 นาที
            SecurityHelper::logSecurityEvent('account_locked', ['user_id' => $userId]);
        }
    }
    
    /**
     * เพิ่มจำนวนครั้งที่ล็อกอินไม่สำเร็จ
     */
    private function incrementFailedAttempts($userId) {
        $sql = "UPDATE users 
                SET failed_attempts = failed_attempts + 1, 
                    last_failed_attempt = NOW()
                WHERE id = ?";
        
        $this->execute($sql, [$userId]);
        
        // ดึงจำนวนครั้งล่าสุด
        $result = $this->fetchOne("SELECT failed_attempts FROM users WHERE id = ?", [$userId]);
        return $result['failed_attempts'];
    }
    
    /**
     * รีเซ็ตจำนวนครั้งที่ล็อกอินไม่สำเร็จ
     */
    private function resetFailedAttempts($userId) {
        $sql = "UPDATE users 
                SET failed_attempts = 0, 
                    last_failed_attempt = NULL,
                    account_locked_until = NULL
                WHERE id = ?";
        
        $this->execute($sql, [$userId]);
    }
    
    /**
     * ล็อคบัญชีชั่วคราว
     */
    private function lockAccount($userId, $minutes = 15) {
        $sql = "UPDATE users 
                SET account_locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                WHERE id = ?";
        
        $this->execute($sql, [$minutes, $userId]);
    }
    
    /**
     * อัปเดตเวลาล็อกอินล่าสุด
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $this->execute($sql, [$userId]);
    }
    
    /**
     * บันทึก log การล็อกอินสำเร็จ
     */
    private function logSuccessfulLogin($userId, $username) {
        $sql = "INSERT INTO login_logs (user_id, username, ip_address, user_agent, login_status)
                VALUES (?, ?, ?, ?, 'success')";
        
        $params = [
            $userId,
            $username,
            SecurityHelper::getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        $this->execute($sql, $params);
    }
    
    /**
     * บันทึก log การล็อกอินไม่สำเร็จ
     */
    private function logFailedAttempt($username, $reason, $userId = null) {
        $sql = "INSERT INTO login_logs (user_id, username, ip_address, user_agent, login_status, failure_reason)
                VALUES (?, ?, ?, ?, 'failed', ?)";
        
        $params = [
            $userId,
            $username,
            SecurityHelper::getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            $reason
        ];
        
        $this->execute($sql, $params);
    }
}