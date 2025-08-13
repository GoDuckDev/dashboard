<?php
require_once '../models/UserModel.php';
require_once '../classes/SessionManager.php';
require_once '../classes/SecurityHelper.php';

/**
 * Authentication Controller
 * จัดการการล็อกอิน ล็อกเอาท์ และการยืนยันตัวตน
 */
class AuthController {
    
    private $userModel;
    private $sessionManager;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->sessionManager = new SessionManager();
    }
    
    /**
     * จัดการการล็อกอิน
     */
    public function login() {
        // ตรวจสอบ method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['error' => 'Invalid request method'], 405);
            return;
        }
        
        // ตรวจสอบ Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            $this->sendResponse(['error' => 'Invalid content type'], 400);
            return;
        }
        
        // ดึงข้อมูลจาก request body
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->sendResponse(['error' => 'Invalid JSON data'], 400);
            return;
        }
        
        // Sanitize input
        $input = SecurityHelper::sanitizeInput($input);
        
        // ตรวจสอบ CSRF token
        if (!isset($input['csrf_token']) || !SecurityHelper::validateCSRFToken($input['csrf_token'])) {
            SecurityHelper::logSecurityEvent('csrf_token_invalid', ['ip' => SecurityHelper::getClientIP()]);
            $this->sendResponse(['error' => 'Invalid security token'], 403);
            return;
        }
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($input['username']) || empty($input['password'])) {
            $this->sendResponse(['error' => 'Username and password are required'], 400);
            return;
        }
        
        $username = $input['username'];
        $password = $input['password'];
        
        // ตรวจสอบ rate limiting
        $clientIP = SecurityHelper::getClientIP();
        if (!SecurityHelper::checkRateLimit('login_' . $clientIP, 5, 900)) { // 5 attempts per 15 minutes
            SecurityHelper::logSecurityEvent('rate_limit_exceeded', [
                'type' => 'login',
                'ip' => $clientIP,
                'username' => $username
            ]);
            $this->sendResponse(['error' => 'Too many login attempts. Please try again later.'], 429);
            return;
        }
        
        // ตรวจสอบ username format
        if (!SecurityHelper::validateUsername($username)) {
            $this->sendResponse(['error' => 'Invalid username format'], 400);
            return;
        }
        
        // พยายามยืนยันตัวตน
        $authResult = $this->userModel->authenticateUser($username, $password);
        
        if ($authResult === false) {
            $this->sendResponse(['error' => 'Invalid username or password'], 401);
            return;
        }
        
        if (is_array($authResult) && isset($authResult['error'])) {
            $this->sendResponse(['error' => $authResult['error']], 423); // Locked
            return;
        }
        
        // ล็อกอินสำเร็จ - สร้าง session
        if ($this->sessionManager->createUserSession($authResult['id'], $authResult)) {
            
            // ดึงข้อมูลผู้ใช้พร้อมสิทธิ์
            $userWithPermissions = $this->userModel->getUserWithPermissions($authResult['id']);
            
            SecurityHelper::logSecurityEvent('successful_login', [
                'user_id' => $authResult['id'],
                'username' => $username
            ]);
            
            $this->sendResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $userWithPermissions['id'],
                    'username' => $userWithPermissions['username'],
                    'role' => $userWithPermissions['role_name'],
                    'permissions' => $userWithPermissions['permissions']
                ],
                'redirect' => '/dashboard.php'
            ]);
            
        } else {
            $this->sendResponse(['error' => 'Failed to create session'], 500);
        }
    }
    
    /**
     * จัดการการล็อกเอาท์
     */
    public function logout() {
        // ตรวจสอบว่าล็อกอินอยู่หรือไม่
        if (!$this->sessionManager->isLoggedIn()) {
            $this->sendResponse(['error' => 'Not logged in'], 401);
            return;
        }
        
        $currentUser = $this->sessionManager->getCurrentUser();
        
        // ทำลาย session
        $this->sessionManager->destroySession();
        
        SecurityHelper::logSecurityEvent('user_logout', [
            'user_id' => $currentUser['user_id'],
            'username' => $currentUser['username']
        ]);
        
        $this->sendResponse([
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => '/login.php'
        ]);
    }
    
    /**
     * ตรวจสอบสถานะการล็อกอิน
     */
    public function checkAuth() {
        if ($this->sessionManager->isLoggedIn()) {
            $currentUser = $this->sessionManager->getCurrentUser();
            $userWithPermissions = $this->userModel->getUserWithPermissions($currentUser['user_id']);
            
            $this->sendResponse([
                'authenticated' => true,
                'user' => [
                    'id' => $userWithPermissions['id'],
                    'username' => $userWithPermissions['username'],
                    'role' => $userWithPermissions['role_name'],
                    'permissions' => $userWithPermissions['permissions']
                ]
            ]);
        } else {
            $this->sendResponse(['authenticated' => false]);
        }
    }
    
    /**
     * เปลี่ยนรหัสผ่าน
     */
    public function changePassword() {
        // ตรวจสอบว่าล็อกอินอยู่หรือไม่
        if (!$this->sessionManager->isLoggedIn()) {
            $this->sendResponse(['error' => 'Authentication required'], 401);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['error' => 'Invalid request method'], 405);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $input = SecurityHelper::sanitizeInput($input);
        
        // ตรวจสอบ CSRF token
        if (!isset($input['csrf_token']) || !SecurityHelper::validateCSRFToken($input['csrf_token'])) {
            $this->sendResponse(['error' => 'Invalid security token'], 403);
            return;
        }
        
        if (empty($input['current_password']) || empty($input['new_password'])) {
            $this->sendResponse(['error' => 'Current and new passwords are required'], 400);
            return;
        }
        
        // ตรวจสอบรหัสผ่านใหม่
        if (!SecurityHelper::validatePassword($input['new_password'])) {
            $this->sendResponse(['error' => 'New password does not meet security requirements'], 400);
            return;
        }
        
        $currentUser = $this->sessionManager->getCurrentUser();
        
        // ตรวจสอบรหัสผ่านเดิม
        $authResult = $this->userModel->authenticateUser($currentUser['username'], $input['current_password']);
        if (!$authResult || is_array($authResult)) {
            $this->sendResponse(['error' => 'Current password is incorrect'], 401);
            return;
        }
        
        // เปลี่ยนรหัสผ่าน
        $result = $this->changeUserPassword($currentUser['user_id'], $input['new_password']);
        
        if ($result) {
            // ทำลาย session ทั้งหมดของผู้ใช้
            $this->sessionManager->destroyAllUserSessions($currentUser['user_id']);
            
            SecurityHelper::logSecurityEvent('password_changed', [
                'user_id' => $currentUser['user_id'],
                'username' => $currentUser['username']
            ]);
            
            $this->sendResponse([
                'success' => true,
                'message' => 'Password changed successfully. Please log in again.',
                'redirect' => '/login.php'
            ]);
        } else {
            $this->sendResponse(['error' => 'Failed to change password'], 500);
        }
    }
    
    /**
     * ดึงรายการ session ที่ active
     */
    public function getActiveSessions() {
        if (!$this->sessionManager->isLoggedIn()) {
            $this->sendResponse(['error' => 'Authentication required'], 401);
            return;
        }
        
        $currentUser = $this->sessionManager->getCurrentUser();
        $sessions = $this->sessionManager->getActiveSessions($currentUser['user_id']);
        
        // ซ่อนข้อมูลที่ sensitive
        $safeSessions = array_map(function($session) {
            return [
                'session_id' => substr($session['session_id'], 0, 8) . '...',
                'ip_address' => $session['ip_address'],
                'user_agent' => $this->parseUserAgent($session['user_agent']),
                'created_at' => $session['created_at'],
                'last_activity' => $session['last_activity'],
                'is_current' => $session['session_id'] === session_id()
            ];
        }, $sessions);
        
        $this->sendResponse(['sessions' => $safeSessions]);
    }
    
    /**
     * เปลี่ยนรหัสผ่านของผู้ใช้
     */
    private function changeUserPassword($userId, $newPassword) {
        try {
            $passwordData = SecurityHelper::hashPassword($newPassword);
            
            $sql = "UPDATE users SET password_hash = ?, salt = ?, updated_at = NOW() WHERE id = ?";
            $params = [$passwordData['hash'], $passwordData['salt'], $userId];
            
            return $this->userModel->execute($sql, $params) > 0;
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * แปลง User Agent ให้อ่านง่าย
     */
    private function parseUserAgent($userAgent) {
        // สามารถใช้ library เช่น WhichBrowser หรือ Mobile_Detect
        // ที่นี่จะทำแบบง่ายๆ
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Google Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Mozilla Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Microsoft Edge';
        } else {
            return 'Unknown Browser';
        }
    }
    
    /**
     * ส่ง response กลับในรูปแบบ JSON
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// จัดการ routing
if (isset($_GET['action'])) {
    $controller = new AuthController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        case 'check':
            $controller->checkAuth();
            break;
        case 'change-password':
            $controller->changePassword();
            break;
        case 'sessions':
            $controller->getActiveSessions();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No action specified']);
}