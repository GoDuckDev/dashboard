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
│   ├── SecurityHelper.php           # คลาสจัดการความ