<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ถูกต้อง"]);
    exit();
}

$staff_name = $data['staff_name'] ?? '';
$staff_phone = $data['staff_phone'] ?? '';
$username = $data['username'] ?? '';
$password = $conn->real_escape_string($data['password'] ?? '');
$address = $data['address'] ?? '';
$status = $data['status'] ?? 'active';

// ตรวจสอบว่า username ซ้ำหรือไม่
$checkSql = "SELECT staff_id FROM delivery_staff WHERE username = '$username'";
if ($conn->query($checkSql)->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username นี้ถูกใช้งานแล้ว"]);
    exit();
}

$sql = "INSERT INTO delivery_staff (staff_name, staff_phone, username, password, address, status) 
        VALUES ('$staff_name', '$staff_phone', '$username', '$password', '$address', '$status')";

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "เพิ่มพนักงานสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $conn->error]);
}