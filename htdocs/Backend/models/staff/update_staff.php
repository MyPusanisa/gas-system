<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$staff_id = (int)($data['staff_id'] ?? 0);
$staff_name = trim($data['staff_name'] ?? '');
$staff_phone = trim($data['staff_phone'] ?? '');
$address = trim($data['address'] ?? '');
$status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
$password = trim($data['password'] ?? '');

if ($staff_id <= 0) {
    echo json_encode(["success" => false, "message" => "ไม่พบ Staff ID"]);
    exit();
}

if ($password !== '') {
    // เก็บรหัสผ่านเป็นตัวอักษรธรรมดาเพื่อให้ admin ดูได้ในหน้าพนักงาน (ตามที่เจ้าของระบบเลือก)
    $stmt = $conn->prepare("UPDATE delivery_staff SET staff_name=?, staff_phone=?, password=?, address=?, status=? WHERE staff_id=?");
    $stmt->bind_param("sssssi", $staff_name, $staff_phone, $password, $address, $status, $staff_id);
} else {
    $stmt = $conn->prepare("UPDATE delivery_staff SET staff_name=?, staff_phone=?, address=?, status=? WHERE staff_id=?");
    $stmt->bind_param("ssssi", $staff_name, $staff_phone, $address, $status, $staff_id);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการบันทึกข้อมูล"]);
}
