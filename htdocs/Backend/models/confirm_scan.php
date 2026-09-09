<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$delivery_id = isset($data['delivery_id']) ? intval($data['delivery_id']) : 0;
// รับค่า cylinder_id ที่พนักงานสแกนได้
$cylinder_id = isset($data['cylinder_id']) ? $data['cylinder_id'] : null; 
$status = isset($data['status']) ? $data['status'] : 'pending';

if ($delivery_id > 0 && !empty($cylinder_id)) {
    // 1. บันทึก cylinder_id และ status ลงตาราง deliveries
    $stmt1 = $conn->prepare("UPDATE deliveries SET cylinder_id = ?, status = ? WHERE delivery_id = ?");
    $stmt1->bind_param("ssi", $cylinder_id, $status, $delivery_id);
    
    // 2. ปรับสถานะถังแก๊สในตาราง gas_cylinder เป็น 'กำลังส่ง'
    $stmt2 = $conn->prepare("UPDATE gas_cylinder SET status = 'กำลังส่ง' WHERE cylinder_id = ? OR serial_number = ?");
    $stmt2->bind_param("ss", $cylinder_id, $cylinder_id);

    if ($stmt1->execute() && $stmt2->execute()) {
        echo json_encode(["success" => true, "message" => "ผูกเลขถังแก๊สเรียบร้อยแล้ว"]);
    } else {
        echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการบันทึกข้อมูล"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "ส่งข้อมูล delivery_id หรือ cylinder_id มาไม่ครบ"]);
}

mysqli_close($conn);
?>