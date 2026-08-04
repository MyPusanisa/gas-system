<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json");

// แก้ไข CORS ให้ตรงกับ Origin ของ Frontend และรองรับ Credentials
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ระบุ Port 3307 ในการเชื่อมต่อ MySQL
$conn = new mysqli("localhost", "root", "", "gas_system", 3307);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "DB connection failed: " . $conn->connect_error]));
}
mysqli_set_charset($conn, "utf8mb4");
?>