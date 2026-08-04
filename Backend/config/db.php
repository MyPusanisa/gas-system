<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json");
// อนุญาตให้ทุก Origin (รวมถึง IP 10.60.1.228) เข้าใช้งาน API นี้ได้
header("Access-Control-Allow-Origin: *"); 
// หมายเหตุ: เมื่อใช้ "*" จะต้องคอมเมนต์หรือลบบรรทัด Allow-Credentials ออก เพื่อไม่ให้เบราว์เซอร์บล็อกซ้ำซ้อน
// header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = new mysqli("localhost", "root", "", "gas_system");
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "DB connection failed"]));
}
mysqli_set_charset($conn, "utf8mb4");
?>