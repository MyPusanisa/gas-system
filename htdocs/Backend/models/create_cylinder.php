<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../config/db.php";

mysqli_set_charset($conn, "utf8mb4");

// รับข้อมูล JSON จาก React
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['serial_number'])) {
    echo json_encode(["success" => false, "message" => "กรุณาระบุ Serial Number"], JSON_UNESCAPED_UNICODE);
    exit();
}

// Escaping ป้องกัน SQL Injection
$sn       = mysqli_real_escape_string($conn, $data['serial_number']);
$brand    = mysqli_real_escape_string($conn, $data['brand'] ?? '');
$gas_type = mysqli_real_escape_string($conn, $data['gas_type'] ?? '');
$size     = mysqli_real_escape_string($conn, $data['size'] ?? '');
$mfg      = !empty($data['manufacture_date']) ? "'".mysqli_real_escape_string($conn, $data['manufacture_date'])."'" : "NULL";
$exp      = !empty($data['expiry_date']) ? "'".mysqli_real_escape_string($conn, $data['expiry_date'])."'" : "NULL";
$last_chk = !empty($data['last_check_date']) ? "'".mysqli_real_escape_string($conn, $data['last_check_date'])."'" : "NULL";
$next_chk = !empty($data['next_check_date']) ? "'".mysqli_real_escape_string($conn, $data['next_check_date'])."'" : "NULL";
$del      = !empty($data['delivered_date']) ? "'".mysqli_real_escape_string($conn, $data['delivered_date'])."'" : "NULL";
$loc      = mysqli_real_escape_string($conn, $data['current_location'] ?? '');
$status   = mysqli_real_escape_string($conn, $data['status'] ?? 'ในคลัง');

// คำสั่ง SQL เพิ่มข้อมูลลงในตาราง gas_cylinder
$sql = "INSERT INTO gas_cylinder 
        (serial_number, brand, gas_type, size, manufacture_date, expiry_date, last_check_date, next_check_date, delivered_date, current_location, status) 
        VALUES 
        ('{$sn}', '{$brand}', '{$gas_type}', '{$size}', {$mfg}, {$exp}, {$last_chk}, {$next_chk}, {$del}, '{$loc}', '{$status}')";

if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true, "message" => "เพิ่มข้อมูลถังแก๊สสำเร็จ"], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success" => false, "message" => mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conn);
?>