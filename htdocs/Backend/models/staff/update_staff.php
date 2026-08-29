<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$staff_id = $data['staff_id'] ?? '';
$staff_name = $data['staff_name'] ?? '';
$staff_phone = $data['staff_phone'] ?? '';
$address = $data['address'] ?? '';
$status = $data['status'] ?? 'active';

if (empty($staff_id)) {
    echo json_encode(["success" => false, "message" => "ไม่พบ Staff ID"]);
    exit();
}

if (!empty($data['password'])) {
    $password = $conn->real_escape_string($data['password']);
    $sql = "UPDATE delivery_staff SET staff_name='$staff_name', staff_phone='$staff_phone', password='$password', address='$address', status='$status' WHERE staff_id='$staff_id'";
} else {
    $sql = "UPDATE delivery_staff SET staff_name='$staff_name', staff_phone='$staff_phone', address='$address', status='$status' WHERE staff_id='$staff_id'";
}

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $conn->error]);
}