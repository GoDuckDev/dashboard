<?php
require_once '../classes/SessionManager.php';
require_once '../classes/SecurityHelper.php';

// ตรวจสอบว่าล็อกอินแล้วหรือยัง
$sessionManager = new SessionManager();
if ($sessionManager->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

// สร้าง CSRF token
$csrfToken = SecurityHelper::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Secure Login System</title>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
    
    <!-- CSS Framework -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-align: center;
            padding: 2rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .loading {
            display: none;
        }
        
        .strength-meter {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .strength-meter .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-100px) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape" style="width: 80px; height: 80px; left: 10%; animation-delay: 0s;"></div>
        <div class="shape" style="width: 120px; height: 120px; left: 20%; animation-delay: 2s;"></div>
        <div class="shape" style="width: 60px; height: 60px; left: 70%; animation-delay: 4s;"></div>
        <div class="shape" style="width: 100px; height: 100px; left: 80%; animation-delay: 6s;"></div>
        <div class="shape" style="width: 40px; height: 40px; left: 90%; animation-delay: 8s;"></div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <div class="login-header">
                        <i class="fas fa-shield-alt fa-3x mb-3"></i>
                        <h2>เข้าสู่ระบบ</h2>
                        <p class="mb-0">ระบบรักษาความปลอดภัยขั้นสูง</p>
                    </div>
                    
                    <div class="login-body">
                        <!-- Alert Messages -->
                        <div id="alertContainer"></div>
                        
                        <form id="loginForm" novalidate>
                            <input type="hidden" name="csrf_token" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            
                            <!-- Username Field -->
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-1"></i>ชื่อผู้ใช้งาน
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           placeholder="กรอกชื่อผู้ใช้งาน"
                                           required
                                           autocomplete="username"
                                           maxlength="50"
                                           pattern="[a-zA-Z0-9_-]{3,50}">
                                    <div class="invalid-feedback">
                                        กรุณากรอกชื่อผู้ใช้งาน (3-50 ตัวอักษร, อนุญาต a-z A-Z 0-9 _ -)
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password Field -->
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>รหัสผ่าน
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="กรอกรหัสผ่าน"
                                           required
                                           autocomplete="current-password"
                                           minlength="8">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword"
                                            tabindex="-1">
                                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        กรุณากรอกรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Remember Me -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                                    <label class="form-check-label" for="rememberMe">
                                        จดจำการเข้าสู่ระบบ
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Login Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                                    <span class="btn-text">
                                        <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                                    </span>
                                    <span class="loading">
                                        <i class="fas fa-spinner fa-spin me-2"></i>กำลังเข้าสู่ระบบ...
                                    </span>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Security Info -->
                        <div class="mt-4 text-center">
                            <div class="text-muted small">
                                <i class="fas fa-shield-alt me-1 text-success"></i>
                                การเชื่อมต่อของคุณได้รับการปกป้องด้วย SSL
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Session จะหมดอายุใน 60 นาที
                                </small>
                            </div>
                        </div>
                        
                        <!-- Links -->
                        <div class="text-center mt-3">
                            <a href="/forgot-password.php" class="text-decoration-none">
                                <i class="fas fa-question-circle me-1"></i>ลืมรหัสผ่าน?
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-4 text-white">
                    <small>
                        © 2025 Secure Login System. 
                        <i class="fas fa-heart text-danger"></i> 
                        Made with security in mind
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle Password Visibility
            $('#togglePassword').click(function() {
                const password = $('#password');
                const icon = $('#togglePasswordIcon');
                
                if (password.attr('type') === 'password') {
                    password.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    password.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Form Validation
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }
                
                handleLogin();
            });
            
            // Real-time Username Validation
            $('#username').on('input', function() {
                const username = $(this).val();
                const pattern = /^[a-zA-Z0-9_-]{3,50}$/;
                
                if (username && !pattern.test(username)) {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                } else if (username.length >= 3) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });
            
            // Password Field Validation
            $('#password').on('input', function() {
                const password = $(this).val();
                
                if (password.length >= 8) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else if (password.length > 0) {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });
            
            // Handle Login
            function handleLogin() {
                const $form = $('#loginForm');
                const $btn = $('#loginBtn');
                const $btnText = $btn.find('.btn-text');
                const $loading = $btn.find('.loading');
                
                // Show loading state
                $btn.prop('disabled', true);
                $btnText.hide();
                $loading.show();
                
                const formData = {
                    username: $('#username').val(),
                    password: $('#password').val(),
                    csrf_token: $('#csrfToken').val()
                };
                
                $.ajax({
                    url: '../controllers/AuthController.php?action=login',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    timeout: 10000,
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            
                            // Redirect after delay
                            setTimeout(function() {
                                window.location.href = response.redirect || '/dashboard.php';
                            }, 1500);
                        } else {
                            showAlert('danger', response.error || 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ');
                            resetButton();
                        }
                    },
                    error: function(xhr, status, error) {
                        let message = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
                        
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else if (status === 'timeout') {
                            message = 'หมดเวลาการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง';
                        } else if (xhr.status === 0) {
                            message = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
                        }
                        
                        showAlert('danger', message);
                        resetButton();
                    }
                });
            }

            // Handle Login
            function addUser() {
                const $form = $('#loginForm');
                const $btn = $('#loginBtn');
                const $btnText = $btn.find('.btn-text');
                const $loading = $btn.find('.loading');
                
                // Show loading state
                $btn.prop('disabled', true);
                $btnText.hide();
                $loading.show();
                
                const formData = {
                    username: $('#username').val(),
                    password: $('#password').val(),
                    csrf_token: $('#csrfToken').val()
                };
                
                $.ajax({
                    url: '../controllers/AuthController.php?action=login',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    timeout: 10000,
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            
                            // Redirect after delay
                            setTimeout(function() {
                                window.location.href = response.redirect || 'dashboard.php';
                            }, 1500);
                        } else {
                            showAlert('danger', response.error || 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ');
                            resetButton();
                        }
                    },
                    error: function(xhr, status, error) {
                        let message = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
                        
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else if (status === 'timeout') {
                            message = 'หมดเวลาการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง';
                        } else if (xhr.status === 0) {
                            message = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
                        }
                        
                        showAlert('danger', message);
                        resetButton();
                    }
                });
            }
            
            function resetButton() {
                const $btn = $('#loginBtn');
                const $btnText = $btn.find('.btn-text');
                const $loading = $btn.find('.loading');
                
                $btn.prop('disabled', false);
                $btnText.show();
                $loading.hide();
            }
            
            function showAlert(type, message) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                $('#alertContainer').html(alertHtml);
                
                // Auto dismiss after 5 seconds
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 5000);
            }
            
            // Prevent form submission on Enter in password field when caps lock is on
            $(document).on('keypress', function(e) {
                if (e.which === 13 && e.target.tagName !== 'BUTTON') {
                    $('#loginForm').submit();
                }
            });
            
            // Caps Lock Detection
            $('#password').on('keypress', function(e) {
                const capsLock = e.originalEvent.getModifierState && e.originalEvent.getModifierState('CapsLock');
                if (capsLock) {
                    if (!$('.caps-warning').length) {
                        $(this).after('<div class="caps-warning text-warning small mt-1"><i class="fas fa-exclamation-triangle me-1"></i>Caps Lock เปิดอยู่</div>');
                    }
                } else {
                    $('.caps-warning').remove();
                }
            });
            
            // Hide caps warning when focus is lost
            $('#password').on('blur', function() {
                $('.caps-warning').remove();
            });
            
            // Security: Clear form on page unload
            $(window).on('beforeunload', function() {
                $('#loginForm')[0].reset();
            });
            
            // Focus on username field
            $('#username').focus();
        });
    </script>
</body>
</html>