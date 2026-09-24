<?php
require_once 'db.php';

// 1. สร้างตาราง users ถ้ายังไม่มี
$createTable = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    role VARCHAR(50) DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createTable);

// 2. นำข้อมูลจาก delivery_staff เข้ามาตาราง users
$conn->query("INSERT IGNORE INTO users (username, password, name, role) 
              SELECT username, '1234', staff_name, 'staff' FROM delivery_staff WHERE username IS NOT NULL;");

// 3. นำข้อมูลจาก admin เข้ามาตาราง users
$conn->query("INSERT IGNORE INTO users (username, password, name, role) 
              SELECT username, password, name, 'admin' FROM admin WHERE username IS NOT NULL;");

// 4. อัปเดตรหัสผ่านของ somchai ให้เป็น 1234 (ทั้งแบบข้อความธรรมดาและ MD5 เพื่อความครอบคลุม)
$conn->query("UPDATE users SET password = '" . MD5('1234') . "' WHERE username = 'somchai'");

echo "<h1>✅ ปรับปรุงโครงสร้างและเพิ่มบัญชี 'somchai' สำเร็จแล้ว!</h1>";
echo "<p>ตอนนี้สามารถล็อกอินด้วย <b>somchai</b> / <b>1234</b> ได้แล้วครับ</p>";
?>
