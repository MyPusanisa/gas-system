<?php
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");

// 1. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../../config/db.php';

// 2. ดึงงานจัดส่งพร้อมชื่อพนักงาน
//    ?staff_id=N -> เฉพาะงานที่มอบหมายให้พนักงานคนนั้น (หน้าพนักงานส่ง)
$staffId = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;

$sql = "SELECT
            d.*,
            s.staff_name AS assignedStaff
        FROM deliveries d
        LEFT JOIN delivery_staff s ON d.staff_id = s.staff_id";

if ($staffId > 0) {
    $stmt = $conn->prepare($sql . " WHERE d.staff_id = ? ORDER BY d.delivery_id DESC");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql . " ORDER BY d.delivery_id DESC");
}

$deliveries = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
    }
}

// 3. ส่งข้อมูลกลับเป็น JSON
echo json_encode([
    "success" => true,
    "data" => $deliveries
], JSON_UNESCAPED_UNICODE);
