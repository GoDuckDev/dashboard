# Secure Login System

ระบบ Sign In ที่มีความปลอดภัยสูง พัฒนาด้วย PHP และ jQuery พร้อมระบบป้องกันการเจาะระบบที่แข็งแกร่ง

## คุณสมบัติหลัก

### 🛡️ ความปลอดภัย
- **Password Hashing**: ใช้ PBKDF2 กับ SHA-256 และ Salt
- **CSRF Protection**: ป้องกัน Cross-Site Request Forgery
- **SQL Injection Protection**: ใช้ Prepared Statements
- **XSS Protection**: การกรอง Input และ Output
- **Rate Limiting**: จำกัดจำนวนครั้งการพยายาม Login
- **Session Security**: จัดการ Session ที่ปลอดภัย
- **Account Locking**: ล็อคบัญชีชั่วคราวเมื่อพยายาม Login ผิดหลายครั้ง

### 🔐 การจัดการผู้ใช้
- **Role-Based Access Control (RBAC)**: ระบบสิทธิ์แบบ Role และ Permission
- **Session Management**: จัดการ Session หลายอุปกรณ์
- **Password Policy**: กำหนดนโยบายรหัสผ่านที่แข็งแกร่ง
- **Activity Logging**: บันทึก Log การเข้าใช้งาน

### 💻 User Interface
- **Responsive Design**: รองรับทุกขนาดหน้าจอ
- **Modern UI**: ใช้ Bootstrap 5 พร้อม Custom Styling
- **Real-time Validation**: ตรวจสอบข้อมูลแบบ Real-time
- **Password Strength Meter**: แสดงระดับความแข็งแกร่งของรหัสผ่าน

## โครงสร้างไฟล์

```
secure-login-system/
├── config/
│   └── database.php                 # การเชื่อมต่อฐานข้อมูล
├── classes/
│   ├── SecurityHelper.php           # คลาสจัดการความปลอดภัย
│   └── SessionManager.php           # จัดการ Session
├── models/
│   └── UserModel.php                # โมเดลผู้ใช้
├── controllers/
│   └── AuthController.php           # ควบคุมการยืนยันตัวตน
├── public/
│   ├── login.php                    # หน้า Sign In
│   ├── dashboard.php                # หน้าแดชบอร์ด
│   └── index.php                    # หน้าแรก
├── sql/
│   └── database_schema.sql          # สคีมาฐานข้อมูล
├── .htaccess                        # การตั้งค่าความปลอดภัย
└── README.md                        # คู่มือนี้
```

## ข้อกำหนดของระบบ

### เซิร์ฟเวอร์
- **PHP**: 7.4 หรือสูงกว่า
- **MySQL**: 5.7 หรือสูงกว่า / MariaDB 10.3+
- **Apache**: 2.4+ พร้อม mod_rewrite
- **SSL Certificate**: แนะนำสำหรับ Production

### PHP Extensions ที่จำเป็น
- PDO MySQL
- OpenSSL
- Hash
- Session
- JSON

## การติดตั้ง

### 1. เตรียมฐานข้อมูล
```sql
-- สร้างฐานข้อมูล
CREATE DATABASE secure_login_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- สร้างผู้ใช้ฐานข้อมูล
CREATE USER 'secure_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON secure_login_system.* TO 'secure_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. นำเข้าสคีมาฐานข้อมูล
```bash
mysql -u secure_user -p secure_login_system < sql/database_schema.sql
```

### 3. ตั้งค่าการเชื่อมต่อฐานข้อมูล
แก้ไขไฟล์ `config/database.php`:
```php
private const DB_HOST = 'localhost';
private const DB_NAME = 'secure_login_system';
private const DB_USER = 'secure_user';
private const DB_PASS = 'your_strong_password';
```

### 4. ตั้งค่าสิทธิ์ไฟล์
```bash
# ให้สิทธิ์อ่านเขียนแก่ Web Server
chmod 755 -R /path/to/secure-login-system/
chmod 644 -R /path/to/secure-login-system/*.php
chmod 600 /path/to/secure-login-system/config/database.php
```

### 5. ตั้งค่า Virtual Host (Apache)
```apache
<VirtualHost *:80>
    ServerName secure-login.local
    DocumentRoot /path/to/secure-login-system/public
    
    <Directory /path/to/secure-login-system/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Redirect to HTTPS (Production)
    # Redirect permanent / https://secure-login.local/
</VirtualHost>

<VirtualHost *:443>
    ServerName secure-login.local
    DocumentRoot /path/to/secure-login-system/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /path/to/secure-login-system/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## การใช้งาน

### สร้างผู้ใช้แรก (Admin)
```php
<?php
require_once 'models/UserModel.php';

$userModel = new UserModel();
$result = $userModel->createUser(
    'admin',                    // username
    'admin@example.com',        // email
    'AdminPassword123!@#',      // password
    1                          // role_id (1 = admin)
);

if ($result['success']) {
    echo "Admin user created successfully!";
} else {
    echo "Error: " . $result['error'];
}
?>
```

### การ Login
1. เข้าไปที่ `/login.php`
2. กรอก Username และ Password
3. ระบบจะตรวจสอบและสร้าง Session
4. เมื่อสำเร็จจะไปที่ Dashboard

### การจัดการสิทธิ์
```php
// ตรวจสอบสิทธิ์
if ($userModel->hasPermission($userId, 'manage_users')) {
    // อนุญาตให้ดำเนินการ
} else {
    // ปฏิเสธการเข้าถึง
}
```

## การตั้งค่าเพิ่มเติม

### Environment Variables (Production)
สร้างไฟล์ `.env`:
```env
DB_HOST=localhost
DB_NAME=secure_login_system
DB_USER=secure_user
DB_PASS=your_strong_password
SESSION_LIFETIME=3600
CSRF_TOKEN_LIFETIME=300
```

### Cron Jobs สำหรับ Maintenance
```bash
# ทำความสะอาด session หมดอายุทุก 30 นาที
*/30 * * * * php /path/to/cleanup_sessions.php

