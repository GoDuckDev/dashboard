<?php
require_once '../classes/SessionManager.php';
require_once '../classes/SecurityHelper.php';
require_once '../models/UserModel.php';

// ตรวจสอบการล็อกอิน
$sessionManager = new SessionManager();
if (!$sessionManager->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// ดึงข้อมูลผู้ใช้
$currentUser = $sessionManager->getCurrentUser();
$userModel = new UserModel();
$userWithPermissions = $userModel->getUserWithPermissions($currentUser['user_id']);

// สร้าง CSRF token สำหรับการทำงานต่างๆ
$csrfToken = SecurityHelper::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด - Secure System</title>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-custom {
            background: linear-gradient(45deg, #667eea, #764ba2);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .sidebar {
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            min-height: calc(100vh - 76px);
        }
        
        .sidebar .nav-link {
            color: #6c757d;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
            font-weight: 600;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .permission-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
            margin: 2px;
            display: inline-block;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .session-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        
        .session-current {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 76px;
                left: -250px;
                width: 250px;
                z-index: 1000;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt me-2"></i>
                Secure System
            </a>
            
            <button class="navbar-toggler d-lg-none" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-2">
                            <?= strtoupper(substr($userWithPermissions['username'], 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline"><?= SecurityHelper::escapeOutput($userWithPermissions['username']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">บัญชีผู้ใช้</h6></li>
                        <li><a class="dropdown-item" href="#" onclick="showProfile()">
                            <i class="fas fa-user me-2"></i>ข้อมูลส่วนตัว
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="showSessions()">
                            <i class="fas fa-desktop me-2"></i>จัดการ Session
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="changePassword()">
                            <i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="logout()">
                            <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-lg-2 sidebar" id="sidebar">
                <div class="py-4">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#dashboard" onclick="showDashboard()">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                แดshboard
                            </a>
                        </li>
                        
                        <?php if (in_array('read_data', $userWithPermissions['permissions'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#data" onclick="showData()">
                                <i class="fas fa-database me-2"></i>
                                ข้อมูล
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (in_array('manage_users', $userWithPermissions['permissions'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#users" onclick="showUsers()">
                                <i class="fas fa-users me-2"></i>
                                จัดการผู้ใช้
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (in_array('system_admin', $userWithPermissions['permissions'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#settings" onclick="showSettings()">
                                <i class="fas fa-cog me-2"></i>
                                ตั้งค่าระบบ
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="#security" onclick="showSecurity()">
                                <i class="fas fa-shield-alt me-2"></i>
                                ความปลอดภัย
                            </a>
                        </li>
                        
                        <li class="nav-item mt-3">
                            <hr>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link text-muted" href="#help">
                                <i class="fas fa-question-circle me-2"></i>
                                ช่วยเหลือ
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-lg-10 main-content" id="mainContent">
                <!-- Dashboard Content -->
                <div id="dashboardContent">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3">แดshboard</h1>
                        <div class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <span id="currentTime"></span>
                        </div>
                    </div>
                    
                    <!-- Welcome Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4>สวัสดี, <?= SecurityHelper::escapeOutput($userWithPermissions['username']) ?>!</h4>
                                    <p class="text-muted mb-2">
                                        บทบาท: <span class="badge bg-primary"><?= SecurityHelper::escapeOutput($userWithPermissions['role_name']) ?></span>
                                    </p>
                                    <div class="mb-3">
                                        <strong>สิทธิ์การใช้งาน:</strong><br>
                                        <?php foreach ($userWithPermissions['permissions'] as $permission): ?>
                                            <span class="permission-badge"><?= SecurityHelper::escapeOutput($permission) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="stats-card p-4 text-center">
                                        <i class="fas fa-user-check stats-icon"></i>
                                        <h5 class="mt-2">สถานะ: ออนไลน์</h5>
                                        <small>เข้าสู่ระบบเมื่อ: <?= date('H:i:s') ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-shield-alt stats-icon"></i>
                                    <h5 class="mt-2">ระดับความปลอดภัย</h5>
                                    <h3>สูง</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock stats-icon"></i>
                                    <h5 class="mt-2">เวลาออนไลน์</h5>
                                    <h3 id="onlineTime">0:00:00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-desktop stats-icon"></i>
                                    <h5 class="mt-2">Session</h5>
                                    <h3 id="activeSessions">1</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-key stats-icon"></i>
                                    <h5 class="mt-2">การเข้ารหัส</h5>
                                    <h3>AES-256</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Info -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle me-2"></i>
                            ข้อมูลระบบ
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>IP Address:</strong></td>
                                            <td><?= SecurityHelper::escapeOutput(SecurityHelper::getClientIP()) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>User Agent:</strong></td>
                                            <td class="small"><?= SecurityHelper::escapeOutput($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Session ID:</strong></td>
                                            <td class="font-monospace small"><?= substr(session_id(), 0, 16) ?>...</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>เวลาเซิร์ฟเวอร์:</strong></td>
                                            <td><?= date('Y-m-d H:i:s') ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Protocol:</strong></td>
                                            <td><?= isset($_SERVER['HTTPS']) ? 'HTTPS' : 'HTTP' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>เขตเวลา:</strong></td>
                                            <td><?= date_default_timezone_get() ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Dynamic Content Area -->
                <div id="dynamicContent" style="display: none;"></div>
            </main>
        </div>
    </div>

    <!-- Modals -->
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">รหัสผ่านปัจจุบัน</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="8">
                            <div class="password-strength mt-2">
                                <div class="strength-meter">
                                    <div class="strength-bar"></div>
                                </div>
                                <small class="strength-text text-muted">ความแข็งแกร่งของรหัสผ่าน</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="submitPasswordChange()">เปลี่ยนรหัสผ่าน</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>ข้อมูลส่วนตัว
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2rem;">
                                <?= strtoupper(substr($userWithPermissions['username'], 0, 1)) ?>
                            </div>
                            <h5><?= SecurityHelper::escapeOutput($userWithPermissions['username']) ?></h5>
                            <p class="text-muted"><?= SecurityHelper::escapeOutput($userWithPermissions['role_name']) ?></p>
                        </div>
                        <div class="col-md-8">
                            <table class="table">
                                <tr>
                                    <td><strong>ID ผู้ใช้:</strong></td>
                                    <td><?= $userWithPermissions['id'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>ชื่อผู้ใช้:</strong></td>
                                    <td><?= SecurityHelper::escapeOutput($userWithPermissions['username']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>อีเมล:</strong></td>
                                    <td><?= SecurityHelper::escapeOutput($userWithPermissions['email']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>บทบาท:</strong></td>
                                    <td><span class="badge bg-primary"><?= SecurityHelper::escapeOutput($userWithPermissions['role_name']) ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>สถานะ:</strong></td>
                                    <td><span class="badge bg-success">ใช้งานได้</span></td>
                                </tr>
                                <tr>
                                    <td><strong>สิทธิ์:</strong></td>
                                    <td>
                                        <?php foreach ($userWithPermissions['permissions'] as $permission): ?>
                                            <span class="permission-badge"><?= SecurityHelper::escapeOutput($permission) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sessions Modal -->
    <div class="modal fade" id="sessionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-desktop me-2"></i>จัดการ Session
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="sessionsContent">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">กำลังโหลดข้อมูล...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize
            updateCurrentTime();
            updateOnlineTime();
            
            // Update time every second
            setInterval(updateCurrentTime, 1000);
            setInterval(updateOnlineTime, 1000);
            
            // Sidebar toggle for mobile
            $('#sidebarToggle').click(function() {
                $('#sidebar').toggleClass('show');
            });
            
            // Close sidebar when clicking outside on mobile
            $(document).click(function(e) {
                if (window.innerWidth <= 768) {
                    if (!$(e.target).closest('#sidebar, #sidebarToggle').length) {
                        $('#sidebar').removeClass('show');
                    }
                }
            });
            
            // Password strength checker
            $('#newPassword').on('input', function() {
                checkPasswordStrength($(this).val());
            });
            
            // Confirm password validation
            $('#confirmPassword').on('input', function() {
                const newPassword = $('#newPassword').val();
                const confirmPassword = $(this).val();
                
                if (confirmPassword && newPassword !== confirmPassword) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            // Auto-logout warning (5 minutes before session expires)
            setTimeout(function() {
                showLogoutWarning();
            }, 55 * 60 * 1000); // 55 minutes
        });
        
        // Navigation functions
        function showDashboard() {
            updateActiveNav('dashboard');
            $('#dashboardContent').show();
            $('#dynamicContent').hide();
        }
        
        function showData() {
            updateActiveNav('data');
            loadContent('data', '<h3>ข้อมูล</h3><p>หน้าสำหรับจัดการข้อมูลต่างๆ</p>');
        }
        
        function showUsers() {
            updateActiveNav('users');
            loadContent('users', '<h3>จัดการผู้ใช้</h3><p>หน้าสำหรับจัดการผู้ใช้ในระบบ</p>');
        }
        
        function showSettings() {
            updateActiveNav('settings');
            loadContent('settings', '<h3>ตั้งค่าระบบ</h3><p>หน้าสำหรับตั้งค่าระบบ</p>');
        }
        
        function showSecurity() {
            updateActiveNav('security');
            loadContent('security', '<h3>ความปลอดภัย</h3><p>หน้าสำหรับจัดการความปลอดภัย</p>');
        }
        
        function updateActiveNav(activeId) {
            $('.sidebar .nav-link').removeClass('active');
            $(`.sidebar .nav-link[href="#${activeId}"]`).addClass('active');
        }
        
        function loadContent(section, content) {
            $('#dashboardContent').hide();
            $('#dynamicContent').html(`<div class="card"><div class="card-body">${content}</div></div>`).show();
        }
        
        // Modal functions
        function showProfile() {
            $('#profileModal').modal('show');
        }
        
        function showSessions() {
            $('#sessionsModal').modal('show');
            loadActiveSessions();
        }
        
        function changePassword() {
            $('#changePasswordModal').modal('show');
        }
        
        // Utility functions
        function updateCurrentTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('th-TH');
            $('#currentTime').text(timeStr);
        }
        
        function updateOnlineTime() {
            const startTime = new Date().getTime() - (performance.now());
            const sessionStart = <?= $_SESSION['session_start'] * 1000 ?>;
            const diff = new Date().getTime() - sessionStart;
            
            const hours = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            
            $('#onlineTime').text(`${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`);
        }
        
        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = '';
            
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;
            
            const colors = ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'];
            const texts = ['อ่อนมาก', 'อ่อน', 'ปานกลาง', 'แข็งแกร่ง', 'แข็งแกร่งมาก'];
            const widths = [20, 40, 60, 80, 100];
            
            $('.strength-bar').css({
                'width': widths[score] + '%',
                'background-color': colors[score]
            });
            $('.strength-text').text(`ความแข็งแกร่ง: ${texts[score]}`);
        }
        
        function loadActiveSessions() {
            $.ajax({
                url: '/controllers/AuthController.php?action=sessions',
                type: 'GET',
                success: function(response) {
                    if (response.sessions) {
                        let html = '';
                        response.sessions.forEach(function(session) {
                            html += `
                                <div class="session-item ${session.is_current ? 'session-current' : ''}">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6>${session.is_current ? 'Session ปัจจุบัน' : 'Session อื่น'}</h6>
                                            <p class="mb-1"><strong>Browser:</strong> ${session.user_agent}</p>
                                            <p class="mb-1"><strong>IP:</strong> ${session.ip_address}</p>
                                            <small class="text-muted">
                                                สร้างเมื่อ: ${new Date(session.created_at).toLocaleString('th-TH')}
                                                | ใช้งานล่าสุด: ${new Date(session.last_activity).toLocaleString('th-TH')}
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            ${session.is_current ? 
                                                '<span class="badge bg-success">ปัจจุบัน</span>' : 
                                                '<button class="btn btn-sm btn-danger" onclick="terminateSession(\'' + session.session_id + '\')">ยุติ</button>'
                                            }
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        $('#activeSessions').text(response.sessions.length);
                        $('#sessionsContent').html(html);
                    }
                },
                error: function() {
                    $('#sessionsContent').html('<div class="alert alert-danger">ไม่สามารถโหลดข้อมูล session ได้</div>');
                }
            });
        }
        
        function submitPasswordChange() {
            const form = $('#changePasswordForm')[0];
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            const newPassword = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();
            
            if (newPassword !== confirmPassword) {
                alert('รหัสผ่านใหม่ไม่ตรงกัน');
                return;
            }
            
            const formData = {
                current_password: $('#currentPassword').val(),
                new_password: newPassword,
                csrf_token: $('[name="csrf_token"]').val()
            };
            
            $.ajax({
                url: '/controllers/AuthController.php?action=change-password',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        window.location.href = response.redirect;
                    } else {
                        alert(response.error);
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON ? xhr.responseJSON.error : 'เกิดข้อผิดพลาด');
                }
            });
        }
        
        function logout() {
            if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
                $.ajax({
                    url: '/controllers/AuthController.php?action=logout',
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect;
                        }
                    },
                    error: function() {
                        // Force redirect even if AJAX fails
                        window.location.href = '/login.php';
                    }
                });
            }
        }
        
        function showLogoutWarning() {
            if (confirm('Session ของคุณจะหมดอายุใน 5 นาที คุณต้องการต่ออายุหรือไม่?')) {
                // Refresh page to renew session
                window.location.reload();
            }
        }
    </script>
</body>
</html>