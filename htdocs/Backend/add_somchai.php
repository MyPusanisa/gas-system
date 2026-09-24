<?php
require_once 'db.php';

$username = 'somchai';
$password = MD5('1234'); // รหัสผ่าน 1234
$name = 'สมชาย สายส่ง';
$role = 'staff';

// เช็คว่าตารางใช้โครงสร้างไหน และเพิ่มข้อมูล
$sql = "INSERT INTO users (username, password, name, role) 
        VALUES ('$username', '$password', '$name', '$role') 
        ON DUPLICATE KEY UPDATE role='$role', password='$password'";

if ($conn->query($sql) === TRUE) {
    echo "<h1>✅ เพิ่มผู้ใช้ 'somchai' (สิทธิ์ staff) สำเร็จแล้ว!</h1>";
    echo "<p> Username: <b>somchai</b> <br> Password: <b>1234</b> </p>";
} else {
    echo "❌ เกิดข้อผิดพลาด: " . $conn->error;
}
?>
