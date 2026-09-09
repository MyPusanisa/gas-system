<?php
// 1. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../../config/db.php';

// 2. ดึงข้อมูลงานจัดส่งทั้งหมดพร้อม JOIN เอาชื่อพนักงานจากตาราง delivery_staff
$sql = "SELECT 
            d.*, 
            s.staff_name AS assignedStaff 
        FROM deliveries d 
        LEFT JOIN delivery_staff s ON d.staff_id = s.staff_id 
        ORDER BY d.delivery_id DESC";

$result = $conn->query($sql);

$deliveries = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
    }
}

// 3. ส่งข้อมูลกลับเป็น JSON
echo json_encode([
    "success" => true,
    "data" => $deliveries
]);
?>