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
    <title>สมัครสมาชิก - Secure Login System</title>
    
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
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .register-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-align: center;
            padding: 2rem;
        }
        
        .register-body {
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
        
        .form-control.is-valid {
            border-color: #28a745;
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        
        .btn-register {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-register:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
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
            transition: all 0.3s ease;
        }
        
        .strength-meter .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .availability-check {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            min-height: 1.2em;
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
            animation: float 25s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-100px) rotate(360deg); }
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #6c757d;
            position: relative;
        }
        
        .step.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 20px;
            height: 2px;
            background: #e9ecef;
            transform: translateY(-50%);
        }
        
        .step:last-child::after {
            display: none;
        }
        
        .step.completed::after {
            background: #28a745;
        }
        
        .terms-scroll {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            background: #f8f9fa;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape" style="width: 60px; height: 60px; left: 5%; animation-delay: 0s;"></div>
        <div class="shape" style="width: 100px; height: 100px; left: 15%; animation-delay: 3s;"></div>
        <div class="shape" style="width: 80px; height: 80px; left: 75%; animation-delay: 6s;"></div>
        <div class="shape" style="width: 120px; height: 120px; left: 85%; animation-delay: 9s;"></div>
        <div class="shape" style="width: 40px; height: 40px; left: 95%; animation-delay: 12s;"></div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-container">
                    <div class="register-header">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <h2>สมัครสมาชิก</h2>
                        <p class="mb-0">สร้างบัญชีของคุณ</p>
                    </div>
                    
                    <div class="register-body">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step active" id="step1">1</div>
                            <div class="step" id="step2">2</div>
                            <div class="step" id="step3">3</div>
                        </div>

                        <!-- Alert Messages -->
                        <div id="alertContainer"></div>
                        
                        <form id="registerForm" novalidate>
                            <input type="hidden" name="csrf_token" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            
                            <!-- Step 1: Account Information -->
                            <div class="form-step active" id="formStep1">
                                <h5 class="mb-4">ข้อมูลบัญชี</h5>
                                
                                <!-- Username Field -->
                                <div class="mb-4">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-1"></i>ชื่อผู้ใช้งาน <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control" 
                                               id="username" 
                                               name="username" 
                                               placeholder="กรอกชื่อผู้ใช้งาน (3-50 ตัวอักษร)"
                                               required
                                               autocomplete="username"
                                               maxlength="50"
                                               pattern="[a-zA-Z0-9_-]{3,50}">
                                        <div class="invalid-feedback">
                                            ชื่อผู้ใช้งานต้องมี 3-50 ตัวอักษร (อนุญาต a-z A-Z 0-9 _ -)
                                        </div>
                                    </div>
                                    <div class="availability-check text-muted" id="usernameCheck"></div>
                                </div>
                                
                                <!-- Email Field -->
                                <div class="mb-4">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>อีเมล <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               placeholder="กรอกอีเมล"
                                               required
                                               autocomplete="email">
                                        <div class="invalid-feedback">
                                            กรุณากรอกอีเมลที่ถูกต้อง
                                        </div>
                                    </div>
                                    <div class="availability-check text-muted" id="emailCheck"></div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="button" class="btn btn-primary btn-register" onclick="nextStep(1)">
                                        ถัดไป <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Step 2: Password -->
                            <div class="form-step" id="formStep2">
                                <h5 class="mb-4">ตั้งรหัสผ่าน</h5>
                                
                                <!-- Password Field -->
                                <div class="mb-4">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>รหัสผ่าน <span class="text-danger">*</span>
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
                                               autocomplete="new-password"
                                               minlength="8">
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                id="togglePassword"
                                                tabindex="-1">
                                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร ประกอบด้วย A-Z, a-z, 0-9, และสัญลักษณ์
                                        </div>
                                    </div>
                                    <div class="strength-meter">
                                        <div class="strength-bar"></div>
                                    </div>
                                    <small class="strength-text text-muted">ความแข็งแกร่งของรหัสผ่าน</small>
                                </div>
                                
                                <!-- Confirm Password Field -->
                                <div class="mb-4">
                                    <label for="confirmPassword" class="form-label">
                                        <i class="fas fa-lock me-1"></i>ยืนยันรหัสผ่าน <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirmPassword" 
                                               name="confirm_password" 
                                               placeholder="กรอกรหัสผ่านอีกครั้ง"
                                               required
                                               autocomplete="new-password">
                                        <div class="invalid-feedback">
                                            รหัสผ่านไม่ตรงกัน
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-secondary w-100" onclick="prevStep(2)">
                                            <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-primary btn-register w-100" onclick="nextStep(2)">
                                            ถัดไป <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 3: Terms & Conditions -->
                            <div class="form-step" id="formStep3">
                                <h5 class="mb-4">เงื่อนไขการใช้งาน</h5>
                                
                                <!-- Terms and Conditions -->
                                <div class="terms-scroll">
                                    <h6>ข้อตกลงและเงื่อนไขการใช้งาน</h6>
                                    <p><strong>1. การยอมรับเงื่อนไข</strong><br>
                                    การใช้งานระบบนี้ถือว่าคุณยอมรับเงื่อนไขทั้งหมดที่ระบุไว้</p>
                                    
                                    <p><strong>2. ข้อมูลส่วนบุคคล</strong><br>
                                    เราจะปกป้องข้อมูลส่วนบุคคลของคุณตามนโยบายความเป็นส่วนตัว</p>
                                    
                                    <p><strong>3. ความปลอดภัย</strong><br>
                                    คุณมีหน้าที่รักษาความปลอดภัยของบัญชีและรหัสผ่านของตนเอง</p>
                                    
                                    <p><strong>4. การใช้งานที่เหมาะสม</strong><br>
                                    ห้ามใช้ระบบในทางที่ผิดกฎหมายหรือเป็นอันตรายต่อผู้อื่น</p>
                                    
                                    <p><strong>5. การยกเลิกบัญชี</strong><br>
                                    เรามีสิทธิ์ยกเลิกบัญชีที่ใช้งานไม่เหมาะสม</p>
                                </div>
                                
                                <!-- Terms Acceptance -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="termsAccepted" name="terms_accepted" required>
                                        <label class="form-check-label" for="termsAccepted">
                                            ฉันยอมรับ