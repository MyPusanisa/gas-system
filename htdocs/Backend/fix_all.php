<?php
require_once 'db.php';

// 1. เพิ่มคอลัมน์ username และ password ให้ตาราง delivery_staff ถ้ายังไม่มี
$conn->query("ALTER TABLE delivery_staff ADD COLUMN IF NOT EXISTS username VARCHAR(50) UNIQUE");
$conn->query("ALTER TABLE delivery_staff ADD COLUMN IF NOT EXISTS password VARCHAR(255)");

// 2. อัปเดตข้อมูลของ somchai
$conn->query("UPDATE delivery_staff SET username = 'somchai', password = '1234' WHERE name LIKE '%สมชาย%' OR staff_id = 1");

// 3. ถ้ายังไม่มีสมชาย ให้เพิ่มเข้าไปใหม่
$check = $conn->query("SELECT * FROM delivery_staff WHERE username = 'somchai'");
if ($check->num_rows === 0) {
    $conn->query("INSERT INTO delivery_staff (name, phone, status, username, password) VALUES ('สมชาย ส่งไว', '0812345678', 'active', 'somchai', '1234')");
}

echo "<h1>✅ ปรับแต่ง Database ให้รองรับ Somchai สำเร็จแล้ว!</h1>";
?>
