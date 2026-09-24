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

$staff_name = trim($data['staff_name'] ?? '');
$staff_phone = trim($data['staff_phone'] ?? '');
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');
$address = trim($data['address'] ?? '');
$status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

if ($staff_name === '' || $username === '' || strlen($password) < 4) {
    echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ (รหัสผ่านอย่างน้อย 4 ตัว)"]);
    exit();
}

// ตรวจสอบว่า username ซ้ำหรือไม่
$check = $conn->prepare("SELECT staff_id FROM delivery_staff WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username นี้ถูกใช้งานแล้ว"]);
    exit();
}

// เก็บรหัสผ่านเป็นตัวอักษรธรรมดาเพื่อให้ admin ดูได้ในหน้าพนักงาน (ตามที่เจ้าของระบบเลือก)
$stmt = $conn->prepare("INSERT INTO delivery_staff (staff_name, staff_phone, username, password, address, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $staff_name, $staff_phone, $username, $password, $address, $status);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "เพิ่มพนักงานสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการบันทึกข้อมูล"]);
}
