<?php
// เปลี่ยน $host เป็น '127.0.0.1' (หลีกเลี่ยงปัญหา socket ของ 'localhost')
$host = "127.0.0.1";
$user = "root";
$pass = ""; // ใส่ password ของ root บน server (หากไม่มีให้เว้นว่าง "")
$db   = "gas_system";
$port = 3306; // บน Ubuntu Server ปกติจะเป็น 3306 (ห้ามใช้ 3308)

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed: " . $conn->connect_error
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$conn->set_charset("utf8mb4");
?>