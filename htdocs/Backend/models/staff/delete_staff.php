<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$staff_id = $data['staff_id'] ?? '';

if (empty($staff_id)) {
    echo json_encode(["success" => false, "message" => "ไม่พบ Staff ID"]);
    exit();
}

$sql = "DELETE FROM delivery_staff WHERE staff_id = '$staff_id'";

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "ลบพนักงานสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $conn->error]);
}