# ทำความสะอาด log files ทุกวัน
0 2 * * * find /path/to/logs -name "*.log" -mtime +30 -delete
```

### การสำรองข้อมูล
```bash
#!/bin/bash
# backup_database.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u secure_user -p secure_login_system > backup_${DATE}.sql
gzip backup_${DATE}.sql
```

## การป้องกันความปลอดภัย

### 1. Password Policy
- อย่างน้อย 8 ตัวอักษร
- มีตัวพิมพ์เล็กและใหญ่
- มีตัวเลข
- มีสัญลักษณ์พิเศษ

### 2. Account Locking
- ล็อกบัญชี 15 นาที หลังพยายาม Login ผิด 5 ครั้ง
- บันทึก Log การพยายามที่ผิด

### 3. Session Security
- Session ID ที่ปลอดภัย
- การ Regenerate Session ID
- ตรวจสอบ IP และ User Agent

### 4. Input Validation
- Sanitize ทุก Input
- ตรวจสอบ CSRF Token
- Validate ทุก Parameter

## การ Monitoring

### Log Files ที่สำคัญ
- **Login Logs**: บันทึกการ Login/Logout
- **Security Logs**: บันทึกเหตุการณ์ความปลอดภัย
- **Error Logs**: บันทึก Error ของระบบ

### Dashboard Monitoring
- จำนวน Active Sessions
- การพยายาม Login ที่ผิด
- IP ที่น่าสงสัย
- ระยะเวลาการใช้งาน

## Troubleshooting

### ปัญหาที่พบบ่อย

#### 1. ไม่สามารถ Login ได้
```php
// ตรวจสอบ Log
SELECT * FROM login_logs WHERE username = 'your_username' ORDER BY created_at DESC LIMIT 10;

// ตรวจสอบ Account Lock
SELECT account_locked_until, failed_attempts FROM users WHERE username = 'your_username';
```

#### 2. Session หมดอายุเร็ว
```php
// ตรวจสอบการตั้งค่า
echo "Session lifetime: " . ini_get('session.gc_maxlifetime');
echo "Cookie lifetime: " . ini_get('session.cookie_lifetime');
```

#### 3. CSRF Token Error
```php
// ตรวจสอบเวลา Token
if (isset($_SESSION['csrf_token_time'])) {
    echo "Token age: " . (time() - $_SESSION['csrf_token_time']) . " seconds";
}
```

#### 4. Database Connection Error
```bash
# ตรวจสอบการเชื่อมต่อ
mysql -u secure_user -p -h localhost secure_login_system -e "SELECT 1"
```

### การ Debug
```php
// เปิด Error Reporting สำหรับ Development
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

## การ Deploy ไปยัง Production

### 1. Security Checklist
- [ ] เปิดใช้งาน HTTPS
- [ ] ซ่อน Error Messages
- [ ] ตั้งค่า Security Headers
- [ ] เปลี่ยน Default Passwords
- [ ] ปิด Debug Mode
- [ ] ตั้งค่า Firewall

### 2. Performance Optimization
- [ ] เปิดใช้งาน OPcache
- [ ] ตั้งค่า Compression
- [ ] Optimize Database Queries
- [ ] ใช้ CDN สำหรับ Static Files

### 3. Monitoring Setup
- [ ] ตั้งค่า Log Rotation
- [ ] เปิดใช้งาน Performance Monitoring
- [ ] ตั้งค่า Alerts
- [ ] สำรองข้อมูลอัตโนมัติ

## การอัพเดท

### Version Control
```bash
# สำรองข้อมูลก่อนอัพเดท
git stash
cp config/database.php config/database.php.backup

# ดึงการอัพเดทล่าสุด
git pull origin main

# Restore การตั้งค่า
cp config/database.php.backup config/database.php

# Run Migration Scripts (ถ้ามี)
php migrate.php
```

## การสนับสนุน

### Documentation
- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Bootstrap Documentation](https://getbootstrap.com/docs/5.3/)
- [jQuery Documentation](https://jquery.com/)

### Best Practices
- [OWASP Security Guidelines](https://owasp.org/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [MySQL Security](https://dev.mysql.com/doc/refman/8.0/en/security.html)

## License

MIT License - ดูรายละเอียดในไฟล์ LICENSE

## Contributors

- Senior PHP Developer
- Security Specialist
- UI/UX Designer

---

**⚠️ หมายเหตุ**: ระบบนี้ออกแบบมาเพื่อความปลอดภัยสูงสุด แต่ความปลอดภัยที่แท้จริงขึ้นอยู่กับการติดตั้งและการดูแลรักษาที่ถูกต้อง กรุณาทำการ Security Audit เป็นประจำ