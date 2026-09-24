<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$staff_id = (int)($data['staff_id'] ?? 0);

if ($staff_id <= 0) {
    echo json_encode(["success" => false, "message" => "ไม่พบ Staff ID"]);
    exit();
}

$stmt = $conn->prepare("DELETE FROM delivery_staff WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "ลบพนักงานสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการลบข้อมูล"]);
}
